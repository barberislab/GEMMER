<html>
<head>
<!--Bootstrap CSS, JQuery and Bootstrap JS-->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

<!--Bootstrap-select for nice select buttons-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/css/bootstrap-select.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/js/bootstrap-select.min.js"></script>

<!--D3, D3.tip and dat-gui-->
<script src="https://d3js.org/d3.v3.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/d3-tip/0.7.1/d3-tip.min.js"></script>
<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/dat-gui/0.5/dat.gui.min.js"></script>

<!--  Datatables  -->
<link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
<script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.15/js/jquery.dataTables.js"></script>

<!--Custom CSS-->
<link rel="stylesheet" type="text/css" href="css/custom_stylesheet.css" />

<style>
div.D3_drawing {
    width: 99%; /* 980px; */
    height: 1200px;
}
.d3-tip {
    font-size: 12px; /* Smaller than bootstrap default of 14 */
    line-height: 1;
    font-weight: bold;
    padding: 12px;
    background: rgba(0, 0, 0, 0.8);
    color: #ffffff;
    border-radius: 2px;
}
.d3-tip table {
    width: auto; /* make sure it just expands with content */
    /* Set max width and word-wrapping */
    word-wrap: break-word;
    min-width: 150px;
    max-width: 300px;
    white-space:normal;
    color: #ffffff;
}
</style>

</head>
<body>

<?php 
    include_once "pages/php_includes/get_form_entries.php";
?>

<div class="alert-warning">
    This is a larger FULL network visualzation only meant for image generation. Return to the previous tab to continue working with the tool.
</div>

<div id="script-text">
    <!-- Captures alerts  -->
</div>

<div id="visualization">
    <?php // load the visualization by including the php page
        define("DOCUMENT_PATH", $_SERVER['DOCUMENT_ROOT']);
        include(DOCUMENT_PATH . '/d3_template.php');
    ?>
</div>

<script>
$(document).ready(function() {
    $('#proteins_table').DataTable({
        "order": [[ 0, 'asc' ]]
    });
} );
$(document).ready(function() {
    $('#interactions_table').DataTable({
        "order": [[ 4, 'desc' ], [ 5, 'desc' ],[ 6, 'desc' ],[ 0, 'asc' ]]
    });
}); 
</script>

</body>
</html>