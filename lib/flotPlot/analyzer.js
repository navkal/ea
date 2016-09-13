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
var g_bShowLastPoint = true;
var g_bDownSampleAuto = null;
var g_bDownSampleAutoPrev = null;
var g_iDownSampleDensity = 100;
var g_iDownSampleOffset = 0;
var g_iDownSampleShow = null;
var g_iDownSampleHide = null;
var g_aColors = null;

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
    rezoom:
    {
        enable: true
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
    // Initialize checkbox accelerator controls
    checkboxAcceleratorsInit();

    // Enable/disable down-sample pattern controls
    downSampleControlsEnable();

    // Initialize with data from opened plot file
    var bShowPlotOpenData = plotRead( aPlotOpenData );
    if ( ! bShowPlotOpenData )
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

        $( "#downsampleControls" ).css( "display", "none" );
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
    var bAxisRight = true;
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
                g_aData.push( { data: aData, label: ( zoomRangeIsNull() ? " " : " " + tChoice.label ), tooltip: tChoice.label, yaxis: tChoice.yaxis, color: tChoice.color } );
                aDataFull.push( { data: g_aSeries[tChoice.series], label: tChoice.label, yaxis: tChoice.yaxis, color: tChoice.color } );

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
                aYaxesNav[tChoice.series] = jQuery.extend( { show: false, reserveSpace: $( "#showYaxisTicks" ).prop( "checked" ) }, aYaxesPlot[tChoice.series] );

                if ( ! $( "#showYaxisTicks" ).prop( "checked" ) )
                {
                  aYaxesPlot[tChoice.series].show = false;
                  aYaxesPlot[tChoice.series].reserveSpace = false;
                }
            }
        }
    }

    // Determine whether to show full data in zoomed view of down-sampled plot
    var bZoomFull = ! ( document.getElementById( "downSampleZoom" ).checked );

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
        // Draw zoomed plot with optional panning
        if ( g_tOptionsPlot.rezoom.rezooming )
        {
          g_tOptionsPlot.pan.interactive = false;
          g_tOptionsPlot.xaxis.panRange = false;
        }
        else
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

        // Bind rezoom handlers
        $("#plotview").bind( 'plotrezoomstart', plotRezoomStart );
        $("#plotview").bind( 'plotrezoomend', plotRezoomEnd );
        $("#plotview").bind( 'plotrezoomclear', plotRezoomClear );

        ///////////////////
        // Overview plot //
        ///////////////////

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

function seriesCheckAll()
{
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", true );
  finishSeriesCheckAction();
}

function seriesCheckNone()
{
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", false );
  finishSeriesCheckAction();
}

function seriesCheckComplement()
{
  var unchecked = $( "#seriesChooser input[type=checkbox]:not(:checked)" );
  $( "#seriesChooser input[type=checkbox]" ).prop( "checked", false );
  unchecked.prop( "checked", true );
  finishSeriesCheckAction();
}

// Filter plot based on selections made in plot chooser
function plotFilter()
{
  finishSeriesCheckAction();
}

function finishSeriesCheckAction()
{
  $( "#seriesChooser .bg-info" ).removeClass( "bg-info" );
  $( "#seriesChooser input[type=checkbox]:checked" ).parent().find( "span[columnName]" ).addClass( "bg-info" );
  plotDraw( { type: "plotFilter" } );
}

// Show or hide y-axis ticks
function plotShowYaxisTicks( event )
{
  plotDraw( { type: "plotShowYaxisTicks" } );
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

        // Update zoom status display
        zoomStatusUpdate();
    }
}

// Start plot rezoom
function plotRezoomStart( tEvent )
{
  console.log( "========> plotRezoomStart" );
  g_tOptionsPlot.rezoom.rezooming = true;
  plotDraw( { type: "plotRezoomStart" } );
}

// End plot rezoom
function plotRezoomEnd( tEvent )
{
  console.log( "========> plotRezoomEnd" );
  plotDraw( { type: "plotRezoomEnd" } );
}

