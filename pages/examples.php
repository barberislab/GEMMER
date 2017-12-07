<h1>Examples</h1>
<p>
    Below we highlight a collection of examples and test cases with brief descriptions. 
    These serve to showcase the utitlity of GEMMER, and how the menu settings can be used to create different network 
    visualizations that help to answer biological questions. 
</p>
<p>
    <b>Note:</b> Hovering over the links for each example generates a "popover" that shows what the output will look like. 
    Clicking the links launches the visualization.
</p> 

<h2>The default interactive D3.js layout</h2>
These examples use our interactive D3.js layout which allows node clustering and coloring.

<h3>Investigating the CLB1 interactome</h3>
<ul>
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_clb1.svg">
            <a href="" id="ex_clb1">Clb1 interactome: a minimal "hairball" example</a>
        </span>
    </li>
    <p>
        <b>Biological query:</b> a user is interested in the interactome of CLB1.
    </p>
    <p>
        <b>Settings:</b> to illustrate a minimal example we turn off clustering and coloring. 
    </p>
    <p>
        <b>Result:</b> This results in a typical "hairball" network. However, the user may interact with this subset of 25
    genes in the interactome of Clb1, that have been chosen because of their high "degree centrality" in the interactome network.
    Clicking each gene reveals more information on that particular gene. 
    The tables at the bottom of the page may additionally be used for inspection of the genes and interactions. 
    
    For example, looking at the "interactions" table we may sort the table in ascending order on the #publications. 
    This will show interactions with the least number of publications first. For example, we now see the interaction 
    between BUB3 and SGF73 which may be of interest.  The user may now proceed with a single click to the manuscript that showed the interaction
    by clicking on the experimental evidence "Positive Genetic" and arrive at the paper by Costanzo et al. from 2010.
    </p>

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_clb1_clustering.svg">
            <a href="" id="ex_clb1_clustering">Clb1 with clustering and coloring</a>
        </span>
    </li>
    <p>
        <b>Biological query:</b> Given the visualization before, the user is now interested to see the functional classification and 
        expression patterns in this network. 
    </p>
    <p>
        <b>Settings:</b> We set clustering the nodes on the compartment they are most abundant in according to the CYCLoPs measurement WT2
        and coloring the nodes based on their predicted primary function "GO term 1".
    </p>
    <p>
        <b>Result:</b> The interaction network is now clustered and colored which quickly provides new relevant information about nodes in the network. 
    </p>

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_clb1_invert_clustering.svg">
            <a href="" id="ex_clb1_invert_clustering">Clb1 invert clustering and coloring</a>
        </span>
    </li>
    <p>
        <b>Biological query:</b> Given that CLB1 is a cell cycle gene (and is also annotated as such by GEMMER) the user 
        now wants to see how genes that function mainly in the cell cycle potentially interact with metabolically active genes. To that end 
        the users inverts the coloring and clustering settings.
    </p>
    <p>
        <b>Settings:</b> We invert the clustering and coloring: cluster by function "GO term 1", color by "CYCLoPs WT2". 
        
    </p>
    <p>
        <b>Result:</b> The interaction network now reveals an easy overview of 3 clusters and their interactions: cell cycle, metabolism and signal transduction.  
    </p>

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_clb1_invert_clustering_all_interactors.svg">
            <a href="" id="ex_clb1_invert_clustering_all_interactors">Clb1 show all interactors</a>
        </span>
    </li>

    <p>
        <b>Biological query:</b> Upon close inspection however, we now see that GEMMER gives an alert at the top of the page of the previous visualizations about having filtered out some nodes and interactions:
        "This query returned 79 nodes and 718 interactions. We reduced the network to 25 nodes based on Degree centrality resulting in 204 interactions."
    
        Suppose the user wants to see all interactors. 
    </p>
    <p>
        <b>Settings:</b> We increase the "Max. number of nodes" to 80. 
        
    </p>
    <p>
        <b>Result:</b> The interaction network now reveals all interactors and does not display the warning message about filtering anymore.  
    </p>

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_clb1_invert_clustering_all_interactors_phy_reg_2pub.svg">
            <a href="" id="ex_clb1_invert_clustering_all_interactors_phy_reg_2pub">Clb1 physical and regulatory interactions only and a minimum of 2 publications</a>
        </span>
    </li>
    
    <p>
        <b>Biological query:</b> The previous visualization, though showing all interactors, looks kind of chaotic. Suppose the user cares more about physical and regulatory interactions than genetic interactions.
        The user therefore wishes to filter out genetic interactions. As a further step to get to a network of proven interactons, the user 
        wishes to only see interactions that have been shown in a minimum of 2 publications. The aim is to reduce the chaos in the image. 
        </p>
    <p>
        <b>Settings:</b> We set the minimal number of publications to 2, and only select physical and regulation interaction types.
    </p>
    <p>
        <b>Result:</b>  GEMMER returns a visualization with only a couple nodes. These are all nodes that have a physical or regulatory interaction with Clb1 with at least 2 publications reporting on them.
        Also notice that there are no regulatory interactions shown. Almost all regulatory interactions have only been shown in one study. 
    </p>
