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


//////////////////////////////////////////////////////////////////////
// ---> Global variables used by the Spectrum Analyzer display ---> //
//////////////////////////////////////////////////////////////////////

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
var g_bPlotSuspended = false;
var g_bShowLastPoint = true;
var g_iSeriesLengthAtSuspendTime = null;
var g_bDownSampleAuto = null;

var g_tEventTimeStamps =
{
    plotSuspend: 0,
    plotPan: 0,
    plotHover: 0,
    plotZoomIn: 0,
    zoomRangeHighlight: 0,
    plotScroll: 0
};

// Series colors, common to both plots
var g_aColors = [ "red", "green", "blue", "purple", "fuchsia" ];

// Main plot options
var g_tOptionsPlot =
{
    xaxis:
    {
        mode: 'time'
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
    colors: g_aColors,
    crosshair:
    {
        mode: "x"
    },
    grid:
    {
        hoverable: true
    },
    pan:
    {
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
        mode: 'time'
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
    colors: g_aColors,
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

//////////////////////////////////////////////////////////////////////
// <--- Global variables used by the Spectrum Analyzer display <--- //
//////////////////////////////////////////////////////////////////////


// Show initial plot, either empty, or initialized with data from opened plot file
function plotInit( aPlotOpenData )
{
    // Enable/disable down-sample pattern controls
    downSampleControlsEnable();

    // Initialize down-sample status
    downSampleStatusUpdate( 0, 0 );

    // Optionally initialize with data from opened plot file
    var bShowPlotOpenData = false;
    if ( typeof aPlotOpenData != "undefined" )
    {
        if ( ! ( bShowPlotOpenData = plotRead( aPlotOpenData ) ) )
        {
            // File open failed; update page status
            $("#pagestatus").attr( "className", "pageerror" );
            $("#pagestatus").text( "Could not decipher Plot File data" );
        }
    }

    // If no data from opened file (or decipher of file data failed), show empty plot
    if ( ! bShowPlotOpenData )
    {
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
    }

    // Enable/disable controls
    plotButtonEnable( "DownSample", true );
}

// Add a set of samples to the plot
function plotSample( aSamples, bLive )
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

        // Update page status line with plot start time
        var tPlotStartTime = tDate;
        if ( typeof ( aSamples[0].time ) != "undefined" )
        {
            tPlotStartTime = new Date( aSamples[0].time );
        }
        $("#pagestatus").attr( "className", "pageinfo" );
        $("#pagestatus").text( "Plot " + ( bLive ? "" : "(loaded from file) " ) + "started at " + $.plot.formatDate( tPlotStartTime, "%m/%d/%y %H:%M:%S" ) + " UTC" );
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

    // If this is a live sample, and plot update is not suspended, update the display
    if ( bLive && ! g_bPlotSuspended )
    {
        plotDraw( { type: "plotSample" } );
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
    for ( var iChoice = 0; iChoice < aChoices.length; iChoice ++ )
    {
        // Get chooser checkbox for the current series
        var tChoice = aChoices[iChoice];

        if ( tChoice.checked )
        {
            var aData = dataDownSample( g_aSeries[tChoice.series] );

            if ( aData.length > 0 )
            {
                // Associate label, axis index, and color with current series
                g_aData.push( { data: aData, label: tChoice.label, yaxis: tChoice.yaxis, color: tChoice.color } );
                aDataFull.push( { data: g_aSeries[tChoice.series], label: tChoice.label, yaxis: tChoice.yaxis, color: tChoice.color } );

                // Associate options with current series
                aYaxesPlot[tChoice.series] =
                {
                    tickFormatter: tChoice.tickFormatter,
                    tickDecimals: tChoice.tickDecimals,
                    tickColor: tChoice.color,
                    panRange: false
                };

                // Add some options for the navigation (overview and scrollbar) plots
                aYaxesNav[tChoice.series] = jQuery.extend( { show: false, reserveSpace: true }, aYaxesPlot[tChoice.series] );
            }
        }
    }

    // Update down-sample status
    downSampleStatusUpdate( ( g_aData.length == 0 ) ? 0 : g_aData[0].data.length, ( aDataFull.length == 0 ) ? 0 : aDataFull[0].data.length );

    // If handling a new data sample, and plot is zoomed, automatically pan plot to the right
    var iAutoPanDelta = 0;
    if ( ( tEvent.type == "plotSample" ) && ! zoomRangeIsNull() )
    {
        iAutoPanDelta = plotAutoPan();
    }

    // Determine whether to show full data in zoomed view of down-sampled plot
    var bZoomFull = ! ( document.getElementById( "downSampleZoom" ).checked );

    // Suppress display update on new sample that is hidden due to down-sampling (but not if automatic down-sampling changed Hide setting)
    if ( ( tEvent.type != "plotSample" ) || g_bShowLastPoint || ( iHidePrev != g_iDownSampleHide ) || ( ( iAutoPanDelta > 0 ) && bZoomFull ) )
    {
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
            // Draw full plot with no panning
            g_tOptionsPlot.pan.interactive = false;
            g_tOptionsPlot.xaxis.panRange = false;
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
            // Draw zoomed plot with panning
            g_tOptionsPlot.pan.interactive = true;
            var aPoints = g_aData[0].data;
            g_tOptionsPlot.xaxis.panRange = [ aPoints[0][0], aPoints[aPoints.length-1][0] ];
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
    }

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
        $("#plotview").bind( "plothover", plotHover );

        // Bind pan of main plot pan to pan of overview zoom range
        $("#plotview").bind( 'plotpan', plotPan );

        ///////////////////
        // Overview plot //
        ///////////////////

        // Bind start of zoom interval selection in overview to suspension of live update
        $("#overview").bind( "mousedown", plotSuspend );

        // Ensure that mouse click does not suspend live update
        $("#overview").bind( "mouseup", plotResume );

        // Bind overview selection to main plot zoom in
        $("#overview").bind( "plotselected", plotZoomIn );

        // Bind overview deselection to main plot zoom out
        $("#overview").bind( "plotunselected", plotZoomOut );

        // Bind overview resize to redraw of selected zoom range
        $("#overview").bind( "resize", zoomRangeHighlight );

        ////////////////////
        // Scrollbar plot //
        ////////////////////

        // Bind start of scroll stroke to suspension of live update
        $("#scrollbar").bind( "plotscrollstart", plotScrollStart );

        // Bind scroller movement to pan in overview and main plots
        $("#scrollbar").bind( "plotscroll", plotScroll );

        // Bind end of scroll stroke to resumption of live update
        $("#scrollbar").bind( "plotscrollend", plotResume );
    }
}

// Flot hook function to bind custom handlers to main plot events
function plotBindEvents( tPlot, tEventHolder )
{
    tEventHolder.bind( "dragend", plotClearPanCursor );
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

// Suspend live update (when the user starts selecting or scrolling a zoom interval)
function plotSuspend( tEvent )
{
    // If we have not already processed this event...
    if ( tEvent.timeStamp != g_tEventTimeStamps.plotSuspend )
    {
        // Save timestamp for next time
        g_tEventTimeStamps.plotSuspend = tEvent.timeStamp;

        // Suppress live update of plot, if all of the following are true:
        // - User clicked left mouse button
        // - At least one series is selected in the plot chooser
        // - Overview plot contains something to select
        if ( ( tEvent.which == 1 ) && ( g_aData.length > 0 ) && ( g_tOverview != null ) )
        {
            debugAddLine( "Suspend: range=" + zoomRangeToString() );
            g_bPlotSuspended = true;

            // Save series length for use in automatic pan
            g_iSeriesLengthAtSuspendTime = g_aSeries[0].length;
        }
    }
}

// Resume live update of plot
function plotResume()
{
    debugAddLine( "Resume (was" + ( g_bPlotSuspended ? "" : " not" ) + " suspended): range=" + zoomRangeToString() );
    g_bPlotSuspended = false;
}

// Filter plot based on selections made in plot chooser
function plotFilter()
{
    plotDraw( { type: "plotFilter" } );
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
            plotResume();
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
        plotResume();
        plotDraw( tEvent );
    }
}

// Automatically pan zoom range to right
function plotAutoPan()
{
    var iDelta = 0;

    // Get current data
    var aData = g_aSeries[0];
    var iLastIndexNew = aData.length - 1;
    var iLastIndexOld = iLastIndexNew - 1;

    // Get bounds of current zoom range
    iMinIndex = g_tZoomRange.xaxis.minindex;
    iMaxIndex = g_tZoomRange.xaxis.maxindex;

    // If current zoom range was previously panned to right edge of plot...
    debugAdd( "plotAutoPan() (" + iMinIndex + "," + iMaxIndex + ")" );
    if ( ( iMinIndex < iLastIndexOld ) && ( ( ( g_iSeriesLengthAtSuspendTime != null ) && ( iMaxIndex >= ( g_iSeriesLengthAtSuspendTime - 1 - g_iDownSampleHide ) ) ) || ( iMaxIndex >= ( iLastIndexOld - g_iDownSampleHide ) ) ) )
    {
        // ... automatically advance pan to new right edge of plot
        iDelta = iLastIndexNew - Math.max( iMinIndex, iMaxIndex );
        zoomRangeSet( aData[iMinIndex + iDelta][0], aData[iMaxIndex + iDelta][0] );
        debugAdd( "->(" + ( iMinIndex + iDelta ) + "," + ( iMaxIndex + iDelta ) + ")" );
    }
    debugAddLine();

    // Clear suspend-time information
    g_iSeriesLengthAtSuspendTime = null;

    return iDelta;
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
    else
    {
        // Artificially indicate left mouse button
        tEvent.which = 1;

        // Suspend live update
        plotSuspend( tEvent );
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

    if ( ( fMin === null ) || ( g_tOverview.getSelection() == null ) || ( fMax <= fMin ) )
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

        // Load zoom range
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
    var sContent = tSeries.label + "=" + tItem.datapoint[1].toFixed( tSeries.yaxis.options.tickDecimals );

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
                $("#plotview .legendLabel").eq( iDataSet ).text( tSeries.label + "=" + nY.toFixed( tSeries.yaxis.tickDecimals ) );
            }
        }
    }
}

