<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );

    require_once "labels.php";
    require_once "parse_help.php";
?>

  <body>

    <div class="container">
      <div class="page-header">
        <p class="h3"><?=METASYS_DATA_ANALYSIS_RESULTS?></p>
      </div>
    </div>

    <?php
      include( "plot.php" );
    ?>
    <!-------------------------------------------- >
    <div class="form-group">
      <label class="control-label" for="cost"><?=COST_PER_KWH?></label>
      <div class="input-group">
        <span class="input-group-addon" id="dollars">$</span>
        <input type="number" value="0.16" min="0.01" step="0.01" class="form-control" id="cost" name="cost" />
      </div>
    </div>
    <!-------------------------------------------->

    <div class="container">
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <!-- Close button -->
          <div style="text-align:center;" >
            <a class="btn btn-default" href="javascript:startClose()" role="button">Close</a>
            <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpPlot">Help</button>
          </div>
        </div>
      </div>
    </div>

  </body>
</html>

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
