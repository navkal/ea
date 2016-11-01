<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";

  $downloadFilename = $_SESSION["completion"]["downloadFilename"];
  $downloadType = $_SESSION["completion"]["downloadType"];
  downloadFile( $downloadFilename, $downloadType );
?>
