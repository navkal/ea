<?php
  // Copyright 2018 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";

  $timestamp = $_GET["timestamp"];

  $deletions = glob( sys_get_temp_dir() . "/mda*_" . $timestamp . "*" );

  foreach( $deletions as $deleteFilename )
  {
    unlink( $deleteFilename );
  }

  echo( json_encode( "" ) );
?>
