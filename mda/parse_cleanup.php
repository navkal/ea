<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;

  @unlink( $columnsFilename );
  @unlink( $resultsFilename );
  @unlink( $paramsFilename );

  echo( json_encode( "" ) );
?>
