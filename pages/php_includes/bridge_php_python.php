<?php 

ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

include_once $_SERVER["DOCUMENT_ROOT"] . "/pages/php_includes/post_form_entries.php";

// Empty input is already filtered out by the form itself
if (isset($_POST['gene'])) {
    echo "<p>Submitting query...</p>";

    if (preg_match("/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/",$gene)) {

        $dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/data/DB_genes_and_interactions.db';
        $db = new SQLite3($dir);

        // build the command to execute the python script
        $array_of_vars = [$gene,$cluster,$color,$int_type,
                        $experiments,$publications,$methods,$method_types,
                        $process,$compartment,str_replace(array("(",")"),"",$expression), // Note we remove brackets here due to errors
                        $max_nodes,$filter_condition,
                        $excel_flag,$filter_flag,$unique_str];
        
        // Apache does not know where python3 is automatically. Add location to path
        putenv("PATH=/usr/local/bin/:" . exec('echo $PATH'));

        // Execute the python script
        $command = 'python3 ' . $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gen_visualization.py ';
        foreach ($array_of_vars as $var) {
            $command = $command . $var . " ";
        }
        $command = $command . " 2>&1";

        exec($command, $out, $status);

        // print output
        echo "<p><h4>Output messages:</h4>";
        foreach($out as $result) {
            if (strlen($result) > 1) {
                // if traceback in $result add bootstrap alert around the coming text until end of loop
                echo $result . "<br/>";
            }
        }
        echo "</p>";

        if ($status == 0) {
            $filename  = $_SERVER["DOCUMENT_ROOT"] . '/output/include_html/include_interactome_' . $gene . '_' . $unique_str . '.php';
            if (file_exists($filename)) {
                echo "Everything went A-OK.";
            }
            else {
                echo "Output file " . $filename . " does not exist.";
            }
        }
        if ($status > 0) {
            echo "An error occured in the shell command.";
        }
    } 
    else {
        echo "<h4>Warning:</h4>Gene ID entered contains invalid characters.";
    }
}
else{
    echo "<h4>Warning:</h4>No gene input given...";
}

?>