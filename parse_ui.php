<link rel="stylesheet" href="../common/wickedpicker/dist/wickedpicker.min.css">
<script type="text/javascript" src="../common/wickedpicker/dist/wickedpicker.unmin.js"></script>

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
      // Initialize the file chooser
      $( "#metasysFile" ).val( "" );
      $( "#uploadFilename" ).val( "" );

      // Create time pickers
      $( '#startTime' ).wickedpicker( { now: "00:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
      $( '#endTime' ).wickedpicker( { now: "00:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

      // Initialize options
      $( "#summarize" ).prop( "checked", false);
      onChangeSummarize();
    }
  );

  // Handle change of Summarize checkbox
  function onChangeSummarize()
  {
    var bDisable = ! $( "#summarize" ).prop( "checked" );

    $( "#daily" ).parent().css( "color", bDisable ? "lightgray" : "black" );
    $( "#daily" ).prop( "disabled", bDisable );
    $( "#daily" ).prop( "checked", false);

    disableTimeInput( "startTime", bDisable );

    onChangeDaily();
  }

  // Handle change of Summarize checkbox
  function onChangeDaily()
  {
    sumChk = $( "#summarize" ).prop( "checked" );
    dlyChk = $( "#daily" ).prop( "checked" );
    var bDisable = ! sumChk || ( sumChk && dlyChk );
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
      messages.push( "Metasys File is required" );
      $( "#uploadFilename" ).parent().addClass( "has-error" );
    }

    // Check time inputs
    if ( ! $( "#startTime" ).prop( "disabled" )
        &&  ! $( "#startTime" ).prop( "disabled" )
        && ( $( "#startTime" ).val() == $( "#endTime" ).val() ) )
    {
      messages.push( "Start Time and End Time must differ" );
      $( "#startTime" ).parent().addClass( "has-error" );
      $( "#endTime" ).parent().addClass( "has-error" );
    }

    // Update visual feedback
    if ( messages.length == 0 )
    {
      setTimeout( pollResults, 1000 );
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

  function pollResults()
  {
    $.ajax(
      "poll.php",
      {
        type: "GET",
        cache: false,
        dataType: "text",
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
      setTimeout( pollResults, 1000 );
    }
    else
    {
      // Load results into output block
      $( "#paramFile" ).text( $( "#uploadFilename" ).val() );

      if ( $( "#summarize" ).prop( "checked" ) )
      {
        $( "#paramStartTime" ).text( $( "#startTime" ).val() );
        var daily = $( "#daily" ).prop( "checked" );
        $( "#paramEndTimeTitle" ).text( daily ? "Full Day" : "End Time" );
        $( "#paramEndTime" ).text( daily ? "" : $( "#endTime" ).val() );
      }
      else
      {
        $( "#paramStartTimeTitle" ).css( "display", "none" );
        $( "#paramStartTime" ).css( "display", "none" );
        $( "#paramEndTimeTitle" ).css( "display", "none" );
        $( "#paramEndTime" ).css( "display", "none" );
      }

      $( "#results" ).html( rsp );

      // Hide input block and show output block
      $( "#input" ).css( "display", "none" );
      $( "#output" ).css( "display", "block" );
    }
  }

  function ajaxError( tJqXhr, sStatus, sErrorThrown )
  {
    console.log( "AJAX error:\n  Status=<" + sStatus +">\n  Error=<" + sErrorThrown + ">" );
    window.location.assign( "../error/404.html" );
  }

  function ajaxComplete( tJqXhr, sStatus )
  {
    console.log( "AJAX complete:\n  Status=<" + sStatus + ">" );
  }

</script>

<!-- Modal dialog for Metasys File help -->
<div class="modal fade" id="helpMetasysFile" tabindex="-1" role="dialog" aria-labelledby="helpMetasysFileLabel">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="helpMetasysFileLabel">Metasys File</h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dd>
            Enter a .csv file exported from Metasys.
          </dd>
        </dl>
       <dl>
          <dt>
            How Metasys File is used:
          </dt>
          <dd>
            <ol>
              <li>
                You click <i>OK</i>.
              </li>
              <li>
                Browser uploads Metasys File to server.
              </li>
              <li>
                Metasys Data Analysis script analyzes contents of Metasys File.
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
        <h4 class="modal-title" id="helpOptionsLabel">Analysis Options</h4>
      </div>
      <div class="modal-body bg-info">
        <dl>
          <dt>
            Summarize
          </dt>
          <dd>
            Aggregates results in specified periods.
          </dd>
        </dl>
        <dl>
          <dt>
            Full Day
          </dt>
          <dd>
            Controls how periods are determined.
          </dd>
        </dl>
        <dl class="padLeftSmall">
          <dd>
            <dl class="dl-horizontal" >
              <dt>
                Checked
              </dt>
              <dd>
                Aggregates results in 24-hour periods beginning with <i>Start Time</i>.
              </dd>
            </dl>
            <dl class="dl-horizontal" >
              <dt>
                Unchecked
              </dt>
              <dd>
                Aggregates results in periods from <i>Start Time</i> to <i>End Time</i>.
              </dd>
              <dd>
                <ul>
                  <li>
                    If <i>Start Time</i> is greater than <i>End Time</i>, the periods cross midnight.
                  </li>
                  <li>
                    <i>Start Time</i> and <i>End Time</i> must have different values.
                  </li>
                </ul>
              </dd>
            </dl>
          </dd>
        </dl>
        </dl>
        <dl>
          <dt>
            Start Time
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
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="container">
  <p class="h3">Metasys Data Analysis</p>
  <br/>

  <div id="input">
    <form id="uploadForm" role="form" onsubmit="return validateFormInput();" action="parse_run.php" method="post" enctype="multipart/form-data" >

      <!-- Metasys File chooser -->
      <div class="row">
        <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
          <div class="panel panel-default">
            <div class="panel-heading">
              <span class="panel-title">
                <div class="row">
                  <div class="col-xs-8 col-sm-10 col-md-11 col-lg-11">
                    Metasys File
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
                    Analysis Options
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
                  <label><input type="checkbox" id="summarize" onchange="onChangeSummarize()" >Summarize</label>
                </div>
              </div>

              <div class="form-group" >
                <label class="control-label" for="startTime" >Start Time</label>
                <input type="text" id="startTime" name="startTime" class="form-control timepicker" style="border-radius:4px" readonly >
              </div>

              <br/>
              <div class="form-group" >
                <div class="checkbox">
                  <label><input type="checkbox" id="daily" onchange="onChangeDaily()" >Full Day</label>
                </div>
              </div>

              <div class="form-group" >
                <label class="control-label" for="endTime" >End Time</label>
                <input type="text" id="endTime" name="endTime" class="form-control timepicker" style="border-radius:4px" readonly >
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

  <div id="output" style="display:none">

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div id="params" class="well">
          <dl class="dl-horizontal" >
            <dt>
              Metasys File
            </dt>
            <dd id="paramFile">
            </dd>
            <dt id="paramStartTimeTitle">
              Start Time
            </dt>
            <dd id="paramStartTime">
            </dd>
            <dt id="paramEndTimeTitle">
            </dt>
            <dd id="paramEndTime">
            </dd>
          </dl>
        </div>

        <div id="results" class="well">
        </div>
      </div>
    </div>

    <div class="container">
      <div style="text-align:center;" >
        <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Close</button>
      </div>
    </div>
  </div>

</div>
