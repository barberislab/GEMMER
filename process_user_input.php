<?php 

ini_set('display_errors', '1');
ini_set('error_reporting', E_ALL);

// Empty input is already filtered out by the form itself
if (isset($_POST['gene'])) {
    //row1
    $gene = $_POST['gene'];
    $cluster = $_POST['cluster'];
    $color = $_POST['color'];
    $int_type = $_POST['int_type'];
    //row2
    $experiments = $_POST['experiments'];
    $publications = $_POST['publications'];
    $methods = $_POST['methods'];
    $method_types = $_POST['method_types'];
    //row3
    $process = $_POST['process'];
    $compartment = $_POST['compartment'];
    $expression = $_POST['expression'];
    //row4
    $max_nodes = $_POST['max_nodes'];
    $filter_condition = $_POST['filter_condition'];
    //other
    $unique_str = $_POST['unique_str'];

    if (isset($_POST['excel_flag'])) {
        $excel_flag = $_POST['excel_flag'];
    }
    else {
        $excel_flag = 0;
    }
    if (isset($_GET['filter_flag'])) {
        $filter_flag = $_GET['filter_flag'];
    }
    else {
        $filter_flag = 1;
    }

    echo "<p>Submitting query...</p>";

    if (preg_match("/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/",$gene)) {

        $dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/DB_genes_and_interactions.db';
        $db = new SQLite3($dir);

        // build the command to execute the python script
        $array_of_vars = [$gene,$cluster,$color,$int_type,
                        $experiments,$publications,$methods,$method_types,
                        $process,$compartment,str_replace(array("(",")"),"",$expression), // Note we remove brackets here due to errors
                        $max_nodes,$filter_condition,
                        $excel_flag,$filter_flag,$unique_str];
        $command = 'python ' . $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gen_visualization.py ';
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