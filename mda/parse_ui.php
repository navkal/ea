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
    $( "#timestamp" ).val( Date.now().toString( 36 ) );

    // Initialize file source radio buttons
    $( "#preload" ).prop( "checked", true );
    onChangeFileSource();

    // Initialize the file preload chooser
    makePreloadPicker();

    // Initialize the file upload chooser
    $( "#metasysFile" ).val( "" );
    $( "#uploadFilename" ).val( "" );

    // Hide Analysis Options form
    $( "#optionsForm" ).css( "display", "none" );
  }

  function onChangeFileSource()
  {
    var bUpload = $( "#upload" ).prop( "checked" );
    $( "#preloadBlock" ).css( "display", bUpload ? "none" : "block" );
    $( "#uploadBlock" ).css( "display", bUpload ? "block" : "none" );
  }

  function makePreloadPicker()
  {
    var picker = $( "#preloadPicker" );
    var preloadedFiles = <?=json_encode( array_slice( scandir( $_SERVER["DOCUMENT_ROOT"]."/mda/input" ), 2 ) )?>;

    for ( var i = 0; i < preloadedFiles.length; i ++ )
    {
      var preloadedFile = preloadedFiles[i];
      var option =
        '<option value="' + preloadedFile + '">'
      +
          preloadedFile
      +
        '</option>';

      picker.append( option );
    }
  }

  function onSubmitFile()
  {
    if ( validateFormInput( validateFileInput ) )
    {
      // Disable submit button
      $( "#submitFileButton" ).prop( "disabled", true );
      $( "#metasysFileFields" ).prop( "disabled", true );

      // Set wait cursor
      $( "body" ).css( "cursor", "progress" );

      // Post file to server
      var postData = new FormData();
      if ( $( "#upload" ).prop( "checked" ) )
      {
        postData.append( "metasysFile", $( "#metasysFile" ).prop( "files" )[0] );
      }
      else
      {
        postData.append( "metasysFilename", $( "#preloadPicker" ).val() );
      }

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
    // Restore cursor and button states
    $( "body" ).css( "cursor", "default" );
    $( "#submitFileButton" ).prop( "disabled", false );
    $( "#metasysFileFields" ).prop( "disabled", false );

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
    $( "#submitFileButton" ).prop( "disabled", false );
    $( "#metasysFileFields" ).prop( "disabled", false );

    ajaxFail( tJqXhr, sStatus, sErrorThrown );
  }

  function showOptionsView( columns )
  {
    // Hide file chooser
    $( "#fileBlock" ).css( "display", "none" );

    // Show Analysis Options form
    $( "#optionsForm" ).css( "display", "block" );
    var inputFilename = $( "#upload" ).prop( "checked" ) ? $( "#uploadFilename" ).val() : $( "#preloadPicker" ).val();
    $( "#inputName" ).val( inputFilename );
    $( "#inputNameText" ).text( inputFilename );

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
    $( "#checkSearch" ).keydown( preventDefault );
    $( "#checkSearch" ).keyup( checkSearch );
    for ( var i in columns )
    {
      $( "#columnPicker" ).append( makeColumnPickerRow( columns[i] ) );
    }
    checkDefault( { target: "fake" } );


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

  function checkDefault( event )
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

    // Check the checkboxes that comprise the default selection
    if ( found )
    {
      checkAllContaining( event, substrings[index-1] );
    }
    else
    {
      checkFirst( event );
    }
  }

  function preventDefault( event )
  {
    if ( event.keyCode == "13" )
    {
      event.preventDefault();
    }
  }

  function checkSearch( event )
  {
    if ( event.keyCode != "9" /* tab */ )
    {
      checkAllContaining( event, $( "#checkSearch" ).val() );
    }
  }

  function checkAllContaining( event, substring )
  {
    uncheckAll( event );

    if ( substring.length > 0 )
    {
      var labels = $( "#columnPicker label" );

      // Select all labels with the substring
      for ( var lbl = 0; lbl < labels.length; lbl ++ )
      {
        var label = $( labels[lbl] );
        var span = label.find( "span" );
        if ( span.text().toLowerCase().indexOf( substring.toLowerCase() )  != -1 )
        {
          label.find( "input" ).prop( "checked", true );
          addEditorColumn( lbl );
        }
      }
    }
  }

  function checkFirst( event )
  {
    uncheckAll( event );

    var labels = $( "#columnPicker label" );
    $( labels[0] ).find( "input" ).prop( "checked", true );
    addEditorColumn( 0 );
  }

  function checkAll( event )
  {
    uncheckAll( event );

    var all = $( "#columnPicker input[type=checkbox]" );
    all.prop( "checked", true );
    for ( var checkboxIndex = 0; checkboxIndex < all.length; checkboxIndex ++ )
    {
      addEditorColumn( checkboxIndex );
    }
  }

  function uncheckAll( event )
  {
    // Clear the search input
    if ( event.target != $( "#checkSearch" )[0] )
    {
      $( "#checkSearch" ).val( "" );
    }

    var all = $( "#columnPicker input[type=checkbox]" );
    all.prop( "checked", false );
    for ( var checkboxIndex = 0; checkboxIndex < all.length; checkboxIndex ++ )
    {
      removeEditorColumn( checkboxIndex );
    }
  }

  function checkComplement( event )
  {
    // Reverse checkbox settings
    var unchecked =  $( "#columnPicker input[type=checkbox]:not(:checked)" );
    uncheckAll( event );
    unchecked.prop( "checked", true );

    // Add newly checked items to editor
    for ( var i = 0; i < unchecked.length; i ++ )
    {
      var checkbox = $( unchecked[i] );
      var checkboxIndex = checkbox.closest( "li" ).index();
      addEditorColumn( checkboxIndex );
    }
  }

  function makeColumnPickerRow( colName )
  {
    var row =
      '<li>'
      +
        '<label class="checkbox checkbox-inline">'
      +
          '<input type="checkbox" value="' + encodeURI( colName ) + '">'
      +
          '<span>'
      +
            colName
      +
          '</span>'
      +
        '</label>'
      +
      '</li>';

    return row;
  }

  function onColumnSelChange( event )
  {
    $( "#checkSearch" ).val( "" );
    var checkbox = $( event.target );
    var checkboxIndex = checkbox.closest( "li" ).index();

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
    var span = $( "#columnPicker li:nth-child(" + (  checkboxIndex + 1 ) + ") label span" );
    span.addClass( "bg-info" );

    var colName = span.text();

    var column =
      '<a class="list-group-item" checkboxIndex="' + checkboxIndex + '" >'
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
            '<input type="text" class="form-control" maxlength="32" placeholder="Nickname" >'
      +
          '</div>'
      +
          '<div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">'
      +
            '<div class="btn-group" role="group" >'
      +
              '<button type="button" class="up btn btn-default" onclick="moveColumnUp(event)" title="Move Up">'
      +
                '<span class="glyphicon glyphicon-menu-up">'
      +
                '</span>'
      +
              '</button>'
      +
              '<button type="button" class="dn btn btn-default" onclick="moveColumnDown(event)" title="Move Down">'
      +
                '<span class="glyphicon glyphicon-menu-down">'
      +
                '</span>'
      +
              '</button>'
      +
            '</div>'
      +
            '<button type="button" class="close"  onclick="uncheckColumn(event)" title="Remove">'
      +
              '<span aria-hidden="true">'
      +
                '&times;'
      +
              '</span>'
      +
            '</button>'
      +
          '</div>'
      +
        '</div>'
      +
      '</a>'
      ;

    $( "#columnEditor" ).append( column );

    setColumnButtonSize();
    setNicknameTabindex();
  }

  function setNicknameTabindex()
  {
    var tabindexBase = 5000;
    var inputs = $( "#columnEditor input" );

    for ( var i = 0; i < inputs.length; i ++ )
    {
       $( inputs[i] ).attr( "tabindex", tabindexBase + i );
    }

    $( "#analyzeButton" ).attr( "tabindex", tabindexBase + ( i++ ) );
    $( "#optionsCancel" ).attr( "tabindex", tabindexBase + ( i++ ) );
    $( "#multiHelp" ).attr( "tabindex", tabindexBase + ( i++ ) );
  }

  function uncheckColumn( event )
  {
    // Clear the search box
    $( "#checkSearch" ).val( "" );

    // Find the corresponding column picker entry
    var editorColumn = $( $( event.target ).closest( "a" )[0] );
    var checkboxIndex = parseInt( editorColumn.attr( "checkboxIndex" ) ) + 1;
    var li = $( "#columnPicker li:nth-child(" + checkboxIndex + ")" );

    // Uncheck the checkbox
    var checkbox = li.find( "input" );
    checkbox.prop( "checked", false );

    // Remove the highlighting
    var span = li.find( "span" );
    span.removeClass( "bg-info" );

    // Remove the column from the editor
    editorColumn.remove();
  }

  function removeEditorColumn( checkboxIndex )
  {
    var span = $( "#columnPicker li:nth-child(" + (  checkboxIndex + 1 ) + ") label span" );
    span.removeClass( "bg-info" );

    $( "#columnEditor a[checkboxIndex=" + checkboxIndex + "]" ).remove();
  }

  function moveColumnUp( event )
  {
    var item = $( event.target ).closest( "a" );
    item.prev().before( item );
    setNicknameTabindex();
  }

  function moveColumnDown( event )
  {
    var item = $( event.target ).closest( "a" );
    item.next().after( item );
    setNicknameTabindex();
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
    if ( $( "#upload" ).prop( "checked" ) && $( "#metasysFile" ).val() == "" )
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
      messages.push( "One or more <?=POINTS_OF_INTEREST?> are required" );
      $( "#columnPicker input[type=checkbox]" ).parent().parent().addClass( "has-error" );
    }

    // Check nicknames
    var nicknames = $( "#columnEditor input" );
    var foundComma = false;
    for ( var i = 0; i < nicknames.length; i ++ )
    {
      var nickname = $( nicknames[i] );
      var val = nickname.val();

      if ( val.indexOf( "," ) != -1 )
      {
        foundComma = true;
        nickname.parent().addClass( "has-error" );
      }
    }
    if ( foundComma )
    {
      messages.push( "Column nicknames must not contain commas" );
    }
    else
    {
      buildColumnData();
    }

    return messages;
  }

  function buildColumnData()
  {
    var columnData = [];
    var columns = $( "#columnEditor > a > div" );
    for ( var i = 0; i < columns.length; i ++ )
    {
      var column = $( columns[i] );
      var name = column.find( "h5" ).text();
      var nickname = column.find( "input" ).val();

      columnData.push(
        {
          name: name,
          nickname: nickname
        }
      );
    }

    var json = JSON.stringify( columnData );
    $( "#columnData" ).val( json );
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
            <fieldset id="metasysFileFields">

              <div class="form-group">
                <label class="control-label"><?=METASYS_FILE?></label>
                <div>
                  <label class="radio-inline" >
                    <input type="radio" id="preload" name="fileSource" onchange="onChangeFileSource()" >
                    Preloaded
                  </label>
                  <label class="radio-inline" >
                    <input type="radio" id="upload" name="fileSource" onchange="onChangeFileSource()" >
                    Uploaded
                  </label>
                </div>
              </div>

              <div class="form-group" id="preloadBlock" >
                <select id="preloadPicker" class="form-control" >
                </select>
              </div>

              <div class="form-group" id="uploadBlock" >
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

            </fieldset>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button id="submitFileButton" class="btn btn-primary" onclick="onSubmitFile()" >Submit</button>
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
              <span id="inputNameText"></span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Hidden inputs -->
    <input type="hidden" id="timestamp" name="timestamp" >
    <input type="hidden" id="inputName" name="inputName" >

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
                    <label class="radio-inline" >
                      <input type="radio" name="format" id="multiple" value="<?=MULTIPLE?>" onchange="onChangeFormat()" >
                      <?=MULTIPLE?>
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

                <!-- Accelerator buttons -->
                <div class="btn-toolbar" role="toolbar" >
                  <span class="btn-group btn-group-xs" style="padding-right:10px" role="group" >
                    <button type="button" id="checkDefault" class="btn btn-default btn-xs" title="Select Default" >Default</button>
                    <button type="button" id="checkAll" class="btn btn-default btn-xs" title="Select All" >All</button>
                    <button type="button" id="uncheckAll" class="btn btn-default btn-xs" title="Deselect All" >None</button>
                    <button type="button" id="checkComplement" class="btn btn-default btn-xs" title="Select Complement" >Complement</button>
                  </span>
                  <span class="btn-group btn-group-xs" role="group" >
                    <input type="text" id="checkSearch" class="form-control" style="height:22px; padding-top:0px; padding-bottom:0px;" placeholder="Search..." autocomplete="off"  title="Select Matches">
                  </span>
                </div>

                <!-- Checkboxes -->
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
                    <input type="hidden" id="columnData" name="columnData" >
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
          <button id="optionsCancel" type="reset" onclick="window.location.assign( window.location.href );" class="btn btn-default" >Cancel</button>
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

