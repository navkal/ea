// Copyright 2017 Energize Apps.  All rights reserved.

//
// Flot plugin representing scrollbar in Plot view
//

(function ($)
{
    function init( plot )
    {
        var g_tSelection =
        {
            first: { x: -1, y: -1 },
            second: { x: -1, y: -1 },
            active: false
        };

        var g_tScroller =
        {
            x: -1,
            active: false,
            paging: 0
        }

        var g_iPageInterval = 0;

        var tSavedHandlers = {};
        var fnMouseUpHandler = null;

        // Set a new scroller on the scrollbar
        function setScroller( tRanges, bPreventEvent )
        {
            // Set up selection range
            var tXaxis = plot.getAxes().xaxis;
            g_tSelection.first.x = tXaxis.p2c( tRanges.xaxis.from );
            g_tSelection.second.x = tXaxis.p2c( tRanges.xaxis.to );
            g_tSelection.first.y = 0;
            g_tSelection.second.y = plot.height();

            // Draw the scroller
            plot.triggerRedrawOverlay();
        }

        // Handle mouse down event as start of scroll stroke or full page scroll
        function onMouseDown( tEvent )
        {
            // If user pressed left mouse button and there is a scroller...
            if ( ( tEvent.which == 1 ) && ( g_tSelection.first.x < g_tSelection.second.x ) )
            {
                // De-select any text selections
                document.body.focus();

                // Prevent text selection and drag in old-school browsers
                if ( ( document.onselectstart !== undefined ) && ( tSavedHandlers.onselectstart == null ) )
                {
                    tSavedHandlers.onselectstart = document.onselectstart;
                    document.onselectstart = function () { return false; };
                }
                if ( ( document.ondrag !== undefined ) && ( tSavedHandlers.ondrag == null ) )
                {
                    tSavedHandlers.ondrag = document.ondrag;
                    document.ondrag = function () { return false; };
                }

                // Handle event according to mouse location relative to scroller
                g_tScroller.paging = onScroller( tEvent );
                if ( g_tScroller.paging == 0 )
                {
                    // Mouse is on the scroller; start the scroll stroke
                    g_tScroller.active = true;
                    scrollStart( tEvent );
                }
                else
                {
                    // Mouse is beside the scroller; start timer to scroll full pages
                    g_iPageInterval = setInterval( function () { scrollPage( tEvent ); }, 250 );
                }

                // Trigger event
                triggerScrollStartEvent();

                // Redraw the scrollbar
                plot.triggerRedrawOverlay();

                // Set handler to process end of pan stroke
                fnMouseUpHandler = function ( tEvent ) { onMouseUp( tEvent ); };
                $(document).one( "mouseup", fnMouseUpHandler );
            }
        }

        // Handle mouse move event as continuation of scroll stroke
        function onMouseMove( tEvent )
        {
            if ( g_tScroller.active )
            {
                // User is dragging scroller
                scrollContinue( tEvent );
            }
            else
            {
                if ( ( g_tScroller.paging != 0 ) && ( g_tScroller.paging != onScroller( tEvent ) ) )
                {
                    // User has changed mouse-scroller relationship while paging; stop paging
                    scrollPageStop();
                }
            }

            // Redraw scrollbar
            plot.triggerRedrawOverlay();
        }

        // Handle mouse up event as end of scroll stroke
        function onMouseUp( tEvent )
        {
            // Clear the mouse-up handler
            fnMouseUpHandler = null;

            // Revert prevention of text selection and drag in old-school browsers
            if ( document.onselectstart !== undefined )
            {
                document.onselectstart = tSavedHandlers.onselectstart;
            }
            if ( document.ondrag !== undefined )
            {
                document.ondrag = tSavedHandlers.ondrag;
            }

            if ( g_tScroller.active )
            {
                // Continue and end the scroll stroke
                scrollContinue( tEvent );
                g_tScroller.active = false;
                triggerScrollEndEvent();
            }
            else if ( g_tScroller.paging != 0 )
            {
                // Scroll one page
                scrollPage( tEvent );

                // Stop repeated paging
                scrollPageStop();
            }

            // Redraw the scrollbar
            plot.triggerRedrawOverlay();

            return false;
        }


        // --> Touch interface handling -->
        function buildTouchEvent(e, touchEnd) {
            var touches = null;
            if (e.originalEvent && e.originalEvent.touches.length) {
                // Ignore all fingers but first
                touches = e.originalEvent.touches;
                e.pageX = touches[0].pageX;
                e.pageY = touches[0].pageY;
                e.which = 1;
                pageX = e.pageX;
                pageY = e.pageY;
            } else {
                // Touch end
                e.pageX = pageX;
                e.pageY = pageY;
            }
            return e;
        }

        function onTouchStart(e) {
            e = buildTouchEvent(e);
            return onMouseDown(e);
        }

        function onTouchEnd(e) {
            e = buildTouchEvent(e, true);
            return onMouseUp(e);
        }

        function onTouchMove(e) {
            e.preventDefault(); // To prevent panning
            e = buildTouchEvent(e);
            return onMouseMove(e);
        }
        // <-- Touch interface handling <--




        // Start the scroll stroke
        function scrollStart( tEvent )
        {
            // Save scroller information
            var tOffset = plot.getPlaceholder().offset();
            var tPlotOffset = plot.getPlotOffset();
            g_tScroller.x = clamp( 0, tEvent.pageX - tOffset.left - tPlotOffset.left, plot.width() );
        }

        // Continue the scroll stroke
        function scrollContinue( tEvent )
        {
            // Determine horizontal delta
            var tOffset = plot.getPlaceholder().offset();
            var tPlotOffset = plot.getPlotOffset();
            var nDelta = clamp( 0 - g_tSelection.first.x, tEvent.pageX - tOffset.left - tPlotOffset.left - g_tScroller.x, plot.width() - g_tSelection.second.x );

            // Execute the scroll
            scrollExecute( nDelta );
        }

        // Scroll a full page to right or left
        function scrollPage( tEvent )
        {
            var iOnScroller = onScroller( tEvent );
            if ( iOnScroller == 0 )
            {
                // Scroller has reached the mouse position; stop repeated paging
                scrollPageStop();
            }
            else
            {
                // Determine horizontal delta
                var nDelta = clamp( 0 - g_tSelection.first.x, ( g_tSelection.second.x - g_tSelection.first.x ) * iOnScroller, plot.width() - g_tSelection.second.x );

                // Execute the scroll
                scrollExecute( nDelta );
            }

            // Redraw the scrollbar
            plot.triggerRedrawOverlay();
        }

        // Scroll by the specified delta
        function scrollExecute( nDelta )
        {
            // Add delta to saved horizontal values
            g_tScroller.x += nDelta;
            g_tSelection.first.x += nDelta;
            g_tSelection.second.x += nDelta;

            // Normalize saved horizontal values
            g_tScroller.x = clamp( 0, g_tScroller.x, plot.width() );
            g_tSelection.first.x = clamp( 0, g_tSelection.first.x, plot.width() );
            g_tSelection.second.x = clamp( 0, g_tSelection.second.x, plot.width() );

            // Trigger event
            triggerScrollEvent();
        }

        // Invoked by client to silence subsequent events originating with current scroll stroke
        function scrollStop()
        {
            g_tScroller.active = false;
        }

        // Stop full-page scrolling
        function scrollPageStop()
        {
            clearInterval( g_iPageInterval );
            g_tScroller.paging = 0;
            triggerScrollEndEvent();
        }

        // Determine location of X coordinate relative to scroller bounds
        function onScroller( tEvent )
        {
            //  Determine current X coordinate
            var tOffset = plot.getPlaceholder().offset();
            var tPlotOffset = plot.getPlotOffset();
            var iX = tEvent.pageX - tOffset.left - tPlotOffset.left;

            // Determine location of X coordinate relative to scroller
            var iOn = 0;
            if ( iX < g_tSelection.first.x )
            {
                // Left of scroller
                iOn = -1;
            }
            else if ( iX > g_tSelection.second.x )
            {
                // Right of scroller
                iOn = 1;
            }

            return iOn;
        }

        // Trigger scroll start event
        function triggerScrollStartEvent()
        {
            plot.getPlaceholder().trigger( "plotscrollstart" );
        }

        // Trigger scroll movement event
        function triggerScrollEvent()
        {
            plot.getPlaceholder().trigger( "plotscroll", [ getSelection() ] );
        }

        // Trigger scroll end event
        function triggerScrollEndEvent()
        {
            plot.getPlaceholder().trigger( "plotscrollend" );
        }

        // Clamp value between min and max
        function clamp( nMin, nValue, nMax )
        {
            return ( nValue < nMin ) ? nMin : ( ( nValue > nMax ) ? nMax : nValue);
        }

        // Define public functions
        plot.setScroller = setScroller;
        plot.scrollStop = scrollStop;
        plot.scrollPageStop = scrollPageStop;

        // Bind event handlers
        plot.hooks.bindEvents.push(
            function( plot, eventHolder )
            {
                if ( plot.getOptions().scrollbar.enable )
                {
                    eventHolder.mousemove( onMouseMove );
                    eventHolder.mousedown( onMouseDown );
                    // Touch
                    eventHolder.bind("touchstart", onTouchStart);
                    eventHolder.bind("touchmove", onTouchMove);
                    eventHolder.bind("touchend", onTouchEnd);
                }
            }
        );

        // Return the current selection
        function getSelection()
        {
            var tSelection = null;

            tSelection = {};
            var iCanvas1 = g_tSelection.first;
            var iCanvas2 = g_tSelection.second;

            $.each(
                plot.getAxes(),
                function ( sName, tAxis )
                {
                    if ( tAxis.used )
                    {
                        var iPlot1 = tAxis.c2p( iCanvas1[tAxis.direction] );
                        var iPlot2 = tAxis.c2p( iCanvas2[tAxis.direction] );
                        tSelection[sName] = { from: Math.min( iPlot1, iPlot2 ), to: Math.max( iPlot1, iPlot2 ) };
                    }
                }
            );

            return tSelection;
        }

        // Define drawing handler
        plot.hooks.drawOverlay.push(
            function ( tPlot, tContext )
            {
                // Save the context
                tContext.save();

                // Set up new context
                var tPlotOffset = tPlot.getPlotOffset();
                tContext.translate( tPlotOffset.left, tPlotOffset.top );

                // Retrieve scrollbar options
                var tOptions = tPlot.getOptions().scrollbar;

                // Draw the scroller
                var tColor = $.color.parse( tOptions.scrollerColor );

                tContext.fillStyle = tColor.scale( 'a', ( g_tScroller.active ? 0.5 : 0.4 ) ).toString();
                tContext.strokeStyle = tColor.scale( 'a', ( g_tScroller.active ? 1.0 : 0.8 ) ).toString();
                tContext.lineWidth = g_tScroller.active ? 2 : 1;
                tContext.lineJoin = "round";

                var iX = Math.min( g_tSelection.first.x, g_tSelection.second.x );
                var iY = Math.min( g_tSelection.first.y, g_tSelection.second.y );
                var iW = Math.abs( g_tSelection.second.x - g_tSelection.first.x );
                var iH = Math.abs( g_tSelection.second.y - g_tSelection.first.y );

                tContext.fillRect( iX, iY, iW, iH );
                tContext.strokeRect( iX, iY, iW, iH );

                // If paging is active, redraw background to left or right of the scroller
                if ( g_tScroller.paging != 0 )
                {
                    tContext.fillStyle = tOptions.pagingColor;

                    switch ( g_tScroller.paging )
                    {
                        case -1:
                            // Paging to left
                            iX = 0;
                            iW = g_tSelection.first.x;
                            break;

                        case 1:
                            // Paging to right
                            iX = g_tSelection.second.x;
                            iW = plot.width() - g_tSelection.second.x;
                            break;
                    }

                    tContext.fillRect( iX, iY, iW, iH );
                }

                // Restore the context
                tContext.restore();
            }
        );

        // Define shutdown handler
        plot.hooks.shutdown.push(
            function ( tPlot, tEventHolder )
            {
                tEventHolder.unbind( "mousemove", onMouseMove );
                tEventHolder.unbind( "mousedown", onMouseDown );
                tEventHolder.unbind("touchstart", onTouchStart);
                tEventHolder.unbind("touchmove", onTouchMove);
                tEventHolder.unbind("touchend", onTouchEnd);

                if ( fnMouseUpHandler )
                {
                    $(document).unbind( "mouseup", fnMouseUpHandler );
                }
            }
        );

    }

    // Add scrollbar to Flot plugins
    $.plot.plugins.push(
        {
            init: init,
            options:
            {
                scrollbar:
                {
                    enable: false,
                    scrollerColor: "blue",
                    pagingColor: "#C6C6C6"
                }
            },
            name: 'scrollbar',
            version: '1.1'
        }
    );
}
)(jQuery);
