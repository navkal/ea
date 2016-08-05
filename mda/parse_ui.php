<?php
  require_once $_SERVER[DOCUMENT_ROOT]."/common/util.php";
  require_once "labels.php";
?>

<link rel="stylesheet" href="../../common/wickedpicker/dist/wickedpicker.min.css">
<script type="text/javascript" src="../../common/wickedpicker/dist/wickedpicker.unmin.js"></script>
<script type="text/javascript" src="../../common/util.js"></script>

<style>
@media( max-width: 767px )
{
  .padLeftSmall
  {
    padding-left: 20px;
  }
}
</style>

<script>
  $( document ).ready(
    function()
    {
      // Initialize identifying timestamp
      $( "#timestamp" ).val( Date.now() );

      // Initialize the file chooser
      $( "#metasysFile" ).val( "" );
      $( "#uploadFilename" ).val( "" );

      // Create time pickers
      $( '#startTime' ).wickedpicker( { now: "05:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
      $( '#endTime' ).wickedpicker( { now: "20:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

      // Initialize options
      $( "#summarize" ).prop( "checked", true );
      onChangeSummarize();
    }
  );

  // Handle change of Summarize checkbox
  function onChangeSummarize()
  {
    var bDisable = ! $( "#summarize" ).prop( "checked" );

    var period = $( "input[type=radio][name=period]" );
    period.parent().css( "color", bDisable ? "lightgray" : "black" );
    period.parent().css( "cursor", bDisable ? "default" : "pointer" );
    period.prop( "disabled", bDisable );
    $( "#fullday" ).prop( "checked", ! bDisable );
    $( "#partday" ).prop( "checked", false );
    $( "label[for=period]" ).css( "color", bDisable ? "lightgray" : "black" );

    disableTimeInput( "startTime", bDisable );

    onChangePeriod();

    $( "#cost" ).prop( "disabled", bDisable );
    $( "#cost" ).val( bDisable ? "" : "0.16" );
    $( "label[for=cost]" ).css( "color", bDisable ? "lightgray" : "black" );
    $( "#dollars" ).css( "color", bDisable ? "lightgray" : "black" );
  }

  // Handle change of Period radio buttons
  function onChangePeriod()
  {
    var summaryChecked = $( "#summarize" ).prop( "checked" );
    var fulldayChecked = $( "#fullday" ).prop( "checked" );

    var bDisable = ! summaryChecked || ( summaryChecked && fulldayChecked );
    disableTimeInput( "endTime", bDisable )
  }

  function disableTimeInput( id, bDisable )
  {
    $( "label[for='" + id + "']" ).css( "color", bDisable ? "lightgray" : "black" );
    var selector =  "#" + id;
    $( selector ).prop( "disabled", bDisable );
    $( selector ).val( bDisable ? "" : $( selector ).wickedpicker( "time" ) );
  }

  // Show selected filename in input field
  function showFilename( sFilenameId, sFileId )
  {
    var sFilename = $( '#' + sFileId ).val().split('\\').pop().split('/').pop();
    $( '#' + sFilenameId ).val( sFilename );
  }

  // Validate input supplied in form
  function validateFormInput()
  {
    // Clear all visual feedback
    $( ".has-error" ).removeClass( "has-error" );
    $( "#messages" ).css( "display", "none" );
    $( "#messageList" ).html( "" );

    var messages = [];

    // Check Metasys File
    if ( $( "#metasysFile" ).val() == "" )
    {
      messages.push( "<?=$labels['metasysFile']?> is required" );
      $( "#uploadFilename" ).parent().addClass( "has-error" );
    }

    // Check time inputs
    if ( ! $( "#startTime" ).prop( "disabled" )
        &&  ! $( "#startTime" ).prop( "disabled" )
        && ( $( "#startTime" ).val() == $( "#endTime" ).val() ) )
    {
      messages.push( "<?=$labels["startTime"]?> and End Time must differ" );
      $( "#startTime" ).parent().addClass( "has-error" );
      $( "#endTime" ).parent().addClass( "has-error" );
    }

    // Update visual feedback
    if ( messages.length == 0 )
    {
      $( "body" ).css( "cursor", "progress" );
      setTimeout( isItReadyYet, 1000 );
    }
    else
    {
      for ( var index in messages )
      {
        $( "#messageList" ).append( '<li>' + messages[index] + '</li>' );
      }
      $( "#messages" ).css( "display", "block" );
    }

    return ( messages.length == 0 );
  }

  function isItReadyYet()
  {
    $.ajax(
      "mda/parse_ready.php?timestamp=" + $( "#timestamp" ).val(),
      {
        type: "GET",
        cache: false,
        dataType: "json",
        success: handlePollResponse,
        error: ajaxError,
        complete: ajaxComplete
      }
    );
  }

  function handlePollResponse( rsp, sStatus, tJqXhr )
  {
    console.log( "=========> poll response=<" + rsp + ">" );
    if ( rsp == "" )
    {
      // Try again
      setTimeout( isItReadyYet, 1000 );
    }
    else
    {
      // Clear wait cursor
      $( "body" ).css( "cursor", "default" );

      // Render results
      window.location.assign( "mda/parse_done.php?timestamp=" + $( "#timestamp" ).val()  );
    }
  }
</script>

<!-- Modal dialog for Metasys File help -->
<div class="modal fade" id="helpMetasysFile" tabindex="-1" role="dialog" aria-labelledby="helpMetasysFileLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpMetasysFileLabel"><?=$labels["metasysFile"]?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dd>
            Enter a .csv file exported from Metasys.
          </dd>
        </dl>
       <dl>
          <dt>
            How <?=$labels["metasysFile"]?> is used:
          </dt>
          <dd>
            <ol>
              <li>
                You click <i>OK</i>.
              </li>
              <li>
                Browser uploads <?=$labels["metasysFile"]?> to server.
              </li>
              <li>
                Metasys Data Analysis script analyzes contents of <?=$labels["metasysFile"]?>.
              </li>
              <li>
                Browser downloads analysis results to your computer.
              </li>
            </ol>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal dialog for Options help -->
<div class="modal fade" id="helpOptions" tabindex="-1" role="dialog" aria-labelledby="helpOptionsLabel">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpOptionsLabel"><?=$labels["analysisOptions"]?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dt>
            <?=$labels["summarize"]?>
          </dt>
          <dd>
            Aggregates results in specified time periods.
          </dd>
        </dl>
        <dl>
          <dt>
            Time Period
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                Full Day
              </dt>
              <dd>
                Aggregates results in 24-hour periods beginning with <i><?=$labels["startTime"]?></i>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                Partial Day
              </dt>
              <dd>
                Aggregates results in periods from <i><?=$labels["startTime"]?></i> to <i>End Time</i>.
              </dd>
              <dd>
                <ul>
                  <li>
                    If <i><?=$labels["startTime"]?></i> is greater than <i>End Time</i>, the time periods cross midnight.
                  </li>
                  <li>
                    <i><?=$labels["startTime"]?></i> and <i>End Time</i> must have different values.
                  </li>
                </ul>
              </dd>
            </dl>
          </dd>
        </dl>
        </dl>
        <dl>
          <dt>
            <?=$labels["startTime"]?>
          </dt>
          <dt>
            End Time
          </dt>
          <dd>
            <ul>
              <li>
                Click to open the Time Editor.
              </li>
              <li>
                Use mouse or arrow keys to edit time.
              </li>
            </ul>
          </dd>
        </dl>
        <dl>
          <dt>
            Cost per kWh
          </dt>
          <dd>
            <ul>
              <li>
                Indicates the cost of electricity in dollars.
              </li>
            </ul>
          </dd>
        </dl>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <div class="page-header">
    <p class="h3">Metasys Data Analysis</p>
  </div>

  <form id="uploadForm" role="form" onsubmit="return validateFormInput();" action="mda/parse_run.php" method="post" enctype="multipart/form-data" >

    <!-- Identifying timestamp -->
    <input type="hidden" id="timestamp" name="timestamp" >

    <!-- Metasys File chooser -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <span class="panel-title">
              <div class="row">
                <div class="col-xs-8 col-sm-10 col-md-11 col-lg-11">
                  <?=$labels["metasysFile"]?>
                </div>
                <div class="col-xs-4 col-sm-2 col-md-1 col-lg-1">
                  <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#helpMetasysFile">
                    Help
                  </button>
                </div>
              </div>
            </span>
          </div>
          <div class="panel-body">
            <div class="form-group" >
              <div class="input-group">
                <label class="input-group-btn">
                  <span class="btn btn-default">
                    Browse…
                    <input type="file" name="metasysFile" id="metasysFile" style="display:none" onchange="showFilename( 'uploadFilename', 'metasysFile' )" >
                  </span>
                </label>
                <input id="uploadFilename" type="text" class="form-control" onclick="$('#metasysFile').click();" readonly >
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <br/>

    <!-- Options -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <span class="panel-title">
              <div class="row">
                <div class="col-xs-8 col-sm-10 col-md-11 col-lg-11">
                  <?=$labels["analysisOptions"]?>
                </div>
                <div class="col-xs-4 col-sm-2 col-md-1 col-lg-1">
                  <button type="button" class="btn btn-info btn-sm" data-toggle="modal" data-target="#helpOptions">
                    Help
                  </button>
                </div>
              </div>
            </span>
          </div>

          <div class="panel-body">

            <div class="form-group" >
              <div class="checkbox">
                <label><input type="checkbox" id="summarize" onchange="onChangeSummarize()" ><?=$labels["summarize"]?></label>
              </div>
            </div>

            <div class="form-group" >
              <label class="control-label" for="startTime" ><?=$labels["startTime"]?></label>
              <input type="text" id="startTime" name="startTime" class="form-control timepicker" style="border-radius:4px" readonly >
            </div>

            <br/>
            <div class="form-group">
              <label class="control-label" for="period" >Time Period</label>
              <div>
                <label class="radio-inline" >
                  <input type="radio" name="period" id="fullday" value="Full Day" onchange="onChangePeriod()" checked >
                  Full Day
                </label>
                <label class="radio-inline" >
                  <input type="radio" name="period" id="partday" value="Partial Day" onchange="onChangePeriod()" >
                  Partial Day
                </label>
              </div>
            </div>

            <div class="form-group" >
              <label class="control-label" for="endTime" >End Time</label>
              <input type="text" id="endTime" name="endTime" class="form-control timepicker" style="border-radius:4px" readonly >
            </div>

            <br/>
            <div class="form-group">
              <label for="cost">Cost per kWh</label>
              <div class="input-group">
                <span class="input-group-addon" id="dollars">$</span>
                <input type="number" value="0.16" min="0.01" step="0.01" class="form-control" id="cost" name="cost" />
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

  </form>

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div style="text-align:center;" >
        <button type="submit" form="uploadForm" class="btn btn-primary" >OK</button>
        <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
      </div>
    </div>
  </div>

  <br/>

  <div class="row">
    <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
      <div id="messages" class="alert alert-danger" style="display:none" role="alert">
        <ul id="messageList">
        </ul>
      </div>
    </div>
  </div>

</div>

