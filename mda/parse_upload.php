<?php
  error_log( "====> files=" . print_r( $_FILES, true ) );
  error_log( "====> rq=" . print_r( $_REQUEST, true ) );
  require_once "labels.php";

  $timestamp = $_REQUEST["timestamp"];
  require_once "filenames.php" ;

  $metasysFile = isset( $_FILES["metasysFile"] ) ? $_FILES["metasysFile"] : NULL ;

  $messages = [];

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
  elseif ( ( $inputFile = fopen( $inputFilename, "r" ) ) === false )
  {
    array_push( $messages, "Failed to open uploaded file" );
  }

  $columns = [];

  if ( empty( $messages ) )
  {
    $colMap = [];

    fgetcsv( $inputFile );
    while( ( $line = fgetcsv( $inputFile ) ) !== false )
    {
      if ( $name = $line[2] )
      {
        $colMap[$name] = "";
      }
    }

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
    error_log( "===> columns=" . print_r( $columns, true ) );
  }

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns
  ];

  echo json_encode( $rsp );
?>
