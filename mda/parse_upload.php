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

        // Skip the column headings
        fgetcsv( $inputFile );

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
        define( "THRESHOLD", 0.0005 );  // 0.00037950664136623 is sufficiently small according to available sample input files

        foreach( $colMap as $key => $properties )
        {
          // Replace properties with format used by client
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

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns,
    "redirect" => $redirect
  ];

  echo json_encode( $rsp );
?>
