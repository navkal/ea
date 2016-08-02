<!DOCTYPE html>
<html>
  <?php
    include "../common/head.php";
    initUi();

    $timestamp = $_GET["timestamp"];
    error_log( "===> parse_done timestamp=" . $timestamp );
    require_once "filenames.php" ;
    $paramsFile = fopen( $paramsFilename, "r" );
    $params = fgetcsv( $paramsFile );
    error_log( "=========> read params=" . print_r( $params, true ) );
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
       <a class="btn btn-default" href="javascript:history.back()" role="button">Close</a>
      </div>
    </div>

    <?php
      // Sticky footer
      include "../common/footer.php";
    ?>

  </body>
</html>
<script>
  $( 'head' ).append( '<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
  document.title = "Metasys Data Analysis";
</script>
