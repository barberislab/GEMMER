<h1>Examples</h1>
Below we highlight a collection of examples and test cases with brief descriptions. 
These serve to showcase the utitlity of GEMMER and how the menu settings can be used to create different network visualizations. 
Click the links to launch the visualization. 

<h2>Our custom D3.js layout</h2>
These examples use our interactive D3.js layout which allows node clustering and coloring.

<ul>
  <li><a href="" id="ex_clb1">Clb1 minimal example</a></li>
  The simplest use case: just visualize the interactome of a single gene (CLB1) and don't do any clustering or coloring. 
  <li><a href="" id="ex_clb1_clustering">Clb1 + clustering</a></li>
  This example shows the same data while additionally clustering the nodes on the compartment they are most abundant 
  in according to the CYCLoPs measurements and coloring the nodes based on their predicted function. 
  <li><a href="" id="ex_clb1_invert_clustering">Clb1 invert clustering and coloring</a></li>
    This example inverts the clustering and coloring: cluster by function, color by compartment. 
  <li><a href="" id="ex_clb1_phyreg_75_2pub">Clb1 physical interactions only, 75 nodes, minimum of 2 publications</a></li>
    This is a more advanced example. Suppose we wish to filter out (not show) genetic interactions, and only consider interactions that have 
    been reported in a minimum of 2 publications. We also want to see more than 25 nodes, up to 75 nodes for instance. 
    GEMMER returns a visualization with only a couple nodes. These are all nodes that have a physical or regulatory interaction with Clb1 with at least 2 publications reporting on them. 
  <li><a href="" id="ex_FKH12_reg">FKH1,2 multi-node example</a></li>
    This example highlights the option to build an interaction network by seeding with more than 1 gene. In this case, we choose the closely related FKH1,2 (Forkhead) transcription factors. 
    For transcription factors like these, we might be particularly interested in the regulatory interactions. So we select those interactions only. 
</ul>

<div id="script-text">
</div>

<h2>D3.js hierarchical edge bundling</h2>
<ul>
    <li><a href="" id="ex_heb_FKH12_reg">FKH1,2 multi-node example</a></li>
    The same example for the FKH1,2 transcription factors as introduced above.
</ul>



<script>
// save writing by saving the default settings 
default_settings = {
    'cluster'             : 'No_clustering',
    'color'               : 'No_coloring',
    'int_type'            : 'physical_genetic_regulation',
    'experiments'         : 1,
    'publications'        : 1,
    'methods'             : 1,
    'method_types'        : 'Affinity_Capture-Luminescence,Affinity_Capture-MS,Affinity_Capture-RNA,Affinity_Capture-Western,Biochemical_Activity,chromatin_immunoprecipitation_evidence,chromatin_immunoprecipitation-chip_evidence,chromatin_immunoprecipitation-seq_evidence,Co-crystal_Structure,Co-fractionation,Co-localization,Co-purification,combinatorial_evidence,computational_combinatorial_evidence,Dosage_Growth_Defect,Dosage_Lethality,Dosage_Rescue,Far_Western,FRET,microarray_RNA_expression_level_evidence,Negative_Genetic,PCA,Phenotypic_Enhancement,Phenotypic_Suppression,Positive_Genetic,Protein-peptide,Protein-RNA,Reconstituted_Complex,Synthetic_Growth_Defect,Synthetic_Haploinsufficiency,Synthetic_Lethality,Synthetic_Rescue,Two-hybrid',
    'process'             : "Cell_cycle,Cell_division,DNA_replication,Metabolism,Signal_transduction,None",
    'compartment'         : "all",
    'expression'          : "G1(P),G1/S,S,G2,G2/M,M,M/G1,G1,No_data",
    'max_nodes'           : 25,
    'filter_condition'    : 'Eigenvector_centrality',
}

// ###########################
data_ex_clb1 = {// create object
    gene                : 'CLB1',
    cluster             : default_settings['cluster'],
    color               : default_settings['color'],
    int_type            : default_settings['int_type'],
    experiments         : default_settings['experiments'],
    publications        : default_settings['publications'],
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : default_settings['max_nodes'],
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var ex_clb1 = document.getElementById('ex_clb1');
ex_clb1.onclick = function() {return execute_visualization(data_ex_clb1);}
// ###########################

// ###########################
data_ex_clb1_clustering = {// create object
    gene                : 'CLB1',
    cluster             : 'CYCLoPs_WT1',
    color               : 'GO_term_1',
    int_type            : default_settings['int_type'],
    experiments         : default_settings['experiments'],
    publications        : default_settings['publications'],
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : default_settings['max_nodes'],
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var ex_clb1_clustering = document.getElementById('ex_clb1_clustering');
ex_clb1_clustering.onclick = function() {return execute_visualization(data_ex_clb1_clustering);}
// ###########################

// ###########################
data_ex_clb1_invert_clustering = {// create object
    gene                : 'CLB1',
    cluster             : 'GO_term_1',
    color               : 'CYCLoPs_WT1',
    int_type            : default_settings['int_type'],
    experiments         : default_settings['experiments'],
    publications        : default_settings['publications'],
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : default_settings['max_nodes'],
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var ex_clb1_invert_clustering = document.getElementById('ex_clb1_invert_clustering');
ex_clb1_invert_clustering.onclick = function() {return execute_visualization(data_ex_clb1_invert_clustering);}
// ###########################

// ###########################
data_ex_clb1_phyreg_75_2pub = {// create object
    gene                : 'CLB1',
    cluster             : 'GO_term_1',
    color               : 'CYCLoPs_WT1',
    int_type            : 'physical_regulation',
    experiments         : default_settings['experiments'],
    publications        : 2,
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : 75,
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var ex_clb1_phyreg_75_2pub = document.getElementById('ex_clb1_phyreg_75_2pub');
ex_clb1_phyreg_75_2pub.onclick = function() {return execute_visualization(data_ex_clb1_phyreg_75_2pub);}
// ###########################


// ###########################
data_ex_FKH12_reg = {// create object
    gene                : 'FKH1_FKH2',
    cluster             : 'GO_term_1',
    color               : 'CYCLoPs_WT1',
    int_type            : 'regulation',
    experiments         : default_settings['experiments'],
    publications        : default_settings['publications'],
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : default_settings['max_nodes'],
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var ex_FKH12_reg = document.getElementById('ex_FKH12_reg');
ex_FKH12_reg.onclick = function() {return execute_visualization(data_ex_FKH12_reg);}
// ###########################

// ###########################
data_ex_heb_FKH12_reg = {// create object
    gene                : 'FKH1_FKH2',
    cluster             : 'GO_term_1',
    color               : 'CYCLoPs_WT1',
    int_type            : 'regulation',
    experiments         : default_settings['experiments'],
    publications        : default_settings['publications'],
    methods             : default_settings['methods'],
    method_types        : default_settings['method_types'],
    process             : default_settings['process'],
    compartment         : default_settings['compartment'],
    expression          : default_settings['expression'],
    max_nodes           : default_settings['max_nodes'],
    filter_condition    : default_settings['filter_condition'],
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'd3_heb', 
}

var ex_heb_FKH12_reg = document.getElementById('ex_heb_FKH12_reg');
ex_heb_FKH12_reg.onclick = function() {return execute_visualization(data_ex_heb_FKH12_reg);}
// ###########################
</script>