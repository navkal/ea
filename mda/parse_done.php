<!DOCTYPE html>
<html>
  <?php
    include $_SERVER[DOCUMENT_ROOT]."/common/head.php";
    initUi( $_SERVER[DOCUMENT_ROOT]."/ea/" );

    $timestamp = $_GET["timestamp"];
    require_once "filenames.php" ;
    $paramsFile = fopen( $paramsFilename, "r" );
    $params = fgetcsv( $paramsFile );
    fclose( $paramsFile );

    require_once "labels.php" ;
  ?>

  <body>
    <div class="container" style="padding-top:30px;padding-bottom:60px">
      <div class="page-header">
        <p class="h3"><?=$labels["metasysDataAnalysis"]?> completion</p>
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
              </dl>
            </li>
            <li class="list-group-item list-group-item-success">
              <dl class="dl-horizontal" >
                <dt>
                  Results File
                </dt>
                <dd>
                  <?=basename( $resultsFilename )?>
                </dd>
              </dl>
            </li>
          </ul>
        </div>
      </div>

      <div style="text-align:center;" >
        <?php
          include "parse_display.php";
        ?>
      </div>

      <!-- Close button -->
      <div style="text-align:center;" >
       <a class="btn btn-default" href="javascript:startClose()" role="button">Close</a>
      </div>
    </div>

    <?php
      // Sticky footer
      include $_SERVER[DOCUMENT_ROOT]."/common/footer.php";
    ?>

  </body>
</html>

<script type="text/javascript" src="../../common/util.js"></script>
<script>
  $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
  document.title = "<?=$labels['metasysDataAnalysis']?>";

  function startClose()
  {
    $.ajax(
      "parse_cleanup.php?timestamp=<?=$timestamp?>",
      {
        type: "GET",
        cache: false,
        dataType: "json",
        success: finishClose,
        error: ajaxError,
        complete: ajaxComplete
      }
    );
  }

  function finishClose()
  {
    history.back();
  }
</script>

