<?php
  $paramsFile = fopen( $paramsFilename, "r" );
  $params = fgetcsv( $paramsFile );
  fclose( $paramsFile );

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

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.colorhelpers.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.crosshair.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.selection.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.min.js"></script>


<script language="javascript" type="text/javascript" src="../lib/flotPlot/analyzer.js"></script>
<script language="javascript" type="text/javascript" src="../lib/flotPlot/scrollbar.js"></script>
<link rel="stylesheet" href="../util/util.css">

<script>

  $( document ).ready( loadPlot );

  function loadPlot()
  {
    var lines = <?=json_encode( $lines, JSON_NUMERIC_CHECK )?>;
    var names = lines[0];
    var seriesPrecision = calculatePrecision( lines, names.length );

    // Build array of samples for plot
    var line = Array( names.length ).fill( 0 );
    var samples = [];
    samples.push( [ "label", "tick", "tickDecimals", "time", "value" ] );

    for ( var lineIndex = 1; lineIndex < lines.length; lineIndex ++ )
    {
      var prevLine = line;
      line = lines[lineIndex];
      var timestamp = new Date( line[0] ).valueOf()

      for ( var nameIndex = 1; nameIndex < names.length; nameIndex ++ )
      {
        // If current cell is empty, revert to value in preceding line
        if ( line[nameIndex] === "" )
        {
          console.log( "========> replacing " + line[nameIndex] +  " with " + prevLine[nameIndex] );
          line[nameIndex] = prevLine[nameIndex];
        }

        // Add a sample for this data cell
        var sample =
          [
            names[nameIndex],
            "",
            seriesPrecision[nameIndex],
            timestamp,
            line[nameIndex]
          ];
          samples.push( sample );
      }
    }

    if ( ! plotInit( samples ) )
    {
      $( "#messages" ).append( '<p>Could not decipher plot data</p>' );
      $( "#messages" ).css( "display", "block" );
      $( "#mainpane" ).css( "display", "none" );
    }
  }

  function calculatePrecision( lines, lineLength )
  {
    var NOT_USED = "NOT USED";

    // Determine min and max values for each series
    var seriesMin = Array( lineLength ).fill( Number.MAX_VALUE );
    seriesMin[0] = NOT_USED;

    var seriesMax = Array( lineLength ).fill( Number.MIN_VALUE );
    seriesMax[0] = NOT_USED;

    console.log( "=======> BF min=" + JSON.stringify( seriesMin ) );
    console.log( "=======> BF max=" + JSON.stringify( seriesMax ) );

    for ( var lineIndex = 1; lineIndex < lines.length; lineIndex ++ )
    {
      var line = lines[lineIndex];

      for ( var nameIndex = 1; nameIndex < lineLength; nameIndex ++ )
      {
        seriesMin[nameIndex] = Math.min( seriesMin[nameIndex], line[nameIndex] );
        seriesMax[nameIndex] = Math.max( seriesMax[nameIndex], line[nameIndex] );
      }
    }

    console.log( "=======> AF min=" + JSON.stringify( seriesMin ) );
    console.log( "=======> AF max=" + JSON.stringify( seriesMax ) );

    // Determine precision for each series
    var seriesPrecision = Array( lineLength ).fill( 0 );
    seriesPrecision[0] = NOT_USED;

    for ( var index = 1; index < seriesPrecision.length; index ++ )
    {
      var min = seriesMin[index];
      var max = seriesMax[index];
      var diff = ( min < 0 ) ? Math.max( Math.abs( min ), max ) : ( max - min );
      var digits = Math.floor( diff * 1000 ).toString().length;

      switch( digits )
      {
        case 1:
          seriesPrecision[index] = 3;
          break;
        case 2:
          seriesPrecision[index] = 2;
          break;
        case 3:
          seriesPrecision[index] = 1;
          break;
        case 4:
        default:
          seriesPrecision[index] = 0;
          break;
      }
    }

    console.log( "=======> precision=" + JSON.stringify(seriesPrecision) );

    return seriesPrecision;
  }

