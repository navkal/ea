<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );
    $timestamp = $_GET["timestamp"];
    require_once "labels.php";
  ?>

  <body>

    <?php
      require_once "parse_help.php";
    ?>

    <div class="container">
      <div class="page-header">
        <p class="h3"><?=METASYS_DATA_ANALYSIS_RESULTS?></p>
      </div>
    </div>

    <?php
      include( "plot/plot.php" );
    ?>

    <div class="container" style="padding-top:10px;padding-bottom:80px">
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <!-- Close button -->
          <div style="text-align:center;" >
            <a class="btn btn-default" href="javascript:startCleanup('<?=$timestamp?>')" role="button">Close</a>
            <a class="btn btn-default" href="parse_download.php" role="button">Download <?=RESULTS_FILE?></a>
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpPlot">Help</button>
          </div>
        </div>
      </div>
    </div>

    <?php
      // Sticky footer
      include $_SERVER["DOCUMENT_ROOT"]."/../common/footer.php";
    ?>

    <script type="text/javascript" src="../util/util.js"></script>
    <script>
      $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
      document.title = "<?=METASYS_DATA_ANALYSIS_RESULTS?>";
    </script>

  </body>
</html>
