<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  include "labels.php";

  error_log( "====> post=" . print_r( $_POST, true ) );

  // Save identifying timestamp
  $timestamp = $_POST["timestamp"];
  require_once "filenames.php";

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

  // Set up Python command
  $python = getenv( "PYTHON" );
  $summarize = isset( $_POST["startTime"] ) ? "-s" : "";
  $start = $summarize ? "--start " . str_replace( ' ', '', $_POST["startTime"] ) : "";
  $end = isset( $_POST["endTime"] ) ? "--end " . str_replace( ' ', '', $_POST["endTime"] ) : "";
  // --> Deprecated parse.py parameter: Cost per kWh -->
  $cost = $summarize ? "--cost " . $_POST["cost"] : "";
  // <-- Deprecated parse.py parameter <--
  $columns = "-c " . $columnsFilename;
  $command = $python . " parse.py -i " . $inputFilename . " -o " . $resultsFilename . " " . $summarize . " " . $start . " " . $end . " " . $columns;
  error_log( "===> command=" . $command );

  // Execute Python script
  exec( $command, $output, $status );
  @unlink( $inputFilename );

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

  // Completion
  if ( empty( $message ) )
  {
    // Normal: Process results

    // Save script parameters in file
    $params = METASYS_FILE . "," . $_POST["inputName"];
    if ( $summarize )
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
    fclose( $paramsFile );

    // Download results
    downloadFile( $resultsFilename );
  }
  else
  {
    // Failure: Report error
    showMessage( $_POST["inputName"], $message );
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
}
?>
