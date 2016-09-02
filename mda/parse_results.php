<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );

    $timestamp = $_GET["timestamp"];
    require_once "filenames.php" ;

    require_once "labels.php";
  ?>

  <body>

    <div class="container">
      <div class="page-header">
        <p class="h3"><?=METASYS_DATA_ANALYSIS?> results</p>
      </div>
      <?php
        include( "graph.php" );
      ?>
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <div class="form-group">
            <label class="control-label" for="cost"><?=COST_PER_KWH?></label>
            <div class="input-group">
              <span class="input-group-addon" id="dollars">$</span>
              <input type="number" value="0.16" min="0.01" step="0.01" class="form-control" id="cost" name="cost" />
            </div>
          </div>
        </div>
      </div>

      <!-- Close and Graph buttons -->
      <div style="text-align:center;" >
       <a class="btn btn-default" href="javascript:startClose()" role="button">Close</a>
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
