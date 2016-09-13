//
// Flot plugin to support zoom range refinement in main plot view
//

(function ($)
{
  function init( plot )
  {
    function onMouseDown( tEvent )
    {
      if ( ( tEvent.which == 1 ) && tEvent.ctrlKey )
      {
        console.log( "====> MOUSE DOWN starts REZOOM" );
        plot.getPlaceholder().trigger( "plotrezoomstart" );
        $(document).one( "mouseup", onMouseUp );
      }
    }

    function onMouseMove( tEvent )
    {
      if ( plot.getOptions().rezoom.rezooming )
      {
        console.log( "====> MOOOOOOOOVE" );
      }
    }

    function onMouseUp( tEvent )
    {
      console.log( "====> MOUSE UP ends REZOOM" );
      plot.getPlaceholder().trigger( "plotrezoomend" );
      console.log( "====> MOUSE UP clears REZOOM" );
      plot.getPlaceholder().trigger( "plotrezoomclear" );
    }

    // Bind event handlers
    plot.hooks.bindEvents.push(
      function( plot, eventHolder )
      {
        if ( plot.getOptions().rezoom.enable )
        {
          eventHolder.mousedown( onMouseDown );
          eventHolder.mousemove( onMouseMove );
        }
      }
    );

    // Define shutdown handler
    plot.hooks.shutdown.push(
      function ( tPlot, tEventHolder )
      {
        tEventHolder.unbind( "mousemove", onMouseMove );
        tEventHolder.unbind( "mousedown", onMouseDown );
      }
    );
  }

  // Add rezoom to Flot plugins
  $.plot.plugins.push(
    {
      init: init,
      options:
      {
        rezoom:
        {
          enable: false,
          rezooming: false,
          selection:
          {
            mode: "x",
            color: "#8888FF",
          }
        }
      },
      name: 'rezoom',
      version: '1.0'
    }
  );
}
)(jQuery);
