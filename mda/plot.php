<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<?php
  $lines = [];
  if ( $resultsFile = @fopen( $_SESSION["resultsFilename"], "r" ) )
  {
    while( ! feof( $resultsFile ) )
    {
      $line = fgetcsv( $resultsFile );

      // Save lines that have commas and contain non-empty values
      if ( count( $line ) > 1 )
      {
        $lineVals = $line;
        array_shift( $lineVals );
        $implode = trim( implode( $lineVals ) );
        if ( $implode != "" )
        {
          array_push( $lines, $line );
        }
      }
    }
    $params = array_pop( $lines );
    fclose( $resultsFile );
  }
?>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.colorhelpers.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.crosshair.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.selection.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.min.js"></script>


<script language="javascript" type="text/javascript" src="../lib/flotPlot/analyzer.js?version=<?=$timestamp?>"></script>
<script language="javascript" type="text/javascript" src="../lib/flotPlot/scrollbar.js"></script>
<link rel="stylesheet" href="../util/util.css">

<style>
  .controlsMargin
  {
    margin: 6px;
  }
</style>

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
      // Report error and hide everything
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

    for ( var lineIndex = 1; lineIndex < lines.length; lineIndex ++ )
    {
      var line = lines[lineIndex];

      for ( var nameIndex = 1; nameIndex < lineLength; nameIndex ++ )
      {
        seriesMin[nameIndex] = Math.min( seriesMin[nameIndex], line[nameIndex] );
        seriesMax[nameIndex] = Math.max( seriesMax[nameIndex], line[nameIndex] );
      }
    }

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

    return seriesPrecision;
  }

</script>

<div class="container" >

  <!-- Collapsible "Parameters" section -->
  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div class="panel-group" role="tablist" >
        <div class="panel panel-default panel-info">
          <div class="panel-heading" role="tab" id="paramsHeading">
            <h4 class="panel-title">
              <a role="button" data-toggle="collapse" href="#paramsCollapse" aria-expanded="true" aria-controls="paramsCollapse">
                <span class="glyphicon glyphicon-stats">&nbsp;</span>Analysis Parameters
                <span class="glyphicon glyphicon-plus pull-right">
              </a>
            </h4>
          </div>
          <div id="paramsCollapse" class="panel-collapse collapse" role="tabpanel" aria-labelledby="paramsHeading">
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
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div id="messages" class="alert alert-danger" style="display:none" role="alert">
      </div>
    </div>
  </div>
</div>

