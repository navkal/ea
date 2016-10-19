<?php
  // Copyright 2016 Energize Apps.  All rights reserved.

  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  include "labels.php";

  error_log( "====> post=" . print_r( $_POST, true ) );

  // Save identifying timestamp
  $timestamp = $_POST["timestamp"];
  require_once "filenames.php";

  // Optionally overwrite temp input filename with preload filename
  if ( isset( $_SESSION["inputFilename"] ) )
  {
    $inputFilename = $_SESSION["inputFilename"];
  }

  // Save selected columns in columns file
  $columnData = json_decode( $_POST["columnData"], true );
  $columnsFile = fopen( $columnsFilename, "w" ) or die( "Unable to open file: " . $columnsFilename );
  foreach ( $columnData as $columnPair )
  {
    $name = $columnPair["name"];
    $nickname = $columnPair["nickname"];
    $line = $name . "," . ( $nickname === "" ? $name : $nickname ) . PHP_EOL;
    fwrite( $columnsFile,  $line );
  }
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
      $message = runParseScript( $runArgs, $inputFilename, $columnsFilename, $resultsFilename );

      // Handle completion
      if ( empty( $message ) )
      {
        // Normal: Save results filename in list
        array_push( $resultsFilenames, $resultsFilename );
      }
      else
      {
        // Failure: Abort with error message
        showMessage( $_POST["inputName"], $message );
      }
    }
    fclose( $multiFile );

    // Format name of zip file to be downloaded
    $zipFilename = $split[0] . ".zip";

    // Save script parameters in file
    $params = METASYS_FILE . "," . $_POST["inputName"];
    $params .= "," . REPORT_FORMAT . "," . MULTIPLE;

    // Save information for Analysis completion report
    $_SESSION["completion"] =
      [
        "params" => $params,
        "resultsFilename" => basename( $zipFilename )
      ];

    // Put the results files into a zip archive
    $zipArchive = new ZipArchive();
    $zipArchive->open( $zipFilename, ZipArchive::CREATE );
    foreach( $resultsFilenames as $filename )
    {
      $zipArchive->addFromString( basename( $filename ), file_get_contents( $filename ) );
    }
    $zipArchive->close();

    // Download the zip file
    downloadFile( $zipFilename, "zip" );
  }
  else
  {
    // Perform single analysis

    // Run the script
    $message = runParseScript( $_POST, $inputFilename, $columnsFilename, $resultsFilename );

    // Handle completion
    if ( empty( $message ) )
    {
      // Normal: Process results

      // Save information for Analysis completion report
      $_SESSION["completion"] =
        [
          "params" => formatParams( $_POST ),
          "resultsFilename" => basename( $resultsFilename )
        ];

      downloadFile( $resultsFilename );
    }
    else
    {
      // Failure: Report error
      showMessage( $_POST["inputName"], $message );
    }
  }

  // Archive or delete copy of uploaded input file
  if ( ! isset( $_SESSION["inputFilename"] ) )
  {
    if ( empty( $message ) )
    {
      archiveInput( $inputFilename, $_POST["inputName"] );
    }
    else
    {
      @unlink( $inputFilename );
    }
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

  function runParseScript( $args, $inputFilename, $columnsFilename, $resultsFilename )
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

  // Archive input file
  function archiveInput( $inputFilename, $uploadFilename )
  {
    $dateFilename =  date( "Y-m-d H-i-s " ) . $uploadFilename;
    $targetFilename = $_SERVER["DOCUMENT_ROOT"]."/mda/archive/" . $dateFilename;
    rename( $inputFilename, $targetFilename );

    $to = "NikhilNavkalContact@gmail.com";
    $subject = "Added to archive: " . $dateFilename;

    $text =
      "<style>body{font-family: arial;}</style>" .
      "<html><body>".
      "<p>The following upload has been added to the " . METASYS_FILE . " archive:</p>" .
      "<p>" . $dateFilename . "</p>" .
      "<hr/>" .
      "</html></body>";

    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: " . $_POST["email"] . "<NikhilNavkalContact@gmail.com>" . "\r\n";

    mail( $to, $subject, $text, $headers );
  }
?>


<?php
function showMessage( $uploadFilename, $message )
{
?>
  <!DOCTYPE html>
  <html>
    <?php
      include $_SERVER["DOCUMENT_ROOT"]."/../common/head.php";
      initUi( $_SERVER["DOCUMENT_ROOT"]."/" );
      include "labels.php";
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

    </body>
  </html>
  <script>
    $( 'head' ).append( '<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
    document.title = "<?=METASYS_DATA_ANALYSIS?>";
  </script>
<?php
  exit();
}
?>
