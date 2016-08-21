<script>
  function setBtnSz()
  {
    if ( $( window ).width() < 768 )
    {
      $( "#prototype button" ).addClass( "btn-xs" );
      $( "#prototype button" ).removeClass( "btn-sm" );
    }
    else
    {
      $( "#prototype button" ).addClass( "btn-sm" );
      $( "#prototype button" ).removeClass( "btn-xs" );
    }
  }
  $( document ).ready(
    function()
    {
      $( ".up" ).click( moveColumnUp );
      $( ".dn" ).click( moveColumnDown );
      setBtnSz();
      $( window ).on( "resize", setBtnSz );
    }
  );
</script>

<div class="row">
  <div class="col-xs-12 col-sm-12 col-md-12 col-lg-12">
    <div class="panel panel-default">
      <div class="panel-heading">
        Selected <?=POINTS_OF_INTEREST?>
      </div>
      <div class="panel-body">
        <div class="list-group" id="prototype">
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">KVAR_Present_Demand.Main-kVAR_Present_Demand (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-xs" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-xs" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">Energy.M1-kWh-Energy (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">KVARh.DE-ATS-kVARh (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">KVA_Present_Demand.Main-kVA_Present_Demand (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">KW_Total.DHB - kW - Present Value (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
          <a class="list-group-item" >
            <div class="row">
              <div class="col-xs-12 col-sm-12 col-md-7 col-lg-7 padBottomSmall">
                <h5 class="list-group-item-text">KVAR_Present_Demand.Main-kVAR_Present_Demand (Trend1)</h5>
              </div>
              <div class="col-xs-7 col-sm-10 col-md-3 col-lg-3">
                <input type="text" class="form-control" placeholder="Nickname" >
              </div>
              <div class="col-xs-5 col-sm-2 col-md-2 col-lg-2">
                <button class="up btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-up"></span></button>
                <button class="dn btn btn-default btn-sm" ><span class="glyphicon glyphicon-menu-down"></span></button>
              </div>
            </div>
          </a>
        </div>
      </div>
    </div>
  </div>
</div>
