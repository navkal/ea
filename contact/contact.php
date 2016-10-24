<!-- Copyright 2016 Energize Apps.  All rights reserved. -->

<div class="container">
  <?php

    require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
    require_once $_SERVER["DOCUMENT_ROOT"]."/../common/contact.php";
    error_log( "====> post=" . print_r( $_POST, true ) );

    if ( count( $_POST ) == 0 )
    {
      showContactForm( "h3", "Energize Apps" );
    }
    else
    {
      sendContactMessage( "EnergizeApps@gmail.com", "We", "The Energize Apps team" );
    }
  ?>
</div>
