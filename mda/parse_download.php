<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";

  error_log( "=========> in parse_download" );

  $downloadFilename = $_SESSION["completion"]["downloadFilename"];
  $downloadType = $_SESSION["completion"]["downloadType"];
  error_log( "=========> bf downloadFile" );
  downloadFile( $downloadFilename, $downloadType );
  error_log( "=========> af downloadFile" );
?>
