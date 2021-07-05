<h1>Excel file export</h1>
<p>
    We will now export the data to Excel. To go back to the visualization use the "Back" button in your browser. 
    Alternatively, use the menu above to navigate elsewhere.
</p>

<?php 

// For getting output with sleep
// header( 'Content-type: text/html; charset=utf-8' );
// header("Cache-Control: no-cache, must-revalidate");
// header ("Pragma: no-cache");
set_time_limit(0);
ob_implicit_flush(1);

if (isset($_GET['gene'])) {
    //row1
    $gene = $_GET['gene'];
    $cluster = $_GET['cluster'];
    $color = $_GET['color'];
    $int_type = $_GET['int_type'];

    //row2
    $experiments = $_GET['experiments'];
    $publications = $_GET['publications'];
    $methods = $_GET['methods'];
    $method_types = $_GET['method_types'];

    //row3
    $process = $_GET['process'];
    $compartment = $_GET['compartment'];
    $expression = $_GET['expression'];

    //row4
    $max_nodes = $_GET['max_nodes'];
    $filter_condition = $_GET['filter_condition'];

    //other
    $unique_str = $_GET['unique_str'];

    if (isset($_GET['excel_flag'])) {
        $excel_flag = $_GET['excel_flag'];
    }
    else {
        $excel_flag = 0;
    }
    if (isset($_GET['filter_flag'])) {
        $filter_flag = $_GET['filter_flag'];
    }
    else {
        $filter_flag = 1; // default to filtering
    }
}
else {
    echo "No gene input given...";
}

$excel_link = $_GET['excel_link'];

$array_of_vars = [$gene,str_replace(" ","_",$cluster),str_replace(" ","_",$color),$int_type,
$experiments,$publications,$methods,$method_types,
$process,$compartment,str_replace(array("(",")"),"",$expression), // Note we remove brackets here due to errors
$max_nodes,str_replace(" ","_",$filter_condition),
$excel_flag,$filter_flag,$unique_str];

// Apache does not know where python3 is automatically. Add location to path
putenv("PATH=/usr/local/bin/:" . exec('echo $PATH'));

$command = 'python3 ' . $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gen_visualization.py ';
foreach ($array_of_vars as $var) {
    $command = $command . $var . " ";
}
$command = $command . " 2>&1";
echo "<p>Busy building the Excel file...</p>";


exec($command, $out, $status);

echo "<p>Done executing command.</p>";

// print output if any
$count = 0;
foreach($out as $result) {
    if (strlen($result) > 1) {
        if ($count == 0) { echo "Python returned the following output.<br/>";}

        echo $result . "<br/>";
    }
    $count += 1;
}

if($status == 0) {
    echo "<br/><br/>Opening Excel file now.<br/>";
    sleep(2);
    $javascript = <<<EOT
<script>
    alert('The Excel file has successfully been generated.');
    window.location.href = "{$excel_link}";
</script>
EOT;

echo $javascript;
}
else {
    echo '<p>Something went wrong in the Excel file generation!</p>';
}

?>