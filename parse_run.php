<?php
  require_once "../common/util.php";
  require_once "filenames.php" ;
  
  // Pre-cleanup output files
  if ( file_exists( $readyFilename ) )
  {
    error_log( "===> pre-clean " . $readyFilename );
    unlink( $readyFilename );
  }
  if ( file_exists( $resultsFilename ) )
  {
    error_log( "===> pre-clean " . $resultsFilename );
    unlink( $resultsFilename );
  }

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
    if ( $metasysFile["size"] > 500000000 )
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

    // Download results
    downloadFile( $resultsFilename );

    // Tell poller that results are ready
    $readyFile = fopen( $readyFilename, "w" ) or die( "Unable to open file: " . $readyFilename );
    fclose( $readyFile );
  }
  else
  {
    // Failure: report error
    initUi();
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
<?php
}
?>
