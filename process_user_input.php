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

    echo "Querying for " . $gene . "<br/>";

    if (preg_match("/^[A-Za-z][A-Za-z0-9]*(?:_[A-Za-z0-9]+)*$/",$gene)) {

        $dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/DB_genes_and_interactions.db';
        $db = new SQLite3($dir);

        // build the command to execute the python script
        echo str_replace(str_replace($expression,"(",""),")","");
        $array_of_vars = [$gene,$cluster,$color,$int_type,
                        $experiments,$publications,$methods,$method_types,
                        $process,$compartment,str_replace(array("(",")"),"",$expression), // Note we remove brackets here due to errors
                        $max_nodes,$filter_condition,
                        $unique_str];
        $command = 'python ' . $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gen_visualization.py ';
        foreach ($array_of_vars as $var) {
            $command = $command . $var . " ";
        }
        $command = $command . " 2>&1";
        echo $command;

        exec($command, $out, $status);

        // print output
        foreach($out as $result) {
            if (strlen($result) > 1) {
                // if traceback in $result add bootstrap alert around the coming text until end of loop
                echo $result . "<br/>";
            }
        }

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
        echo "Gene ID entered contains invalid characters.";
    }
}
else{
    echo "No gene input given...";
}

?>