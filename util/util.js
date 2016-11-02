// Copyright 2016 Energize Apps.  All rights reserved.

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
