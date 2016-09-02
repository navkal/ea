<script>

</script>

<div class="row">
  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        Graph
      </div>
      <div class="panel-body">
        <h5>graph will be here</h5>
        <?php
          $resultsFile = fopen( $resultsFilename, "r" );
          echo( print_r( fgetcsv( $resultsFile ), true ) );
          fclose( $resultsFile );
        ?>
      </div>
    </div>
  </div>
</div>
