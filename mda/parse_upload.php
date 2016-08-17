<?php
  error_log( "====> files=" . print_r( $_FILES, true ) );
  error_log( "====> rq=" . print_r( $_REQUEST, true ) );

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

    while( ( $line = fgetcsv( $inputFile ) ) !== false )
    {
      $colMap[$line[2]] = "";
    }
    fclose( $inputFile );

    $columns = array_keys( $colMap );
    error_log( "===> columns=" . print_r( $columns, true ) );
  }

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns
  ];

  echo json_encode( $rsp );
?>
