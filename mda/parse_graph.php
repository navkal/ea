<!DOCTYPE html>
<html>
  <?php
    include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
    initUi( $_SERVER["DOCUMENT_ROOT"]."/" );

    $timestamp = $_GET["timestamp"];
    require_once "filenames.php" ;

    $resultsFile = fopen( $resultsFilename, "r" );
    echo( print_r( fgetcsv( $resultsFile ), true ) );
    fclose( $resultsFile );

    require_once "labels.php";
  ?>

  <body>
    <div class="container">
      <div class="page-header">
        <p class="h3"><?=METASYS_DATA_ANALYSIS?> grapy</p>
      </div>
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
    </div>

  </body>
</html>
