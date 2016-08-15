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
