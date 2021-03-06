// Copyright 2018 Energize Apps.  All rights reserved.

/////////////////////////////
// ---> Debug utility ---> //
/////////////////////////////

var g_bDebugEnable = false;  // Debug flag
var g_sDebug = "";           // Debug content
var g_iDebugDraw = 0;        // Debug counter

function debugShow()
{
    if ( g_bDebugEnable )
    {
        if ( document.getElementById( "areaDebug" ) == null )
        {
            $('#mainpane').append( '<br /><textarea id="areaDebug" name="areaDebug" onkeyup="debugClear( event );" rows="60" cols="120" /><br />' );
        }

        $('#areaDebug').val( g_sDebug );
    }
}

function debugClear( tEvent )
{
    if ( typeof tEvent == "undefined" )
    {
        g_iDebugDraw = 0;
    }

    if ( ( typeof tEvent == "undefined" ) || ( tEvent.keyCode == 46 /* Delete key */ ) )
    {
        g_sDebug = "";

        if ( document.getElementById( "areaDebug" ) != null )
        {
            $('#areaDebug').val( g_sDebug );
        }
    }
}

function debugAdd( sMessage )
{
    if ( g_bDebugEnable )
    {
        // If this message starts a new line, prefix with timestamp
        if ( g_sDebug.indexOf( "\n", g_sDebug.length - 1 ) === g_sDebug.length - 1 )
        {
            g_sDebug += "[" + new Date() + "] ";
        }

        // Append message content
        g_sDebug += sMessage;
    }
}

function debugAddLine( sMessage, bAbbreviate )
{
    if ( g_bDebugEnable )
    {
        // Add the newline
        sMessage = ( ( typeof sMessage == "undefined" ) ? "" : sMessage ) + "\n";

        // Optionally abbreviate the message
        if ( ( typeof bAbbreviate != "undefined" ) && bAbbreviate )
        {
            if ( g_sDebug.indexOf( sMessage, g_sDebug.length - sMessage.length ) !== -1 )
            {
                g_sDebug = g_sDebug.substr( 0,  g_sDebug.length - sMessage.length );
                g_sDebug += ".";
            }
        }

        // Add the message
        debugAdd( sMessage );
    }
}

function debugEnable( bEnable )
{
    g_bDebugEnable = bEnable;
}

/////////////////////////////
// <--- Debug utility <--- //
/////////////////////////////


/////////////////////////////////////////////////////
// ---> Global variables used by Plot display ---> //
/////////////////////////////////////////////////////

var DOWNSAMPLEMODE_AUTO = "auto";
var DOWNSAMPLEMODE_MANUAL = "manual";

var COLLAPSE_INCREMENT = 40;

var g_iPlotRefreshAjaxIndex = null;
var g_iAutoStopAjaxIndex = null;
var g_aChoosers = null;
var g_aSeries = null;
var g_iLegendUpdateTimeout = null;
var g_tMousePosition = null;
var g_aData = null;
var g_tPlot = null;
var g_tOverview = null;
var g_tScrollbar = null;
var g_bPlotBindDone = false;
var g_iTooltipSeriesIndex = null;
var g_iTooltipDataIndex = null;
var g_tZoomRange = null;
var g_bShowLastPoint = true;
var g_sDownSampleMode = DOWNSAMPLEMODE_AUTO;
var g_iDownSampleDensity = 100;
var g_iDownSampleOffset = 0;
var g_iDownSampleShow = null;
var g_iDownSampleHide = null;
var g_aColors = null;
var g_aDownSampleStack = [];
var g_aCropStack = [];
var g_aSeriesChooserItems = null;
var g_aNicknameMap = null;
var g_bPan = null;

var g_tEventTimeStamps =
{
    plotPan: 0,
    plotHover: 0,
    plotZoomIn: 0,
    zoomRangeHighlight: 0,
    plotScroll: 0
};

// Main plot options
var g_tOptionsPlot =
{
    xaxis:
    {
        mode: 'time',
        timezone: "browser"
    },
    series:
    {
        lines:
        {
            show: true,
            lineWidth: 1
        },
        points:
        {
            show: true,
            radius: 1
        }
    },
    crosshair:
    {
        mode: "x"
    },
    grid:
    {
        hoverable: true
    },
    hooks:
    {
        bindEvents: [ plotBindEvents ]
    }
};

// Overview plot options
var g_tOptionsOverview =
{
    legend:
    {
        show: false
    },
    xaxis:
    {
        mode: 'time',
        show: false
    },
    series:
    {
        lines:
        {
            show: true,
            lineWidth: 1
        },
        shadowSize: 0
    },
    selection:
    {
        mode: "x",
        color: "#8888FF"
    }
};

// Scrollbar plot options
var g_tOptionsScrollbar =
{
    grid:
    {
        borderWidth: 0,
        backgroundColor: "#DCDCDC"
    },
    legend:
    {
        show: false
    },
    xaxis:
    {
        mode: 'time',
        show: false
    },
    series:
    {
        lines:
        {
            show: false
        }
    },
    scrollbar:
    {
        enable: true
    }
};

/////////////////////////////////////////////////////
// <--- Global variables used by Plot display <--- //
/////////////////////////////////////////////////////


// Show initial plot, either empty, or initialized with data from opened plot file
function plotInit( aPlotOpenData, aNicknameMap )
{
    // Build the nickname map
    g_aNicknameMap = [];
    for ( var iName = 0; iName < aNicknameMap.length; iName += 2 )
    {
      g_aNicknameMap[aNicknameMap[iName]] = aNicknameMap[iName+1];
    }

    // Initialize checkbox accelerator controls
    checkboxAcceleratorsInit();

    // Initialize plot drag action
    plotSetDragOptions();

    // Initialize with data from opened plot file
    var bShowPlotOpenData = plotRead( aPlotOpenData );

    if ( bShowPlotOpenData )
    {
      initEventHandlers();
    }
    else
    {
        $( "#checkboxAccelerators" ).css( "display", "none" );
        chooserClear();

        var tOptions =
        {
            xaxis:
            {
                mode: 'time'
            },
            yaxis:
            {
                show: false
            },
            grid:
            {
            }
        };

        $.plot( $("#plotview"), [{}], tOptions );
        $.plot( $("#overview"), [{}], tOptions );

        // Show scrollbar, without time tick
        tOptions.xaxis.show = false;
        tOptions.grid.show = false;
        $.plot( $("#scrollbar"), [{}], tOptions );

        $( "#plotControls" ).css( "display", "none" );
    }

    return bShowPlotOpenData;
}

// Add a set of samples to the plot
function plotSample( aSamples )
{
    var iSample;
    var tDate = new Date();

    // If new plot, initialize series array and chooser
    if ( g_aSeries == null )
    {
        g_aSeries = [];
        chooserClear();

        for ( iSample = 0; iSample < aSamples.length; iSample ++ )
        {
            g_aSeries[iSample] = [];
            chooserAdd( aSamples, iSample );
        }
    }

    // Add the new plot points to their respective series
    var iTime = tDate.getTime();
    for ( iSample = 0; iSample < aSamples.length; iSample ++ )
    {
        // If data contains timestamp (occurs with plot file), use saved timestamp instead of current
        if ( typeof ( aSamples[iSample].time ) != "undefined" )
        {
            iTime = aSamples[iSample].time;
        }

        g_aSeries[iSample].push( [ iTime, aSamples[iSample].value ] )
    }
}

