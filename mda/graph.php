<?php
error_log( "=========> IN IT!!1 GRAPH.PHP" );
  $lines = [];
  $heads = [];
  $resultsFile = fopen( $resultsFilename, "r" );
  while( ! feof( $resultsFile ) )
  {
    $line = fgetcsv( $resultsFile );
    if ( count( $line ) > 1 )
    {
      array_push( $lines, $line );
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

  error_log( "======> heads=" . print_r( $heads, true ) );
  error_log( "======> lines=" . print_r( $lines, true ) );

  $names = $lines[0];
  $samples = [];
  array_push( $samples, [ "label", "tick", "tickDecimals", "time", "value" ] );
  for ( $lineIndex = 1; $lineIndex < count( $lines ); $lineIndex ++ )
  {
    $line = $lines[$lineIndex];

    for ( $nameIndex = 1; $nameIndex < count( $names ); $nameIndex ++ )
    {
      $sample =
        [
          $names[$nameIndex],
          "",
          1,
          strtotime( $line[0] ),
          $line[$nameIndex]
        ];
        array_push( $samples, $sample );
    }
  }
  error_log( "======> samples=" . print_r( $samples, true ) );
?>

<script>
$( document ).ready( init );
function init()
{
  plotInit( <?=json_encode( $samples, JSON_NUMERIC_CHECK )?> );
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
