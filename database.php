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

$dir = $_SERVER["DOCUMENT_ROOT"] . '/cgi-bin/gemmer/DB_genes_and_interactions.db';
$db = new SQLite3($dir);

// If gene argument is set from ?gene=, open gene specific page, otherwise database overview
if (isset($_GET['gene'])) {
    $gene = $_GET['gene'];
    echo "<h4>Information for " . $gene . '</h4>';

    $results = $db->query("SELECT * from genes WHERE standard_name = '$gene'");

    while($row=$results->fetchArray()){
        $description_table = <<<EOT
        <p>
        <table class="table table-bordered table-condensed table-striped">
            <thead>
                <th>Standard name</th>
                <th>Systematic name</th>
                <th>Name description</th>
                <th>Description</th>
            </thead>
            <tbody>
                <tr>
                    <td><a href="index.php?id=database&gene={$row['standard_name']}">{$row['standard_name']}</a></td>
                    <td><a href="http://www.yeastgenome.org/locus/{$row['systematic_name']}/overview" target="blank">{$row['systematic_name']}</a></td>
                    <td>{$row['name_desc']}</td>
                    <td>{$row['desc']}</td>
                </tr>
            </tbody>
        </table>
        </p>
EOT;

        echo $description_table;

        $GO_table = <<<EOT
            <div class="row">
                <div class="table-responsive col-xs-12">
                    <h4>Detailed parent GO term count</h4>
                    {$row['go_terms']}
                </div>
            </div>
EOT;
        echo $GO_table;

        $CC_table = <<<EOT
            <div class="row">
                <div class="table-responsive col-xs-6">
                    <h4>Timing and cell cycle phase of expression peak</h4>
                    <table class="table table-bordered table-condensed table-striped">
                        <thead>
                            <th>Cell Cycle Phase</th>
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
        echo $CC_table;


        $CYCLoPs_table = <<<EOT
            <div class="row">
                <div class="table-responsive col-xs-6">
                    <h4>CYCLoPs abundance & localization</h4>
                    {$row['CYCLoPs_html']}
                </div>
                <div class=" table-responsive col-xs-6">
                    <h4>GFP abundance & localization</h4>
                    <table id="GFP_table" class="table table-bordered table-condensed table-striped">
                        <thead>
                            <th>GFP abundance</th>
                            <th>GFP localization</th>
                        </thead>
                        <tbody>
                            <tr>
                            <td>{$row['GFP_abundance']}</td>
                            <td>{$row['GFP_localization']}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
EOT;
        echo $CYCLoPs_table;
    }
    // List of interactors
    $results = $db->query("SELECT * from interactions WHERE (source = '$gene' or target = '$gene') ORDER BY num_experiments DESC, num_publications DESC, num_methods DESC");

    //class="table table-bordered table-condensed table-striped"
    $interactor_table = <<<EOT
        <h4>List of interactors</h4>
        Use the search utility to find the gene you are looking for. 
        By clicking the column headers the table will be sorted on that column. Use shift+click to sort on multiple columns. 
        Default sorting is on number of experiments, number of publications, number of methods and alphabetical on standard name, in that order.
        <table id="interactor_table" class="table table-bordered table-condensed table-striped">
            <thead>
                <th>Standard name</th>
                <th>Systematic name</th>
                <th>Name description</th>
                <th>Type</th>
                <th>#Experiments</th>
                <th>#Publications</th>
                <th>#Methods</th>
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
                <td> {$row['num_publications']} </td>
                <td> {$row['num_methods']} </td>
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
        "sAjaxSource": "return_database_content_json.php",
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