// Draw plot with selected series
function plotDraw( tEvent )
{
    debugAddLine( "==>plotDraw( " + tEvent.type + " )" );

    // Save previous Hide setting
    var iHidePrev = g_iDownSampleHide;

    // Get array of checkboxes in the series chooser
    var aChoices = $("#seriesChooser").find( "input" );

    // Set up plot data and axis information
    g_aData = [];
    aDataFull = [];
    var aYaxesPlot = [];
    var aYaxesNav = [];
    var bScaleIndependent = $( "#scaleIndependent" ).prop( "checked" );
    var bShowYaxisTicks = $( "#showYaxisTicks" ).prop( "checked" );
    var bAxisRight = true;
    for ( var iChoice = 0; iChoice < aChoices.length; iChoice ++ )
    {
        // Get chooser checkbox for the current series
        var tChoice = aChoices[iChoice];

        if ( tChoice.checked )
        {
            var aCrop = dataCrop( g_aSeries[tChoice.series] );
            var aData = dataDownSample( aCrop );

            if ( aData.length > 0 )
            {
                // Associate label, axis index, and color with current series
                var yaxis = ( bScaleIndependent ? tChoice.yaxis : "" );
                g_aData.push( { data: aData, label: ( zoomRangeIsNull() ? " " : " " + tChoice.label ), tooltip: tChoice.label, yaxis: yaxis, color: tChoice.color } );
                aDataFull.push( { data: g_aSeries[tChoice.series], label: tChoice.label, yaxis: yaxis, color: tChoice.color } );

                // Associate options with current series
                aYaxesPlot[tChoice.series] =
                {
                    tickFormatter: tChoice.tickFormatter,
                    tickDecimals: tChoice.tickDecimals,
                    tickColor: tChoice.color,
                    panRange: false,
                    position: ( bAxisRight = ! bAxisRight )? "right" : "left"
                }

                // Add some options for the navigation (overview and scrollbar) plots
                aYaxesNav[tChoice.series] = jQuery.extend( { show: false }, aYaxesPlot[tChoice.series] );

                if ( ! bShowYaxisTicks )
                {
                  aYaxesPlot[tChoice.series].show = false;
                  aYaxesPlot[tChoice.series].reserveSpace = false;
                }
            }
        }
    }

    // Determine whether to show full data in zoomed view of down-sampled plot
    var bZoomFull = document.getElementById( "downSampleZoom" ).checked;

    // Ensure that global zoom range lies within bounds of down-sampled data
    if ( ! zoomRangeIsNull() && ( g_aData.length > 0 ) )
    {
        // Save full zoom range
        var tZoomRangeFull = jQuery.extend( true, {}, g_tZoomRange );

        // Trim global zoom range
        var aPoints = g_aData[0].data;
        zoomRangeSet( Math.max( g_tZoomRange.xaxis.min, aPoints[0][0] ), Math.min( g_tZoomRange.xaxis.max, aPoints[aPoints.length-1][0] ) );

        // Debug
        if ( zoomRangeIsNull() || ( g_tZoomRange.xaxis.min != tZoomRangeFull.xaxis.min ) || ( g_tZoomRange.xaxis.max != tZoomRangeFull.xaxis.max ) )
        {
            debugAddLine( "Changed zoom range from (" + tZoomRangeFull.xaxis.min + "," + tZoomRangeFull.xaxis.max + ") to " + zoomRangeToString() );
        }
    }

    // Draw the main plot and scrollbar
    debugAddLine( "Draw " + ( g_iDebugDraw ++ ) + " with zoom range=" + zoomRangeToString() );
    g_tOptionsPlot.yaxes = aYaxesPlot;
    if ( zoomRangeIsNull() || ( g_aData.length == 0 ) )
    {
        // Draw full plot
        if ( g_bPan )
        {
          g_tOptionsPlot.pan.interactive = false;
          g_tOptionsPlot.xaxis.panRange = false;
        }

        g_tPlot = $.plot( $("#plotview"), g_aData, g_tOptionsPlot );

        // Draw scrollbar plot
        g_tOptionsScrollbar.yaxes = copyPlotTicksTo( aYaxesNav );
        g_tOptionsScrollbar.grid.show = false;
        $("#scrollbar").css( "cursor", "default" )
        if ( g_tScrollbar != null )
        {
            g_tScrollbar.scrollPageStop();
        }
        g_tScrollbar = $.plot( $("#scrollbar"), g_aData, g_tOptionsScrollbar );
    }
    else
    {
        // Draw zoomed plot with optional panning
        if ( g_bPan )
        {
          g_tOptionsPlot.pan.interactive = true;
          var aPoints = g_aData[0].data;
          g_tOptionsPlot.xaxis.panRange = [ aPoints[0][0], aPoints[aPoints.length-1][0] ];
        }

        var aDataZoom = bZoomFull ? aDataFull : g_aData;
        var tZoomRange = bZoomFull ? tZoomRangeFull : g_tZoomRange;
        g_tPlot = $.plot( $("#plotview"), aDataZoom, $.extend( true, {}, g_tOptionsPlot, tZoomRange ) );

        // If not handling a scroll event, draw scrollbar
        if ( tEvent.type != "plotscroll" )
        {
            // Draw scrollbar plot with highlight
            g_tOptionsScrollbar.yaxes = copyPlotTicksTo( aYaxesNav );
            g_tOptionsScrollbar.grid.show = true;
            $("#scrollbar").css( "cursor", "pointer" )
            g_tScrollbar = $.plot( $("#scrollbar"), g_aData, g_tOptionsScrollbar );
            var tRange = new Object();
            tRange.xaxis = new Object();
            tRange.xaxis.from = g_tZoomRange.xaxis.min;
            tRange.xaxis.to = g_tZoomRange.xaxis.max;
            g_tScrollbar.setScroller( tRange, true );
        }
    }

    // If not handling a scroll event, draw overview plot
    if ( tEvent.type != "plotscroll" )
    {
        g_tOptionsOverview.yaxes = copyPlotTicksTo( aYaxesNav );
        g_tOverview = $.plot( $("#overview"), g_aData, g_tOptionsOverview );
        zoomRangeHighlight( { timeStamp: new Date().getTime() } );
    }

    // If we saved the global zoom range, restore it
    if ( typeof tZoomRangeFull != "undefined" )
    {
        g_tZoomRange = jQuery.extend( true, {}, tZoomRangeFull );
    }

    // Bind event handlers to main, overview, and scrollbar plots
    plotBindHandlers();

    // Update plot status
    downsampleStatusUpdate( ( g_aData.length == 0 ) ? 0 : g_aData[0].data.length, ( aDataFull.length == 0 ) ? 0 : aDataFull[0].data.length );
    zoomStatusUpdate();

    // Enable/disable down-sample pattern controls
    downSampleControlsEnable();

    debugShow();
}

