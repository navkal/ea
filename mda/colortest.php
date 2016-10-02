<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<script language="javascript" type="text/javascript" src="../lib/flotPlot/analyzer.js"></script>

<script>
  $( document ).ready( testColors );

  function testColors()
  {
    for ( var iColors = 1; iColors <= 20; iColors ++ )
    {
      var aColors = generateColors( iColors, true );
      makeColorTest( aColors );
    }
  }

  function makeColorTest( aColors)
  {
    var sDiv =
      '<div class="row">' +
        '<div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">' +
          '<ul class="list-unstyled checkboxList" >';

    for ( var i in aColors )
    {
      var tColor = aColors[i];
      sDiv +=
        '<li>'
        +
          '<label class="checkbox checkbox-inline" >'
        +
            '<input type="checkbox" >'
        +
            '<svg width="12" height="12">'
        +
              '<rect width="12" height="12" style="fill:' + tColor.color + '; stroke-width:1; stroke:black" />'
        +
            '</svg> '
        +
            '<span class="bg-info" columnName >'
        +
              ( tColor.color + ": " + tColor.idx + "*" + tColor.ang + " " + tColor.sat + " " + tColor.val + " " + tColor.drk )
        +
            '</span>'
        +
          '</label>'
        +
        '</li>';
    }

    sDiv += '</ul></div></div><hr/>';

    $( "#colortest" ).append( sDiv );
  }
</script>

<div id="colortest" >
</div>
