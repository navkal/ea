<style>
  body
  {
    background-image: url( "contact/bg.jpg" );
    background-position: center top;
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-size: cover;
  }

  label, p.h3, p.h2, .form-control
  {
    color: white;
  }

  .form-control
  {
    background-color: transparent;
  }
</style>

<div class="container">

<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  error_log( "====> post=" . print_r( $_POST, true ) );

  if ( count( $_POST ) == 0 )
  {
    showContactForm( "h3", "Energize Apps" );
  }
  else
  {
    $to = "EnergizeApps@gmail.com";

    $name = $_POST["firstName"] . " " . $_POST["lastName"];
    $subject = "From " . $name;
    $comment = str_replace( "\n", "<br/>", $_POST["comment"] );

    $text =
      "<style>body{font-family: arial;}</style>" .
      "<html><body>".
      "<h4><u>Name</u></h4><span>" . $name . "</span>" .
      "<hr/>" .
      "<h4><u>Email</u></h4><p>" . $_POST["email"] . "</p>" .
      "<hr/>" .
      "<h4><u>Subject</u></h4><p>" . $_POST["subject"] . "</p>" .
      "<hr/>" .
      "<h4><u>Comment</u></h4><p>" . $comment . "</p>" .
      "<hr/>" .
      "</html></body>";


    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $_POST["email"] . "<SmtpDispatch@gmail.com>" . "\r\n";

    if ( mail( $to, $subject, $text, $headers ) )
    {
      sayThankYou();
    }
    else
    {
      reportContactError();
    }

  }
?>

</div>


<?php
  function sayThankYou()
  {
?>
    <br/>
    <p class="h3">Thank you for your interest.</p>
    <p class="h3">The Energize Apps Team will be in touch!</p>
<?php
  }
?>


<script>
  function onSubmitContact()
  {
    $( ".form-control" ).prop( "readonly", true );
    $( "#submitButton" ).prop( "disabled", true );
    $( "#cancelButton" ).prop( "disabled", true );
    return true;
  }
</script>