// Bind event handlers to main, overview, and scrollbar plots
function plotBindHandlers()
{
    if ( ! g_bPlotBindDone )
    {
        g_bPlotBindDone = true;

        ///////////////
        // Main plot //
        ///////////////

        // Bind crosshair to plot legend update
        $("#plotview").on( "plothover", plotHover );

        // Bind drag action handlers
        plotBindDragHandlers();

        ///////////////////
        // Overview plot //
        ///////////////////

        // Bind overview selection to main plot zoom in
        $("#overview").on( "plotselected", plotZoomIn );

        // Bind overview deselection to main plot zoom out
        $("#overview").on( "plotunselected", plotZoomOut );

        // Bind overview resize to redraw of selected zoom range
        $("#overview").on( "resize", zoomRangeHighlight );

        ////////////////////
        // Scrollbar plot //
        ////////////////////

        // Bind start of scroll stroke to suspension of live update
        $("#scrollbar").on( "plotscrollstart", plotScrollStart );

        // Bind scroller movement to pan in overview and main plots
        $("#scrollbar").on( "plotscroll", plotScroll );
    }
}

// Bind drag action handlers
function plotBindDragHandlers()
{
  if ( g_bPan )
  {
    // Unbind zoom handlers
    $("#plotview").off( 'plotselected' );
    $("#plotview").off( 'plotunselected' );

    // Bind pan handler
    $("#plotview").on( 'plotpan', plotPan );
  }
  else
  {
    // Unbind pan handler
    $("#plotview").off( 'plotpan' );

    // Bind zoom handlers
    $("#plotview").on( 'plotselected', plotZoomIn );
    $("#plotview").on( "plotunselected", plotZoomOut );
  }
}

// Flot hook function to bind custom handlers to main plot events
function plotBindEvents( tPlot, tEventHolder )
{
  // Handle native JS events here
  tEventHolder.on( 'dragend', plotClearPanCursor );
}

function initEventHandlers()
{
  // Handle native JS events from non-plot elements

  // Set handlers to show plus and minus icons on collapse panel heads
  $( ".collapse" ).on( "shown.bs.collapse", collapseShow );
  $( ".collapse" ).on( "hidden.bs.collapse", collapseHide );
}

function collapseShow( tEvent )
{
  $(this).parent().find( ".glyphicon-plus" ).removeClass( "glyphicon-plus" ).addClass( "glyphicon-minus" );
  var height = parseInt( $( "#plotview" ).css( "height" ) ) - COLLAPSE_INCREMENT;
  $( "#plotview" ).css( "height", height.toString() + "px" );
}

function collapseHide( tEvent )
{
  $(this).parent().find( ".glyphicon-minus" ).removeClass( "glyphicon-minus" ).addClass( "glyphicon-plus" );
  var height = parseInt( $( "#plotview" ).css( "height" ) ) + COLLAPSE_INCREMENT;
  $( "#plotview" ).css( "height", height.toString() + "px" );
}

// Handle plothover event fired by plot
function plotHover( tEvent, tPos, tItem )
{
    if ( tEvent.timeStamp != g_tEventTimeStamps.plotHover )
    {
        g_tEventTimeStamps.plotHover = tEvent.timeStamp;

        // Save the mouse position
        g_tMousePosition = tPos;

        // Schedule the legend update
        if ( g_iLegendUpdateTimeout == null )
        {
            g_iLegendUpdateTimeout = setTimeout( legendUpdate, 50);
        }

        // Show/hide tooltip
        if ( tItem )
        {
            if ( ( g_iTooltipSeriesIndex != tItem.seriesIndex ) || ( g_iTooltipDataIndex != tItem.dataIndex ) )
            {
                // Change the tooltip
                g_iTooltipSeriesIndex = tItem.seriesIndex;
                g_iTooltipDataIndex = tItem.dataIndex;
                $("#tooltip").remove();
                showTooltip( tItem );
            }
        }
        else
        {
            // Clear the tooltip
            $("#tooltip").remove();
            g_iTooltipSeriesIndex = null;
            g_iTooltipDataIndex = null;
        }
    }
}

function seriesCheckAll()
{
  clearStartMulti();
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", true );
  finishSeriesCheckAction();
}

function seriesCheckNone()
{
  clearStartMulti();
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", false );
  finishSeriesCheckAction();
}

function seriesCheckComplement()
{
  clearStartMulti();
  var unchecked = $( "#seriesChooser input[type=checkbox]:not(:checked)" );
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", false );
  unchecked.prop( "checked", true );
  finishSeriesCheckAction();
}

function seriesCheckMultiple( iStartCheck, iEndCheck, bCheck )
{
  // Check or uncheck contiguous series of checkboxes
  var iChkFirst = Math.min( iStartCheck, iEndCheck );
  var iChkLast = Math.max( iStartCheck, iEndCheck );
  $( '#seriesChooser li input:checkbox' ).slice( iChkFirst, iChkLast + 1 ).prop( 'checked', bCheck );
}

// Filter plot based on selections made in plot chooser
function plotFilter( tEvent )
{
  // Determine whether we are in a multi sequence
  var bPart2MultiCheck = g_bMultiCheckShiftKey && $( '.startMultiCheck' ).length;
  var bPart2MultiUncheck = g_bMultiCheckShiftKey && $( '.startMultiUncheck' ).length;
  var iStartCheck = $( '.startMultiCheck' ).closest('li').index();
  var iStartUncheck = $( '.startMultiUncheck' ).closest('li').index();
  clearStartMulti();

  var checkbox = $( tEvent.target );
  var checkboxIndex = checkbox.closest( "li" ).index();

  if ( bPart2MultiCheck )
  {
    // Multi-check part 2
    seriesCheckMultiple( iStartCheck, checkboxIndex, true )
  }
  else if ( bPart2MultiUncheck )
  {
    // Multi-uncheck part 2
    seriesCheckMultiple( iStartUncheck, checkboxIndex, false )
  }
  else
  {
    if ( checkbox.prop( "checked" ) )
    {
      // Multi-check part 1
      checkbox.closest( 'label' ).find( 'span[columnName]' ).addClass( 'startMultiCheck' );
    }
    else
    {
      // Multi-uncheck part 1
      checkbox.closest( 'label' ).find( 'span[columnName]' ).addClass( 'startMultiUncheck' );
    }
  }

  // Clear multi-select flag
  g_bMultiCheckShiftKey = false;

  // Finish
  finishSeriesCheckAction();
}

function finishSeriesCheckAction()
{
  $( "#seriesChooser .bg-info" ).removeClass( "bg-info" );
  $( "#seriesChooser input[type=checkbox]:checked" ).parent().find( "span[columnName]" ).addClass( "bg-info" );
  plotDraw( { type: "plotFilter" } );
}

