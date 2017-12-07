<!DOCTYPE html>
<html lang="en">

<?php
    ini_set('display_errors', '1');
    ini_set('error_reporting', E_ALL);
?>

<head>
    <meta http-equiv="core-Type" core="text/html; charset=utf-8">
    <meta name="viewport" core="width=device-width, initial-scale=1">

    <!--Bootstrap CSS, JQuery and Bootstrap JS-->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script> <!-- first load jquery -->

    <!--Bootstrap-select for nice select buttons-->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/css/bootstrap-select.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.3/js/bootstrap-select.min.js"></script>

    <!-- D3 -->
    <script src="https://d3js.org/d3.v3.min.js"></script>

    <!--  Datatables  -->
    <link rel="stylesheet" type="text/css" href="//cdn.datatables.net/1.10.15/css/jquery.dataTables.css">
    <script type="text/javascript" charset="utf8" src="//cdn.datatables.net/1.10.15/js/jquery.dataTables.js"></script>

    <!--Custom CSS-->
    <link rel="stylesheet" type="text/css" href="css/custom_stylesheet.css" />

    <!--Adjust title with page id-->
    <?php   
        // Set the default name 
        $id = 'landing'; 
        // Specify some disallowed paths 
        $disallowed_paths = array('header', 'footer'); 
        if (!empty($_GET['id'])) { 
            $tmp_id = basename($_GET['id']); 
            // If it's not a disallowed path, and if the file exists, update $id 
            if (!in_array($tmp_id, $disallowed_paths)) 
                $id = $tmp_id; 
        } 
        echo "<title>GEMMER: GEnome-wide tool for Multi-scale Modeling data Extraction and Representation - " . $id . "</title>";
    ?>

    <script src="js/tooltip_toggle.js"></script>
    <script src="js/form-submit.js"></script>

</head>

<body>
    <div class="container">
        <div class="row">
            <div class="col-ld-1"></div> <!-- margin column -->

            <div class="col-ld-10"><!-- A 8 unit wide column for large desktop size --> 
                <div id="page" > <!-- container with white background -->
                    <header id="header">
                        <div id="header-inner">
                            <div class="row row-m-b-0">
                            <div id="logo" class="col-md-3">
                            <div class="image-placeholder">
                                <img src="img/GEMMER_logo.png" class="img-responsive">
                            </div>
                            </div>
                            <div id="logo-desc" class="col-md-9 col-fixed-height">
                                <h1 class="text-primary text-center"><font class="big-letter">GE</font>nome-wide tool 
                                for <font class="big-letter">M</font>ulti-scale <font class="big-letter">M</font>odeling 
                                <br />data <font class="big-letter">E</font>xtraction and
                                <font class="big-letter">R</font>epresentation <br/>
                                <font class="small-letter">for <i>Saccharomyces cerevisiae</i></font></h1>
                            </div>
                            </div>
                            <div class="clr"></div>
                        </div>
                    </header>

                    <div id="feature"> <!-- Menu -->
                        <ul>
                            <li><a href="index.php">Home</a></li>
                            <li><a href="index.php?id=tool">Visualize</a></li>
                            <li><a href="index.php?id=database">Database</a></li>
                            <li><a href="index.php?id=documentation">Documentation</a></li>
                            <li><a href="index.php?id=examples">Examples</a></li>
                            <li><a href="index.php?id=news">News</a></li>
                            <li><a href="https://github.com/ThierryMondeel/GEMMER">Github repository</a></li>
                            <li><a href="index.php?id=contact">Contact</a></li>
                        </ul>
                    </div>

                    <div id="core"> <!-- where the cool stuff happens -->
                        <div id="core-inner"> <!--  -->
                            <main id="corebar">
                                <div class="article">
                                    <?php   
                                        // Include ID from ?id=
                                        if (file_exists("pages/{$id}.php")) {
                                            include("pages/$id.php"); 
                                        }
                                        if (file_exists("pages/{$id}.html")) {
                                            include("pages/$id.html"); 
                                        }
                                    ?>
                                </div>
                            </main>
                            <div class="clr"></div>
                        </div>
                    </div>

                    <footer id="footer"> 
                        <div id="footer-inner">
                            <p>&copy; Copyright <a href="http://www.barberislab.com">Barberislab.com</a> | 
                            <?php 
                            date_default_timezone_set("Europe/Amsterdam");
                            $dbdir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/data/DB_genes_and_interactions.db';
                            if (file_exists($dbdir)) { 
                                echo "Database was last updated: " . date ("F d Y H:i:s.", filemtime($dbdir)); 
                            } 
                            ?>
                            <div class="clr"></div>
                        </div>
                    </footer>
                </div>
            </div>
            
            <div class="col-ld-1"></div> <!-- margin column -->
        </div>
    </div>
</body>

</html>