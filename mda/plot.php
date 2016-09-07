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

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.colorhelpers.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.crosshair.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.navigate.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.resize.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.selection.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/flot/0.8.3/jquery.flot.time.min.js"></script>

<script language="javascript" type="text/javascript" src="flotPlot/scr/analyzer.js"></script>
<script language="javascript" type="text/javascript" src="flotPlot/scr/scrollbar.js"></script>

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
      var timestamp = new Date( line[0] ).valueOf()

      for ( var nameIndex = 1; nameIndex < names.length; nameIndex ++ )
      {
        var sample =
          [
            names[nameIndex],
            "",
            1,
            timestamp,
            Number( line[nameIndex] )
          ];
          samples.push( sample );
      }
    }

    plotInit( samples );
  }
</script>

<div id="mainpane" >
  <div style="width:90%; margin-left:auto; margin-right:auto;" >
    <table>
      <tr>
        <td>
          <span id="pagestatus" name="pagestatus" class="pageinfo">Test</span>
        </td>
      </tr>
    </table>
  </div>
  <div name="seriesChooser" id="seriesChooser" style="width:90%; height:30px; margin-left:auto; margin-right:auto; margin-top:20px; text-align:right;" ></div>
  <div name="plotview" id="plotview" style="width:90%; height:350px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
  <br/>
  <div name="overview" id="overview" style="width:90%; height:100px; margin-left:auto; margin-right:auto; cursor: pointer;" ></div>
  <br/>
  <div name="scrollbar" id="scrollbar" style="width:90%; height:20px; margin-left:auto; margin-right:auto;" ></div>
  <br/>
  <center>
            <table>
                <tr>
                    <td align="center" colspan="2" class="formlegend">
                        Down Sample
                    </td>
                </tr>
                <tr>
                    <td align="center" colspan="2" class="captionline">
                        Alternately show and hide samples, starting at offset.
                    </td>
                </tr>
                <tr>
                    <td>
                        &nbsp;
                    </td>
                </tr>
                <tr>
                    <td class="formprompt">
                        Status:
                    </td>
                    <td align="center" colspan="1" class="formfieldarea">
                        <span id="downSampleStatus" name="downSampleStatus"></span>
                    </td>
                </tr>
                <tr>
                    <td class="formprompt">
                        &nbsp;
                    </td>
                    <td align="center" colspan="1" class="formfieldarea">
                        <input type="checkbox" name="downSampleAuto" id="downSampleAuto" tabindex="100" value="" checked="checked" onchange="downSampleAutoChanged()" /> Use 'Limit' and 'Offset' to calculate 'Show' and 'Hide' automatically
                    </td>
                </tr>
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
  </center>
</div>

