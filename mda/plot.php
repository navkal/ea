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

  <div class="container" id="downsampleControls" style="text-align:center" >

    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title">Down Sample</h3>
        <p>Alternately show and hide samples, starting at offset.<p/>
      </div>
      <div class="panel-body">
        <p>Currently showing <b><span id="downSampleShowing"></span></b> of <b><span id="downSampleOf"></span></b> samples.</p>

        <label class="checkbox checkbox-inline" >
          <input type="checkbox" name="downSampleAuto" id="downSampleAuto" tabindex="100" value="" checked="checked" onchange="downSampleAutoChanged()" />
          Use 'Limit' and 'Offset' to calculate 'Show' and 'Hide' automatically
        </label>
      </div>
    </div>


    <br/>
    <center>
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="margin-left:auto; margin-right:auto;" >
          <table>

                <tr>
                    <td class="formprompt">
                        Settings:
                    </td>
                    <td class="formfieldarea">
                        &nbsp;Limit&nbsp;
                        <script type="text/javascript">var g_iDownSampleLimit = 100;</script>
                        <input type="text" disabled="true" size="3" maxlength="3" name="downSampleLimit_current" id="downSampleLimit_current" value="100" />
                        &nbsp;Offset&nbsp;
                        <script type="text/javascript">var g_iDownSampleOffset = 0;</script>
                        <input type="text" disabled="true" size="3" maxlength="3" name="downSampleOffset_current" id="downSampleOffset_current" value="0" />
                        &nbsp;Show&nbsp;
                        <script type="text/javascript">var g_iDownSampleShow = 1;</script>
                        <input type="text" disabled="true" size="3" maxlength="3" name="downSampleShow_current" id="downSampleShow_current" value="1" />
                        &nbsp;Hide&nbsp;
                        <script type="text/javascript">var g_iDownSampleHide = 0;</script>
                        <input type="text" disabled="true" size="3" maxlength="3" name="downSampleHide_current" id="downSampleHide_current" value="0" />
                    </td>
                </tr>
                <tr>
                    <td class="formprompt">
                        Change to:
                    </td>
                    <td class="formfieldarea">
                        &nbsp;Limit&nbsp;
                        <input type="text" size="3" maxlength="3" name="downSampleLimit" id="downSampleLimit" onkeyup="clickDownSampleButton( event )" />
                        &nbsp;Offset&nbsp;
                        <input type="text" size="3" maxlength="3" name="downSampleOffset" id="downSampleOffset" onkeyup="clickDownSampleButton( event )" />
                        &nbsp;Show&nbsp;
                        <input type="text" size="3" maxlength="3" name="downSampleShow" id="downSampleShow" onkeyup="clickDownSampleButton( event )" />
                        &nbsp;Hide&nbsp;
                        <input type="text" size="3" maxlength="3" name="downSampleHide" id="downSampleHide" onkeyup="clickDownSampleButton( event )" />
                        &nbsp;
                        <a href="javascript:void(0)" onclick="return false;" class="mainbtn" id="DownSample" tabindex="100">APPLY</a>
                    </td>
                </tr>
                <tr>
                    <td class="formprompt">
                        Down-Sample in Zoom view:
                    </td>
                    <td align="center" colspan="1" class="formfieldarea">
                        <input type="checkbox" name="downSampleZoom" id="downSampleZoom" tabindex="100" value="" checked="checked" onchange="downSampleZoomChanged()" />
                    </td>
                </tr>
          </table>
        </div>
      </div>
    </div>
    </center>

  </div>
</div>
