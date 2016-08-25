<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  include "labels.php";

  error_log( "====> post=" . print_r( $_POST, true ) );

  // Save identifying timestamp
  $timestamp = $_POST["timestamp"];
  require_once "filenames.php";

  // Optionally overwrite temp input filename with preload filename
  if ( file_exists( $preloadFilename ) )
  {
    $preloadFile = fopen( $preloadFilename, "r" );
    $inputFilename = '"' . fgets( $preloadFile ) . '"';
    fclose( $preloadFile );
  }

  // Save selected columns in columns file
  $columnData = json_decode( $_POST["columnData"], true );
  $columnsFile = fopen( $columnsFilename, "w" ) or die( "Unable to open file: " . $columnsFilename );
  foreach ( $columnData as $columnPair )
  {
    $name = $columnPair["name"];
    $nickname = $columnPair["nickname"];
    $line = $name . "," . ( $nickname ? $nickname : $name ) . PHP_EOL;
    fwrite( $columnsFile,  $line );
  }
  fclose( $columnsFile );


  if ( $_POST["format"] == "Multiple" )
  {
    $split = explode( ".", $resultsFilename );
    $resultsFilenames = [];
    $iteration = 0;

    // Loop through multiple-run input file.  Each line represents one analysis run.
    $multiFile = fopen( "multiple.csv", "r" );
    while( ( $arglist = fgetcsv( $multiFile ) ) !== false )
    {
      error_log( "====> arglist=" . print_r( $arglist, true ) );
      // Build the argument list
      $runArgs = [];
      for ( $index = 0; $index < count( $arglist ); $index += 2 )
      {
        $runArgs[$arglist[$index]] = $arglist[$index+1];
      }
      $resultsFilename = $split[0] . "_" . ( ++ $iteration ) . "." . $split[1];

      $message = runParseScript( $runArgs, $inputFilename, $columnsFilename, $resultsFilename );

      if ( empty( $message ) )
      {
        array_push( $resultsFilenames, $resultsFilename );
      }
      else
      {
        // Abort with error message
        showMessage( $_POST["inputName"], $message );
      }
    }
    fclose( $multiFile );

    // Format name of zip file that will be downloaded
    $zipFilename = basename( $split[0] . ".zip" );

    // Save script parameters in file
    $params = METASYS_FILE . "," . $_POST["inputName"];
    $params .= "," . REPORT_FORMAT . "," . MULTIPLE;
    $paramsFile = fopen( $paramsFilename, "w" ) or die( "Unable to open file: " . $paramsFilename );
    fwrite( $paramsFile, $params . PHP_EOL );
    fwrite( $paramsFile, $zipFilename . PHP_EOL );
    fclose( $paramsFile );

    // Put the results files into a zip archive
    $zip = new ZipArchive();
    $zipFile = tempnam( sys_get_temp_dir(), "mda_" . $timestamp . "_" );
    $zip->open( $zipFile, ZipArchive::CREATE );
    foreach( $resultsFilenames as $filename )
    {
      $zip->addFromString( basename( $filename ), file_get_contents( $filename ) );
    }
    $zip->close();

    // Download the zip file
    downloadZip( $zipFile, $zipFilename );
  }
  else
  {
    $message = runParseScript( $_POST, $inputFilename, $columnsFilename, $resultsFilename );

    // Completion
    if ( empty( $message ) )
    {
      // Normal: Process results

      // Save script parameters in file
      $params = METASYS_FILE . "," . $_POST["inputName"];
      if ( isset( $_POST["startTime"] ) )
      {
        $params .= "," . START_TIME . "," . str_replace( ' ', '', $_POST["startTime"] );
        if ( $_POST["period"] == FULL_DAY )
        {
          $params .= "," . TIME_PERIOD . "," . $_POST["period"];
        }
        else
        {
          $params .= "," . END_TIME . "," . str_replace( ' ', '', $_POST["endTime"] );
        }
        $params .= "," . COST_PER_KWH . ",$" . $_POST["cost"];
      }
      else
      {
        $params .= "," . REPORT_FORMAT . "," . DETAILED;
      }

      $paramsFile = fopen( $paramsFilename, "w" ) or die( "Unable to open file: " . $paramsFilename );
      fwrite( $paramsFile, $params . PHP_EOL );
      fwrite( $paramsFile, basename( $resultsFilename ) . PHP_EOL );
      fclose( $paramsFile );
      downloadFile( $resultsFilename );
    }
    else
    {
      showMessage( $_POST["inputName"], $message );
    }
  }

  // Delete copy of uploaded input file
  if ( ! file_exists( $preloadFilename ) )
  {
    @unlink( $inputFilename );
  }

  function runParseScript( $args, $inputFilename, $columnsFilename, $resultsFilename )
  {
    error_log( "====> args=" . print_r( $args, true ) );
    // Set up Python command
    $python = getenv( "PYTHON" );
    $summarize = isset( $args["startTime"] ) ? "-s" : "";
    $start = $summarize ? "--start " . str_replace( ' ', '', $args["startTime"] ) : "";
    $end = isset( $args["endTime"] ) ? "--end " . str_replace( ' ', '', $args["endTime"] ) : "";
    // --> Deprecated parse.py parameter: Cost per kWh -->
    $cost = $summarize ? "--cost " . $args["cost"] : "";
    // <-- Deprecated parse.py parameter <--
    $columns = "-c " . $columnsFilename;
    $command = $python . " parse.py -i " . $inputFilename . " -o " . $resultsFilename . " " . $summarize . " " . $start . " " . $end . " " . $columns;
    error_log( "===> command=" . $command );

    // Execute Python script
    exec( $command, $output, $status );

    // Check whether script generated an output file
    $message = "";
    if ( ! file_exists( $resultsFilename ) )
    {
      $message = METASYS_DATA_ANALYSIS . " script failed to generate output file.<br/>";
      foreach ( $output as $line )
      {
        $message .= "<br/>" . $line;
      }
    }

    return $message;
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
      include "labels.php" ;
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
  exit;
}
?>
