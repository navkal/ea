<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );

    $timestamp = $_GET["timestamp"];
    require_once "filenames.php" ;
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
      $to = "EnergizeApps@gmail.com";
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

      mail( $to, $subject, $text, $headers );
    }

    // Save results filename with full path for use by Plot view
    $_SESSION["resultsFilename"] = $resultsFilename;

    // Get analysis parameters for completion display
    $params = str_getcsv( $_SESSION["completion"]["params"] );
    $resultsFilename = $_SESSION["completion"]["resultsFilename"];

    $columns = [];
    $columnsFile = fopen( $columnsFilename, "r" );
    while( ( $line = fgetcsv( $columnsFile ) ) !== false )
    {
      array_push( $columns, $line[0] );
    }
    fclose( $columnsFile );
  ?>

  <body>
    <div class="container" style="padding-top:30px;padding-bottom:60px">
      <div class="page-header">
        <p class="h3"><?=METASYS_DATA_ANALYSIS?> completion</p>
      </div>
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <ul class="list-group">
            <li class="list-group-item list-group-item-info">
              <dl class="dl-horizontal" >
                <?php
                  for ( $index = 0; $index < count( $params ); $index += 2 )
                  {
                    echo "<dt>";
                    echo $params[$index];
                    echo "</dt>";
                    echo "<dd>";
                    echo $params[$index+1];
                    echo "</dd>";
                  }
                ?>
                <dt>
                  <?=POINTS_OF_INTEREST?>
                </dt>
                <dd>
                  <ul>
                    <?php
                      foreach ( $columns as $colName )
                      {
                        echo "<li>";
                        echo $colName;
                        echo "</li>";
                      }
                    ?>
                  </ul>
                </dd>
              </dl>
            </li>
            <li class="list-group-item list-group-item-success">
              <dl class="dl-horizontal" >
                <dt>
                  <?=RESULTS_FILE?>
                </dt>
                <dd>
                  <?=$resultsFilename?>
                </dd>
              </dl>
            </li>
          </ul>
        </div>
      </div>

      <!-- Close and Plot buttons -->
      <div style="text-align:center;" >
        <a class="btn btn-default" href="javascript:startClose()" role="button">Close</a>
        <?php
          // If single-run, display more
          $split = explode( ".", $resultsFilename );
          if ( $split[ count( $split ) - 1 ] == "csv" )
          {
            echo '<a class="btn btn-primary" href="parse_results.php?timestamp=' . $timestamp . '" role="button">Plot</a>';
          }
        ?>
      </div>
    </div>

    <?php
      // Sticky footer
      include $_SERVER["DOCUMENT_ROOT"]."/../common/footer.php";
    ?>

    <script type="text/javascript" src="../util/util.js"></script>
    <script>
      $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
      document.title = "<?=METASYS_DATA_ANALYSIS?>";

      function startClose()
      {
        $.ajax(
          "parse_cleanup.php?timestamp=<?=$timestamp?>",
          {
            type: "GET",
            cache: false,
            dataType: "json"
          }
        )
        .done( finishClose )
        .fail( ajaxError );
      }

      function finishClose()
      {
        document.location.href="/";
      }
    </script>

  </body>
</html>
