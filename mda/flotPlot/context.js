
// Set initial focus on appropriate control, when opening a new web page
function firstfocus()
{
    var nTabIndexMin = Number.MAX_VALUE;
    var objectFirst = null;

    // Traverse all forms of the document
    var nForm = 0;
    for ( nForm = 0; nForm < document.forms.length; nForm++ )
    {
        // Get the next form
        var form = document.forms[nForm];

        // Traverse all elements (drop-downs, text fields, etc.) of the current form
        var nElement = 0;
        for ( nElement = 0; nElement < form.elements.length; nElement++ )
        {
            // Get the next element
            var element = form.elements[nElement];

            // Optionally update object that should be given first focus
            if ( isTabIndexMin( element, nTabIndexMin ) )
            {
                nTabIndexMin = element.tabIndex;
                objectFirst = element;
            }
        }
    }

    // Traverse all links (buttons, etc.) of the document
    var nLink = 0;
    for ( nLink = 0; nLink < document.links.length; nLink++ )
    {
        // Get the next link
        var link = document.links[nLink];

        // Optionally update object that should be given first focus
        if ( isTabIndexMin( link, nTabIndexMin ) )
        {
            nTabIndexMin = link.tabIndex;
            objectFirst = link;
        }
    }

    // If we found a first object, focus on it
    if ( objectFirst != null )
    {
        objectFirst.focus();
    }
}

function isTabIndexMin( object, nTabIndexMin )
{
    var bDisabled = ( object.disabled != null ) && object.disabled;
    var bReadOnly = ( object.readOnly != null ) && object.readOnly;
    var bTabIndexMin = ( object.tabIndex != null ) && ( object.tabIndex > 0 )  && ( object.tabIndex < nTabIndexMin );

    return !bDisabled && !bReadOnly && bTabIndexMin;
}

var g_iDemoParam = 0;
function sampleForDemo()
{
    var aSamples = [];
    aSamples.push( { label: 'Sin(x)', tick: '', tickDecimals: 4, value: Math.sin( g_iDemoParam ) } );
    aSamples.push( { label: 'Sin(x+180)', tick: '', tickDecimals: 4, value: Math.sin( g_iDemoParam + 180 ) } );
    plotSample( aSamples, true );
    g_iDemoParam += 0.05;
}

function ajaxStart( sNotUsed1, sAction, sNotUsed2, sFunction, iMs )
{
    var iReturn = 0;

    switch ( sAction )
    {
        case "plotSample":
            iReturn = setInterval( "sampleForDemo()", iMs/10 );
            break;

        case "plotDone":
            iReturn = setTimeout( sFunction, iMs );
            break;
    }

    return iReturn;
}

function ajaxDone( iIndex )
{
    clearTimeout( iIndex );
    clearInterval( iIndex );
}

function ajaxGet( sNotUsed, sAction )
{
    if ( sAction == "plotOpenDialog" )
    {
        if ( window.name == "" )
        {
            alert( "There is no saved plot to open." );
        }
        else
        {
            if ( confirm( "Open saved plot?" ) )
            {
                window.location = "plotDemo.html";
            }
        }
    }
}

function submitCommand( sNotUsed, sAction, sData )
{
    if ( sAction == "plotSave" )
    {
        // Save plot data as window name, for persistence across pages
        var aLines = sData.split( "\n" );
        var sWindowName = "";

        if ( aLines.length > 1 )
        {
            // Construct string representing JavaScript array of lines
            var bHeaderLine = true; // Reading plot file header line?
            for ( var iIndex = 0; iIndex < aLines.length; iIndex ++ )
            {
                // Get the current line
                var sLine = aLines[iIndex];

                // Remove extraneous timestamp data that has been added for readability and compatibility with MS Excel
                var aLine = sLine.split( "," );
                if ( aLine[aLine.length] == "" )
                {
                    aLine.pop();
                }
                aLine.pop();

                if ( bHeaderLine )
                {
                    // Find index of numeric time field within data line
                    var iTimeIndex = aLine.indexOf( "'time'" );
                    bHeaderLine = false;
                }
                else
                {
                    // Restore numeric time field to integer
                    aLine[iTimeIndex] = aLine[iTimeIndex].split( "'" )[1];
                }

                // Reconstitute line
                sLine = aLine.join();

                // Convert each line to an array and add it to the big array
                var sComma = ( sWindowName == "" ) ? "" : ",";
                sWindowName += sComma + "[" + sLine + "]";
            }
            sWindowName = "[" + sWindowName + "]";
        }

        window.name = sWindowName;
    }
}

function setDialogSize()
{
    // Do nothing
}

function setDialogTitle()
{
    // Do nothing
}

function setDialogLoading()
{
    // Do nothing
}

function showDialog()
{
    // Do nothing
}

function clearWaitCursor()
{
    // Do nothing
}

function onLoad()
{
    firstfocus();
    plotInit( eval( window.name ) );
}