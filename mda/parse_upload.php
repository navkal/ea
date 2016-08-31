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

          // Increment the appropriate counter
          if ( $value < $colMap[$name]["value"] )
          {
            $colMap[$name]["lt"]++;
          }
          else if ( $value > $colMap[$name]["value"] )
          {
            $colMap[$name]["gt"]++;
          }
          else
          {
            $colMap[$name]["eq"]++;
          }

          // Save value
          $colMap[$name]["value"] = $value;
        }
        else
        {
          // First occurrence of this column name
          $colMap[$name] = [ "first" => $value, "value" => $value, "lt" => 0, "gt" => 0, "eq" => 0 ];
        }
      }
    }
    fclose( $inputFile );

    error_log( "===========> AF COLUMNS" );

    // Replace properties with format used by client
    foreach( $colMap as $key => $properties )
    {
      $volatility = 1;
      $total =  $properties["lt"] + $properties["gt"] + $properties["eq"];
      if ( $properties["first"] < $properties["value"] )
      {
        $volatility = $properties["lt"] / $total;
      }
      else if ( $properties["first"] > $properties["value"] )
      {
        $volatility = $properties["gt"] / $total;
      }

      $summarizable = $volatility < 0.0005;  // 0.00037950664136623 is sufficient based on testing
      error_log( "=========> key=" . $key . " volatility=" . $volatility . " regressions=" . ( $volatility * $total ) . " summarizable=" . $summarizable );

      $colMap[$key] = ["summarizable" => $summarizable ];
      $old =
        ( ( ( $properties["lt"] ) <= 2 ) && ( $properties["first"] < $properties["value"] ) )
        ||
        ( ( ( $properties["gt"] ) <= 2 ) && ( $properties["first"] > $properties["value"] ) )
        ;

      if ( $old != $summarizable ) error_log( "============DIFFERENT!================" );
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

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns
  ];

  echo json_encode( $rsp );
?>
