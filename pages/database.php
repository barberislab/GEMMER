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
// data array to submit when clicking the visualize interactome button
default_settings = {
    'gene'                : <?php echo '"' . $gene . '"';?>,
    'cluster'             : 'No_clustering',
    'color'               : 'No_coloring',
    'int_type'            : 'physical,genetic,regulation',
    'experiments'         : 1,
    'publications'        : 1,
    'methods'             : 1,
    'method_types'        : 'Affinity_Capture-Luminescence,Affinity_Capture-MS,Affinity_Capture-RNA,Affinity_Capture-Western,Biochemical_Activity,chromatin_immunoprecipitation_evidence,chromatin_immunoprecipitation-chip_evidence,chromatin_immunoprecipitation-seq_evidence,Co-crystal_Structure,Co-fractionation,Co-localization,Co-purification,combinatorial_evidence,computational_combinatorial_evidence,Dosage_Growth_Defect,Dosage_Lethality,Dosage_Rescue,Far_Western,FRET,microarray_RNA_expression_level_evidence,Negative_Genetic,PCA,Phenotypic_Enhancement,Phenotypic_Suppression,Positive_Genetic,Protein-peptide,Protein-RNA,Reconstituted_Complex,Synthetic_Growth_Defect,Synthetic_Haploinsufficiency,Synthetic_Lethality,Synthetic_Rescue,Two-hybrid',
    'process'             : "Cell_cycle,Cell_division,DNA_replication,Metabolism,Signal_transduction,None",
    'compartment'         : "all",
    'expression'          : "G1(P),G1/S,S,G2,G2/M,M,M/G1,G1,No_data",
    'max_nodes'           : 25,
    'filter_condition'    : 'Eigenvector_centrality',
    'unique_str'          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    'layout'              : 'D3js', 
}
var default_visualization = document.getElementById('default_visualization');
default_visualization.onclick = function() {return execute_visualization(default_settings);}
</script>

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