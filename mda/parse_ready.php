<?php
  require_once $_SERVER[DOCUMENT_ROOT]."/../common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;

  if ( $paramsFile = @fopen( $paramsFilename, "r" ) )
  {
    fclose( $paramsFile );
    $rsp = "ready";
  }
  else
  {
    $rsp = "";
  }

  echo( json_encode( $rsp ) );
?>
