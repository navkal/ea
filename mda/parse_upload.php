<?php
  error_log( "====> files=" . print_r( $_FILES, true ) );
  error_log( "====> rq=" . print_r( $_REQUEST, true ) );
  require_once "labels.php";

  $timestamp = $_REQUEST["timestamp"];
  require_once "filenames.php" ;

  $messages = [];

  if ( isset( $_POST["metasysFilename"] ) )
  {
    // Overwrite temp input filename with name of selected preload
    $inputFilename = "input/" . $_POST["metasysFilename"];

    // Save preload filename for future reference
    $preloadFile = fopen( $preloadFilename, "w" ) or die( "Unable to open file: " . $preloadFilename );
    fwrite( $preloadFile, $inputFilename );
    fclose( $preloadFile );
  }
  else
  {
    $metasysFile = isset( $_FILES["metasysFile"] ) ? $_FILES["metasysFile"] : NULL ;

    if ( $metasysFile == NULL )
    {
      array_push( $messages, "No file was uploaded" );
    }
    elseif ( $metasysFile["error"] != 0 )
    {
      array_push( $messages, "Upload error: " . $metasysFile["error"] );
    }
    elseif ( $metasysFile["size"] > 499999999 )
    {
      array_push( $messages, "File too large: " . $metasysFile["size"] . " bytes" );
    }
    elseif( ! move_uploaded_file ( $metasysFile["tmp_name"], $inputFilename ) )
    {
      array_push( $messages, "Failed to move uploaded file" );
    }
  }


  if ( empty( $messages ) )
  {
    if ( ( $inputFile = fopen( $inputFilename, "r" ) ) === false )
    {
      array_push( $messages, "Failed to open " . METASYS_FILE );
    }
  }

  $columns = [];

  if ( empty( $messages ) )
  {
    error_log( "===========> BF COLUMNS" );

    $colMap = [];

    // Skip the column headings
    fgetcsv( $inputFile );


    // Loop through the data
    while( ( $line = fgetcsv( $inputFile ) ) !== false )
    {
      if ( isset( $line[2] ) && isset( $line[3] ) )
      {
        $name = $line[2];
        $value = floatval( $line[3] );

        if ( isset( $colMap[$name] ) )
        {
          // We've seen this column name before

          // Update count
          if ( $value > $colMap[$name]["value"] )
          {
            // Value decreased; increment count
            $colMap[$name]["less"][0]++;
          }
          else
          {
            // Value did not decrease

            // If current count is non-zero, start a new one
            if ( $colMap[$name]["less"][0] > 0 )
            {
              array_unshift( $colMap[$name]["less"], 0 );
            }
          }

          // Save value
          $colMap[$name]["value"] = $value;
        }
        else
        {
          // First occurrence of this column name
          $colMap[$name] = [ "first" => $value, "value" => $value, "less" => [0] ];
        }
      }
    }

    $summarizable = [];
    foreach( $colMap as $key => $profile )
    {
      if ( ( count( $profile["less"] ) == 1 ) && ( $profile["less"][0] == 0 ) && ( $profile["first"] != $profile["value"] ) )
      {
        array_push( $summarizable, $key );
      }
    }

    error_log( "===========> AF COLUMNS" );
    //error_log( "===> map=" . print_r( $colMap, true ) );
    error_log( "===> summarizable=" . print_r( $summarizable, true ) );

    fclose( $inputFile );

    if ( count( $colMap ) )
    {
      $columns = array_keys( $colMap );
      sort( $columns );
    }
    else
    {
      array_push( $messages, "Uploaded file does not contain any " . POINTS_OF_INTEREST );
    }
  }

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns
  ];

  echo json_encode( $rsp );
?>