// Toggle sort of checkboxes between original and alphabetical
function seriesSort()
{
  if ( g_aSeriesChooserItems )
  {
    $( "#seriesChooser" ).append( g_aSeriesChooserItems );
    g_aSeriesChooserItems = null;
  }
  else
  {
    g_aSeriesChooserItems = $( "#seriesChooser li" );
    $( "#seriesChooser" ).append( $( "#seriesChooser li" ).sort( seriesCheckboxCompare ) );
  }
}

function seriesCheckboxCompare( a, b )
{
  var sA = $( a ).find( "span" ).text();
  var sB = $( b ).find( "span" ).text();
  return ( sA > sB ) ? 1 : ( ( sA < sB ) ? -1 : 0 );
}

// Show or hide y-axis ticks
function plotShowYaxisTicks( tEvent )
{
  plotDraw( { type: "plotShowYaxisTicks" } );
}

// Scale series independently or in common
function plotScaleIndependent( tEvent )
{
  plotDraw( { type: "plotScaleIndependent" } );
}

// Set plot drag action to pan or select
function plotChangeDragAction( tEvent )
{
  // Set plot options for drag
  plotSetDragOptions();

  // Bind drag event handlers
  plotBindDragHandlers();

  // Redraw the plot
  plotDraw( { type: "plotChangeDragAction" } );
}

// Set plot drag action to pan or select
function plotSetDragOptions()
{
  var sAction = $( "input[name='dragAction']:checked" ).val();
  g_bPan = ( sAction == "pan" );

  // Set plot drag behavior
  if ( g_bPan )
  {
    delete g_tOptionsPlot.selection;
    g_tOptionsPlot.pan = {};
  }
  else
  {
    delete g_tOptionsPlot.pan;
    g_tOptionsPlot.xaxis.panRange = false;
    g_tOptionsPlot.selection =
    {
        mode: "x",
        color: "#8888FF"
    }
  }
}

function plotZoomIn( tEvent, tRanges )
{
    debugAddLine( "==>plotZoomIn()", true );

    if ( tEvent.timeStamp != g_tEventTimeStamps.plotZoomIn )
    {
        g_tEventTimeStamps.plotZoomIn = tEvent.timeStamp;

        // If main plot is not currently zoomed to supplied range, update zoom
        if ( ( typeof tRanges.xaxis != "undefined" ) && ( zoomRangeIsNull() || ( ( g_tZoomRange.xaxis.min != tRanges.xaxis.from ) || ( g_tZoomRange.xaxis.max != tRanges.xaxis.to ) ) ) )
        {
            // Set range
            zoomRangeSet( tRanges.xaxis.from, tRanges.xaxis.to );

            // Draw the zoomed plot
            debugAddLine( "Zoom In at range=" + zoomRangeToString() );
            plotDraw( tEvent );
        }
    }
}

function plotZoomOut( tEvent )
{
    // If plot is zoomed in, and at least one series is selected in the plot chooser, zoom it out
    if ( ( ! zoomRangeIsNull() ) && ( g_aData.length > 0 ) )
    {
        debugAddLine( "Zoom Out" );
        zoomRangeSet( null );
        plotDraw( tEvent );
    }
}

// Redraw zoom range to reflect new panned position of main plot
function plotPan( tEvent, tPlot )
{
    // If we have not already processed this event...
    if ( tEvent.timeStamp != g_tEventTimeStamps.plotPan )
    {
        debugAddLine( "pan at time " + tEvent.timeStamp );
        // Save timestamp for next time
        g_tEventTimeStamps.plotPan = tEvent.timeStamp;

        // Save new zoom range
        var tXaxis = tPlot.getAxes().xaxis;
        zoomRangeSet( tXaxis.min, tXaxis.max );

        // Update zoom range display
        zoomRangeHighlight( tEvent );
    }
}

// Start plot scroll stroke
function plotScrollStart( tEvent )
{
    if ( zoomRangeIsNull() )
    {
        // Silence subsequent scroller events
        g_tScrollbar.scrollStop();
    }
}

// Redraw zoom range in overview and panned position of main plot
function plotScroll( tEvent, tRanges )
{
    debugAddLine( "==>plotScroll()", true );

    if ( tEvent.timeStamp != g_tEventTimeStamps.plotScroll )
    {
        g_tEventTimeStamps.plotScroll = tEvent.timeStamp;

        // If main plot is not currently zoomed to supplied range, update zoom
        if ( ( typeof tRanges.xaxis != "undefined" ) && ! zoomRangeIsNull() && ( ( g_tZoomRange.xaxis.min != tRanges.xaxis.from ) || ( g_tZoomRange.xaxis.max != tRanges.xaxis.to ) ) )
        {
            // Set range
            zoomRangeSet( tRanges.xaxis.from, tRanges.xaxis.to );
            debugAddLine( "Scroll to range=" + zoomRangeToString() );

            // Set flag to control redraw of scroller
            tEvent.bScrollerUpToDate = ! zoomRangeIsNull();

            // Update zoom range highlight in overview plot
            zoomRangeHighlight( tEvent );

            // Redraw the main plot
            plotDraw( tEvent );
        }
    }
}

// Clear pan cursor at end of pan stroke
function plotClearPanCursor( tEvent )
{
    $("#plotview").css( "cursor", "pointer" )
}

// Update zoom range in overview plot
function zoomRangeHighlight( tEvent )
{
    debugAddLine( "==>zoomRangeHighlight()" );
    // If we have not already processed this event...
    if ( tEvent.timeStamp != g_tEventTimeStamps.zoomRangeHighlight )
    {
        // Save timestamp for next time
        g_tEventTimeStamps.zoomRangeHighlight = tEvent.timeStamp;

        // If plot is zoomed in, show the selection in the overview
        if ( ! zoomRangeIsNull() )
        {
            var tRange = new Object();
            tRange.xaxis = new Object();
            tRange.xaxis.from = g_tZoomRange.xaxis.min;
            tRange.xaxis.to = g_tZoomRange.xaxis.max;
            debugAddLine( "Set ovw sel range=" + zoomRangeToString() );
            debugAddLine( "==>setSelection()" );
            g_tOverview.setSelection( tRange );
            if ( ! tEvent.bScrollerUpToDate )
            {
                g_tScrollbar.setScroller( tRange, true );
            }
        }
    }
}

