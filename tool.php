<script src="form-submit.js"></script>

<!-- Advanced options menu panel collapse -->
<script>
// on click glyph toggle between collapsed and expanded glyph
$(document).on('click', '.panel-heading span', function(e){
    var $this = $(this);
    console.log($(this).find('i')[0].className.split(' '))
    if($this.find('i').hasClass('glyphicon-chevron-up')) {
        $this.find('i').removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    } else {
        $this.find('i').removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    }
})
// click the text link
$(document).on('click', '.text_data_toggle', function(e){
    var $this = $(this);
    var element =  document.getElementById('glyph_adv');
    if($("#glyph_adv").hasClass("glyphicon-chevron-up")) {
        $("#glyph_adv").removeClass('glyphicon-chevron-up').addClass('glyphicon-chevron-down');
    } else {
        $("#glyph_adv").removeClass('glyphicon-chevron-down').addClass('glyphicon-chevron-up');
    }
})
</script>

<p>
Enter a gene ID corresponding with an SGD gene into the field below, e.g. SIC1, ORC1, NTH1. 
After several seconds the page will reload with the network visualization.
</p>

<?php // Set default form values or repopulate form with submitted data
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
        $int_type = $_GET['int_type'];
    }
    else {$int_type = 'physical_genetic_regulation';}
    if (isset($_GET['experiments'])) {
        $experiments = $_GET['experiments'];
    }
    //row 2
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
        $method_types = $_GET['method_types'];
        $methods_selected = explode(',',$method_types);
    }
    else {$method_types = '';}
    //row 3
    if (isset($_GET['process'])) {
        $process_orig = $_GET['process'];
        $process = explode(",",str_replace("_"," ",$_GET['process']));
    }
    else {$process = array("Cell cycle","Cell division","DNA replication","Metabolism","Signal transduction","None");} # we will auto-select all
    if (isset($_GET['compartment'])) {
        $compartment = $_GET['compartment'];
    }
    else {$compartment = 'all';}
    if (isset($_GET['expression'])) {
        $expression_orig = $_GET['expression']; // for the excel link
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
    else {$filter_condition = 'Eigenvector centrality';}

    if (isset($_GET['unique_str'])) {
        $unique_str = $_GET['unique_str'];
    }
    else {
        $unique_str = '';
    }
    if (isset($_GET['full'])) {
        $full = '_' . $_GET['full'];
    }
    else {
        $full = '';
    }
?>

<div id="tool_form" class="tool_form">
    <form name="tool" class="form-horizontal">
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <h3 class="panel-title">Visualization input options</h3>
                    </div>
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-3">
                                <label for="gene" id="gene_label">Gene</label>
                            </div>
                            <div class="col-md-3">
                                <label for="cluster" id="cluster_label">Cluster by</label>
                            </div>
                            <div class="col-md-3">
                                <label for="color" id="color_label">Color by</label>
                            </div>
                            <div class="col-md-3">
                                <label for="int_type" id="int_type_label">Interaction type</label>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3">
                                <input type="text" name="gene" id="gene" style='width:100%;' value="<?php echo str_replace('_',', ',$gene); ?>" class="text-input" />
                                <label class="error" for="gene" id="gene_error">This field is required</label>
                            </div>
                            <div class="col-md-3">
                                <select name="cluster" id="cluster" class="selectpicker" data-width="100%">
                                    <?php 
                                        $clusters = array("CYCLoPs WT1", "CYCLoPs WT2", "CYCLoPs WT3", "GO term 1", "GO term 2");
                                        foreach ($clusters as $value) {
                                            $value_proc = str_replace(' ','_',$value);
                                            echo '<option value=' . $value_proc . " " . (($value_proc==$cluster)?'selected="selected"':"") . ">$value</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="color" id="color" class="selectpicker" data-width="100%">
                                    <?php 
                                        $colors = array("CYCLoPs WT1", "CYCLoPs WT2", "CYCLoPs WT3", "GO term 1", "GO term 2");
                                        foreach ($colors as $value) {
                                            $value_proc = str_replace(' ','_',$value);
                                            echo '<option value=' . $value_proc . " " . (($value_proc==$color)?'selected="selected"':"") . ">$value</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select name="int_type" id="int_type" class="selectpicker" data-width="100%">
                                    <?php 
                                        $types = array("physical", "physical, genetic", "physical, genetic, regulation", "physical, regulation", "genetic", "genetic, regulation", "regulation");
                                        foreach ($types as $value) {
                                            $value_proc = str_replace(', ','_',$value);
                                            echo "<option value=\"" . $value_proc . "\"" . (($value_proc==$int_type)?' selected="selected"':"") . ">$value</option>";
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel-group" id="accordion">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h3 class="panel-title">
                            <a data-toggle="collapse" data-parent="#accordion" href="#collapse_adv" class="text_data_toggle">Advanced options</a></h3>
                            <span class="pull-right"><a data-toggle="collapse" data-parent="#accordion" href="#collapse_adv"><i id="glyph_adv" class="glyphicon glyphicon-chevron-down"></i></a></span>
                        </div>
                        <div id="collapse_adv" class="panel-collapse collapse">
                            <div class="panel-body">

                            <div class="row">
                                    <div class="col-md-3">
                                        <label for="experiments" id="experiments_label">Number of experiments</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="methods" id="methods_label">Number of methods</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="method_types" id="method_types">Method types</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="publications" id="publications_label">Number of publications</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="number" name="experiments" id="experiments" style='width:100%;' value="<?php echo $experiments; ?>" class="text-input" />
                                        <label class="error" for="experiments" id="experiments_error">Cannot be lower than 1</label>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="methods" id="methods" style='width:100%;' value="<?php echo $methods; ?>" class="text-input" />
                                        <label class="error" for="methods" id="methods_error">Cannot be lower than 1</label>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="method_types" id="method_types" class="selectpicker" data-live-search="true" multiple data-actions-box="true" data-selected-text-format="count" data-width="100%">
                                            <?php 
                                                $file_loc = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/data/unique_experimental_methods.txt';
                                                $compartments = file($file_loc);
                                                $myfile = fopen($file_loc, "r") or die("Unable to open file!");

                                                while(!feof($myfile)) {
                                                    $value = trim(fgets($myfile));
                                                    $value_proc = str_replace(': ',':',$value);
                                                    $value_proc = str_replace(' ','_',$value_proc);
                                                    if ($method_types == '') {
                                                        echo '<option value=' . $value_proc . " selected=\"selected\">$value</option>";
                                                    }
                                                    else {
                                                        echo '<option value=' . $value_proc . " " . ((in_array($value_proc,$methods_selected))?'selected="selected"':"") . ">$value</option>";
                                                    }
                                                }
                                                fclose($myfile);
                                            ?>
                                            </select>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="number" name="publications" id="publications" style='width:100%;' value="<?php echo $publications; ?>" class="text-input" />
                                        <label class="error" for="publications" id="publications_error">Cannot be lower than 1</label>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="process" id="process">Process</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="compartment" id="compartment_label">Compartment</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="expression" id="expression">Peak expression phase</label>
                                    </div>
                                    <div class="col-md-3">
                                        
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                    <select name="process" id="process" class="selectpicker" multiple data-actions-box="true" data-selected-text-format="count" data-width="100%">
                                        <?php 
                                            $types = array("Cell cycle","Cell division","DNA replication","Metabolism","Signal transduction","None");
                                            foreach ($types as $value) {
                                                echo "<option value=\"" . $value . "\"" . ((in_array($value,$process))?' selected="selected"':"") . ">$value</option>";
                                            }
                                        ?>
                                    </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="compartment" id="compartment" class="selectpicker" data-live-search="true" data-width="100%">
                                            <?php 
                                                $file_loc = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/data/unique_compartments.txt';
                                                $compartments = file($file_loc);
                                                $myfile = fopen($file_loc, "r") or die("Unable to open file!");

                                                while(!feof($myfile)) {
                                                    $value = trim(fgets($myfile));
                                                    $value_proc = str_replace(': ',':',$value);
                                                    $value_proc = str_replace(' ','_',$value_proc);
                                                    echo '<option value=' . $value_proc . " " . (($value_proc==$compartment)?'selected="selected"':"") . ">$value</option>";
                                                }
                                                fclose($myfile);
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="expression" id="expression" class="selectpicker" multiple data-actions-box="true" data-selected-text-format="count" data-width="100%">
                                            <?php 
                                                $phases = array("G1(P)", "G1/S","S","G2","G2/M","M","M/G1","G1","No data");
                                                foreach ($phases as $value) {
                                                    echo "<option value=\"" . $value . "\"" . ((in_array($value,$expression))?' selected="selected"':"") . ">$value</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-md-3">
                                        <label for="max_nodes" id="max_nodes">Max. number of nodes visualized</label>
                                    </div>
                                    <div class="col-md-3">
                                        <label array("metabolism", "cell cycle","cell division","DNA replication","None") for="selection_criteria" id="selection_criteria">Node selection criteria</label>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-3">
                                        <input type="number" name="max_nodes" id="max_nodes" style='width:100%;' value="<?php echo $max_nodes; ?>" class="text-input" />
                                            <label class="error" for="max_nodes" id="max_nodes_error">Minimum: 10, Maximum: 100. See the full network link after submission.</label>
                                    </div>
                                    <div class="col-md-3">
                                        <select name="filter_condition" id="filter_condition" class="selectpicker" data-width="100%">
                                            <?php 
                                                $types = array("Degree centrality", "Eigenvector centrality","Katz centrality");
                                                foreach ($types as $value) {
                                                    echo "<option value=\"" . $value . "\"" . (($value==$filter_condition)?' selected="selected"':"") . ">$value</option>";
                                                }
                                            ?>
                                        </select>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="submit-btn">
            <!-- this button needs to be of class button for javascript to work correctly-->
            <button type="button" class="button btn btn-primary"><span class="glyphicon glyphicon-search"></span> Submit query</button>
        </div>
    </form>
</div>

<div id="script-text">
    <!-- Captures alerts  -->
</div>

<div id="visualization">
    <?php // load the visualization by including the php page
        if (isset($_GET['gene'])) {
            define("DOCUMENT_PATH", $_SERVER['DOCUMENT_ROOT']); 
            include(DOCUMENT_PATH . '/template1.php');

            $js_focus = <<<EOT
            <script>
                document.getElementById('D3_drawing').focus();
            </script>
EOT;
            echo $js_focus;
        } 
        else {
            // Do nothing
        }
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

