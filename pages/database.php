<script>
$(document).ready(function() {
  $(".search").keyup(function () {
    var searchTerm = $(".search").val();
    var listItem = $('.results tbody').children('tr');
    var searchSplit = searchTerm.replace(/ /g, "'):containsi('")
    
  $.extend($.expr[':'], {'containsi': function(elem, i, match, array){
        return (elem.textContent || elem.innerText || '').toLowerCase().indexOf((match[3] || "").toLowerCase()) >= 0;
    }
  });
    
  $(".results tbody tr").not(":containsi('" + searchSplit + "')").each(function(e){
    $(this).attr('visible','false');
  });

  $(".results tbody tr:containsi('" + searchSplit + "')").each(function(e){
    $(this).attr('visible','true');
  });

  var jobCount = $('.results tbody tr[visible="true"]').length;
    $('.counter').text(jobCount + ' item');

  if(jobCount == '0') {$('.no-result').show();}
    else {$('.no-result').hide();}
		  });
});
</script>

<!-- hidden inputs for visualisation -->
<!-- row1 -->
<input type="hidden" id="gene" value="<?php echo $_GET['gene']; ?>" />
<select id="cluster" style="display:none">
    <option value="No clustering">empty</option>
</select>
<select id="color" style="display:none" />
    <option value="No coloring">empty</option>
</select>
<select id="int_type" style="display:none" />
    <option value="physical_genetic_regulation">empty</option>
</select>
<!-- row 2 -->
<input type="hidden" id="experiments" value="1" />
<input type="hidden" id="publications" value="1" />
<input type="hidden" id="methods" value="1" />
<select id="method_types" multiple="multiple" style="display:none" />
<?php 
    $file_loc = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/data/unique_experimental_methods.txt';
    $compartments = file($file_loc);
    $myfile = fopen($file_loc, "r") or die("Unable to open file!");

    while(!feof($myfile)) {
        $value = trim(fgets($myfile));
        $value_proc = str_replace(': ',':',$value);
        $value_proc = str_replace(' ','_',$value_proc);
        echo '<option value=' . $value_proc . " selected=\"selected\">$value</option>";
    }
    fclose($myfile);
?>
</select>
<!-- row 3 -->
<select id="process"  multiple="multiple" style="display:none" />
<?php 
    $types = array("Cell cycle","Cell division","DNA replication","Metabolism","Signal transduction","None");
    foreach ($types as $value) {
        echo "<option value=\"" . $value . "\"" . " selected=\"selected\")" . ">$value</option>";
    }
?>
</select>
<select id="compartment" style="display:none" />
    <option value="Any">empty</option>
</select>
<select id="expression" multiple="multiple" style="display:none" />
<?php 
    $phases = array("G1(P)", "G1/S","S","G2","G2/M","M","M/G1","G1","No data");
    foreach ($phases as $value) {
        echo "<option value=\"" . $value . "\"" . " selected=\"selected\")" . ">$value</option>";
    }
?>
</select>
<!-- row 4 -->
<input type="hidden" id="max_nodes" value="25" />
<select id="filter_condition" style="display:none" />
    <option value="Eigenvector centrality">empty</option>
</select>

<?php

$dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/data/DB_genes_and_interactions.db';
$db = new SQLite3($dir);


