<?php
  /////////////////////////////////////////////////////////
  // Copyright 2016 Energize Apps.  All rights reserved. //
  /////////////////////////////////////////////////////////
?>

<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";

  $timestamp = $_GET["timestamp"];
  require_once "filenames.php" ;

  if ( isset( $_SESSION["completion"] ) )
  {
    $rsp = "ready";
  }
  else
  {
    $rsp = "";
  }

  echo( json_encode( $rsp ) );
?>
