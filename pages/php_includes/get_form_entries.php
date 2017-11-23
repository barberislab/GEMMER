<?php

// Set default form values or repopulate form with submitted data
// default values need to take on the processed form!!
if (isset($_GET['gene'])) {
    $gene = $_GET['gene'];
}
else { $gene = 'SIC1, ORC1, NTH1';}
if (isset($_GET['cluster'])) {
    $cluster = $_GET['cluster'];
}
else {$cluster = 'CYCLoPs_WT1';}
if (isset($_GET['color'])) {
    $color = $_GET['color'];
}
else {$color = 'GO_term_1'; }
if (isset($_GET['int_type'])) {
    $int_type_string = $_GET['int_type']; // the string version
    $int_type = explode(",",$_GET['int_type']); // the array version
}
else {$int_type = array('physical','genetic','regulation');}

//row 2
if (isset($_GET['experiments'])) {
    $experiments = $_GET['experiments'];
}
else {$experiments = 1;}
if (isset($_GET['publications'])) {
    $publications = $_GET['publications'];
}
else {$publications = 1;}
if (isset($_GET['methods'])) {
    $methods = $_GET['methods'];
}
else {$methods = 1;}
if (isset($_GET['method_types'])) {
    $methods_string = $_GET['method_types']; // the string version
    $methods_selected = explode(',',$methods_string); // the array version
}
else {$methods_string = '';}

//row 3
if (isset($_GET['process'])) {
    $process_string = $_GET['process']; // the string version
    $process = explode(",",str_replace("_"," ",$_GET['process'])); // array version
}
else {$process = array("Cell cycle","Cell division","DNA replication","Metabolism","Signal transduction","None");} # we will auto-select all
if (isset($_GET['compartment'])) {
    $compartment = $_GET['compartment'];
}
else {$compartment = 'all';}
if (isset($_GET['expression'])) {
    $expression_string = $_GET['expression']; // for the excel link
    $expression = explode(",",str_replace("_"," ",$_GET['expression']));
}
else {$expression = array("G1(P)", "G1/S","S","G2","G2/M","M","M/G1","G1","No data");}
//row4
if (isset($_GET['max_nodes'])) {
    $max_nodes = $_GET['max_nodes'];
}
else {$max_nodes = 25;}
if (isset($_GET['filter_condition'])) {
    $filter_condition = str_replace("_"," ",$_GET['filter_condition']);
}
else {$filter_condition = 'Degree centrality';}

if (isset($_GET['unique_str'])) {
    $unique_str = $_GET['unique_str'];
}
else {
    $unique_str = '';
}
if (isset($_GET['layout'])) {
    $layout = $_GET['layout'];
}
else {
    // do nothing layout should not exist without submitting a query
}
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
?>