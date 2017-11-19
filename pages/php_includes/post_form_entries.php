<?php

// Do not set default values here, values should be dictated by the form input
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

?>