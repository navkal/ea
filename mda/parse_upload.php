<?php
  // Copyright 2018 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";


  error_log( "====> files=" . print_r( $_FILES, true ) );
  error_log( "====> rq=" . print_r( $_REQUEST, true ) );
  require_once "labels.php";

  $timestamp = $_REQUEST["timestamp"];
  require_once "filenames.php" ;

  $messages = [];

  if ( isset( $_FILES["resultsFile"] ) )
  {
    // User has uploaded a results file. Check it.
    $messages = checkFileUpload( $_FILES, "resultsFile", $resultsFilename );
  }
  else if ( isset( $_POST["sampleFilename"] ) )
  {
    // User has selected a preloaded results file.  Copy it to temp location.
    copy( "sample/" . $_POST["sampleFilename"], $resultsFilename );
  }
  else if ( isset( $_POST["metasysFilename"] ) )
  {
    // User has selected a preloaded input file

    // Overwrite temp input filename with name of selected preload
    $inputFilename = "input/" . $_POST["metasysFilename"];

    // Save preload filename for future reference
    $_SESSION["inputFilename"] = $inputFilename;
  }
  else
  {
    // User has uploaded an input file
    $messages = checkFileUpload( $_FILES, "metasysFile", $inputFilename );
    $_SESSION["archiveFilename"] = $inputFilename;
  }

  $redirect = "";
  $dates = [];
  $columns = [];

  if ( empty( $messages ) )
  {
    if ( isset( $_FILES["resultsFile"] ) || isset( $_POST["sampleFilename"] ) )
    {
      // Processing a pre-existing Results File

      $messages = unmarkFile( $resultsFilename, RESULTS_FILE );
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
      // Processing Metasys (or other) exported file

      if ( ( $inputFile = fopen( $inputFilename, "r" ) ) === false )
      {
        array_push( $messages, "Failed to open " . METASYS_FILE );
      }

      if ( empty( $messages ) )
      {
        if ( count( fgetcsv( $inputFile ) ) >= 28 )
        {
          // Data looks like National Grid format; convert to Metasys format

          // Convert the input file
          ngridToMetasys( $inputFilename, $convertFilename );

          // Overwrite input filename with convert filename
          $inputFilename = $convertFilename;
          $_SESSION["inputFilename"] = $inputFilename;
        }
        fclose( $inputFile );
      }

      if ( empty( $messages ) )
      {
        $datesAndMeters = findDatesAndMeters( $inputFilename, $metersFilename, $messages );
        $dates = $datesAndMeters['dates'];
        $columns = $datesAndMeters['meters'];
      }

      if ( empty( $messages ) )
      {
        if ( count( $columns ) == 0 )
        {
          array_push( $messages, "No " . POINTS_OF_INTEREST . " found" );
        }
      }
    }
  }

  // Manage system-wide nickname definitions
  $knownNames = []; // Names that are either mapped to nicknames in nicknames.csv, or already present in nonicknames.csv
  $nicknames = [];
  if ( empty( $messages ) )
  {
    // Retrieve current nickname definitions
    if ( ( $nicknameFile = @fopen( "nicknames.csv", "r" ) ) !== false )
    {
      while( ( $line = fgetcsv( $nicknameFile ) ) !== false )
      {
        if ( count( $line ) >= 2 )
        {
          $name = trim( $line[0] );
          if ( $name != "" )
          {
            $nickname = trim( $line[1] );
            if ( $nickname != "" )
            {
              // Name is mapped to nickname; save it as a known name
              $knownNames[$name] = $name;

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
    "dates" => $dates,
    "columns" => $columns,
    "nicknames" => $nicknames,
    "redirect" => $redirect
  ];

  echo json_encode( $rsp );


  //==================================================================

  function checkFileUpload( $files, $whichFile, $moveFilename )
  {
    // Determine upload size limit
    $envSize = getenv( $whichFile . '_MAX_SIZE' );
    if ( $envSize !== false )
    {
      $size = abbrToBytes( $envSize );
    }
    else
    {
      $postMaxSize = abbrToBytes( ini_get( 'post_max_size' ) );
      $uploadMaxSize = abbrToBytes( ini_get( 'upload_max_filesize' ) );
      $size = min( $postMaxSize, $uploadMaxSize );
    }

    error_log( "===> Limiting '" . $whichFile . "' upload to " . number_format( $size ) . ' bytes' );

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
      array_push( $messages, "File too large: " . number_format( $uploadFile["size"] ) . " bytes" );
    }
    elseif( ! move_uploaded_file ( $uploadFile["tmp_name"], $moveFilename ) )
    {
      array_push( $messages, "Failed to move uploaded file" );
    }

    return $messages;
  }

  function abbrToBytes( $val )
  {
    $val = trim($val);

    $last = strtolower( $val[strlen($val)-1] );

    $val = substr( $val, 0, -1 );

    switch( $last )
    {
      case 'g':
        $val *= 1024;
      case 'm':
        $val *= 1024;
      case 'k':
        $val *= 1024;
    }

    return $val;
  }

  function ngridToMetasys( $ngridFilename, $convertFilename )
  {
    // Execute Python script to convert National Grid format to Metasys format
    $command = quote( getenv( "PYTHON" ) ) . " ngridToMetasys.py -i " . quote( $ngridFilename ) . " -o " . quote( $convertFilename );
    error_log( "===> command=" . $command );
    exec( $command, $output, $status );
    error_log( "===> output=" . print_r( $output, true ) );
  }

  function compareNgridLines( $line1, $line2 )
  {
    $time1 = strtotime( str_getcsv( $line1 )[1] );
    $time2 = strtotime( str_getcsv( $line2 )[1] );
    return $time1 - $time2;
  }

  function findDatesAndMeters( $inputFilename, $metersFilename, &$messages )
  {
    $dates = [];
    $meters = [];

    // Determine status with respect to preloaded meters file
    $bWantPreload = isset( $_POST["metasysFilename"] );
    $preloadFilename = $bWantPreload ? 'meters/' . $_POST["metasysFilename"] : '';
    $bHavePreload = file_exists( $preloadFilename ) && ( filemtime( $inputFilename ) < filemtime( $preloadFilename ) );

    if ( $bHavePreload )
    {
      // We have a preloaded meters filename corresponding to the selected preloaded input file.  Copy it to temp location.
      copy( $preloadFilename, $metersFilename );
    }
    else
    {
      // Execute Python script to find date range and summarizable columns (meters)
      $command = quote( getenv( "PYTHON" ) ) . " findMeters.py -i " . quote( $inputFilename ) . " -o " . quote( $metersFilename ) . " -c 0.9 -r 0.95";
      error_log( "===> command=" . $command );
      exec( $command, $output, $status );
      error_log( "===> output=" . print_r( $output, true ) );

      if ( $bWantPreload )
      {
        // We want a preloaded meters file, but it doesn't exist yet.  Copy the one we just generated, for future use.
        error_log( '============> Trying to copy ' . $metersFilename . ' to ' . $preloadFilename );
        copy( $metersFilename, $preloadFilename );
      }
    }

    // Retrieve information from meters file
    if ( ( $metersFile = @fopen( $metersFilename, "r" ) ) !== false )
    {
      if( ( $range = fgetcsv( $metersFile ) ) !== false )
      {
        $dates = [ 'fromDate' => $range[0], 'toDate' => $range[1] ];

        while( ( $meter = fgetcsv( $metersFile ) ) !== false )
        {
          $meters[$meter[0]]["summarizable"] = ( $meter[1] == "True" );
        }
      }

      fclose( $metersFile );
    }
    else
    {
      $message = METASYS_DATA_ANALYSIS . " preprocessing failed.<br/>";
      foreach ( $output as $line )
      {
        $message .= "<br/>" . $line;
      }
      array_push( $messages, $message );
    }

    return [ 'dates' => $dates, 'meters' => $meters ];
  }

  function makeColMap( $inputFilename, &$messages )
  {
    // Open convert file for reading, and skip the column headings
    $inputFile = fopen( $inputFilename, "r" );
    fgetcsv( $inputFile );

    $colMap = [];
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
    return $colMap;
  }

  function analyzeColMap( $colMap )
  {
    $THRESHOLD = 0.001284274064085;

    foreach( $colMap as $key => $properties )
    {
      // Replace properties with format used by client

      $volatility = $THRESHOLD + 1;
      if ( $properties["eq"] == 0 || ( ( ( $properties["lt"] + $properties["gt"] ) / $properties["eq"] ) > 0.0003 ) )
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

      //error_log( "=======> $key $volatility" );
      $summarizable = $volatility < $THRESHOLD;

      $colMap[$key] = [ "summarizable" => $summarizable ];
    }

    ksort( $colMap );
    // error_log( "===> map=" . print_r( $colMap, true ) );

    return $colMap;
  }
?>