// Set global zoom range object
function zoomRangeSet( fMin, fMax )
{
    debugAddLine( "zoomRangeSet() range bf " + zoomRangeToString() );

    if ( ( fMin === null ) || ( ( g_tPlot.getSelection() == null ) && ( g_tOverview.getSelection() == null ) ) || ( fMax <= fMin ) )
    {
        g_tZoomRange = null;
    }
    else
    {
        var iMinIndex = null;
        var iMaxIndex = null;
        var aData = g_aSeries[0];
        var iPoint = 0;

        // Locate lower bound
        for ( iPoint = 0; ( iPoint < aData.length ) && ( iMinIndex == null ); iPoint ++ )
        {
            if ( fMin <= aData[iPoint][0] )
            {
                iMinIndex = iPoint;
            }
        }

        // Locate upper bound
        for ( iPoint = aData.length - 1; ( iPoint >= 0 ) && ( iMaxIndex == null ); iPoint -- )
        {
            if ( fMax >= aData[iPoint][0] )
            {
                iMaxIndex = iPoint;
            }
        }

        // Load zoom range if wide enough
        var fRangeZoom = fMax - fMin;
        var fRangeOverview = g_tOverview.getXAxes()[0].max - g_tOverview.getXAxes()[0].min;
        var fRangePixels = $( "#overview" ).width() * fRangeZoom / fRangeOverview;
        if ( fRangePixels > 5 )
        {
          g_tZoomRange =
          {
              xaxis:
              {
                  min: fMin,
                  max: fMax,
                  minindex: iMinIndex,
                  maxindex: iMaxIndex
              }
          };
        }
    }

    debugAddLine( "zoomRangeSet() range af " + zoomRangeToString() );
}

// Is zoom range null?  Occurs when plot is zoomed out.
function zoomRangeIsNull()
{
    return g_tZoomRange == null;
}

// Return string representing current zoom range, for debug
function zoomRangeToString()
{
    sRange = "(";

    if ( zoomRangeIsNull() )
    {
        sRange += "null";
    }
    else
    {
        sRange += g_tZoomRange.xaxis.min;
        sRange += ",";
        sRange += g_tZoomRange.xaxis.max;
    }

    sRange += ")";

    return sRange;
}

function showTooltip( tItem )
{
    var tSeries = tItem.series;
    var sLabel = ( tSeries.tooltip || tSeries.label );
    var sTime =  formatTimestamp( tItem.datapoint[0] );
    var sValue = makeInterpolation( tItem.datapoint[1], tSeries.yaxis.options.tickDecimals );
    var sContent = "<div>" + sLabel + "</div><div>&nbsp;[" + sTime + "] &nbsp;<b>" + sValue + "</b>&nbsp;</div>";

    $( '<div id="tooltip">' + sContent + '</div>' ).css
    (
        {
            position: 'absolute',
            display: 'none',
            top: tItem.pageY + 10,
            left: tItem.pageX + 15,
            padding: '2px',
            'background-color': tSeries.color,
            color: 'white',
            opacity: 0.80
        }
    ).appendTo( "body" ).fadeIn( 200 );
}

// Update the legend
function legendUpdate()
{
    // Clear the timeout ID
    g_iLegendUpdateTimeout = null;

    // Find any series that is currently in the display
    var sYaxisPrefix = "";
    for ( var sCoord in g_tMousePosition )
    {
        if ( ( sCoord.length >= 2 ) && ( sCoord.indexOf( "y" ) == 0 ) )
        {
            // Save prefix of series that is in the display
            sYaxisPrefix = sCoord;
            break;
        }
    }

    if ( sYaxisPrefix != "" )
    {
        // Account for inconsistency in Flot data structures:
        // - In mouse position, "y1" is present as a duplicate of "y"
        // - In axes, "y1" is not present
        if ( sYaxisPrefix == "y1" )
        {
            sYaxisPrefix = "y";
        }

        // Determine mouse position and plot bounds
        var tAxes = g_tPlot.getAxes();
        var iMouseX = g_tMousePosition.x;
        var tXaxis = tAxes.xaxis;
        var iMouseY = g_tMousePosition[sYaxisPrefix];
        var tYaxis = tAxes[sYaxisPrefix + "axis"];

        // If mouse position lies within plot bounds, update the legend
        if ( ( iMouseX >= tXaxis.min ) && ( iMouseX <= tXaxis.max ) && ( iMouseY >= tYaxis.min ) && ( iMouseY <= tYaxis.max ) )
        {
            // Mouse position lies within plot bounds

            // Get array of series displayed by the plot
            var aDataSet = g_tPlot.getData();

            // Iterate through the plot's series
            for ( var iDataSet = 0; iDataSet < aDataSet.length; ++iDataSet )
            {
                // Get current series
                var tSeries = aDataSet[iDataSet];

                // Find index of X coordinate nearest to mouse position
                var iCoord;
                for ( iCoord = 0; ( iCoord < tSeries.data.length ) && ( tSeries.data[iCoord][0] <= g_tMousePosition.x ) ; ++ iCoord )
                {
                    // Do nothing
                }

                // Interpolate
                var aPoint1 = tSeries.data[iCoord-1];
                var aPoint2 = tSeries.data[iCoord];
                var nY;
                if ( aPoint1 == null )
                {
                    nY = aPoint2[1];
                }
                else if ( aPoint2 == null )
                {
                    nY = aPoint1[1];
                }
                else
                {
                    nY = aPoint1[1] + ( aPoint2[1] - aPoint1[1] ) * ( g_tMousePosition.x - aPoint1[0] ) / ( aPoint2[0] - aPoint1[0] );
                }

                // Update the legend text for the current series
                var equal = ( tSeries.label.trim() == "" ) ? "" : "=";
                $("#plotview .legendLabel").eq( iDataSet ).text( tSeries.label + equal + makeInterpolation( nY, tSeries.yaxis.tickDecimals ) );
            }
        }
    }
}

function makeInterpolation( nY, nDecimals )
{
  // Get optional cost factor
  var sCost = $( "#cost" ).val();
  var nCost = Number( sCost );

  // Set up string representing dollar cost
  var sDollars = "";
  if ( ( sCost != "" ) && ! isNaN( nCost ) )
  {
    var nDollars = nY * nCost;
    sDollars = ( ", $" + ( nDollars ).toFixed( ( nDollars < 10 ) ? 2 : 0 ) );
  }

  // Format and return string representing interpolated value
  var sValue = nY.toFixed( nDecimals ) + sDollars;
  return sValue;
}

// Clear the plot chooser
function chooserClear()
{
    $("#seriesChooser").empty();
    g_aChoosers = [];
}

// Add a checkbox to the series chooser
function chooserAdd( aSamples, iSample )
{
    var tSample = aSamples[iSample];

    // Create the checkbox
    var sLabel = tSample.label;
    var sName = g_aNicknameMap[sLabel] || "";
    var sId = "chk_" + sLabel;
    var sChooser =
      '<li>'
      +
        '<label class="checkbox checkbox-inline" title="' + sName + '" >'
      +
          '<input type="checkbox" checked="checked" name="' + sId + '" id="' + sId + '" >'
      +
          '<svg width="12" height="12">'
      +
            '<rect width="12" height="12" style="fill:' + g_aColors[iSample] + '; stroke-width:1; stroke:black" />'
      +
          '</svg> '
      +
          '<span class="bg-info" columnName >'
      +
            sLabel
      +
          '</span>'
      +
        '</label>'
      +
      '</li>';

    $("#seriesChooser").append( sChooser );

    // Save attributes needed to plot the associated series
    $( "#seriesChooser" ).disableTextSelect();
    var tCheckBox = document.getElementById( sId );
    tCheckBox.onclick = onCheckboxClick;
    tCheckBox.onchange = plotFilter;
    tCheckBox.series = iSample;
    tCheckBox.yaxis = iSample + 1;
    tCheckBox.color = iSample;
    tCheckBox.tick = tSample.tick;
    tCheckBox.tickDecimals = tSample.tickDecimals;
    tCheckBox.label = sLabel;
    var sFormatterName = 'formatYaxis' + iSample;
    eval( sFormatterName+'=function(v,axis){return "<span style=\\"color:"+g_tOptionsPlot.colors[axis.options.tickColor]+"\\">"+v.toFixed(axis.tickDecimals)+"'+tSample.tick+'"+"</span>";};');
    tCheckBox.tickFormatter = eval( sFormatterName );

    // Save checkbox in array of choosers
    g_aChoosers.push( tCheckBox );
}

