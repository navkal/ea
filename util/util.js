// Copyright 2018 Energize Apps.  All rights reserved.

//
// --> Multi-check accelerator for checkbox list -->
//
var g_bMultiCheckShiftKey = false;

function onCheckboxClick( tEvent )
{
  g_bMultiCheckShiftKey = tEvent.shiftKey;
}

function clearStartMulti()
{
  $( '.startMultiCheck' ).removeClass( 'startMultiCheck' );
  $( '.startMultiUncheck' ).removeClass( 'startMultiUncheck' );
}

jQuery.fn.disableTextSelect = function()
{
  return this.each(
    function()
    {
      $( this ).css(
        {
          'MozUserSelect':'none',
          'webkitUserSelect':'none'
        }
      )
      .attr( 'unselectable', 'on' )
      .bind(
        'selectstart',
        function()
        {
          return false;
        }
      );
    }
  );
};

//
// <-- Multi-check accelerator for checkbox list <--
//

function ajaxSuccess( rsp, sStatus, tJqXhr )
{
  // Do nothing
}
function ajaxError( tJqXhr, sStatus, sErrorThrown )
{
  console.log( "AJAX error: Status=<" + sStatus +"> Error=<" + sErrorThrown + ">" );
}
function ajaxComplete( tJqXhr, sStatus )
{
  console.log( "AJAX complete: Status=<" + sStatus + ">" );
}

function startCleanup( timestamp, pathFragment, completionHandler )
{
  if ( pathFragment == null )
  {
    pathFragment = "";
  }

  if ( completionHandler == null )
  {
    completionHandler = finishCleanup;
  }

  $.ajax(
    pathFragment + "parse_cleanup.php?timestamp=" + timestamp,
    {
      type: "GET",
      cache: false,
      dataType: "json"
    }
  )
  .done( completionHandler )
  .fail( completionHandler );
}

function finishCleanup()
{
  document.location.href="/";
}
