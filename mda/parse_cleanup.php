<?php
  require_once $_SERVER[DOCUMENT_ROOT]."/common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;
  error_log( "===> parse_cleanup resultsFilename=" . $resultsFilename );
  error_log( "===> parse_cleanup paramsFilename=" . $paramsFilename );

  @unlink( $paramsFilename );
  @unlink( $resultsFilename );

  echo( json_encode( "" ) );
?>