// Copy ticks generated for main plot to array of Y axis options objects, for use by other plots
function copyPlotTicksTo( aYaxes )
{
    var aAxes = g_tPlot.getAxes();
    var bShowYaxisTicks = $( "#showYaxisTicks" ).prop( "checked" );

    // Iterate through array of Y axis options objects
    for ( var iYaxis = 0; iYaxis < this.length; iYaxis++ )
    {
        // Get ticks array that main plot is showing for the current Y axis
        var sAxisName = "y" + ( ( iYaxis == 0 ) ? "" : ( parseInt( iYaxis ) + 1 ) ) + "axis";

        // Copy ticks data to options object for the current Y axis
        var aTicks = aAxes[sAxisName].ticks;
        if ( aTicks )
        {
          aYaxes[iYaxis].reserveSpace = bShowYaxisTicks;
          aYaxes[iYaxis].ticks = new Array();
          for ( var iTick = 0; iTick < aTicks.length; iTick ++ )
          {
              aYaxes[iYaxis].ticks.push( [ aTicks[iTick].v, aTicks[iTick].label ] );
          }
        }
        else
        {
          // Main plot not using this y-axis; don't reserve space for it
          aYaxes[iYaxis].reserveSpace = false;
        }
    }

    return aYaxes;
}

// Read data from opened plot file
function plotRead( aPlotOpenData )
{
    try
    {
        // Extract cell names
        var aNames = aPlotOpenData.shift();

        // Determine location of time data in samples
        var iIndexTime = indexOf( aNames, "time" );
        if ( iIndexTime == -1 )
        {
            // Can't proceed without knowing where the timestamps are
            return false;
        }

        // Group samples into sets, according to common timestamp
        var aSampleSets = [];
        var iSample = 0;
        var iSet = 0;
        while ( iSample < aPlotOpenData.length )
        {
            // Initialize empty sample set
            aSampleSets[iSet] = [];

            // Retrieve timestamp of current sample set
            var iTimestamp = parseInt( aPlotOpenData[iSample][iIndexTime] );
            if ( isNaN( iTimestamp ) )
            {
                return false;
            }

            // Check integrity of array lengths
            if ( aPlotOpenData[iSample].length != aNames.length )
            {
                return false;
            }

            // Load sample set
            while( ( iSample < aPlotOpenData.length ) && ( aPlotOpenData[iSample][iIndexTime] == iTimestamp ) )
            {
                // Convert sample array to object using cell names
                var tSample = {};
                for ( var iIndex = 0; iIndex < aNames.length; iIndex ++ )
                {
                    tSample[aNames[iIndex]] = aPlotOpenData[iSample][iIndex];
                }

                // Add sample object to sample set
                aSampleSets[iSet].push( tSample );

                // Advance to next data sample
                iSample ++;
            }

            // Increment count of sample sets
            iSet ++;
        }


        // Generate colors for this plot
        g_aColors = generateColors( aSampleSets[0].length );
        g_tOptionsPlot.colors = g_aColors;
        g_tOptionsOverview.colors = g_aColors;

        // Load array of sample sets into plot data structure (but do not draw)
        for ( var iSet = 0; iSet < aSampleSets.length; iSet ++ )
        {
            plotSample( aSampleSets[iSet] );
        }

        $( "#totalPoints" ).text( aPlotOpenData.length.toLocaleString() );

        // Draw the plot
        plotDraw( { type: "plotRead" } );

        // Success
        return true;
    }
    catch ( exception )
    {
        // Could not decipher plot data
        return false;
    }
}

// Initialize checkbox accelerator controls
function checkboxAcceleratorsInit()
{
  $( "#seriesCheckAll" ).click( seriesCheckAll );
  $( "#seriesCheckNone" ).click( seriesCheckNone );
  $( "#seriesCheckComplement" ).click( seriesCheckComplement );
  $( "#seriesSort" ).change( seriesSort );
}

// Enable/disable down-sample pattern controls
function downSampleControlsEnable( tEvent )
{
  $( "#plotControls .has-error" ).removeClass( "has-error" );

  // Enable/disable crop- and zoom-related buttons
  $( "#plotCropIn" ).prop( "disabled", zoomRangeIsNull() );
  $( "#plotZoomOut" ).prop( "disabled", zoomRangeIsNull() );
  $( "#plotCropOut" ).prop( "disabled", g_aCropStack.length == 0 );

  var bAuto = $( "#downSampleAuto" ).prop( "checked" );
  var bManual = $( "#downSampleManual" ).prop( "checked" );

  $( "#density" ).css( "display", bAuto ? "block" : "none" );
  $( "#pattern" ).css( "display", bManual ? "block" : "none" );


  $( "#downSampleDensity" ).val( "" );
  $( "#downSampleShow" ).val( "" );
  $( "#downSampleHide" ).val( "" );
  $( "#downSampleOffset" ).val( "" );

}

// Find index of item in array
function indexOf( aArray, tItem )
{
    var iIndex;

    if ( Array.prototype.indexOf )
    {
        // JavaScript version supports Array.indexOf()
        iIndex = aArray.indexOf( tItem );
    }
    else
    {
        // Define minimal Array.indexOf() functionality for IE8, which uses older JavaScript interpreter
        iIndex = 0;
        while ( ( iIndex < aArray.length ) && ( aArray[iIndex] != tItem ) )
        {
            iIndex ++;
        }

        if ( iIndex == aArray.length )
        {
            // Not found
            iIndex = -1;
        }
    }

    return iIndex;
}

