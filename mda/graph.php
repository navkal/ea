<?php
  $lines = [];
  $heads = [];
  if ( $resultsFile = @fopen( $resultsFilename, "r" ) )
  {
    while( ! feof( $resultsFile ) )
    {
      $line = fgetcsv( $resultsFile );
      if ( count( $line ) > 1 )
      {
        // Save lines; purge any that have no values
        $lineVals = $line;
        array_shift( $lineVals );
        $implode = trim( implode( $lineVals ) );
        if ( $implode != "" )
        {
          array_push( $lines, $line );
        }
      }
      else
      {
        if ( ( $head = trim( $line[0] ) ) != "" )
        {
          array_push( $heads, $head );
        }
      }
    }
    fclose( $resultsFile );
  }

  error_log( "======> heads=" . print_r( $heads, true ) );
  error_log( "======> lines=" . print_r( $lines, true ) );
?>

<script>
$( document ).ready( loadPlot );

function loadPlot()
{
  var lines = <?=json_encode( $lines, JSON_NUMERIC_CHECK )?>;

  var names = lines[0];
  var samples = [];
  samples.push( [ "label", "tick", "tickDecimals", "time", "value" ] );
  for ( var lineIndex = 1; lineIndex < lines.length; lineIndex ++ )
  {
    var line = lines[lineIndex];

    for ( var nameIndex = 1; nameIndex < names.length; nameIndex ++ )
    {
      var sample =
        [
          names[nameIndex],
          "",
          1,
          new Date( line[0] ).valueOf(),
          Number( line[nameIndex] )
        ];
        samples.push( sample );
    }
  }

  plotInit( samples );
}
</script>

<div class="row">
  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="panel-body">
      <?php
        include( "flotPlot.php" );
      ?>
    </div>
  </div>
</div>