<div id="mainpane">

  <div class="container" >

    <!-- Collapsible "Parameters" section -->
    <div class="panel-group" role="tablist" >
      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="pointsHeading">
          <h4 class="panel-title">
            <a role="button" data-toggle="collapse" href="#pointsCollapse" aria-expanded="true" aria-controls="pointsCollapse">
              <span class="glyphicon glyphicon-check">&nbsp;</span><?=POINTS_OF_INTEREST?>
              <span class="glyphicon glyphicon-minus pull-right">
            </a>
          </h4>
        </div>
        <div id="pointsCollapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="pointsHeading">
          <div class="panel-body">

            <!-- Checkbox accelerators -->
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div id="checkboxAccelerators" class="btn-toolbar" role="toolbar" >
                  <span class="btn-group btn-group-xs" style="padding-right:10px" role="group" >
                    <button type="button" id="seriesCheckAll" class="btn btn-default btn-xs" title="Select All" >All</button>
                    <button type="button" id="seriesCheckNone" class="btn btn-default btn-xs" title="Deselect All" >None</button>
                    <button type="button" id="seriesCheckComplement" class="btn btn-default btn-xs" title="Select Complement" >Complement</button>
                  </span>
                </div>
              </div>
            </div>

            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <ul name="seriesChooser" id="seriesChooser" class="list-unstyled checkboxList" ></ul>
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="container-fluid" >

    <!-- Timestamp bounds -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="width:82%; margin-left:auto; margin-right:auto;" class="text-right" >
          <small>
            <span id="timestampFrom"></span> - <span id="timestampTo"></span>
          </small>
        </div>
      </div>
    </div>

    <!-- Plot -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div name="plotview" id="plotview" style="width:90%; height:430px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
        <br/>
        <div name="overview" id="overview" style="width:90%; height:100px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
        <br/>
        <div name="scrollbar" id="scrollbar" style="width:90%; height:28px; margin-left:auto; margin-right:auto;" ></div>
        <br/>
      </div>
    </div>

  </div>

  <div class="container" id="plotControls" >

    <!-- Collapsible "Controls" section -->
    <div class="panel-group" role="tablist" >
      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="controlsHeading">
          <h4 class="panel-title">
            <a role="button" data-toggle="collapse" href="#controlsCollapse" aria-expanded="true" aria-controls="controlsCollapse">
              <span class="glyphicon glyphicon-cog">&nbsp;</span>Controls
              <span class="glyphicon glyphicon-minus pull-right">
            </a>
          </h4>
        </div>
        <div id="controlsCollapse" class="panel-collapse collapse in" role="tabpanel" aria-labelledby="controlsHeading">
          <div class="panel-body">
            <div class="row">
              <div class="col-xs-12 col-sm-7 col-md-7 col-lg-7">
                <div class="row">
                  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <p class="small text-muted controlsMargin" >
                      Drag mouse across plot to zoom in.
                    </p>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <button type="button" id="plotCropIn" class="btn btn-default controlsMargin" onclick="plotCropIn();return false;" title="Crop plot to zoom range" >
                      <span class="glyphicon glyphicon-zoom-in"></span> Crop
                    </button>
                    <button type="button" id="plotCropOut" class="btn btn-default controlsMargin" onclick="plotCropOut();return false;" title="Show previous crop range" >
                      <span class="glyphicon glyphicon-zoom-in"></span> Uncrop
                    </button>
                  </div>
                </div>
                <div class="row">
                  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                    <button type="button" id="plotReset" class="btn btn-default controlsMargin" onclick="plotReset();return false;" title="Reset plot view" >
                      <span class="glyphicon glyphicon-home"></span> Reset
                    </button>
                    <button type="button" id="plotZoomOut" class="btn btn-default controlsMargin" onclick="plotZoomOut(event);return false;" title="Clear zoom range" >
                      <span class="glyphicon glyphicon-arrow-up"></span> Zoom out
                    </button>
                  </div>
                </div>
              </div>
              <div class="col-xs-12 col-sm-5 col-md-5 col-lg-5">
                <div class="controlsMargin" >
                  <div class="checkbox" >
                    <label>
                      <input type="checkbox" id="downSampleZoom" onchange="downSampleZoomChanged()" />
                      Show all samples in zoom range
                    </label>
                  </div>
                  <div class="checkbox" >
                    <label>
                      <input type="checkbox" id="showYaxisTicks" onchange="plotShowYaxisTicks()" />
                      Show Y-axis ticks
                    </label>
                  </div>
                  <div class="checkbox" >
                    <label>
                      <input type="checkbox" id="scaleIndependent" checked onchange="plotScaleIndependent()" />
                      Scale series independently
                    </label>
                  </div>
                </div>
                <div class="form-inline controlsMargin">
                  <div class="form-group">
                    <div class="input-group">
                      <span class="input-group-addon">$</span>
                      <input class="form-control" type="number" min="0.01" step="0.01" id="cost" />
                      <span class="input-group-addon">per unit</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Collapsible "Advanced" section -->
    <div class="panel-group" role="tablist">
      <div class="panel panel-default">
        <div class="panel-heading" role="tab" id="advancedHeading">
          <h4 class="panel-title">
            <a href="#advancedCollapse" class="collapsed" role="button" data-toggle="collapse" aria-expanded="false" aria-controls="advancedCollapse">
              <span class="glyphicon glyphicon-wrench">&nbsp;</span>Advanced
              <span class="glyphicon glyphicon-plus pull-right">
            </a>
          </h4>
        </div>
        <div class="collapse panel-collapse" role="tabpanel" id="advancedCollapse" aria-labelledby="advancedHeading">
          <ul class="list-group">
            <li class="list-group-item">

              <div class="row">
                <div class="col-xs-12 col-sm-12 col-md-3 col-lg-3">
                  <div class="form-group">
                    <label class="control-label" for="downSampleMode" >Down Sample by</label>
                    <div>
                      <label class="radio-inline" >
                        <input type="radio" name="downSampleMode" id="downSampleAuto" value="auto" checked onchange="downSampleControlsEnable(event)" />
                        Density
                      </label>
                      <label class="radio-inline" >
                        <input type="radio" name="downSampleMode" id="downSampleManual" value="manual" onchange="downSampleControlsEnable(event)" />
                        Pattern
                      </label>
                      <label class="radio-inline" style="display:none" >
                        <input type="radio" name="downSampleMode" id="downSampleByZoom" value="zoom" onchange="downSampleControlsEnable(event)" />
                        Zoom
                      </label>
                    </div>
                  </div>
                </div>
                <div id="density" >
                  <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
                    <div class="form-group" >
                      <label class="control-label" for="downSampleDensity" >Density</label>
                      <input type="text" id="downSampleDensity" class="form-control" maxlength="5" onkeyup="clickDownSampleButton( event )" />
                    </div>
                  </div>
                  <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
                  </div>
                </div>
                <div id="pattern" >
                  <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
                    <div class="form-group" >
                      <label class="control-label" for="downSampleShow" >Show</label>
                      <input type="text" id="downSampleShow" class="form-control" maxlength="5" onkeyup="clickDownSampleButton( event )" />
                    </div>
                  </div>
                  <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
                    <div class="form-group" >
                      <label class="control-label" for="downSampleHide" >Hide</label>
                      <input type="text" id="downSampleHide" class="form-control" maxlength="10" onkeyup="clickDownSampleButton( event )" />
                    </div>
                  </div>
                </div>
                <div id="offset" >
                  <div class="col-xs-12 col-sm-12 col-md-2 col-lg-2">
                    <div class="form-group" >
                      <label class="control-label" for="downSampleOffset" >Offset</label>
                      <input type="text" id="downSampleOffset" class="form-control" maxlength="10" onkeyup="clickDownSampleButton( event )" />
                    </div>
                  </div>
                </div>
                <div class="col-xs-12 col-sm-12 col-md-1 col-lg-1">
                </div>
                <div class="col-xs-12 col-sm-12 col-md-1 col-lg-1">
                  <label class="control-label" >&nbsp;</label>
                  <div><button type="button" class="btn btn-default btn-sm" onclick="plotDownSample();return false;" title="Apply Down Sample settings to Plot" >Apply</button></div>
                </div>
              </div>

            </li>
          </ul>

          <div class="panel-footer">
            <div class="row">
              <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                Down Sample includes
                <b><span id="downSampleShowing"></span></b>
                of
                <b><span id="downSampleOf"></span></b>
                samples.
              </div>
              <div class="col-xs-12 col-sm-6 col-md-6 col-lg-6">
                <span id="densityCurrent" >
                  <i>Density</i>=<b><span id="downSampleDensity_current"></span></b>,
                </span>
                <i>Show</i>=<b><span id="downSampleShow_current"></span></b>,
                <i>Hide</i>=<b><span id="downSampleHide_current"></span></b>,
                <i>Offset</i>=<b><span id="downSampleOffset_current"></span></b>.
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>
