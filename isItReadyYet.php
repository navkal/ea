<?php
  require_once "../common/util.php";
  require_once "filenames.php" ;

  if ( @fopen( $paramsFilename, "r" ) )
  {
    $rsp = "ready";
  }
  else
  {
    $rsp = "";
  }

  echo( json_encode( $rsp ) );
?>
