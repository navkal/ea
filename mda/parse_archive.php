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
    $text =
      "<style>body{font-family: arial;}</style>" .
      "<html>" .

        "<head>" .
          "<style>" .
          "table { border: 1px dotted black; }" .
          "td { padding-right: 10px; }" .
          "</style>" .
        "</head>" .

        "<body>" .
          "<p><b>" . $_SESSION["archiveInput"]["deployment"] . "</b> has archived a new " . METASYS_FILE . ":</p>" .
          "<p>" . $dateFilename . "</p>" .
          "<br/>" .

          "<table>" .
            "<tr>" .
              "<td>Server Name:</td>" .
              "<td>" . $_SERVER["SERVER_NAME"] . "</td>" .
            "</tr>" .
            "<tr>" .
              "<td>Server Address:</td>" .
              "<td>" . $_SERVER["SERVER_ADDR"] . "</td>" .
            "</tr>" .
            "<tr>" .
              "<td>Server Port:</td>" .
              "<td>" . $_SERVER["SERVER_PORT"] . "</td>" .
            "</tr>" .
            "<tr>" .
              "<td>Remote Address:</td>" .
              "<td>" . $_SERVER["REMOTE_ADDR"] . "</td>" .
            "</tr>" .
          "</table>" .
        "</body>" .

      "</html>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Energize Apps <SmtpDispatch@gmail.com>" . "\r\n";

    global $mailto;
    $subject = "Archive notice: " . $_SESSION["archiveInput"]["deployment"];
    mail( $mailto, $subject, $text, $headers );
  }

  echo( json_encode( "" ) );
?>
