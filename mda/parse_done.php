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
  ?>

  <body>
    <div class="container" style="padding-top:30px">

      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <div class="well">
            <dl class="dl-horizontal" >
              <dt>
                Metasys File
              </dt>
              <dd>
                <?php
                  echo $params[0];
                ?>
              </dd>
              <dt>
                Start Time
              </dt>
              <dd>
                <?php
                  echo( isset( $params[1] ) ? $params[1] : "n/a" );
                ?>
              </dd>
              <dt>
                End Time
              </dt>
              <dd>
                <?php
                  echo( isset( $params[2] ) ? $params[2] : "n/a" );
                ?>
              </dd>
            </dl>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <div class="well" >
            <p>
              <?php
                $resultsFile = fopen( $resultsFilename, "r" );
                echo( print_r( fgetcsv( $resultsFile ), true ) );
                fclose( $resultsFile );
              ?>
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Close button -->
    <div class="container">
      <div style="text-align:center;" >
       <a class="btn btn-default" href="javascript:startClose()" role="button">Close</a>
      </div>
    </div>

    <?php
      // Sticky footer
      $_SERVER[DOCUMENT_ROOT]."/common/footer.php";
    ?>

  </body>
</html>

<script type="text/javascript" src="../../common/util.js"></script>
<script>
  $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
  document.title = "Metasys Data Analysis";

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

