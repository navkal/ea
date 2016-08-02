<?php
  require_once "../common/util.php";
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
