<?php
  $resultsFile = fopen( sys_get_temp_dir() . "/" .  $resultsFilename, "r" );
  echo( print_r( fgetcsv( $resultsFile ), true ) );
  fclose( $resultsFile );
?>

<div class="container">
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