</ul>

<h3>Investigating the Forkhead transcription factors</h3>
<ul>    
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_FKH12_reg.svg">
            <a href="" id="ex_FKH12_reg">FKH1,2 multi-node example</a>
        </span>
    </li>
    This example highlights the option to build an interaction network by seeding with more than 1 gene. 

    <p>
        <b>Biological query:</b>  In this case, we choose the closely related FKH1,2 (Forkhead) transcription factors. 
        For such genes, we might be particularly interested in the regulatory interactions. The user might vaguely remember that FKH1 and FKH2 interact, but is not sure.
        Let's ask GEMMER.
        </p>
    <p>
        <b>Settings:</b> We select only the regulatory interactions type and limit the number of nodes to 2.
    </p>
    <p>
        <b>Result:</b> GEMMER returns a network with 2 nodes: FKH1 and FKH2. Indeed, there is a regulatory interaction between them. 
        The tables on the bottom of the visualization page point the user to the literature: computational combinatorial evidence through
        the MacIsaac et al. study from 2006..
    </p>

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_FKH12_DNArep_targets_S_phase.svg">
            <a href="" id="ex_FKH12_DNArep_targets_S_phase">FKH1,2 multi-node example: targets active in S phase DNA replication</a>
        </span>
    </li>

    <p>
        <b>Biological query:</b> Which interactors do the Forkhead transcription factors have that function in DNA replication, and 
        have their peak of transcription in the S phase?
        </p>
    <p>
        <b>Settings:</b> We select no clustering and coloring. We reduce the "process" option to only DNA replication and we 
        reduce the "peak expression phase" option to only "S".
    </p>
    <p>
        <b>Result:</b> We see an interaction network with only ORC1 and TDA7. From the table of information on the nodes we gather that ORC1's
        primary function is in DNA replication, whereas TDA7's secondary function is in DNA replication. Note that the FKH2 peaks in G1(P) phase
        and that FKH1 only has a small DNA replication role according to its GO terms (3rd function when counted, see the database page).
    </p>
</ul>



<h2>Alternative network layouts</h2>
GEMMER also provides a collection of alternative network layouts: hierarchical edge bundling, 
a D3 layout using Cola.js, an nxviz matrix plot and a circular layout provided through Cytoscape.js.
We include links to examples of these below. Through the tool each query may be visualized with the alternative layouts by scrolling down 
to the "Alternative visualizations" header and clicking the layout of your choice. 

Each example shown here considers a different layout but queries for FKH1 and FKH2 interactome with clustering and coloring, but only considering
regulatory interactions.

