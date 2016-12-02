<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";


  error_log( "====> files=" . print_r( $_FILES, true ) );
  error_log( "====> rq=" . print_r( $_REQUEST, true ) );
  require_once "labels.php";

  $timestamp = $_REQUEST["timestamp"];
  require_once "filenames.php" ;


  function checkFileUpload( $files, $whichFile, $size, $moveFilename )
  {
    $messages = [];

    $uploadFile = isset( $files[$whichFile] ) ? $files[$whichFile] : NULL ;

    if ( $uploadFile == NULL )
    {
      array_push( $messages, "No file was uploaded" );
    }
    elseif ( $uploadFile["error"] != 0 )
    {
      array_push( $messages, "Upload error: " . $uploadFile["error"] );
    }
    elseif ( $uploadFile["size"] > $size )
    {
      array_push( $messages, "File too large: " . $uploadFile["size"] . " bytes" );
    }
    elseif( ! move_uploaded_file ( $uploadFile["tmp_name"], $moveFilename ) )
    {
      array_push( $messages, "Failed to move uploaded file" );
    }

    return $messages;
  }

  function convertNgridFile( $ngridFile, $convertFilename, $headings )
  {
    $convertFile = fopen( $convertFilename, "w" );
    fwrite( $convertFile, 'Date / Time,Name Path Reference,Object Name,Object Value' . PHP_EOL );

    $sum = [];
    while( ( $inline = fgetcsv( $ngridFile ) ) !== false )
    {
      if ( ( $inline[0] != "" ) && ( $inline[1] != "" ) && ( $inline[3] != "" ) )
      {
        $colname = $inline[0] . "." . $inline[3];
        $sumname = $colname . ".sum";
        for ( $index = 4; $index < count( $inline ); $index ++ )
        {
          if ( $inline[$index] != "" )
          {
            // Format time string, correcting for invalid use of "24:00:00" in final column
            $timeFragments = explode( ":", $headings[$index] );
            $hours = $timeFragments[0];
            $minutes = $timeFragments[1];
            if ( $hours == 24 )
            {
              $tDateTime = new DateTime( $inline[1] );
              $tDateTime->add( new DateInterval( "P1D" ) );
            }
            else
            {
              $tDateTime = new DateTime( $inline[1] . " " . $hours . ":" . $minutes );
            }
            $sDateTime = $tDateTime->format( "m/d/Y H:i" );

            // Generate raw data sample
            $outline = $sDateTime . "," . $colname . "," . $colname . "," . $inline[$index] . PHP_EOL;
            fwrite( $convertFile, $outline );

            // Optionally generate cumulative data sample
            if ( ( $inline[3] == "kWh" ) || ( $inline[3] == "kVAh" ) )
            {
              if ( ! isset( $sum[$sumname] ) )
              {
                $sum[$sumname] = 0;
              }
              $sum[$sumname] += $inline[$index];
              $outline = $sDateTime . "," . $sumname . "," . $sumname . "," . $sum[$sumname] . PHP_EOL;
              fwrite( $convertFile, $outline );
            }
          }
        }
      }
    }
    fclose( $convertFile );

    // Re-open convert file for reading, and skip the column headings
    $convertFile = fopen( $convertFilename, "r" );
    fgetcsv( $convertFile );

    return $convertFile;
  }

  $messages = [];

  if ( isset( $_FILES["resultsFile"] ) )
  {
    $messages = checkFileUpload( $_FILES, "resultsFile", 50000000, $resultsFilename );
  }
  else if ( isset( $_POST["sampleFilename"] ) )
  {
    // Copy sample file to temp location
    copy( "sample/" . $_POST["sampleFilename"], $resultsFilename );
  }
  else if ( isset( $_POST["metasysFilename"] ) )
  {
    // Overwrite temp input filename with name of selected preload
    $inputFilename = "input/" . $_POST["metasysFilename"];

    // Save preload filename for future reference
    $_SESSION["inputFilename"] = $inputFilename;
  }
  else
  {
    $messages = checkFileUpload( $_FILES, "metasysFile", 340000000, $inputFilename );
    $_SESSION["archiveFilename"] = $inputFilename;
  }

  $redirect = "";
  $columns = [];

  if ( empty( $messages ) )
  {
    if ( isset( $_FILES["resultsFile"] ) || isset( $_POST["sampleFilename"] ) )
    {
      $messages = unmarkFile( $resultsFilename );
      if ( empty( $messages ) )
      {
        // Save name of results file for use by plot
        $_SESSION["resultsFilename"] = $resultsFilename;

        // Save information to download sample Results File
        if ( isset( $_POST["sampleFilename"] ) )
        {
          $_SESSION["completion"] =
            [
              "downloadFilename" => "sample/" . $_POST["sampleFilename"],
              "downloadExt" => ".csv",
              "downloadType" => "text/csv"
            ];
        }

        // Set redirect URL
        $redirect = "mda/parse_results.php?timestamp=" . $timestamp;
      }
    }
    else
    {
      if ( ( $inputFile = fopen( $inputFilename, "r" ) ) === false )
      {
        array_push( $messages, "Failed to open " . METASYS_FILE );
      }

      if ( empty( $messages ) )
      {
        $colMap = [];

        // Read the column headings
        $headings = fgetcsv( $inputFile );
        if ( count( $headings ) >= 28 )
        {
          $inputFile = convertNgridFile( $inputFile, $convertFilename, $headings );

          // Overwrite input filename with convert file
          $inputFilename = $convertFilename;
          $_SESSION["inputFilename"] = $inputFilename;
        }


        // Loop through the data
        while( empty( $messages ) && ( ( $line = fgetcsv( $inputFile ) ) !== false ) )
        {
          if ( isset( $line[0] ) && isset( $line[2] ) && isset( $line[3] ) )
          {
            $time = strtotime( $line[0] );

            if ( $time )
            {
              $name = $line[2];
              $value = floatval( $line[3] );

              if ( isset( $colMap[$name] ) )
              {
                // We've seen this column name before

                // Increment the appropriate counter
                if ( $value < $colMap[$name]["value"] )
                {
                  if ( $time > $colMap[$name]["time"] )
                  {
                    $colMap[$name]["lt"]++;
                  }
                  else if ( $time < $colMap[$name]["time"] )
                  {
                    $colMap[$name]["gt"]++;
                  }
                }
                else if ( $value > $colMap[$name]["value"] )
                {
                  if ( $time > $colMap[$name]["time"] )
                  {
                    $colMap[$name]["gt"]++;
                  }
                  else if ( $time < $colMap[$name]["time"] )
                  {
                    $colMap[$name]["lt"]++;
                  }
                }
                else
                {
                  $colMap[$name]["eq"]++;
                }

                // Save data pertaining to this record
                $colMap[$name]["value"] = $value;
                $colMap[$name]["time"] = $time;

                if ( $time < $colMap[$name]["t0"] )
                {
                  $colMap[$name]["first"] = $value;
                  $colMap[$name]["t0"] = $time;
                }
                else if ( $time > $colMap[$name]["tn"] )
                {
                  $colMap[$name]["last"] = $value;
                  $colMap[$name]["tn"] = $time;
                }
              }
              else
              {
                // First occurrence of this column name
                $colMap[$name] = [ "first" => $value, "value" => $value, "last" => $value, "t0" => $time, "time" => $time, "tn" => $time, "lt" => 0, "gt" => 0, "eq" => 0 ];
              }
            }
            else
            {
              array_push( $messages, "Timestamp format not valid: " . $line[0] );
            }
          }
        }

        fclose( $inputFile );
      }

      if ( empty( $messages ) )
      {
        define( "THRESHOLD", 0.0005 );    // 0.00037950664136623 is sufficiently small according to available sample input files
        define( "VARIABILITY_MINIMUM", 0.0003 );  // 0.00019267822736031 is sufficiently large according to available sample input files

        foreach( $colMap as $key => $properties )
        {
          // Replace properties with format used by client

          $volatility = THRESHOLD + 1;
          if ( $properties["eq"] == 0 || ( ( ( $properties["lt"] + $properties["gt"] ) / $properties["eq"] ) > VARIABILITY_MINIMUM ) )
          {
            $totalDeltas = $properties["lt"] + $properties["gt"] + $properties["eq"];
            if ( $properties["first"] < $properties["last"] )
            {
              $volatility = $properties["lt"] / $totalDeltas;
            }
            else if ( $properties["first"] > $properties["last"] )
            {
              $volatility = $properties["gt"] / $totalDeltas;
            }
          }

          $summarizable = $volatility < THRESHOLD;
          $oldSummarizable = oldSummarizable( $properties );

          if ( $summarizable != $oldSummarizable )
          {
            error_log( "===> !!! Change in summarizable result: key=" . $key . " variability=" . ( ( $properties["lt"] + $properties["gt"] ) / $properties["eq"] ) . " volatility=" . $volatility . " props=" . print_r( $properties, true ) );
          }

          $colMap[$key] = [ "summarizable" => $summarizable ];
        }

        ksort( $colMap );
        // error_log( "===> map=" . print_r( $colMap, true ) );

        if ( count( $colMap ) )
        {
          $columns = $colMap;
        }
        else
        {
          array_push( $messages, "Uploaded file does not contain any " . POINTS_OF_INTEREST );
        }
      }
    }
  }

  // Manage system-wide nickname definitions
  $knownNames = [];
  $nicknames = [];
  if ( empty( $messages ) )
  {
    // Retrieve current nickname definitions
    if ( ( $nicknameFile = @fopen( "nicknames.csv", "r" ) ) !== false )
    {
      while( ( $line = fgetcsv( $nicknameFile ) ) !== false )
      {
        $name = trim( $line[1] );
        $knownNames[$name] = $name;

        $nickname = trim( $line[2] );
        if ( $nickname != "" )
        {
          if ( in_array( $nickname, $nicknames ) )
          {
            error_log( "==> !!! Ignoring duplicate nickname: name=<" . $name . "> nickname=<" . $nickname . ">" );
          }
          else
          {
            $nicknames[$name] = $nickname;
          }
        }
      }
      fclose( $nicknameFile );
    }

    // Finish loading list of known names from no-nickname file
    if ( ( $nonicknameFile = @fopen( "archive/nonicknames.csv", "r" ) ) !== false )
    {
      // Read the names
      while( ( $line = fgetcsv( $nonicknameFile ) ) !== false )
      {
        $name = trim( $line[1] );
        $knownNames[$name] = $name;
      }
      fclose( $nonicknameFile );
    }

    $time = time();
    if ( ( $nonicknameFile = @fopen( "archive/nonicknames.csv", "a" ) ) !== false )
    {
      foreach ( $columns as $colName => $notUsed )
      {
        if ( ! isset( $knownNames[$colName] ) )
        {
          fwrite( $nonicknameFile, $time . "," . $colName . "," . PHP_EOL );
        }
      }
      fclose( $nonicknameFile );
    }
  }

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns,
    "nicknames" => $nicknames,
    "redirect" => $redirect
  ];

  echo json_encode( $rsp );

  // Delete this when no longer needed
  function oldSummarizable( $properties )
  {
    $totalDeltas = $properties["lt"] + $properties["gt"] + $properties["eq"];

    $volatility = THRESHOLD + 1;
    if ( $properties["first"] < $properties["last"] )
    {
      $volatility = $properties["lt"] / $totalDeltas;
    }
    else if ( $properties["first"] > $properties["last"] )
    {
      $volatility = $properties["gt"] / $totalDeltas;
    }

    $summarizable = $volatility < THRESHOLD;
    return $summarizable;
  }
?>
