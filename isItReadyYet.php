<?php
  require_once "../common/util.php";
  require_once "filenames.php" ;

  if ( file_exists( $readyFilename ) )
  {
    unlink( $readyFilename );
    $resultsFile = fopen( $resultsFilename, "r" );
    echo( print_r( fgetcsv( $resultsFile ), true ) );
    fclose( $resultsFile );
  }
  else
  {
    echo( "" );
  }
?>
