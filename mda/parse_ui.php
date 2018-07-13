<!-- Copyright 2018 Energize Apps.  All rights reserved. -->

<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  require_once "labels.php";
  require_once "parse_help.php";

  // Reinitialize session variables
  session_unset();
  $version = time();
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/css/bootstrap-datepicker.min.css" integrity="sha256-JDBcnYeV19J14isGd3EtnsCQK05d8PczJ5+fvEvBJvI=" crossorigin="anonymous" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.8.0/js/bootstrap-datepicker.min.js" integrity="sha256-tW5LzEC7QjhG0CiAvxlseMTs2qJS7u3DRPauDjFJ3zo=" crossorigin="anonymous"></script>
<link rel="stylesheet" href="lib/wickedpicker/dist/wickedpicker.min.css">
<script type="text/javascript" src="lib/wickedpicker/dist/wickedpicker.unmin.js"></script>
<link rel="stylesheet" href="../util/util.css?version=<?=$version?>">
<script type="text/javascript" src="../util/util.js?version=<?=$version?>"></script>

<style>
@media( max-width: 767px )
{
  .padLeftSmall
  {
    padding-left: 20px;
  }
}

@media( max-width: 991px )
{
  .padBottomSmall
  {
    padding-bottom: 10px;
  }
}

[draggable=true]
{
  -khtml-user-drag: element;
  -webkit-user-drag: element;
  -khtml-user-select: none;
  -webkit-user-select: none;
}

.columnHover
{
  color: #555;
  text-decoration: none;
  background-color: #f5f5f5;
}

.notSuitable
{
  color: #A8A8A8;
}

.marked
{
  border: 1px solid darkgray;
}
</style>