<ul>
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_cola_FKH12_reg.svg">
            <a href="#" id="ex_cola_FKH12_reg">[D3.js + Cola.js] FKH1,2 multi-node example</a>
        </span>
    </li>
    

    <li>
        <span data-toggle="popover" data-full="img/examples/ex_heb_FKH12_reg.svg">
            <a href="" id="ex_heb_FKH12_reg">[Hierarchical edge bundling] FKH1,2 multi-node example</a>
        </span>
    </li>
    
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_large_FKH12_reg.svg">
            <a href="" id="ex_large_FKH12_reg">[Up to 250 nodes] FKH1,2 multi-node example</a>
        </span>
    </li>
    
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_circ_FKH12_reg.png">
            <a href="" id="ex_circ_FKH12_reg">[Cytoscape.js circular] FKH1,2 multi-node example</a>
        </span>
    </li>
    
    <li>
        <span data-toggle="popover" data-full="img/examples/ex_matrix_FKH12_reg.png">
            <a href="" id="ex_matrix_FKH12_reg">[Matrix] FKH1,2 multi-node example</a>
        </span>
    </li>
    
</ul>

<div id="script-text">
</div>

<div class="row spacer-200"></div>

<script>
// save writing by saving the default settings 
var data_default_settings = {
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
    'filter_condition'    : 'Degree_centrality',
    'unique_str'          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    'layout'              : 'D3js'
}

// ###########################
var data_ex_clb1 = $.extend({}, data_default_settings);
data_ex_clb1['gene'] = 'CLB1';

var ex_clb1 = document.getElementById('ex_clb1');
ex_clb1.onclick = function() {return execute_visualization(data_ex_clb1);}
// ###########################

