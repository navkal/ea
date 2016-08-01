<?php
  require_once "../common/util.php";
  include( "filenames.php" );

  if ( file_exists( $readyFilename ) )
  {
    unlink( $readyFilename );
    $file = fopen( $resultsFilename, "r" );
    echo( print_r( fgetcsv( $file ), true ) );
    fclose( $file );
  }
  else
  {
    echo( "" );
  }
?>