// Optionally down-sample the data, especially for browsers (IE8 and lower) that can not handle large data sets
function dataDownSample( aSeries )
{
    // Automatically configure down-sampling pattern
    downSampleAutoConfig( aSeries.length );

    g_bShowLastPoint = true;
    if ( ( g_iDownSampleOffset == 0 ) && ( g_iDownSampleHide == 0 ) )
    {
        // Nothing to hide; return the original data
        return aSeries;
    }
    else
    {
        // Down-sample according to specified pattern
        var aDown = [];
        var iPoint = g_iDownSampleOffset;
        g_bShowLastPoint = false;

        // Show/hide
        while ( iPoint < aSeries.length )
        {
            // Add the next sequence of points that should be shown
            var iShow = 0;
            while ( ( iShow < g_iDownSampleShow ) && ( iPoint < aSeries.length ) )
            {
                aDown.push( aSeries[iPoint] );
                iShow ++;
                iPoint ++;
                g_bShowLastPoint = true;
            }

            // Skip the next sequence of points that should be hidden
            if ( ( g_iDownSampleHide > 0 ) && ( iPoint < aSeries.length ) )
            {
                iPoint = Math.min( iPoint + g_iDownSampleHide, aSeries.length );
                g_bShowLastPoint = false;
            }
        }

        return aDown;
    }
}

// Crop the data to be drawn
function dataCrop( aSeries )
{
  var aCrop = null;

  if ( g_aCropStack.length == 0 )
  {
    aCrop = aSeries;
  }
  else
  {
    var cropTop = g_aCropStack[g_aCropStack.length-1];
    aCrop = aSeries.slice( cropTop.minindex, cropTop.maxindex + 1 );
  }

  return aCrop;
}

function plotDownSample( bReset )
{
    $( "#plotControls .has-error" ).removeClass( "has-error" );

    // Save the mode
    g_sDownSampleMode = $( "#plotControls input:radio[name='downSampleMode']:checked" ).val();

    // Get 'offset' input
    var tOffset = document.getElementById( "downSampleOffset" );
    var sOffset = tOffset.value = tOffset.value.trim();
    var iOffset = ( sOffset == "" ) ? g_iDownSampleOffset : parseInt( sOffset );
    var bValidOffset = /^\d*$/.test( sOffset ) && ! isNaN( iOffset ) && ( iOffset >= 0 );
    if ( ! bValidOffset )
    {
      $("#downSampleOffset").parent().addClass( "has-error" );
    }

    // Get 'density' input
    var tDensity = document.getElementById( "downSampleDensity" );
    var sDensity = tDensity.value = tDensity.value.trim();
    var iDensity = ( sDensity == "" ) ? g_iDownSampleDensity : parseInt( sDensity );
    var bValidDensity = ( g_sDownSampleMode != DOWNSAMPLEMODE_AUTO ) || ( /^\d*$/.test( sDensity ) && ! isNaN( iDensity ) && ( iDensity >= 1 ) );
    if ( ! bValidDensity )
    {
      $("#downSampleDensity").parent().addClass( "has-error" );
    }

    // Get 'show' input
    var tShow = document.getElementById( "downSampleShow" );
    var sShow = tShow.value = tShow.value.trim();
    var iShow = ( sShow == "" ) ? g_iDownSampleShow : parseInt( sShow );
    var bValidShow = /^\d*$/.test( sShow ) && ! isNaN( iShow ) && ( iShow >= 1 );
    if ( ! bValidShow )
    {
      $("#downSampleShow").parent().addClass( "has-error" );
    }

    // Get 'hide' input
    var tHide = document.getElementById( "downSampleHide" );
    var sHide = tHide.value = tHide.value.trim();
    var iHide = ( sHide == "" ) ? g_iDownSampleHide : parseInt( sHide );
    var bValidHide = /^\d*$/.test( sHide ) && ! isNaN( iHide ) && ( iHide >= 0 );
    if ( ! bValidHide )
    {
      $("#downSampleHide").parent().addClass( "has-error" );
    }

    // If inputs are valid, update view
    if ( bValidOffset && bValidDensity && bValidShow && bValidHide )
    {
        tDensity.value = "";
        tOffset.value = "";
        tShow.value = "";
        tHide.value = "";

        if ( bReset || ( iDensity != g_iDownSampleDensity ) || ( iOffset != g_iDownSampleOffset ) || ( iShow != g_iDownSampleShow ) || ( iHide != g_iDownSampleHide ) )
        {
            // We are resetting, or down-sample parameters have changed
            g_iDownSampleDensity = ( g_sDownSampleMode == DOWNSAMPLEMODE_AUTO ) ? iDensity: 0;
            g_iDownSampleOffset = iOffset;
            g_iDownSampleShow = iShow;
            g_iDownSampleHide = iHide;

            // Optionally redraw plot
            if ( g_aSeries != null )
            {
              plotDraw( { type: "plotDownSample" } );
            }
        }
    }
}

// Allow user to change down-sample settings by pressing <enter> in show/hide text fields
function clickDownSampleButton( tEvent )
{
    // If user pressed <Enter>, update down-sample settings
    if ( tEvent.keyCode == 13 )
    {
        plotDownSample();
    }
}




function plotCropIn()
{
  if ( ! zoomRangeIsNull() )
  {
    g_aCropStack.push(
      {
        minindex: g_tZoomRange.xaxis.minindex,
        maxindex: g_tZoomRange.xaxis.maxindex
      }
    );

    zoomRangeSet( null );
  }

  plotDraw( { type: "plotCropIn" } );
}

function plotCropOut()
{
  g_aCropStack.pop();

  plotDraw( { type: "plotCropOut" } );
}

function plotReset()
{
  g_aCropStack = [];
  zoomRangeSet( null );

  $( "#downSampleAuto" ).prop( "checked", true );
  $( "#downSampleManual" ).prop( "checked", false );
  $( "#downSampleDensity" ).val( 100 );
  $( "#downSampleShow" ).val( "" );
  $( "#downSampleHide" ).val( "" );
  $( "#downSampleOffset" ).val( 0 );

  plotDownSample( true );
}

// Handle change of zoom detail checkbox
function downSampleZoomChanged()
{
    // Optionally redraw plot
    if ( ( g_aSeries != null ) && ! zoomRangeIsNull() && ( g_iDownSampleHide > 0 ) )
    {
        plotDraw( { type: "downSampleZoomChanged" } );
    }
}

// Automatically set down-sample pattern according to browser and data size
function downSampleAutoConfig( iSampleTotal )
{
    if ( g_sDownSampleMode == DOWNSAMPLEMODE_AUTO )
    {
        // Set the Show parameter
        g_iDownSampleShow = 1;

        // Set the Hide parameter
        g_iDownSampleHide = 0;
        var iSamples = iSampleTotal - g_iDownSampleOffset;
        while ( ( iSamples / ( g_iDownSampleShow + g_iDownSampleHide ) ) > g_iDownSampleDensity )
        {
            g_iDownSampleHide ++;
        }
    }
}

// Update plot status display
function downsampleStatusUpdate( iShowing, iOf )
{
  $( "#downSampleShowing" ).text( iShowing.toLocaleString() );
  $( "#downSampleOf" ).text( iOf.toLocaleString() );

  $( "#downSampleDensity_current" ).text( g_iDownSampleDensity );
  $( "#downSampleShow_current" ).text( g_iDownSampleShow );
  $( "#downSampleHide_current" ).text( g_iDownSampleHide );
  $( "#downSampleOffset_current" ).text( g_iDownSampleOffset );

  $( "#densityCurrent" ).css( "display", ( g_sDownSampleMode == DOWNSAMPLEMODE_AUTO ) ? "inline" : "none" );
}

