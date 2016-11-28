<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  initUi( $_SERVER["DOCUMENT_ROOT"]."/" );
  require_once "labels.php";

  error_log( "====> post=" . print_r( $_POST, true ) );

  // Save identifying timestamp
  $timestamp = $_POST["timestamp"];
  require_once "filenames.php";

  // Optionally overwrite temp input filename with preload filename
  if ( isset( $_SESSION["inputFilename"] ) )
  {
    $inputFilename = $_SESSION["inputFilename"];
  }

  // Save selected columns in columns file and nickname map
  $columnData = json_decode( $_POST["columnData"], true );
  $columnsFile = fopen( $columnsFilename, "w" ) or die( "Unable to open file: " . $columnsFilename );
  $nicknameMap = ".,.,";
  foreach ( $columnData as $columnPair )
  {
    $name = $columnPair["name"];
    $nickname = $columnPair["nickname"];
    $line = $name . "," . ( $nickname === "" ? $name : $nickname ) . PHP_EOL;
    fwrite( $columnsFile,  $line );
    if ( $nickname != "" )
    {
      $nicknameMap .= $nickname . "," . $name . ",";
    }
  }
  $nicknameMap = rtrim( $nicknameMap, "," ) . PHP_EOL;
  fclose( $columnsFile );


  if ( $_POST["format"] == "Multiple" )
  {
    // Perform multiple analyses, as indicated in multiple-run input file

    $split = explode( ".", $resultsFilename );
    $resultsFilenames = [];
    $iteration = 0;

    // Loop through multiple-run input file.  Each line represents one analysis run.
    $multiFile = fopen( "multiple.csv", "r" );
    while( ( $arglist = fgetcsv( $multiFile ) ) !== false )
    {
      // Build the argument list
      $runArgs = [];
      $runArgs["inputName"] = $_POST["inputName"];
      for ( $index = 0; $index < count( $arglist ); $index += 2 )
      {
        $runArgs[$arglist[$index]] = $arglist[$index+1];
      }
      $tag = "";
      if ( isset( $runArgs["startTime"] ) )
      {
        $tag .= str_replace( ":", "_", $runArgs["startTime"] );
        $tag .= "-";
        if ( isset( $runArgs["endTime"] ) )
        {
          $tag .= str_replace( ":", "_", $runArgs["endTime"] );
        }
        else
        {
          $tag .= "full";
        }
      }
      else
      {
        $tag .= "detail";
      }
      $resultsFilename = $split[0] . "_" . ( ++ $iteration ) . "_" . $tag . "." . $split[1];

      // Run the analysis
      $message = runParseScript( $runArgs, $inputFilename, $columnsFilename, $resultsFilename, $nicknameMap );

      // Handle completion
      if ( empty( $message ) )
      {
        // Normal: Save results filename in list
        array_push( $resultsFilenames, $resultsFilename );
      }
      else
      {
        // Failure: Abort with error message
        showMessage( $_POST["inputName"], $message, $timestamp );
      }
    }
    fclose( $multiFile );

    // Archive input file
    archiveInput();

    // Format name of zip file to be downloaded
    $zipFilename = $split[0] . ".zip";

    // Put the results files into a zip archive
    $zipArchive = new ZipArchive();
    $zipArchive->open( $zipFilename, ZipArchive::CREATE );
    foreach( $resultsFilenames as $filename )
    {
      $zipArchive->addFromString( basename( $filename ), file_get_contents( $filename ) );
    }
    $zipArchive->close();

    // Save information for Analysis completion report
    $params = METASYS_FILE . "," . $_POST["inputName"];
    $params .= "," . REPORT_FORMAT . "," . MULTIPLE;
    $_SESSION["completion"] =
      [
        "params" => $params,
        "resultsFilename" => basename( $zipFilename ),
        "downloadFilename" => $zipFilename,
        "downloadExt" => "",
        "downloadType" => "zip"
      ];

    // Redirect to completion page
    showCompletion( $timestamp );
  }
  else
  {
    // Perform single analysis

    // Run the script
    $message = runParseScript( $_POST, $inputFilename, $columnsFilename, $resultsFilename, $nicknameMap );

    // Handle completion
    if ( empty( $message ) )
    {
      // Normal: Process results

      // Archive input file
      archiveInput();

      // Save information for Analysis completion report
      $_SESSION["completion"] =
        [
          "params" => formatParams( $_POST ),
          "resultsFilename" => basename( $resultsFilename ),
          "downloadFilename" => $resultsFilename,
          "downloadExt" => "",
          "downloadType" => "octet-stream"
        ];

      // Redirect to completion page
      showCompletion( $timestamp );
    }
    else
    {
      // Failure: Report error
      showMessage( $_POST["inputName"], $message, $timestamp );
    }
  }

  // Archive uploaded input file
  function archiveInput()
  {
    // Optionally archive uploaded input file
    if ( ( $archiveDeployment = getenv( "ARCHIVE_DEPLOYMENT" ) ) && isset( $_SESSION["archiveFilename"] ) )
    {
      // Format filenames
      $dateFilename =  date( "Y-m-d H-i-s " ) . $_POST["inputName"];
      $zipFilename = $_SERVER["DOCUMENT_ROOT"]."/mda/archive/" . $dateFilename . ".zip";

      // Put the uploaded input file into a zip archive
      $zipArchive = new ZipArchive();
      $zipArchive->open( $zipFilename, ZipArchive::CREATE );
      $zipArchive->addFromString( $dateFilename, file_get_contents( $_SESSION["archiveFilename"] ) );
      $zipArchive->close();


      // Send notification email
      $text =
        "<style>body{font-family: arial;}</style>" .
        "<html>" .

          "<head>" .
            "<style>" .
            "table { border: 1px dotted black; }" .
            "td { padding-right: 10px; }" .
            "</style>" .
          "</head>" .

          "<body>" .
            "<p><b>" . $archiveDeployment . "</b> has archived a new " . METASYS_FILE . ":</p>" .
            "<p>" . $dateFilename . "</p>" .
            "<br/>" .

            "<table>" .
              "<tr>" .
                "<td>Server Name:</td>" .
                "<td>" . $_SERVER["SERVER_NAME"] . "</td>" .
              "</tr>" .
              "<tr>" .
                "<td>Server Address:</td>" .
                "<td>" . $_SERVER["SERVER_ADDR"] . "</td>" .
              "</tr>" .
              "<tr>" .
                "<td>Server Port:</td>" .
                "<td>" . $_SERVER["SERVER_PORT"] . "</td>" .
              "</tr>" .
              "<tr>" .
                "<td>Remote Address:</td>" .
                "<td>" . $_SERVER["REMOTE_ADDR"] . "</td>" .
              "</tr>" .
            "</table>" .
          "</body>" .

        "</html>";

      $headers = "MIME-Version: 1.0" . "\r\n";
      $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
      $headers .= "From: Energize Apps <SmtpDispatch@gmail.com>" . "\r\n";

      global $mailto;
      $subject = "Archive notice: " . $archiveDeployment;
      mail( $mailto, $subject, $text, $headers );
    }
  }

  function showCompletion( $timestamp )
  {
    echo '<script type="text/javascript">window.location.assign( "parse_done.php?timestamp='.$timestamp.'");</script>';
    exit();
  }

  function quote( $s )
  {
    $s = trim( $s );
    if ( $s[0] != '"' )
    {
      $s = '"' . $s . '"';
    }
    return $s;
  }

  function runParseScript( $args, $inputFilename, $columnsFilename, $resultsFilename, $nicknameMap )
  {
    // Set up Python command
    $summarize = $args["format"] == SUMMARY ? "-s" : "";
    $start = isset( $args["startTime"] ) ? "--start " . str_replace( ' ', '', $args["startTime"] ) : "";
    $end = isset( $args["endTime"] ) ? "--end " . str_replace( ' ', '', $args["endTime"] ) : "";
    $columns = "-c " . quote( $columnsFilename );
    $command = quote( getenv( "PYTHON" ) ) . " parse.py -i " . quote( $inputFilename ) . " -o " . quote( $resultsFilename ) . " " . $summarize . " " . $start . " " . $end . " " . $columns;
    error_log( "===> command=" . $command );

    // Execute Python script
    exec( $command, $output, $status );

    // If Python script generated an output file, append parameter information to it
    $message = "";
    if ( file_exists( $resultsFilename ) )
    {
      $resultsFile = fopen( $resultsFilename, "a" );
      fwrite( $resultsFile, formatParams( $args ) );
      fwrite( $resultsFile, $nicknameMap );
      fclose( $resultsFile );
      markFile( $resultsFilename );
    }
    else
    {
      $message = METASYS_DATA_ANALYSIS . " script failed to generate output file.<br/>";
      foreach ( $output as $line )
      {
        $message .= "<br/>" . $line;
      }
    }

    return $message;
  }

  // Format list of input parameters
  function formatParams( $args )
  {
    $params = METASYS_FILE . "," . $args["inputName"];
    $params .= "," . REPORT_FORMAT . "," . $args["format"];
    if ( isset ( $args["period"] ) )
    {
      $params .= "," . TIME_PERIOD . "," . $args["period"];

      if ( isset( $args["startTime"] ) )
      {
        $params .= "," . START_TIME . "," . str_replace( ' ', '', $args["startTime"] );

        if ( isset( $args["endTime"] ) )
        {
          $params .= "," . END_TIME . "," . str_replace( ' ', '', $args["endTime"] );
        }
      }
    }

    return $params . PHP_EOL;
  }
?>


<?php
function showMessage( $uploadFilename, $message, $timestamp )
{
?>
  <!DOCTYPE html>
  <html>
    <?php
      include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
      initUi( $_SERVER["DOCUMENT_ROOT"]."/" );
      require_once "labels.php";
    ?>

    <body>
      <div class="container" style="padding-top:30px">
        <div class="alert alert-danger" >
          <h4>Error processing file <b><?=$uploadFilename?></b></h4>
          <p><?=$message?></p>
        </div>
      </div>

      <!-- OK button -->
      <div class="container">
        <div style="text-align:center;" >
          <a class="btn btn-default" href="/" role="button">OK</a>
        </div>
      </div>

      <?php
        // Sticky footer
        include $_SERVER["DOCUMENT_ROOT"]."/../common/footer.php";
      ?>

      <script type="text/javascript" src="../util/util.js?version=<?=$timestamp?>"></script>
      <script>
        $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
        document.title = "<?=METASYS_DATA_ANALYSIS?>";

        // Clean up temp files
        startCleanup( "<?=$timestamp?>", "", function(){} );
      </script>

    </body>
  </html>
<?php
  exit();
}
?>
