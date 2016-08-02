<?php
  require_once "../common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;
  error_log( "===> isItReadyYet timestamp=" . $timestamp );
  error_log( "===> isItReadyYet paramsFilename=" . $paramsFilename );

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