// If gene argument is set from ?gene=, open gene specific page, otherwise database overview
if (isset($_GET['gene'])) {
    $gene = $_GET['gene'];

    $results = $db->query("SELECT * from genes WHERE standard_name = '$gene'");
    $row=$results->fetchArray();

    echo <<<EOT
    <div class="row">
    <div class=" table-responsive col-xs-12">
        <table class="table table-bordered table-condensed table-striped">
            <thead>
                <th>Standard name</th>
                <th>Systematic name</th>
                <th>Name description</th>
                <th>Description</th>
            </thead>
            <tbody>
                <tr>
                    <td>{$row['standard_name']}</td>
                    <td>{$row['systematic_name']}</td>
                    <td>{$row['name_desc']}</td>
                    <td>{$row['desc']}</td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
EOT;

    echo <<<EOT
    <div class="row">
        <div class=" table-responsive col-xs-6">
            <table class="table table-bordered table-condensed table-striped">
                <thead>
                    <th>Visualize the interactome of {$gene}</th>
                </thead>
                <tbody>
                    <tr>
                    <td>
                        <form id="form-id">
                            <div class="submit-btn">
                                <button id="default_visualization" class="button btn btn-primary" style="margin:0px;">Click here</button>
                            </div>
                        </form>
                        <div id="script-text"></div>
                    </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class=" table-responsive col-xs-6">
            <table class="table table-bordered table-condensed table-striped">
            <thead>
                <th>Links to external databases:</th> 
            </thead>
            <tbody>
                <tr>
                <td> 
                    &emsp; 
                    <a href="http://www.yeastgenome.org/locus/{$row['systematic_name']}/overview" target="blank">SGD</a> 
                    &emsp; | &emsp;
                    <a href="http://www.genome.jp/dbget-bin/www_bget?sce:{$row['systematic_name']}" target="blank">KEGG</a>
                    &emsp; | &emsp;
                </td>
                </tr>
            </tobdy>
        </table>
        </div>
    </div>
EOT;

    echo <<<EOT
        <div class="row">
            <div class="table-responsive col-xs-6">
                <h4>Cell cycle phase and timing of transcription peak</h4>
                <table class="table table-bordered table-condensed table-striped">
                    <thead>
                        <th>Cell cycle phase</th>
                        <th>Time (min)</th>
                    </thead>
                    <tbody>
                        <tr>
                        <td>{$row['expression_peak_phase']}</td>
                        <td>{$row['expression_peak_time']}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
EOT;

    echo <<<EOT
        <div class="row">
            <div class="table-responsive col-xs-6">
                <h4>CYCLoPs localization & abundance</h4>
                {$row['CYCLoPs_html']}
            </div>
            <div class="table-responsive col-xs-6">
                <h4>GFP localization & abundance</h4>
                <table id="GFP_table" class="table table-bordered table-condensed table-striped">
                    <thead>
                        <th>GFP localization</th>
                        <th>GFP abundance</th>  
                    </thead>
                    <tbody>
                        <tr>
                        <td>{$row['GFP_localization']}</td>
                        <td>{$row['GFP_abundance']}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
EOT;

    echo <<<EOT
    <div class="row">
        <div class="table-responsive col-xs-12">
            <h4>GO term count</h4>
            {$row['go_terms']}
        </div>
    </div>
EOT;

    // Metabolic enzyme info: only print this for enzymes
    if ($row['is_enzyme'] == 1) {
        echo <<<EOT
        <div class="row">
            <div class=" table-responsive col-xs-12">
                <h4>Enzyme information</h4>
                <table id="enzyme_table" class="table table-bordered table-condensed table-striped">
                    <thead>
                        <th>Catalyzed reactions in Yeast 7.6</th>  
                    </thead>
                    <tbody>
                        <tr>
                        <td>{$row['catalyzed_reactions']}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
EOT;
    }

    // List of interactors
    $results = $db->query("SELECT * from interactions WHERE (source = '$gene' or target = '$gene') ORDER BY num_experiments DESC, num_publications DESC, num_methods DESC");

    //class="table table-bordered table-condensed table-striped"
    $interactor_table = <<<EOT
        <h4>List of interactors</h4>
        Use the search utility to find the interactor you are looking for. 
        By clicking the column headers the table will be sorted on that column. Use shift+click to sort on multiple columns. 
        Default sorting is on number of experiments, number of unique methods and the number of publications, and alphabetical on standard name, in that order.
        <table id="interactor_table" class="table table-bordered table-condensed table-striped">
            <thead>
                <th>Standard name</th>
                <th>Systematic name</th>
                <th>Name description</th>
                <th>Type</th>
                <th>#Experiments</th>
                <th>#Methods</th>
                <th>#Publications</th>
                <th>Evidence</th>
            </thead>
            <tbody>

EOT;
        echo $interactor_table;

    while($row=$results->fetchArray()){
        if ($row['source'] == $gene) {
            $interactor = $row["target"];
        }
        else {
            $interactor = $row["source"];
        }
        $results_interactor = $db->query("SELECT * from genes WHERE standard_name = '$interactor'");
        while($row_interactor=$results_interactor->fetchArray()){
            echo <<<EOT
            <tr>
                <td><a href="index.php?id=database&gene={$interactor}">{$interactor}</a></td>
                <td><a href="http://www.yeastgenome.org/locus/{$row_interactor['systematic_name']}/overview" target="blank"> {$row_interactor['systematic_name']} </a></td>
                <td> {$row_interactor['name_desc']} </td>
                <td> {$row['type']}</td>
                <td> {$row['num_experiments']} </td>
                <td> {$row['num_methods']} </td>
                <td> {$row['num_publications']} </td>
                <td> {$row['evidence_html']} </td>
            </tr>
EOT;
        }
    }
    echo "</tbody></table>";
}
else {

        echo <<<EOT
        <table id="database_content" class="table table-hover table-bordered table-condensed results">
            <thead>
                <tr>
                    <th>Standard name</th>
                    <th>Systematic name</th>
                    <th>Description</th>
                </tr>
            </thead>
        </table>
EOT;

}

?>

<script>

$(document).ready(function() {
    $('#database_content').DataTable( {
        "bProcessing": true,
        "sAjaxSource": "pages/php_includes/return_database_content_json.php",
        "deferRender": true
    });
    } );
$(document).ready(function() {
    $('#interactor_table').DataTable({
        "deferRender": true,
        "order": [[ 4, 'desc' ], [ 5, 'desc' ],[ 6, 'desc' ],[ 0, 'asc' ]]
    });
} );
</script>