</script>

<div class="container" >
  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <dl class="dl-horizontal list-group-item list-group-item-info" >
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
    </div>
  </div>
  <br/>
  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div id="messages" class="alert alert-danger" style="display:none" role="alert">
      </div>
    </div>
  </div>
</div>

<div id="mainpane" >

  <div class="container" >
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <ul name="seriesChooser" id="seriesChooser" class="list-unstyled checkboxList" ></ul>
      </div>
    </div>
  </div>

  <div class="container-fluid" >
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div name="plotview" id="plotview" style="width:90%; height:350px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
        <br/>
        <div name="overview" id="overview" style="width:90%; height:100px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
        <br/>
        <div name="scrollbar" id="scrollbar" style="width:90%; height:20px; margin-left:auto; margin-right:auto;" ></div>
        <br/>
      </div>
    </div>
  </div>

  <div class="container" id="downsampleControls" >

    <div class="panel panel-default">

      <div class="panel-heading">

        <div class="row">
          <div class="col-xs-12 col-sm-12 col-md-9 col-lg-9">
            <p>
              Showing
              <b><span id="downSampleShowing"></span></b>
              of
              <b><span id="downSampleOf"></span></b>
              samples.
              <span id="densityCurrent" >
                <i>Density</i>=<b><span id="downSampleDensity_current"></span></b>,
              </span>
              <i>Show</i>=<b><span id="downSampleShow_current"></span></b>,
              <i>Hide</i>=<b><span id="downSampleHide_current"></span></b>,
              <i>Offset</i>=<b><span id="downSampleOffset_current"></span></b>.
            </p>
          </div>


          <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
            <div class="form-group" >
              <label class="checkbox checkbox-inline" >
                <input type="checkbox" id="downSampleZoom" value="" checked="checked" onchange="downSampleZoomChanged()" />
                Down Sample in Zoom view
              </label>
            </div>
          </div>
        </div>

      </div>

      <div class="panel-body">

        <div class="row">
          <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
            <div class="form-group">
              <label class="control-label" for="downSampleAuto" >Down Sample by</label>
              <div>
                <label class="radio-inline" >
                  <input type="radio" name="downSampleAuto" id="downSampleAuto" value="" checked="checked" onchange="downSampleControlsEnable()" />
                  Density
                </label>
                <label class="radio-inline" >
                  <input type="radio" name="downSampleAuto" id="downSampleManual" value="" onchange="downSampleControlsEnable()" />
                  Pattern
                </label>
              </div>
            </div>
          </div>
          <div id="density" >
            <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
              <div class="form-group" >
                <label class="control-label" for="downSampleDensity" >Density</label>
                <input type="text" id="downSampleDensity" class="form-control" maxlength="4" onkeyup="clickDownSampleButton( event )" />
              </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
            </div>
          </div>
          <div id="pattern" >
            <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
              <div class="form-group" >
                <label class="control-label" for="downSampleShow" >Show</label>
                <input type="text" id="downSampleShow" class="form-control" maxlength="4" onkeyup="clickDownSampleButton( event )" />
              </div>
            </div>
            <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
              <div class="form-group" >
                <label class="control-label" for="downSampleHide" >Hide</label>
                <input type="text" id="downSampleHide" class="form-control" maxlength="10" onkeyup="clickDownSampleButton( event )" />
              </div>
            </div>
          </div>
          <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
            <div class="form-group" >
              <label class="control-label" for="downSampleOffset" >Offset</label>
              <input type="text" id="downSampleOffset" class="form-control" maxlength="10" onkeyup="clickDownSampleButton( event )" />
            </div>
          </div>
          <div class="col-xs-12 col-sm-12 col-md-1 col-lg-1">
            <label class="control-label" >&nbsp;</label>
            <div><button type="button" class="btn btn-default btn-sm" onclick="plotDownSample();return false;" title="Apply Down Sample settings to Plot" >Apply</button></div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>
