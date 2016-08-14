<?php
  require_once $_SERVER["DOCUMENT_ROOT"]."/../common/util.php";
  include "labels.php";

  // Save identifying timestamp
  $timestamp = $_POST["timestamp"];
  require_once "filenames.php";

  // Check uploaded file for errors
  $metasysFile = $_FILES["metasysFile"];
  $message = "";

  if ( $metasysFile == NULL )
  {
    $message = "No file was uploaded";
  }

  if ( $metasysFile["error"] != 0 )
  {
    $message = "Upload error: " . $metasysFile["error"];
  }

  if ( empty( $message ) )
  {
    if ( $metasysFile["size"] > 499999999 )
    {
      $message = "File too large: " . $metasysFile["size"] . " bytes";
    }
  }

  if ( empty( $message ) )
  {
    // Set up Python command
    $python = ( strpos( strtolower( php_uname( "s" ) ), "windows" ) === FALSE ) ? "python" : '"C:\Users\Ayee\Anaconda3\python.exe"';
    $summarize = $_POST["startTime"] ? "-s" : "";
    $start = $summarize ? "--start " . str_replace( ' ', '', $_POST["startTime"] ) : "";
    $end = $_POST["endTime"] ? "--end " . str_replace( ' ', '', $_POST["endTime"] ) : "";
    $cost = $summarize ? "--cost " . $_POST["cost"] : "";
    $command = $python . " parse.py -i " . $metasysFile["tmp_name"] . " -o " . $resultsFilename . " " . $summarize . " " . $start . " " . $end . " " . $cost;
    error_log( "===> command=" . $command );

    // Execute Python script
    exec( $command, $output, $status );

    // Check whether script generated an output file
    if ( ! file_exists( $resultsFilename ) )
    {
      $message = $labels['metasysDataAnalysis'] . " script failed to generate output file.<br/>";
      foreach ( $output as $line )
      {
        $message .= "<br/>" . $line;
      }
    }
  }

  // Completion
  if ( empty( $message ) )
  {
    // Normal: Process results

    // Save script parameters in file
    $params = $labels["metasysFile"] . "," . $metasysFile["name"];
    if ( $summarize )
    {
      $params .= "," . $labels["startTime"] . "," . str_replace( ' ', '', $_POST["startTime"] );
      if ( $_POST["period"] == $labels["fullday"] )
      {
        $params .= "," . $labels["period"] . "," . $_POST["period"];
      }
      else
      {
        $params .= "," . $labels["endTime"] . "," . str_replace( ' ', '', $_POST["endTime"] );
      }
      $params .= "," . $labels["cost"] . ",$" . $_POST["cost"];
    }
    else
    {
      $params .= "," . $labels["format"] . "," . $labels["detailed"];
    }

    $paramsFile = fopen( $paramsFilename, "w" ) or die( "Unable to open file: " . $paramsFilename );
    fwrite( $paramsFile, $params );
    fclose( $paramsFile );

    // Download results
    downloadFile( $resultsFilename );
  }
  else
  {
    // Failure: Report error
    showMessage( $metasysFile["name"], $message );
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
         <a class="btn btn-default" href="javascript:history.back()" role="button">OK</a>
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
    document.title = "<?=$labels['metasysDataAnalysis']?>";
  </script>
<?php
}
?>