// Handle change of checkbox state
function autoStopChkChanged()
{
    // Enable period field only when checkbox is checked
    controlEnable( "autoStopPeriod", document.getElementById( "autoStop" ).checked );
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
    var sId = "chk_" + sLabel;
    var sCheckBox = '<input type="checkbox" checked="checked" name="' + sId + '" id="' + sId + '" >';
    var sText = '<label for="' + sId + '">' + sLabel + '&nbsp;</label>';
    $("#seriesChooser").append( sCheckBox + sText );

    // Save attributes needed to plot the associated series
    var tCheckBox = document.getElementById( sId );
    tCheckBox.onclick = plotFilter;
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
    // Iterate through array of Y axis options objects
    for ( var iYaxis in aYaxes )
    {
        // Get ticks array that main plot is showing for the current Y axis
        var aAxes = g_tPlot.getAxes();
        var sAxisName = "y" + ( ( iYaxis == 0 ) ? "" : ( parseInt( iYaxis ) + 1 ) ) + "axis";
        var aTicks = aAxes[sAxisName].ticks;

        // Copy ticks data to options object for the current Y axis
        aYaxes[iYaxis].ticks = new Array();
        for ( var iTick = 0; iTick < aTicks.length; iTick ++ )
        {
            aYaxes[iYaxis].ticks.push( [ aTicks[iTick].v, aTicks[iTick].label ] );
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

        // Load array of sample sets into plot data structure (but do not draw)
        for ( var iSet = 0; iSet < aSampleSets.length; iSet ++ )
        {
            plotSample( aSampleSets[iSet], false );
        }

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

// Change state of button specified by name
function plotButtonEnable( sName, bEnable )
{
    // Get the button
    tButton = document.getElementById( sName );

    // If the button exists, update its state
    if ( tButton != null )
    {
        // Set button state and class
        tButton.disabled = ! bEnable;
        tButton.className = bEnable ? "mainbtn" : "mainnobtn";

        // Set button onclick handler
        sHandler = bEnable ? ( "plot" + sName + "(); " ) : "";
        sHandler += "return false;";
        tButton.setAttribute( "onclick", sHandler );
    }
}

// Change state of control specified by name
function controlEnable( sName, bEnable )
{
    tControl = document.getElementById( sName );
    if ( tControl != null )
    {
        tControl.disabled = ! bEnable;
    }
}

// Enable/disable down-sample pattern controls
function downSampleControlsEnable()
{
    // Set states
    g_bDownSampleAuto = document.getElementById( "downSampleAuto" ).checked;
    controlEnable( "downSampleShow", ! g_bDownSampleAuto );
    controlEnable( "downSampleHide", ! g_bDownSampleAuto );

    // Clear values
    $( "#downSampleShow" ).val( "" );
    $( "#downSampleHide" ).val( "" );
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

function plotDownSample()
{
    // Get 'limit' input
    var tLimit = document.getElementById( "downSampleLimit" );
    var sLimit = tLimit.value = tLimit.value.trim();
    var iLimit = ( sLimit == "" ) ? g_iDownSampleLimit : parseInt( sLimit );
    var bValidLimit = /^\d*$/.test( sLimit ) && ! isNaN( iLimit ) && ( iLimit >= 1 );
    $("#downSampleLimit").css( "color", ( bValidLimit ? "black" : "red" ) );

    // Get 'offset' input
    var tOffset = document.getElementById( "downSampleOffset" );
    var sOffset = tOffset.value = tOffset.value.trim();
    var iOffset = ( sOffset == "" ) ? g_iDownSampleOffset : parseInt( sOffset );
    var bValidOffset = /^\d*$/.test( sOffset ) && ! isNaN( iOffset ) && ( iOffset >= 0 );
    $("#downSampleOffset").css( "color", ( bValidOffset ? "black" : "red" ) );

    // Get 'show' input
    var tShow = document.getElementById( "downSampleShow" );
    var sShow = tShow.value = tShow.value.trim();
    var iShow = ( sShow == "" ) ? g_iDownSampleShow : parseInt( sShow );
    var bValidShow = /^\d*$/.test( sShow ) && ! isNaN( iShow ) && ( iShow >= 1 );
    $("#downSampleShow").css( "color", ( bValidShow ? "black" : "red" ) );

    // Get 'hide' input
    var tHide = document.getElementById( "downSampleHide" );
    var sHide = tHide.value = tHide.value.trim();
    var iHide = ( sHide == "" ) ? g_iDownSampleHide : parseInt( sHide );
    var bValidHide = /^\d*$/.test( sHide ) && ! isNaN( iHide ) && ( iHide >= 0 );
    $("#downSampleHide").css( "color", ( bValidHide ? "black" : "red" ) );

    // If inputs are valid, update view
    if ( bValidLimit && bValidOffset && bValidShow && bValidHide )
    {
        tLimit.value = "";
        tOffset.value = "";
        tShow.value = "";
        tHide.value = "";

        if ( ( iLimit != g_iDownSampleLimit ) || ( iOffset != g_iDownSampleOffset ) || ( iShow != g_iDownSampleShow ) || ( iHide != g_iDownSampleHide ) )
        {
            // Down-sample pattern changed

            // Update show/hide fields
            document.getElementById("downSampleLimit_current").value = g_iDownSampleLimit = iLimit;
            document.getElementById("downSampleOffset_current").value = g_iDownSampleOffset = iOffset;
            document.getElementById("downSampleShow_current").value = g_iDownSampleShow = iShow;
            document.getElementById("downSampleHide_current").value = g_iDownSampleHide = iHide;

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

// Handle change of zoom detail checkbox
function downSampleZoomChanged()
{
    // Optionally redraw plot
    if ( ( g_aSeries != null ) && ! zoomRangeIsNull() && ( g_iDownSampleHide > 0 ) )
    {
        plotDraw( { type: "downSampleZoomChanged" } );
    }
}

// Handle change of auto-down-sample checkbox
function downSampleAutoChanged()
{
    // Enable/disable down-sample pattern controls
    downSampleControlsEnable();

    // Optionally redraw plot
    if ( g_bDownSampleAuto && ( g_aSeries != null ) )
    {
        plotDraw( { type: "downSampleAutoChanged" } );
    }
}

// Automatically set down-sample pattern according to browser and data size
function downSampleAutoConfig( iSampleTotal )
{
    if ( g_bDownSampleAuto )
    {
        // Set the Show parameter
        g_iDownSampleShow = 1;

        // Set the Hide parameter
        g_iDownSampleHide = 0;
        var iSamples = iSampleTotal - g_iDownSampleOffset;
        while ( ( iSamples / ( g_iDownSampleShow + g_iDownSampleHide ) ) > g_iDownSampleLimit )
        {
            g_iDownSampleHide ++;
        }

        // Update the Show and Hide controls in the GUI
        document.getElementById( "downSampleShow_current" ).value = g_iDownSampleShow;
        document.getElementById( "downSampleHide_current" ).value = g_iDownSampleHide;
    }
}

// Update the down-sample status line
function downSampleStatusUpdate( iShowing, iOf )
{
    $("#downSampleStatus").text( "Showing " +  iShowing + " of " + iOf  + " samples" );
}