<script>
  var SYSTEM_NICKNAMES = {};

  $( document ).ready( initFileView );

  function initFileView()
  {
    // Initialize identifying timestamp
    $( "#timestamp" ).val( Date.now().toString( 36 ) );

    // Show Analyze tab
    $( "#inputFileTabs a[href='#analyzeTab']" ).tab( "show" );

    // Set handlers to initialize file chooser views
    $( "#inputFileTabs a[href='#analyzeTab']" ).on( "shown.bs.tab", onShowAnalyzeTab );
    $( "#inputFileTabs a[href='#plotTab']" ).on( "shown.bs.tab", onShowPlotTab );

    // Initialize dropdowns for preloaded input and sample results files
    makeFilePicker( "preloadPicker", <?=json_encode( array_slice( scandir( $_SERVER["DOCUMENT_ROOT"]."/mda/input" ), 2 ) )?> );
    makeFilePicker( "samplePicker", <?=json_encode( array_slice( scandir( $_SERVER["DOCUMENT_ROOT"]."/mda/sample" ), 2 ) )?> );

    onShowAnalyzeTab();

    // Hide Analysis Options form
    $( "#optionsForm" ).css( "display", "none" );
  }

  function onShowAnalyzeTab()
  {
    $( "#preload" ).prop( "checked", true );
    onChangeFileSource();
  }

  function onShowPlotTab()
  {
    $( "#sample" ).prop( "checked", true );
    onChangeFileSource();
  }

  function onChangeFileSource()
  {
    clearMessages();

    // Initialize dropdown selections
    var filePickers = JSON.parse( ( typeof Storage === "undefined" ) ? "{}" : ( localStorage.getItem( "filePickers" ) || "{}" ) );
    $( "#preloadPicker" ).val( filePickers["preloadPicker"] || $( "#preloadPicker option:first" ).val() );
    $( "#samplePicker" ).val( filePickers["samplePicker"] || $( "#samplePicker option:first" ).val() );

    // Initialize file upload choosers
    $( "#metasysFile" ).val( "" );
    $( "#resultsFile" ).val( "" );
    $( "#uploadFilename" ).val( "" );
    $( "#resultsFilename" ).val( "" );

    // Show appropriate file chooser block
    $( "#preloadBlock" ).css( "display", $( "#preload" ).prop( "checked" ) ? "block" : "none" );
    $( "#uploadBlock" ).css( "display", $( "#upload" ).prop( "checked" ) ? "block" : "none" );
    $( "#sampleBlock" ).css( "display", $( "#sample" ).prop( "checked" ) ? "block" : "none" );
    $( "#resultsBlock" ).css( "display", $( "#results" ).prop( "checked" ) ? "block" : "none" );
  }

  function makeFilePicker( pickerId, pickerFiles )
  {
    var picker = $( "#" + pickerId );

    for ( var i = 0; i < pickerFiles.length; i ++ )
    {
      var pickerFile = pickerFiles[i];
      var option =
        '<option value="' + pickerFile + '">'
      +
          pickerFile
      +
        '</option>';

      picker.append( option );
    }

    $( "#" + pickerId ).on( "change", onChangeFilePicker );
  }

  function onChangeFilePicker( event )
  {
    if ( typeof Storage !== "undefined" )
    {
      var filePickers = JSON.parse( localStorage.getItem( "filePickers" ) || "{}" );
      var picker = $( event.target );
      filePickers[picker.attr( "id" )] = picker.val();
      localStorage.setItem( "filePickers", JSON.stringify( filePickers ) );
    }
  }

  function onSubmitFile()
  {
    if ( validateFormInput( validateFileInput ) )
    {
      // Disable submit button
      $( "#submitFileButton" ).prop( "disabled", true );
      $( "#inputFileFields" ).prop( "disabled", true );
      $( "#inputFileTabs a" ).prop( "disabled", true );

      // Set wait cursor
      $( "body" ).css( "cursor", "progress" );

      // Post file to server
      var postData = new FormData();
      if ( $( "#upload" ).prop( "checked" ) )
      {
        postData.append( "metasysFile", $( "#metasysFile" ).prop( "files" )[0] );
      }
      else if ( $( "#results" ).prop( "checked" ) )
      {
        postData.append( "resultsFile", $( "#resultsFile" ).prop( "files" )[0] );
      }
      else if ( $( "#sample" ).prop( "checked" ) )
      {
        postData.append( "sampleFilename", $( "#samplePicker" ).val() );
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
    $( "#inputFileFields" ).prop( "disabled", false );
    $( "#inputFileTabs a" ).prop( "disabled", false );

    if ( rsp.messages.length )
    {
       showMessages( rsp.messages );

      // Clean up temp files
      startCleanup( $( "#timestamp" ).val(), "mda/", function(){} );
    }
    else if ( rsp.redirect )
    {
      window.location.assign( rsp.redirect  );
    }
    else
    {
      SYSTEM_NICKNAMES = rsp.nicknames;
      showOptionsView( rsp.dates, rsp.columns );
    }
  }

  function handlePostError( tJqXhr, sStatus, sErrorThrown )
  {
    // Restore cursor and button states
    $( "body" ).css( "cursor", "default" );
    $( "#submitFileButton" ).prop( "disabled", false );
    $( "#inputFileFields" ).prop( "disabled", false );
    $( "#inputFileTabs a" ).prop( "disabled", false );

    showMessages( ["AJAX error: Status=<" + sStatus +"> Error=<" + sErrorThrown + ">"] );

    // Clean up temp files
    startCleanup( $( "#timestamp" ).val(), "mda/", function(){} );
  }

  function showOptionsView( dates, columns )
  {
    // Hide Help modal dialog
    $( "#helpInputFile" ).modal( "hide" );

    // Hide file chooser
    $( "#fileBlock" ).css( "display", "none" );

    // Show Analysis Options form
    $( "#optionsForm" ).css( "display", "block" );
    var inputFilename = $( "#upload" ).prop( "checked" ) ? $( "#uploadFilename" ).val() : $( "#preloadPicker" ).val();
    $( "#inputName" ).val( inputFilename );
    $( "#inputNameText" ).text( inputFilename );

    // Show first Options tab
    $( "#optionsTabs a[href='#analysisOptionsTab']" ).tab( "show" );

    // Create date pickers
    $( '#fromDate' ).val( dates.fromDate );
    $( '#toDate' ).val( dates.toDate );
    $( '#dateRange' ).datepicker(
      {
        startDate: dates.fromDate,
        endDate: dates.toDate,
        autoclose: true
      }
    );

    // Create time pickers
    $( '#startTime' ).wickedpicker( { now: "05:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );
    $( '#endTime' ).wickedpicker( { now: "20:00", twentyFour: true, minutesInterval: 15, title: 'Time Editor' } );

    // Initialize Columns
    $( "#checkDefault" ).click( checkDefault );
    $( "#checkAll" ).click( checkAll );
    $( "#uncheckAll" ).click( uncheckAll );
    $( "#checkComplement" ).click( checkComplement );
    $( "#checkSearch" ).keydown( preventDefault );
    $( "#checkSearch" ).keyup( checkSearch );
    $( "#checkAdd" ).click( checkAdd );
    $( "#checkRemove" ).click( checkRemove );
    for ( var colName in columns )
    {
      $( "#columnPicker" ).append( makeColumnPickerRow( colName, columns[colName] ) );
    }

    // Initialize Analysis Options
    if ( $( "#columnsTab *[summarizable=true]" ).length )
    {
      // Data contains summarizable points of interest
      $( "#summary" ).prop( "checked", true );
    }
    else
    {
      // Data contains no summarizable points of interest
      $( "#detailed" ).prop( "checked", true );
      $( "#detailed" ).parent().parent().parent().hide();
    }
    onChangeFormat();

    // Set column-related handlers
    $( "#columnPicker" ).disableTextSelect();
    $( "#columnPicker input[type=checkbox]" ).click( onCheckboxClick );
    $( "#columnPicker input[type=checkbox]" ).change( onColumnSelChange );
    $( window ).on( "resize", setColumnButtonSize );

    // Set handlers to update Help button targets
    $( "#optionsTabs a[href='#analysisOptionsTab']" ).on( "shown.bs.tab", onShowOptionsTab );
    $( "#optionsTabs a[href='#columnsTab']" ).on( "shown.bs.tab", onShowColumnsTab );
    onShowOptionsTab();
  }

  // Handle change of Report Format radio buttons
  function onChangeFormat()
  {
    var bDisable = $( "#multiple" ).prop( "checked" );

    var period = $( "input[type=radio][name=period]" );
    period.parent().css( "color", bDisable ? "lightgray" : "" );
    period.parent().css( "cursor", bDisable ? "default" : "pointer" );
    period.prop( "disabled", bDisable );
    $( "#fullday" ).prop( "checked", ! bDisable );
    $( "#partday" ).prop( "checked", false );
    $( "label[for=period]" ).css( "color", bDisable ? "lightgray" : "" );

    onChangePeriod();

    $( "#includeNotSuitable" ).prop( "checked", false );

    // Update column picker
    clearSearch();
    showSuitable();
    checkPrevious();
  }

  // Handle change of Period radio buttons
  function onChangePeriod()
  {
    var summary = $( "#summary" ).prop( "checked" );
    var detailed = $( "#detailed" ).prop( "checked" );
    var partday = $( "#partday" ).prop( "checked" );

    var useStartTime = summary || ( detailed && partday );
    var useEndTime = ( summary || detailed ) && partday;

    disableTimeInput( "startTime", ! useStartTime );
    disableTimeInput( "endTime", ! useEndTime );
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

  function checkPrevious()
  {
    // Clear selections
    uncheckAll();

    // Retrieve list of previous column selections
    var aColumns = JSON.parse( ( typeof Storage === "undefined" ) ? "[]" : ( localStorage.getItem( getColumnStorageName() ) || "[]" ) );

    // Attempt to restore previous column selections
    for ( var iCol = 0; iCol < aColumns.length; iCol ++ )
    {
      // Look for span with matching column name
      var tSpan = $( '#columnPicker span[columnName]' ).filter( function() { return ( $(this).text() === aColumns[iCol] ) } );

      // If matching span exists, restore the column selection
      if ( tSpan.length )
      {
        var tCheckbox = tSpan.parent().find( 'input' );
        tCheckbox.prop( 'checked', true );
        addEditorColumn( tCheckbox.closest( "li" ).index() );
      }
    }

    // If no columns are checked, resort to the default settings
    if ( ! $( '#columnPicker input:checked' ).length )
    {
      checkDefault();
    }
  }

  function checkDefault()
  {
    // List of substrings identifying default checkbox selection, in order of preference
    var substrings =
      $( "#detailed" ).prop( "checked" ) ?
        [
          "kw_",
          "kvr_",
          "kvar_",
          "co2"
        ]
      :
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
      checkAllContaining( substrings[index-1] );
    }
    else
    {
      checkFirst();
    }
  }

  function preventDefault( event )
  {
    if ( event.keyCode == "13" )
    {
      event.preventDefault();
    }
  }

  function checkAllContaining( substring )
  {
    uncheckAll();

    if ( substring.length > 0 )
    {
      var labels = $( "#columnPicker label" );

      // Select all labels with the substring
      for ( var lbl = 0; lbl < labels.length; lbl ++ )
      {
        var label = $( labels[lbl] );
        var span = label.find( "span[columnName]" );
        if ( span.is( ".suitable, .notNotSuitable" ) && span.text().toLowerCase().indexOf( substring.toLowerCase() )  != -1 )
        {
          label.find( "input" ).prop( "checked", true );
          addEditorColumn( lbl );
        }
      }
    }
  }

  function checkFirst()
  {
    uncheckAll();

    // Prefer first suitable point of interest, if any; otherwise use first listed
    var suitable = $( "#columnsTab span[columnName].suitable, #columnsTab span[columnName].notNotSuitable" );
    var iFirst = suitable.length ? $( suitable[0] ).parent().parent().index() : 0;

    var labels = $( "#columnPicker label" );
    $( labels[iFirst] ).find( "input" ).prop( "checked", true );
    addEditorColumn( iFirst );
  }

  function checkAll()
  {
    uncheckAll();

    var all = $( "#columnPicker .suitable, #columnPicker .notNotSuitable" ).parent().find( "input[type=checkbox]" );
    all.prop( "checked", true );

    for ( var allIndex = 0; allIndex < all.length; allIndex ++ )
    {
      addEditorColumn( $( all[allIndex] ).closest( "li" ).index() );
    }
  }

  function uncheckAll()
  {
    clearSearch();
    clearStartMulti();

    var all = $( "#columnPicker input[type=checkbox]" );
    all.prop( "checked", false );
    for ( var checkboxIndex = 0; checkboxIndex < all.length; checkboxIndex ++ )
    {
      removeEditorColumn( checkboxIndex );
    }
  }

  function checkComplement()
  {
    // Reverse checkbox settings
    var unchecked =  $( "#columnPicker .suitable, #columnPicker .notNotSuitable" ).parent().find( "input[type=checkbox]:not(:checked)" );
    uncheckAll();
    unchecked.prop( "checked", true );

    // Add newly checked items to editor
    for ( var i = 0; i < unchecked.length; i ++ )
    {
      var checkbox = $( unchecked[i] );
      var checkboxIndex = checkbox.closest( "li" ).index();
      addEditorColumn( checkboxIndex );
    }
  }

  function checkMultiple( iStartCheck, iEndCheck, bCheck )
  {
    // Set checkboxes to checked state
    var iChkFirst = Math.min( iStartCheck, iEndCheck );
    var iChkLast = Math.max( iStartCheck, iEndCheck );
    $( '#columnPicker li input:checkbox' ).slice( iChkFirst, iChkLast + 1 ).prop( 'checked', bCheck );

    // Add editor columns
    for ( var iChk = iChkFirst; iChk <= iChkLast; iChk ++ )
    {
      removeEditorColumn( iChk );

      if ( bCheck )
      {
        addEditorColumn( iChk );
      }
    }
  }

  function checkSearch( event )
  {
    if ( ! event || ( event.keyCode != "9" /* tab */ ) )
    {
      var substring = $( "#checkSearch" ).val();
      if ( substring.length > 0 )
      {
        // Mark all checkbox labels containing the substring
        var spans = $( "#columnPicker label span[columnName].suitable, #columnPicker label span[columnName].notNotSuitable" );
        for ( var index = 0; index < spans.length; index ++ )
        {
          var span = $( spans[index] );
          if ( span.text().toLowerCase().indexOf( substring.toLowerCase() ) != -1 )
          {
            // Label contains substring
            markSearchResult( span );
          }
          else
          {
            // Label does not contain substring
            unmarkSearchResult( span );
          }
        }
      }
      else
      {
        clearSearch();
      }
    }
  }

  function checkAdd( event )
  {
    addMarked( event, true );
  }

  function checkRemove( event )
  {
    addMarked( event, false );
  }

  function addMarked( event, bAdd )
  {
    clearStartMulti();

    // Find all checkboxes marked as search results
    var marked = $( "#columnPicker label span[columnName].marked" );

    // Add (or remove) selected points of interest
    for ( var index = 0; index < marked.length; index ++ )
    {
      var checkbox = $( marked[index] ).parent().find( "input" );
      var checkboxIndex = checkbox.closest( "li" ).index();

      // If checkbox not already in desired state, toggle it
      if ( bAdd != checkbox.prop( "checked" ) )
      {
        // Toggle checkbox state
        checkbox.prop( "checked", bAdd );

        // Update column editor
        if ( bAdd )
        {
          addEditorColumn( checkboxIndex );
        }
        else
        {
          removeEditorColumn( checkboxIndex );
        }
      }
    }

    clearSearch();
  }

  function clearSearch()
  {
    $( "#checkSearch" ).val( "" );

    var spans = $( "#columnPicker label span[columnName]" );

    for ( var index = 0; index < spans.length; index ++ )
    {
      var span = $( spans[index] );
      unmarkSearchResult( span );
    }
  }

  function markSearchResult( span )
  {
    span.addClass( "marked" );

    if ( span.parent().find( "input" ).prop( "checked" ) )
    {
      span.removeClass( "bg-info" );
      span.addClass( "bg-danger" );
    }
    else
    {
      span.addClass( "bg-success" );
    }
  }

  function unmarkSearchResult( span )
  {
    span.removeClass( "marked" );
    span.removeClass( "bg-danger" );
    span.removeClass( "bg-success" );

    if ( span.parent().find( "input" ).prop( "checked" ) )
    {
      span.addClass( "bg-info" );
    }
  }

  function makeColumnPickerRow( colName, props )
  {
    var row =
      '<li>'
      +
        '<label class="checkbox checkbox-inline" summarizable=' + props.summarizable + ' >'
      +
          '<input type="checkbox" value="' + encodeURI( colName ) + '">'
      +
          '<span columnName >'
      +
            colName
      +
          '</span>'
      +
          '<span columnAsterisk >'
      +
            ' *'
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
    clearSearch();

    // Determine whether we are in a multi sequence
    var bPart2MultiCheck = g_bMultiCheckShiftKey && $( '.startMultiCheck' ).length;
    var bPart2MultiUncheck = g_bMultiCheckShiftKey && $( '.startMultiUncheck' ).length;
    var iStartCheck = $( '.startMultiCheck' ).closest('li').index();
    var iStartUncheck = $( '.startMultiUncheck' ).closest('li').index();
    clearStartMulti();

    var checkbox = $( event.target );
    var checkboxIndex = checkbox.closest( "li" ).index();

    if ( bPart2MultiCheck )
    {
      // Multi-check part 2
      checkMultiple( iStartCheck, checkboxIndex, true )
    }
    else if ( bPart2MultiUncheck )
    {
      // Multi-uncheck part 2
      checkMultiple( iStartUncheck, checkboxIndex, false )
    }
    else
    {
      if ( checkbox.prop( "checked" ) )
      {
        addEditorColumn( checkboxIndex );

        // Multi-check part 1
        checkbox.closest( 'label' ).find( 'span[columnName]' ).addClass( 'startMultiCheck' );
      }
      else
      {
        removeEditorColumn( checkboxIndex );

        // Multi-uncheck part 1
        checkbox.closest( 'label' ).find( 'span[columnName]' ).addClass( 'startMultiUncheck' );
      }
    }

    // Clear multi-select flag
    g_bMultiCheckShiftKey = false;
  }

  function addEditorColumn( checkboxIndex )
  {
    var span = $( "#columnPicker li:nth-child(" + (  checkboxIndex + 1 ) + ") label span[columnName]" );
    span.addClass( "bg-info" );

    var colName = span.text();

    var summarizable = eval( span.parent().attr( "summarizable" ) );

    var styleCursorMove = ( navigator.userAgent.indexOf( "Edge" ) == -1 ) ? ' style="cursor:move" ' : "" ;

    var nicknames = JSON.parse( ( typeof Storage === "undefined" ) ? "{}" : ( localStorage.getItem( "nicknames" ) || "{}" ) );
    var nickname = SYSTEM_NICKNAMES[colName] || nicknames[colName] || "";
    var disabled = SYSTEM_NICKNAMES[colName] ? "disabled" : "";

    var column =
      '<a class="list-group-item" checkboxIndex="' + checkboxIndex + '" draggable="true" ondragstart="onDragStartColumn(event)" ondragover="onDragOverColumn(event)" ondrop="onDropColumn(event)" ondragend="onDragEndColumn(event)" ' + styleCursorMove + ' >'
      +
        '<div class="row" draggable="true" ondragstart="onDragStartColumn(event)" style="cursor:move" >'
      +
          '<div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall" summarizable=' + summarizable + ' style="padding-top:8px;" >'
      +
            '<span class="list-group-item-text" columnName >' + colName + '</span>'
      +
            '<span columnAsterisk >'
      +
              ' *'
      +
            '</span>'
      +
          '</div>'
      +
          '<div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">'
      +
            '<input type="text" class="form-control" maxlength="32" title="Nickname" placeholder="Nickname" onblur="rememberNickname(event)" value="' + nickname + '" ' + disabled + ' >'
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
    showSuitable();
  }

  function rememberColumns()
  {
    // Save column selections for future use
    if ( typeof Storage !== "undefined" )
    {
      // Find column selections
      var aChecked = $( '#columnPicker input:checked' );

      // Create list of selected column names
      var aColumns = [];
      for ( var iChk = 0; iChk < aChecked.length; iChk ++ )
      {
        var tCheckbox = $( aChecked[iChk] );
        var sLabel = tCheckbox.parent().find( 'span[columnname]' ).text();
        aColumns.push( sLabel );
      }

      // Save selected column names in local storage
      localStorage.setItem( getColumnStorageName(), JSON.stringify( aColumns ) );
    }
  }

  function getColumnStorageName()
  {
    var sName = $( "input:radio[name='format']:checked" ).val();
    sName = ( sName == '<?=MULTIPLE?>' ) ? '<?=SUMMARY?>' : sName;
    return 'columns_' + sName;
  }

  function rememberNickname( event )
  {
    if ( typeof Storage !== "undefined" )
    {
      var target = $( event.target );
      var fullname = target.closest( "a" ).find( "span[columnName]" ).text();
      var nickname = target.val();
      var nicknames = JSON.parse( localStorage.getItem( "nicknames" ) || "{}" );
      nicknames[fullname] = nickname;
      localStorage.setItem( "nicknames", JSON.stringify( nicknames ) );
    }
  }

  var DRAG_TARGET = null;
  function onDragStartColumn( event )
  {
    DRAG_TARGET = $( event.target ).closest( "a" );
    event.dataTransfer.setData( "text", "" );
    event.dataTransfer.setDragImage( DRAG_TARGET.find( "span[columnName]" )[0], -25, -10);
  }

  function onDragOverColumn( event )
  {
    if ( DRAG_TARGET != null )
    {
      event.preventDefault();

      var target = $( event.target ).closest( "a" );
      $( ".columnHover" ).removeClass( "columnHover" );
      target.addClass( "columnHover" );

      if ( DRAG_TARGET.index() != target.index() )
      {
        if ( DRAG_TARGET.offset().top > target.offset().top )
        {
          target.next().after( target );
        }
        else
        {
          target.prev().before( target );
        }
        setNicknameTabindex();
      }
    }
  }

  function onDropColumn( event )
  {
    $( ".columnHover" ).removeClass( "columnHover" );
    event.preventDefault();
    DRAG_TARGET = null;
  }


  function onDragEndColumn( event )
  {
    $( ".columnHover" ).removeClass( "columnHover" );
    DRAG_TARGET = null;
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
    clearSearch();

    // Find the corresponding column picker entry
    var editorColumn = $( $( event.target ).closest( "a" )[0] );
    var checkboxIndex = parseInt( editorColumn.attr( "checkboxIndex" ) ) + 1;
    var li = $( "#columnPicker li:nth-child(" + checkboxIndex + ")" );

    // Uncheck the checkbox
    var checkbox = li.find( "input" );
    checkbox.prop( "checked", false );

    // Remove the highlighting
    var span = li.find( "span[columnName]" );
    span.removeClass( "bg-info" );

    // Remove the column from the editor
    editorColumn.remove();
  }

  function removeEditorColumn( checkboxIndex )
  {
    var span = $( "#columnPicker li:nth-child(" + (  checkboxIndex + 1 ) + ") label span[columnName]" );
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
    clearSearch();
    var valid = validateFormInput( validateOptionsInput );

    if ( valid )
    {
      rememberColumns();
      $( "#analyzeButton" ).prop( "disabled", true );
      $( "body" ).css( "cursor", "progress" );
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
    else if ( $( "#results" ).prop( "checked" ) && $( "#resultsFile" ).val() == "" )
    {
      messages.push( "<?=RESULTS_FILE?> is required" );
      $( "#resultsFilename" ).parent().parent().addClass( "has-error" );
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
    var nicknameMap = {};
    var nicknames = $( "#columnEditor input" );
    var foundComma = false;
    var foundDuplicate = false;
    for ( var i = 0; i < nicknames.length; i ++ )
    {
      var nickname = $( nicknames[i] );
      var val = nickname.val();
      if ( val != "" )
      {
        if ( nicknameMap[val] )
        {
          // Set flag
          foundDuplicate = true;

          // Highlight both offenders
          nicknameMap[val].parent().addClass( "has-error" );
          nickname.parent().addClass( "has-error" );
        }
        else
        {
          // Save entry
          nicknameMap[val] = nickname;
        }
      }

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
    if ( foundDuplicate )
    {
      messages.push( "Column nicknames must be unique" );
    }

    if ( ! messages.length )
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
      var name = column.find( "span[columnName]" ).text();
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

  function onShowOptionsTab()
  {
    // Attach corresponding Help dialog
    $( "#multiHelp" ).attr( "data-target", "#helpOptions" );
  }

  function onShowColumnsTab()
  {
    // Attach corresponding Help dialog
    $( "#multiHelp" ).attr( "data-target", "#helpColumns" );
  }

  function showSuitable()
  {
    var detailed = $( "#detailed" ).prop( "checked" );

    // Highlight suitable Points of Interest
    var notSuitableClass = $( "#includeNotSuitable" ).prop( "checked" ) ? "notNotSuitable" : "notSuitable";
    $( "#columnsTab *[summarizable=" + detailed + "] span[columnName]" ).addClass( notSuitableClass ).removeClass( "suitable" );
    $( "#columnsTab *[summarizable=" + detailed + "] span[columnAsterisk]" ).css( "display", "inline" );
    $( "#columnsTab *[summarizable=" + ! detailed + "] span[columnName]" ).addClass( "suitable" ).removeClass( notSuitableClass );
    $( "#columnsTab *[summarizable=" + ! detailed + "] span[columnAsterisk]" ).css( "display", "none" );

    // Show suitability footnote
    var showFootnote = $( "#columnsTab .notSuitable, #columnsTab .notNotSuitable" ).length > 0;
    $( "#notSuitableFootnote" ).css( "display", showFootnote ? "block" : "none" );
  }

  // Handle change of include-not-suitable checkbox
  function includeNotSuitableChanged()
  {
    if ( $( "#includeNotSuitable" ).prop( "checked" ) )
    {
      // Checked: Show names in normal color
      $( "#columnsTab *[summarizable] span[columnName].notSuitable" ).addClass( "notNotSuitable" ).removeClass( "notSuitable" );
    }
    else
    {
      // Unchecked: Show names in gray color
      $( "#columnsTab *[summarizable] span[columnName].notNotSuitable" ).addClass( "notSuitable" ).removeClass( "notNotSuitable" );
    }

    // Update search highlighting
    var sSearch = $( "#checkSearch" ).val();
    if ( sSearch )
    {
      clearSearch();
      $( "#checkSearch" ).val( sSearch );
      checkSearch();
    }
  }
</script>


<?php
  if ( getenv( "COLORTEST" ) )
  {
    include "plot/colortest.php";
  }
?>


<div class="container">
  <div class="page-header">
    <p class="h3"><?=METASYS_DATA_ANALYSIS?></p>
  </div>

  <div id="fileBlock" >

    <!-- Input file tabs -->
    <ul id="inputFileTabs" class="nav nav-tabs">
      <li><a data-toggle="tab" href="#analyzeTab">Analyze</a></li>
      <li id="plotTabItem" ><a id="plotTabLink" data-toggle="tab" href="#plotTab">Plot</a></li>
    </ul>

    <fieldset id="inputFileFields">
      <div class="tab-content">

        <!-- Analyze -->
        <div id="analyzeTab" class="tab-pane fade">
          <div class="row" >
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
              <div class="panel panel-default">
                <div class="panel-body">
                  <div class="form-group">
                    <h4>Analyze <?=METASYS_FILE?></h4>
                    <div class="radio">
                      <label>
                        <input type="radio" id="preload" name="fileSource" onchange="onChangeFileSource()" >
                        Sample
                      </label>
                    </div>
                    <div class="radio">
                      <label>
                        <input type="radio" id="upload" name="fileSource" onchange="onChangeFileSource()" >
                        Your file
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
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Plot -->
        <div id="plotTab" class="tab-pane fade">
          <div class="row" >
            <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
              <div class="panel panel-default">
                <div class="panel-body">
                  <div class="form-group">
                    <h4>Plot <?=METASYS_DATA_ANALYSIS_RESULTS?></h4>
                    <div class="radio">
                      <label>
                        <input type="radio" id="sample" name="fileSource" onchange="onChangeFileSource()" >
                        Sample
                      </label>
                    </div>
                    <div class="radio">
                      <label>
                        <input type="radio" id="results" name="fileSource" onchange="onChangeFileSource()" >
                        Your file
                      </label>
                    </div>
                  </div>
                  <div class="form-group" id="sampleBlock" >
                    <select id="samplePicker" class="form-control" >
                    </select>
                  </div>
                  <div class="form-group" id="resultsBlock" >
                    <div class="input-group">
                      <label class="input-group-btn">
                        <span class="btn btn-default">
                          Browse…
                          <input type="file" name="resultsFile" id="resultsFile" style="display:none" onchange="showFilename( 'resultsFilename', 'resultsFile' )" >
                        </span>
                      </label>
                      <input id="resultsFilename" type="text" class="form-control" onclick="$('#resultsFile').click();" readonly >
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </fieldset>

    <div class="row">
      <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
        <div style="text-align:center;" >
          <button id="submitFileButton" class="btn btn-primary" onclick="onSubmitFile()" >Submit</button>
          <button id="fileCancel" type="reset" onclick="startCleanup( $( '#timestamp' ).val(), 'mda/' );" class="btn btn-default" >Cancel</button>
          <svg class="helpButtonSpacer"></svg>
          <button id="fileHelp" type="button" class="btn btn-info" data-toggle="modal" data-target="#helpInputFile">Help</button>
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
                  <label class="control-label" for="fromDate" ><?=DATE_RANGE?></label>
                  <div id="dateRange" class="input-group input-daterange">
                    <input id="fromDate" name="fromDate" type="text" class="form-control" readonly >
                    <div class="input-group-addon">to</div>
                    <input id="toDate" name="toDate" type="text" class="form-control" readonly >
                  </div>
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

                <br/>
                <div class="form-group" >
                  <label class="control-label" for="startTime" ><?=START_TIME?></label>
                  <input type="text" id="startTime" name="startTime" class="form-control timepicker" style="border-radius:4px" readonly >
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
                <span class="glyphicon glyphicon-check">&nbsp;</span>Available <?=POINTS_OF_INTEREST?>
              </div>
              <div class="panel-body">

                <!-- Checkbox accelerators -->
                <div class="row">
                  <div class="col-xs-12 col-sm-4 col-md-4 col-lg-4">
                    <div class="btn-toolbar" role="toolbar" style="padding-bottom: 5px" >
                      <span class="btn-group btn-group-xs" style="padding-right:10px" role="group" >
                        <button type="button" id="checkDefault" class="btn btn-default btn-xs" title="Select Default" >Default</button>
                        <button type="button" id="checkAll" class="btn btn-default btn-xs" title="Select All" >All</button>
                        <button type="button" id="uncheckAll" class="btn btn-default btn-xs" title="Deselect All" >None</button>
                        <button type="button" id="checkComplement" class="btn btn-default btn-xs" title="Select Complement" >Complement</button>
                      </span>
                    </div>
                  </div>

                  <div class="col-xs-12 col-sm-8 col-md-6 col-lg-6">
                    <div class="input-group" style="padding-bottom: 5px">
                      <input type="text" id="checkSearch" class="form-control" style="height:22px; padding-top:0px; padding-bottom:0px;" placeholder="Search..." autocomplete="off"  title="Find matching <?=POINTS_OF_INTEREST?>">
                      <div class="input-group-btn">
                        <button type="button" id="checkAdd" class="btn btn-success btn-xs" title="Add Search Results" >Add</button>
                        <button type="button" id="checkRemove" class="btn btn-danger btn-xs" title="Remove Search Results" >Remove</button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Checkboxes -->
                <ul id="columnPicker" class="list-unstyled checkboxList" >
                </ul>
                <small id="notSuitableFootnote" class="text-center" style="padding-top:10px" >
                  * <?=POINT_OF_INTEREST?> not suitable for selected <?=REPORT_FORMAT?>
                  <div class="checkbox" >
                    <label>
                      <input type="checkbox" id="includeNotSuitable" onchange="includeNotSuitableChanged()" />
                      Include unsuitable <?=POINTS_OF_INTEREST?>
                    </label>
                  </div>
                </small>
              </div>
            </div>
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
                <div class="panel panel-default">
                  <div class="panel-heading">
                    <span class="glyphicon glyphicon-ok">&nbsp;</span>Selected <?=POINTS_OF_INTEREST?>
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
          <button id="optionsCancel" type="reset" onclick="startCleanup( $( '#timestamp' ).val(), 'mda/' );" class="btn btn-default" >Cancel</button>
          <svg class="helpButtonSpacer"></svg>
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
