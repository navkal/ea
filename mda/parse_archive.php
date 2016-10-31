<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  initUi( $_SERVER["DOCUMENT_ROOT"]."/" );
  require_once "labels.php";

  // Optionally archive uploaded input file
  if ( isset( $_SESSION["archiveInput"] ) )
  {
    // Format filenames
    $dateFilename =  date( "Y-m-d H-i-s " ) . $_SESSION["archiveInput"]["uploadFilename"];
    $zipFilename = $_SERVER["DOCUMENT_ROOT"]."/mda/archive/" . $dateFilename . ".zip";

    // Put the uploaded input file into a zip archive
    $zipArchive = new ZipArchive();
    $zipArchive->open( $zipFilename, ZipArchive::CREATE );
    $zipArchive->addFromString( $dateFilename, file_get_contents( $_SESSION["archiveInput"]["inputFilename"] ) );
    $zipArchive->close();

    // Send notification email
    $subject = "Added to archive: " . $dateFilename;

    $text =
      "<style>body{font-family: arial;}</style>" .
      "<html><body>".
      "<p>The following upload has been added to the " . METASYS_FILE . " archive:</p>" .
      "<p>" . $dateFilename . "</p>" .
      "<hr/>" .
      "</html></body>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Energize Apps <SmtpDispatch@gmail.com>" . "\r\n";

    global $mailto;
    mail( $mailto, $subject, $text, $headers );
  }

  echo( json_encode( "" ) );
?>
