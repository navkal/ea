<?php
  require_once "../common/util.php";
  require_once "filenames.php" ;

  // Pre-cleanup output files
  @unlink( $resultsFilename );
  @unlink( $paramsFilename );

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
    $command = $python . " parse.py -i " . $metasysFile["tmp_name"] . " -o " . $resultsFilename . " " . $summarize . " " . $start . " " . $end;

    // Execute Python script
    exec( $command, $output, $status );

    // Check whether script generated an output file
    if ( ! file_exists( $resultsFilename ) )
    {
      $message = "Metasys Data Analysis script failed to generate output file.<br/>";
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
    $params = $metasysFile["name"];
    if ( $summarize )
    {
      $params .= "," . str_replace( ' ', '', $_POST["startTime"] );

      if ( $_POST["endTime"] )
      {
        $params .= "," . str_replace( ' ', '', $_POST["endTime"] );
      }
    }

    error_log( "=======> saving params=" . $params );
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
      include "../common/head.php";
      initUi();
    ?>

    <body>
      <div class="container" style="padding-top:30px">
        <div class="alert alert-danger" >
          <h4>Error processing file <b><?php echo $uploadFilename;?></b></h4>
          <p><?php echo $message;?></p>
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
        include "../common/footer.php";
      ?>

    </body>
  </html>
  <script>
    $( 'head' ).append( '<link href="favicon.ico" rel="shortcut icon" type="image/x-icon" />' );
    document.title = "Metasys Data Analysis";
  </script>
<?php
}
?>
