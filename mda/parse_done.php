<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );

    $timestamp = $_GET["timestamp"];
    require_once "filenames.php" ;

    // Save results filename with full path for use by Plot view
    $_SESSION["resultsFilename"] = $resultsFilename;

    // Get analysis parameters for completion display
    error_log( "==> sess completion params=" . $_SESSION["completion"]["params"] );
    $params = str_getcsv( $_SESSION["completion"]["params"] );
    error_log( "==> str_getcsv returned params=" . $params );
    $resultsFilename = $_SESSION["completion"]["resultsFilename"];

    $columns = [];
    $columnsFile = fopen( $columnsFilename, "r" );
    while( ( $line = fgetcsv( $columnsFile ) ) !== false )
    {
      array_push( $columns, $line[0] );
    }
    fclose( $columnsFile );

    require_once "labels.php";
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