// ###########################
var data_ex_clb1_clustering = $.extend({}, data_ex_clb1);
data_ex_clb1_clustering['cluster'] = 'CYCLoPs_WT2';
data_ex_clb1_clustering['color'] = 'GO_term_1';
data_ex_clb1_clustering['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_clb1_clustering = document.getElementById('ex_clb1_clustering');
ex_clb1_clustering.onclick = function() {return execute_visualization(data_ex_clb1_clustering);}
// ###########################

// ###########################
var data_ex_clb1_invert_clustering = $.extend({}, data_ex_clb1);
data_ex_clb1_invert_clustering['cluster'] = 'GO_term_1';
data_ex_clb1_invert_clustering['color'] = 'CYCLoPs_WT2';
data_ex_clb1_invert_clustering['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_clb1_invert_clustering = document.getElementById('ex_clb1_invert_clustering');
ex_clb1_invert_clustering.onclick = function() {return execute_visualization(data_ex_clb1_invert_clustering);}
// ###########################

var data_ex_clb1_invert_clustering_all_interactors = $.extend({}, data_ex_clb1_invert_clustering);
data_ex_clb1_invert_clustering_all_interactors['max_nodes'] = 80;
data_ex_clb1_invert_clustering_all_interactors['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_clb1_invert_clustering_all_interactors = document.getElementById('ex_clb1_invert_clustering_all_interactors');
ex_clb1_invert_clustering_all_interactors.onclick = function() {return execute_visualization(data_ex_clb1_invert_clustering_all_interactors);}

// ###########################
var data_ex_clb1_invert_clustering_all_interactors_phy_reg_2pub = $.extend({}, data_ex_clb1_invert_clustering_all_interactors);
data_ex_clb1_invert_clustering_all_interactors_phy_reg_2pub['int_type'] = 'physical,regulation';
data_ex_clb1_invert_clustering_all_interactors_phy_reg_2pub['publications'] = 2;
data_ex_clb1_invert_clustering_all_interactors_phy_reg_2pub['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_clb1_invert_clustering_all_interactors_phy_reg_2pub = document.getElementById('ex_clb1_invert_clustering_all_interactors_phy_reg_2pub');
ex_clb1_invert_clustering_all_interactors_phy_reg_2pub.onclick = function() {return execute_visualization(data_ex_clb1_invert_clustering_all_interactors_phy_reg_2pub);}
// ###########################


// ###########################
var data_ex_FKH12_reg = $.extend({}, data_ex_clb1);
data_ex_FKH12_reg['gene'] = 'FKH1_FKH2';
data_ex_FKH12_reg['int_type'] = 'regulation';
data_ex_FKH12_reg['max_nodes'] = 2;
data_ex_FKH12_reg['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_FKH12_reg = document.getElementById('ex_FKH12_reg');
ex_FKH12_reg.onclick = function() {return execute_visualization(data_ex_FKH12_reg);}
// ###########################

var data_ex_FKH12_DNArep_targets_S_phase = $.extend({}, data_ex_FKH12_reg);
data_ex_FKH12_DNArep_targets_S_phase['gene'] = 'FKH1_FKH2';
data_ex_FKH12_DNArep_targets_S_phase['int_type'] = 'physical,genetic,regulation';
data_ex_FKH12_DNArep_targets_S_phase['max_nodes'] = 100;
data_ex_FKH12_DNArep_targets_S_phase['process'] = "DNA_replication";
data_ex_FKH12_DNArep_targets_S_phase['expression'] = "S";
data_ex_FKH12_DNArep_targets_S_phase['unique_str'] = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

var ex_FKH12_DNArep_targets_S_phase = document.getElementById('ex_FKH12_DNArep_targets_S_phase');
ex_FKH12_DNArep_targets_S_phase.onclick = function() {return execute_visualization(data_ex_FKH12_DNArep_targets_S_phase);}
// ###########################

var data_ex_cola_FKH12_reg = $.extend(true, {}, data_ex_FKH12_reg); // copy previous object
data_ex_cola_FKH12_reg['layout'] = 'd3_cola';
data_ex_cola_FKH12_reg['max_nodes'] = '25';

var ex_cola_FKH12_reg = document.getElementById('ex_cola_FKH12_reg');
ex_cola_FKH12_reg.onclick = function() {return execute_visualization(data_ex_cola_FKH12_reg);}
// ###########################

// ###########################
var data_ex_heb_FKH12_reg = $.extend(true, {}, data_ex_cola_FKH12_reg); // copy previous object
data_ex_heb_FKH12_reg['layout'] = 'd3_heb';

var ex_heb_FKH12_reg = document.getElementById('ex_heb_FKH12_reg');
ex_heb_FKH12_reg.onclick = function() {return execute_visualization(data_ex_heb_FKH12_reg);}
// ###########################

var data_ex_large_FKH12_reg = $.extend(true, {}, data_ex_cola_FKH12_reg); // copy previous object
data_ex_large_FKH12_reg['layout'] = 'd3_large';

var ex_large_FKH12_reg = document.getElementById('ex_large_FKH12_reg');
ex_large_FKH12_reg.onclick = function() {return execute_visualization(data_ex_large_FKH12_reg);}
// ###########################

var data_ex_circ_FKH12_reg = $.extend({}, data_ex_cola_FKH12_reg);;
data_ex_circ_FKH12_reg['layout'] = 'circular';

var ex_circ_FKH12_reg = document.getElementById('ex_circ_FKH12_reg');
ex_circ_FKH12_reg.onclick = function() {return execute_visualization(data_ex_circ_FKH12_reg);}
// ###########################

var data_ex_matrix_FKH12_reg = $.extend({}, data_ex_cola_FKH12_reg);
data_ex_matrix_FKH12_reg['layout'] = 'nxviz_matrix';

var ex_matrix_FKH12_reg = document.getElementById('ex_matrix_FKH12_reg');
ex_matrix_FKH12_reg.onclick = function() {return execute_visualization(data_ex_matrix_FKH12_reg);}
// ###########################

// ###########################
</script>

<script>
// Wait for the web page to be ready
$(document).ready(function() {
  // grab all thumbnails and add bootstrap popovers
  // https://getbootstrap.com/javascript/#popovers
  $('[data-toggle="popover"]').popover({
    html: true,
    placement: 'right',
    trigger: 'hover',
    content: function() {
      // get the url for the full size img
      var url = $(this).data('full');
      return '<img src="' + url + '">'
    }
  });
});
</script>