// Clear plot rezoom
function plotRezoomClear( tEvent )
{
  console.log( "========> plotRezoomClear" );
  g_tOptionsPlot.rezoom.rezooming = false;
  plotDraw( { type: "plotRezoomClear" } );
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
    var sContent = ( tSeries.tooltip || tSeries.label ) + "=" + tItem.datapoint[1].toFixed( tSeries.yaxis.options.tickDecimals );

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
                $("#plotview .legendLabel").eq( iDataSet ).text( tSeries.label + equal + nY.toFixed( tSeries.yaxis.tickDecimals ) );
            }
        }
    }
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
    var sChooser =
      '<li>'
      +
        '<label class="checkbox checkbox-inline" >'
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

        // Copy ticks data to options object for the current Y axis
        var aTicks = aAxes[sAxisName].ticks;
        if ( aTicks )
        {
          aYaxes[iYaxis].ticks = new Array();
          for ( var iTick = 0; iTick < aTicks.length; iTick ++ )
          {
              aYaxes[iYaxis].ticks.push( [ aTicks[iTick].v, aTicks[iTick].label ] );
          }
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
}

// Enable/disable down-sample pattern controls
function downSampleControlsEnable()
{
  $( ".has-error" ).removeClass( "has-error" );

  g_bDownSampleAutoPrev = g_bDownSampleAuto;
  g_bDownSampleAuto = $( "#downSampleAuto" ).prop( "checked" );

  $( "#density" ).css( "display", g_bDownSampleAuto ? "block" : "none" );
  $( "#pattern" ).css( "display", g_bDownSampleAuto ? "none" : "block" );

  $( "#downSampleDensity" ).val( "" );
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
    $( ".has-error" ).removeClass( "has-error" );

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
    var bValidDensity = ! g_bDownSampleAuto || ( /^\d*$/.test( sDensity ) && ! isNaN( iDensity ) && ( iDensity >= 1 ) );
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

        if ( ( g_bDownSampleAuto != g_bDownSampleAutoPrev ) || ( iDensity != g_iDownSampleDensity ) || ( iOffset != g_iDownSampleOffset ) || ( iShow != g_iDownSampleShow ) || ( iHide != g_iDownSampleHide ) )
        {
            // Down-sample parameters changed

            g_iDownSampleDensity = g_bDownSampleAuto ? iDensity: 0;
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
    if ( g_bDownSampleAuto )
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
  $( "#downSampleShowing" ).text( iShowing );
  $( "#downSampleOf" ).text( iOf );

  $( "#downSampleDensity_current" ).text( g_iDownSampleDensity );
  $( "#downSampleShow_current" ).text( g_iDownSampleShow );
  $( "#downSampleHide_current" ).text( g_iDownSampleHide );
  $( "#downSampleOffset_current" ).text( g_iDownSampleOffset );

  $( "#densityCurrent" ).css( "display", g_bDownSampleAuto ? "inline" : "none" );
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

  $( "#timestampFrom" ).text( new Date( timestampFrom ).toLocaleString() );
  $( "#timestampTo" ).text( new Date( timestampTo ).toLocaleString() );
}

// --> Color generation -->

// Generate N unique colors
function generateColors( iColors )
{
    var aColors =
    [
      // Force first color to be black to work around Flot 8.3 failure to color ticks of first y axis
      "black",
      "red",
      "green",
      "blue",
      "purple",
      "fuchsia",
      "#d2691e" /*chocolate*/,
      "#008080", /*teal*/
      "#696969" /*dimgray*/,
      "#00bfff" /*deepskyblue*/,
    ];

    if ( iColors > aColors.length )
    {
      aColors = [ "black" ];

      // Generate colors

      var iAngle = 360 / ( iColors - 1 );

      for ( var iColor = 0; iColor < iColors; iColor ++ )
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
              iSaturation = 80;
              iValue = 78;
              break;

            case 2:
              iSaturation = 60;
              iValue = 90;
              break;

            case 3:
              iSaturation = 40;
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

        aColors.push( hsvToRgb( iAngle * iColor, iSaturation, iValue ) );
      }
    }

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

// <-- Color generation <--