function zoomStatusUpdate()
{
  var timestampFrom = "";
  var timestampTo = "";

  if ( zoomRangeIsNull() || ( g_aData.length == 0 ) )
  {
    var tXaxis = g_tPlot.getXAxes()[0];
    timestampFrom = tXaxis.datamin;
    timestampTo = tXaxis.datamax;
  }
  else
  {
    timestampFrom = g_tZoomRange.xaxis.min;
    timestampTo = g_tZoomRange.xaxis.max;
  }

  $( "#timestampFrom" ).text( formatTimestamp( timestampFrom ) );
  $( "#timestampTo" ).text( formatTimestamp( timestampTo ) );
}

function formatTimestamp( timestamp )
{
  var tDate = new Date( timestamp );

  var sFormat = "";
  sFormat += [ "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat" ][ tDate.getDay() ];
  sFormat += ", ";
  sFormat += [ "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ][ tDate.getMonth() ];
  sFormat += " ";
  var iDate = tDate.getDate();
  sFormat += ( ( iDate < 10 ) ? "0" : "" ) + iDate;
  sFormat += " ";
  sFormat += tDate.getFullYear();
  if ( $( "#reportFormat" ).val() == "Detailed" )
  {
    sFormat += ", ";
    var iHours = tDate.getHours();
    sFormat += ( ( iHours < 10 ) ? "0" : "" ) + iHours;
    sFormat += ":";
    var iMinutes = tDate.getMinutes();
    sFormat += ( ( iMinutes < 10 ) ? "0" : "" ) + iMinutes;
  }

  return sFormat;
}




// --> Color generation -->

// Generate N unique colors
function generateColors( iColors, bDebug )
{
  var aColorNames =
  [
    // "red",
    // "green",
    // "blue",
    // "purple",
    // "fuchsia",
    // "#d2691e" /*chocolate*/,
    // "#008080", /*teal*/
    // "#696969" /*dimgray*/,
    // "#00bfff" /*deepskyblue*/,
  ];

  var tColor = null;
  var aColors = [];

  //
  // Generate colors
  //
  var iAngle = 360 / ( iColors - 1 );

  for ( var iColor = 0; iColor < ( iColors - 1 ) ; iColor ++ )
  {
    if ( iColors <= ( aColorNames.length + 1 ) )
    {
      sColor = aColorNames[iColor];

      tColor =
        bDebug ?
          {
            color: sColor,
            idx: 0,
            ang: 0,
            sat: 0,
            val: 0,
            drk: 0
          }
        :
          sColor;
    }
    else
    {
      // Initialize saturation and value
      var iSaturation = 100;
      var iValue = 100;

      // If generating numerous colors, vary saturation and value to add further distinction
      if ( iColors > 12 )
      {
        switch( iColor % 4 )
        {
          case 0:
          iSaturation = 100;
          iValue = 63;
          break;

          case 1:
          iSaturation = 90;
          iValue = 78;
          break;

          case 2:
          iSaturation = 80;
          iValue = 90;
          break;

          case 3:
          iSaturation = 70;
          iValue = 100;
          break;
        }
      }
      else if ( iColors > 6 )
      {
        switch( iColor % 2 )
        {
          case 0:
          iSaturation = 100;
          iValue = 80;
          break;

          case 1:
          iSaturation = 80;
          iValue = 100;
          break;
        }
      }

      var iHue = iAngle * iColor
      var sColor = hsvToRgb( iHue, iSaturation, iValue );
      var nDarker = 0;
      if ( iHue >= 30 && iHue <= 100 )
      {
        nDarker = -0.3;
        sColor = colorLuminance( sColor, nDarker );
      }

      tColor =
        bDebug ?
          {
            color: sColor,
            idx: iColor,
            ang: Math.floor( iAngle ),
            sat: iSaturation,
            val: iValue,
            drk: nDarker
          }
        :
          sColor;
    }

    aColors.push( tColor );
  }

  aColors.unshift(
    bDebug ?
      {
        color:"black",
        idx: 0,
        ang: 0,
        sat: 0,
        val: 0,
        drk: 0
      }
    :
      "black"
  );

  return aColors;
}

// Convert HSV color representation to RGB
function hsvToRgb(h, s, v)
{
  var r, g, b;
  var i;
  var f, p, q, t;

  // Make sure our arguments stay in-range
  h = Math.max(0, Math.min(360, h));
  s = Math.max(0, Math.min(100, s));
  v = Math.max(0, Math.min(100, v));

  // We accept saturation and value arguments from 0 to 100 because that's
  // how Photoshop represents those values. Internally, however, the
  // saturation and value are calculated from a range of 0 to 1. We make
  // That conversion here.
  s /= 100;
  v /= 100;

  if(s == 0)
  {
    // Achromatic (grey)
    r = g = b = v;
    return [Math.round(r * 255), Math.round(g * 255), Math.round(b * 255)];
  }

  h /= 60; // sector 0 to 5
  i = Math.floor(h);
  f = h - i; // factorial part of h
  p = v * (1 - s);
  q = v * (1 - s * f);
  t = v * (1 - s * (1 - f));

  switch(i)
  {
    case 0:
    r = v;
    g = t;
    b = p;
    break;

    case 1:
    r = q;
    g = v;
    b = p;
    break;

    case 2:
    r = p;
    g = v;
    b = t;
    break;

    case 3:
    r = p;
    g = q;
    b = v;
    break;

    case 4:
    r = t;
    g = p;
    b = v;
    break;

    default: // case 5:
    r = v;
    g = p;
    b = q;
  }

  return "#" + addLeadingZeros( ( ( Math.round( r * 255 ) << 16 ) + ( Math.round(g * 255) << 8 ) + Math.round(b * 255) ).toString(16), 6 );
}

// Pad value with leading zeroes
function addLeadingZeros( iNumber, iLength )
{
  // Default to 4 for most common use, which is 4-digit equipment ID
  if ( typeof iLength == "undefined" )
  {
    iLength = 4;
  }

  var sNumber  = '' + iNumber;
  while ( sNumber.length < iLength )
  {
    sNumber = '0' + sNumber;
  }

  return sNumber;
}

function colorLuminance( hex, lum )
{
	// validate hex string
	hex = String(hex).replace(/[^0-9a-f]/gi, '');
	if (hex.length < 6)
  {
		hex = hex[0]+hex[0]+hex[1]+hex[1]+hex[2]+hex[2];
	}
	lum = lum || 0;

	// convert to decimal and change luminosity
	var rgb = "#", c, i;
	for (i = 0; i < 3; i++)
  {
		c = parseInt(hex.substr(i*2,2), 16);
		c = Math.round(Math.min(Math.max(0, c + (c * lum)), 255)).toString(16);
		rgb += ("00"+c).substr(c.length);
	}

	return rgb;
}

// <-- Color generation <--
