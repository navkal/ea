<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  require_once "labels.php";
  require_once "parse_help.php";
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
  $( document ).ready( initFileView );

  function initFileView()
  {
    clearMessages();

    // Initialize identifying timestamp
    $( "#timestamp" ).val( Date.now() );

    // Initialize the file chooser
    $( "#metasysFile" ).val( "" );
    $( "#uploadFilename" ).val( "" );

    // Hide Analysis Options form
    $( "#optionsForm" ).css( "display", "none" );
  }

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
      showOptionsView( rsp.columns );
    }
  }

  function showOptionsView( columns )
  {
    // Hide file chooser
    $( "#fileBlock" ).css( "display", "none" );

    // Show Analysis Options form
    $( "#optionsForm" ).css( "display", "block" );
    $( "#uploadName" ).val( $( "#uploadFilename" ).val() );
    $( "#uploadNameText" ).text( $( "#uploadFilename" ).val() );

    // Show first Options tab
    $( "#optionsTabs a[href='#analysisOptions']" ).tab( "show" );

    // Create time pickers
    $( '#startTime' ).wickedpicker( { now: "05:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
    $( '#endTime' ).wickedpicker( { now: "20:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

    // Initialize Analysis Options
    $( "#summary" ).prop( "checked", true );
    onChangeFormat();

    // Initialize Columns
    $( "#columns" ).html( "" );
    for ( var i in columns )
    {
      $( "#columns" ).append( "<li>" + columns[i] +  "</li>" );
    }

    // Set handlers to update Help button targets
    $( "#optionsTabs a[href='#analysisOptions']" ).on( "shown.bs.tab", setOptionsHelp );
    $( "#optionsTabs a[href='#columns']" ).on( "shown.bs.tab", setColumnsHelp );
    setOptionsHelp();
  }

  function showFileView()
  {
    clearMessages();
    $( "#fileBlock" ).css( "display", "block" );
    $( "#optionsForm" ).css( "display", "none" );
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
      messages.push( "<?=START_TIME?> and <?=END_TIME?> must differ" );
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

  function setOptionsHelp()
  {
    $( "#multiHelp" ).attr( "data-target", "#helpOptions" );
  }

  function setColumnsHelp()
  {
    $( "#multiHelp" ).attr( "data-target", "#helpColumns" );
  }
</script>

<div class="container">
  <div class="page-header">
    <p class="h3"><?=METASYS_DATA_ANALYSIS?></p>
  </div>


  <div id="fileBlock" >
    <!-- Metasys File chooser -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div class="panel panel-default">
          <div class="panel-body">
            <div class="form-group" >
              <label class="control-label" for="metasysFile" ><?=METASYS_FILE?></label>
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
          <button class="btn btn-primary" onclick="onSubmitFile()" >Next</button>
          <button type="reset" onclick="initFileView();" class="btn btn-default" >Cancel</button>
          <button type="button" class="btn btn-info" data-toggle="modal" data-target="#helpMetasysFile">Help</button>
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

    <!-- Options tabs -->
    <ul id="optionsTabs" class="nav nav-tabs">
      <li><a data-toggle="tab" href="#analysisOptions"><?=ANALYSIS_OPTIONS?></a></li>
      <li><a data-toggle="tab" href="#columns"><?=COLUMNS?></a></li>
    </ul>

    <div class="tab-content">

      <!-- Analysis Options -->
      <div id="analysisOptions" class="tab-pane fade">
        <div class="row" >
          <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="panel panel-default">
              <div class="panel-body">

                <div class="form-group">
                  <label class="control-label" for="format" ><?=REPORT_FORMAT?></label>
                  <div>
                    <label class="radio-inline" >
                      <input type="radio" name="format" id="summary" value="<?=SUMMARY?>" onchange="onChangeFormat()" >
                      <?=SUMMARY?>
                    </label>
                    <label class="radio-inline" >
                      <input type="radio" name="format" id="detailed" value="<?=DETAILED?>" onchange="onChangeFormat()" >
                      <?=DETAILED?>
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
                  <label class="control-label" for="period" ><?=TIME_PERIOD?></label>
                  <div>
                    <label class="radio-inline" >
                      <input type="radio" name="period" id="fullday" value="<?=FULL_DAY?>" onchange="onChangePeriod()" >
                      <?=FULL_DAY?>
                    </label>
                    <label class="radio-inline" >
                      <input type="radio" name="period" id="partday" value="<?=PARTIAL_DAY?>" onchange="onChangePeriod()" >
                      <?=PARTIAL_DAY?>
                    </label>
                  </div>
                </div>

                <div class="form-group" >
                  <label class="control-label" for="endTime" ><?=END_TIME?></label>
                  <input type="text" id="endTime" name="endTime" class="form-control timepicker" style="border-radius:4px" readonly >
                </div>

                <br/>
                <div class="form-group">
                  <label for="cost"><?=COST_PER_KWH?></label>
                  <div class="input-group">
                    <span class="input-group-addon" id="dollars">$</span>
                    <input type="number" value="0.16" min="0.01" step="0.01" class="form-control" id="cost" name="cost" />
                  </div>
                </div>

              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Columns -->
      <div id="columns" class="tab-pane fade">
        <h3><?=COLUMNS?></h3>
        <ul id="columns">
        </ul>
      </div>

    </div>

    <!-- Form buttons -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button type="submit" form="optionsForm" class="btn btn-primary" >Finish</button>
          <button type="reset" onclick="showFileView();" class="btn btn-default" >Back</button>
          <button id="multiHelp" type="button" class="btn btn-info" data-toggle="modal">Help</button>
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

