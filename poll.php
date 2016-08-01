<?php
  require_once "../common/util.php";
  $resultsFile = "out.csv";
  if ( file_exists( $resultsFile ) )
  {
    $file = fopen( $resultsFile, "r" );
    error_log( print_r( fgetcsv( $file ), true ) );
    echo( print_r( fgetcsv( $file ), true ) );
    fclose( $file );
  }
  else
  {
    echo( "" );
  }
?>
