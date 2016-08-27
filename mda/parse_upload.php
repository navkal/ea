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
    $colMap = [];

    fgetcsv( $inputFile );
    while( ( $line = fgetcsv( $inputFile ) ) !== false )
    {
      if ( isset( $line[2] ) )
      {
        $colMap[$line[2]] = "";
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
  }

  $rsp =
  [
    "messages" => $messages,
    "columns" => $columns
  ];

  echo json_encode( $rsp );
?>
