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
#columnPicker
{
  -webkit-column-width: 270px; /* Chrome, Safari, Opera */
  -moz-column-width: 270px; /* Firefox */
  column-width: 270px;
}
@media( max-width: 991px )
{
  .padBottomSmall
  {
    padding-bottom: 10px;
  }
}

</style>

<script>
  $( document ).ready( initFileView );

  function initFileView()
  {
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
      // Disable upload button
      $( "#uploadButton" ).prop( "disabled", true );

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
      .fail( handlePostError );
    }
  }

  function handlePostResponse( rsp, sStatus, tJqXhr )
  {
    console.log( "handlePostResponse, rsp=" + JSON.stringify( rsp ) );
    // Restore cursor and button states
    $( "body" ).css( "cursor", "default" );
    $( "#uploadButton" ).prop( "disabled", false );

    if ( rsp.messages.length )
    {
       showMessages( rsp.messages );
    }
    else
    {
      showOptionsView( rsp.columns );
    }
  }

  function handlePostError( tJqXhr, sStatus, sErrorThrown )
  {
    // Restore cursor and button states
    $( "body" ).css( "cursor", "default" );
    $( "#uploadButton" ).prop( "disabled", false );

    ajaxFail( tJqXhr, sStatus, sErrorThrown );
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
    $( "#optionsTabs a[href='#analysisOptionsTab']" ).tab( "show" );

    // Create time pickers
    $( '#startTime' ).wickedpicker( { now: "05:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
    $( '#endTime' ).wickedpicker( { now: "20:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

    // Initialize Analysis Options
    $( "#summary" ).prop( "checked", true );
    onChangeFormat();

    // Initialize Columns
    $( "#checkDefault" ).click( checkDefault );
    $( "#checkAll" ).click( checkAll );
    $( "#uncheckAll" ).click( uncheckAll );
    $( "#checkComplement" ).click( checkComplement );
    for ( var i in columns )
    {
      $( "#columnPicker" ).append( makeColumnPickerRow( i, columns[i] ) );
    }
    checkDefault();

    // Set column-related handlers
    $( "#columnPicker input[type=checkbox]" ).change( onColumnSelChange );
    $( window ).on( "resize", setColumnButtonSize );

    // Set handlers to update Help button targets
    $( "#optionsTabs a[href='#analysisOptionsTab']" ).on( "shown.bs.tab", setOptionsHelp );
    $( "#optionsTabs a[href='#columnsTab']" ).on( "shown.bs.tab", setColumnsHelp );
    setOptionsHelp();
  }

  // Handle change of Report Format radio buttons
  function onChangeFormat()
  {
    var bDisable = ! $( "#summary" ).prop( "checked" );

    var period = $( "input[type=radio][name=period]" );
    period.parent().css( "color", bDisable ? "lightgray" : "" );
    period.parent().css( "cursor", bDisable ? "default" : "pointer" );
    period.prop( "disabled", bDisable );
    $( "#fullday" ).prop( "checked", ! bDisable );
    $( "#partday" ).prop( "checked", false );
    $( "label[for=period]" ).css( "color", bDisable ? "lightgray" : "" );

    disableTimeInput( "startTime", bDisable );

    onChangePeriod();

    $( "#cost" ).prop( "disabled", bDisable );
    $( "#cost" ).val( bDisable ? "" : "0.16" );
    $( "label[for=cost]" ).css( "color", bDisable ? "lightgray" : "" );
    $( "#dollars" ).css( "color", bDisable ? "lightgray" : "" );
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
    $( "label[for='" + id + "']" ).css( "color", bDisable ? "lightgray" : "" );
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

  function checkDefault()
  {
    // List of substrings identifying default checkbox selection, in order of preference
    var substrings =
    [
      "energy",
      "kwh",
      "kvrh",
      "kvarh",
      "kw",
      "kvr",
      "kvar"
    ];

    // Concatenate all checkbox labels into one lowercase string
    var labels = $( "#columnPicker label" );
    var lcCat = labels.text().toLowerCase();

    // Determine whether labels contain any of the substrings
    var found = false;
    for ( var index = 0; ( index < substrings.length ) && ! found; index ++ )
    {
      found = lcCat.indexOf( substrings[index] ) != -1;
    }

    // Start with all checkboxes unchecked
    uncheckAll();

    // Check the checkboxes that comprise the default selection
    if ( found )
    {
      // Determine which substring was chosen as the default
      var substring = substrings[index-1];

      // Select all labels with the substring
      for ( var lbl = 0; lbl < labels.length; lbl ++ )
      {
        var label = $( labels[lbl] );
        if ( label.text().toLowerCase().indexOf( substring )  != -1 )
        {
          label.find( "input" ).prop( "checked", true );
          addEditorColumn( lbl );
        }
      }
    }
    else
    {
      $( labels[0] ).find( "input" ).prop( "checked", true );
      addEditorColumn( 0 );
    }
  }

  function checkAll()
  {
    var all = $( "#columnPicker input[type=checkbox]" );
    all.prop( "checked", true );
    for ( var checkboxIndex = 0; checkboxIndex < all.length; checkboxIndex ++ )
    {
      addEditorColumn( checkboxIndex );
    }
  }

  function uncheckAll()
  {
    var all = $( "#columnPicker input[type=checkbox]" );
    all.prop( "checked", false );
    for ( var checkboxIndex = 0; checkboxIndex < all.length; checkboxIndex ++ )
    {
      removeEditorColumn( checkboxIndex );
    }
  }

  function checkComplement()
  {
    var checked = $( "#columnPicker input[type=checkbox]:checked" );
    var unchecked =  $( "#columnPicker input[type=checkbox]:not(:checked)" );

    // Reverse checkbox settings
    checked.prop( "checked", false );
    unchecked.prop( "checked", true );

    // Remove newly unchecked items from editor
    for ( var i = 0; i < checked.length; i ++ )
    {
      var checkbox = $( checked[i] );
      var checkboxIndex = checkbox.closest( "li" ).index();
      removeEditorColumn( checkboxIndex );
    }

    // Add newly checked items from editor
    for ( var i = 0; i < unchecked.length; i ++ )
    {
      var checkbox = $( unchecked[i] );
      var checkboxIndex = checkbox.closest( "li" ).index();
      addEditorColumn( checkboxIndex );
    }
  }

  function makeColumnPickerRow( index, colName )
  {
    return '<li><label class="checkbox checkbox-inline"><input type="checkbox" name="columns-' + index + '" value="' + encodeURI( colName ) + '">' + colName + '</label></li>';
  }

  function onColumnSelChange()
  {
    var checkbox = $( event.target );
    console.log( "change at " + checkbox.parent().text() );

    var checkboxIndex = checkbox.closest( "li" ).index();
    console.log( "index of checkbox=" + checkboxIndex );
    if ( checkbox.prop( "checked" ) )
    {
      addEditorColumn( checkboxIndex );
    }
    else
    {
      removeEditorColumn( checkboxIndex );
    }
  }

  function addEditorColumn( checkboxIndex )
  {
    var colName = $( "#columnPicker li:nth-child(" + (  checkboxIndex + 1 ) + ") label" ).text();

    var column =
      '<a class="list-group-item ' + makeCheckboxIndexClass( checkboxIndex ) +'" >'
      +
        '<div class="row">'
      +
          '<div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">'
      +
            '<h5 class="list-group-item-text">' + colName + '</h5>'
      +
          '</div>'
      +
          '<div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">'
      +
            '<input type="text" class="form-control" placeholder="Nickname" >'
      +
          '</div>'
      +
          '<div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">'
      +
            '<button type="button" class="up btn btn-default" onclick="moveColumnUp(event)"><span class="glyphicon glyphicon-menu-up"></span></button>'
      +
            '<button type="button" class="dn btn btn-default" onclick="moveColumnDown(event)"><span class="glyphicon glyphicon-menu-down"></span></button>'
      +
          '</div>'
      +
        '</div>'
      +
      '</a>'
      ;

    $( "#columnEditor" ).append( column );

    setColumnButtonSize();
  }

  function removeEditorColumn( checkboxIndex )
  {
    console.log( "======> in removeEditorColumn(), checkboxIndex=" + checkboxIndex );

    $( "#columnEditor ." + makeCheckboxIndexClass( checkboxIndex ) ).remove();
  }

  function makeCheckboxIndexClass( checkboxIndex )
  {
    return "checkboxIndex-" + checkboxIndex;
  }

  function moveColumnUp( event )
  {
    var item = $( event.target ).closest( "a" );
    item.prev().before( item );
  }

  function moveColumnDown( event )
  {
    var item = $( event.target ).closest( "a" );
    item.next().after( item );
  }

  function setColumnButtonSize()
  {
    if ( $( window ).width() < 768 )
    {
      $( "#columnEditor button" ).addClass( "btn-xs" );
      $( "#columnEditor button" ).removeClass( "btn-sm" );
    }
    else
    {
      $( "#columnEditor button" ).addClass( "btn-sm" );
      $( "#columnEditor button" ).removeClass( "btn-xs" );
    }
  }

  function onSubmitOptions()
  {
    var valid = validateFormInput( validateOptionsInput );

    if ( valid )
    {
      $( "#analyzeButton" ).prop( "disabled", true );
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
      $( "#uploadFilename" ).parent().parent().addClass( "has-error" );
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

    // Check column selections
    if ( $( "#columnPicker input[type=checkbox]:checked" ).length == 0 )
    {
      messages.push( "You must select at least one column" );
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
    .fail( handlePollError );
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
      // Restore cursor and button states
      $( "body" ).css( "cursor", "default" );
      $( "#analyzeButton" ).prop( "disabled", false );

      // Render results
      window.location.assign( "mda/parse_done.php?timestamp=" + $( "#timestamp" ).val()  );
    }
  }

  function handlePollError( tJqXhr, sStatus, sErrorThrown )
  {
    // Restore cursor and button states
    $( "body" ).css( "cursor", "default" );
    $( "#analyzeButton" ).prop( "disabled", false );

    ajaxFail( tJqXhr, sStatus, sErrorThrown );
  }

  function ajaxFail( tJqXhr, sStatus, sErrorThrown )
  {
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

  <?php // include "prototype.php";?>

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
          <button id="uploadButton" class="btn btn-primary" onclick="onSubmitFile()" >Upload</button>
          <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
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
      <li><a data-toggle="tab" href="#analysisOptionsTab"><?=ANALYSIS_OPTIONS?></a></li>
      <li id="columnsTabItem" ><a id="columnsTabLink" data-toggle="tab" href="#columnsTab"><?=POINTS_OF_INTEREST?></a></li>
    </ul>

    <div class="tab-content">

      <!-- Analysis Options -->
      <div id="analysisOptionsTab" class="tab-pane fade">
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
      <div id="columnsTab" class="tab-pane fade">
        <div class="row" style="padding-top:20px; padding-bottom:20px;">
          <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
            <div class="panel panel-default">
              <div class="panel-heading">
                Available <?=POINTS_OF_INTEREST?>
              </div>
              <div class="panel-body">
                <div style="padding-bottom: 15px;">
                  <button type="button" id="checkDefault" class="btn btn-default btn-xs" >Check Default</button>
                  <button type="button" id="checkAll" class="btn btn-default btn-xs" >Check All</button>
                  <button type="button" id="uncheckAll" class="btn btn-default btn-xs" >Uncheck All</button>
                  <button type="button" id="checkComplement" class="btn btn-default btn-xs" >Check Complement</button>
                </div>
                <ul id="columnPicker" class="list-unstyled" >
                </ul>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    Selected <?=POINTS_OF_INTEREST?>
                  </div>
                  <div class="panel-body">
                    <div class="list-group" id="columnEditor">
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Form buttons -->
    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button id="analyzeButton" type="submit" form="optionsForm" class="btn btn-primary" >Analyze</button>
          <button type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
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

