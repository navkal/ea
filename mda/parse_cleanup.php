<?php
  require_once $_SERVER[DOCUMENT_ROOT]."/../common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;

  @unlink( $paramsFilename );
  @unlink( $resultsFilename );

  echo( json_encode( "" ) );
?>
