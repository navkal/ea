<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  require_once "labels.php";
?>

<link rel="stylesheet" href="lib/wickedpicker/dist/wickedpicker.min.css">
<script type="text/javascript" src="lib/wickedpicker/dist/wickedpicker.unmin.js"></script>
<script type="text/javascript" src="../util/util.js"></script>

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
      console.log( "===> timestamp=" + $( "#timestamp" ).val() );

      // Initialize the file chooser
      $( "#metasysFile" ).val( "" );
      $( "#uploadFilename" ).val( "" );

      // Hide Analysis Options form
      $( "#optionsForm" ).css( "display", "none" );
    }
  );

  function onSubmitFile()
  {
    if ( validateFormInput( validateFileInput ) )
    {
      // Set wait cursor
      $( "body" ).css( "cursor", "progress" );

      // Post file to server
      var postData = new FormData();
      postData.append( "metasysFile", $( "#metasysFile" ).prop( "files" )[0] );

      $.ajax(
        "mda/parse_upload.php?timestamp=" + $( "#timestamp" ).val(),
        {
          type: 'POST',
          processData: false,
          contentType: false,
          dataType : 'json',
          data: postData
        }
      )
      .done( handlePostResponse )
      .fail( ajaxFail );
    }
  }

  function handlePostResponse( rsp, sStatus, tJqXhr )
  {
    $( "body" ).css( "cursor", "default" );
    console.log( "handlePostResponse, rsp=" + JSON.stringify( rsp ) );

    if ( rsp.messages.length )
    {
      showMessages( rsp.messages );
    }
    else
    {
      showOptions( rsp.columns );
    }
  }

  function showOptions( columns )
  {
    // Hide file chooser
    $( "#fileBlock" ).css( "display", "none" );

    // Show Analysis Options form
    $( "#optionsForm" ).css( "display", "block" );
    $( "#uploadName" ).val( $( "#uploadFilename" ).val() );
    $( "#uploadNameText" ).text( $( "#uploadFilename" ).val() );

    // Create time pickers
    $( '#startTime' ).wickedpicker( { now: "05:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
    $( '#endTime' ).wickedpicker( { now: "20:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

    // Initialize options
    $( "#summary" ).prop( "checked", true );
    onChangeFormat();
  }

  // Handle change of Report Format radio buttons
  function onChangeFormat()
  {
    var bDisable = ! $( "#summary" ).prop( "checked" );

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
    var summaryChecked = $( "#summary" ).prop( "checked" );
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

  function onSubmitOptions()
  {
    var valid = validateFormInput( validateOptionsInput );

    if ( valid )
    {
      $( "body" ).css( "cursor", "progress" );
      setTimeout( isItReadyYet, 1000 );
    }

    return valid;
  }

  // Validate input supplied in form
  function validateFormInput( validateWhat )
  {
    clearMessages();
    var messages = validateWhat();
    showMessages( messages );
    return ( messages.length == 0 );
  }

  function validateFileInput()
  {
      var messages = [];

    // Check Metasys File
    if ( $( "#metasysFile" ).val() == "" )
    {
      messages.push( "<?=METASYS_FILE?> is required" );
      $( "#uploadFilename" ).parent().addClass( "has-error" );
    }

    return messages;
}

  function validateOptionsInput()
  {
      var messages = [];

    // Check time inputs
    if ( ! $( "#startTime" ).prop( "disabled" )
        &&  ! $( "#endTime" ).prop( "disabled" )
        && ( $( "#startTime" ).val() == $( "#endTime" ).val() ) )
    {
      messages.push( "<?=START_TIME?> and <?=$labels["endTime"]?> must differ" );
      $( "#startTime" ).parent().addClass( "has-error" );
      $( "#endTime" ).parent().addClass( "has-error" );
    }

    return messages;
  }

  function clearMessages()
  {
    $( ".has-error" ).removeClass( "has-error" );
    $( "#messages" ).css( "display", "none" );
    $( "#messageList" ).html( "" );
  }

  function showMessages( messages )
  {
    if ( messages.length > 0 )
    {
      for ( var index in messages )
      {
        $( "#messageList" ).append( '<li>' + messages[index] + '</li>' );
      }
      $( "#messages" ).css( "display", "block" );
    }
  }

  function isItReadyYet()
  {
    $.ajax(
      "mda/parse_ready.php?timestamp=" + $( "#timestamp" ).val(),
      {
        type: "GET",
        cache: false,
        dataType: "json"
      }
    )
    .done( handlePollResponse )
    .fail( ajaxFail );
  }

  function handlePollResponse( rsp, sStatus, tJqXhr )
  {
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

  function ajaxFail( tJqXhr, sStatus, sErrorThrown )
  {
    $( "body" ).css( "cursor", "default" );
    showMessages( ["AJAX error: Status=<" + sStatus +"> Error=<" + sErrorThrown + ">"] );
  }
</script>

<!-- Modal dialog for Metasys File help -->
<div class="modal fade" id="helpMetasysFile" tabindex="-1" role="dialog" aria-labelledby="helpMetasysFileLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpMetasysFileLabel"><?=METASYS_FILE?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dd>
            Enter a .csv file exported from Metasys.
          </dd>
        </dl>
       <dl>
          <dt>
            How <?=METASYS_FILE?> is used:
          </dt>
          <dd>
            <ol>
              <li>
                You click <i>OK</i>.
              </li>
              <li>
                Browser uploads <?=METASYS_FILE?> to server.
              </li>
              <li>
                <?=METASYS_DATA_ANALYSIS?> script analyzes contents of <?=METASYS_FILE?>.
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
        <h4 class="modal-title" id="helpOptionsLabel"><?=ANALYSIS_OPTIONS?></h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dt>
            <?=REPORT_FORMAT?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                <?=$labels["summary"]?>
              </dt>
              <dd>
                Aggregates results in specified time periods.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=$labels["detailed"]?>
              </dt>
              <dd>
                Includes a result for each distinct timestamp found in Metasys File.
              </dd>
            </dl>
          </dd>
        </dl>
        <dl>
          <dt>
            <?=$labels["period"]?>
          </dt>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                <?=$labels["fullday"]?>
              </dt>
              <dd>
                Aggregates results in 24-hour periods beginning with <i><?=START_TIME?></i>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                <?=$labels["partday"]?>
              </dt>
              <dd>
                Aggregates results in periods from <i><?=START_TIME?></i> to <i><?=$labels["endTime"]?></i>.
              </dd>
              <dd>
                <ul>
                  <li>
                    If <i><?=START_TIME?></i> is greater than <i><?=$labels["endTime"]?></i>, the time periods cross midnight.
                  </li>
                  <li>
                    <i><?=START_TIME?></i> and <i><?=$labels["endTime"]?></i> must have different values.
                  </li>
                </ul>
              </dd>
            </dl>
          </dd>
        </dl>
        <dl>
          <dt>
            <?=START_TIME?>
          </dt>
          <dt>
            <?=$labels["endTime"]?>
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
            <?=$labels["cost"]?>
          </dt>
          <dd>
            <ul style="list-style-type:none" >
              <li>
                Cost of electricity in dollars.
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
    <p class="h3"><?=METASYS_DATA_ANALYSIS?></p>
  </div>


  <div id="fileBlock" >
    <!-- Metasys File chooser -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <span class="panel-title">
              <div class="row">
                <div class="col-xs-8 col-sm-10 col-md-11 col-lg-11">
                  <?=METASYS_FILE?>
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

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button class="btn btn-primary" onclick="onSubmitFile()" >OK</button>
          <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
        </div>
      </div>
    </div>
  </div>

  <form id="optionsForm" role="form" onsubmit="return onSubmitOptions();" action="mda/parse_run.php" method="post" enctype="multipart/form-data" >

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="alert alert-info well-sm" >
          <div class="row">
            <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2">
              <label class="control-label text-right" ><?=METASYS_FILE?></label>
            </div>
            <div class="col-xs-9 col-sm-10 col-md-10 col-lg-10">
              <span id="uploadNameText"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden inputs -->
    <input type="hidden" id="timestamp" name="timestamp" >
    <input type="hidden" id="uploadName" name="uploadName" >

    <!-- Options -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="panel panel-default">
          <div class="panel-heading">
            <span class="panel-title">
              <div class="row">
                <div class="col-xs-8 col-sm-10 col-md-11 col-lg-11">
                  <?=ANALYSIS_OPTIONS?>
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

            <div class="form-group">
              <label class="control-label" for="format" ><?=REPORT_FORMAT?></label>
              <div>
                <label class="radio-inline" >
                  <input type="radio" name="format" id="summary" value="<?=$labels["summary"]?>" onchange="onChangeFormat()" >
                  <?=$labels["summary"]?>
                </label>
                <label class="radio-inline" >
                  <input type="radio" name="format" id="detailed" value="<?=$labels["detailed"]?>" onchange="onChangeFormat()" >
                  <?=$labels["detailed"]?>
                </label>
              </div>
            </div>

            <br/>
            <div class="form-group" >
              <label class="control-label" for="startTime" ><?=START_TIME?></label>
              <input type="text" id="startTime" name="startTime" class="form-control timepicker" style="border-radius:4px" readonly >
            </div>

            <br/>
            <div class="form-group">
              <label class="control-label" for="period" ><?=$labels["period"]?></label>
              <div>
                <label class="radio-inline" >
                  <input type="radio" name="period" id="fullday" value="<?=$labels["fullday"]?>" onchange="onChangePeriod()" >
                  <?=$labels["fullday"]?>
                </label>
                <label class="radio-inline" >
                  <input type="radio" name="period" id="partday" value="<?=$labels["partday"]?>" onchange="onChangePeriod()" >
                  <?=$labels["partday"]?>
                </label>
              </div>
            </div>

            <div class="form-group" >
              <label class="control-label" for="endTime" ><?=$labels["endTime"]?></label>
              <input type="text" id="endTime" name="endTime" class="form-control timepicker" style="border-radius:4px" readonly >
            </div>

            <br/>
            <div class="form-group">
              <label for="cost"><?=$labels["cost"]?></label>
              <div class="input-group">
                <span class="input-group-addon" id="dollars">$</span>
                <input type="number" value="0.16" min="0.01" step="0.01" class="form-control" id="cost" name="cost" />
              </div>
            </div>

          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button type="submit" form="optionsForm" class="btn btn-primary" >OK</button>
          <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
        </div>
      </div>
    </div>

  </form>

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

