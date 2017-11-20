<h1>Examples</h1>
A collection of examples and test cases. 

<h2>Our custom D3.js layout</h2>

<ul>
  <li><a href="" id="gemmer_1">Clb1 minimal example</a></li>
  <li>Example 2</li>
  <li>Example 3</li>
</ul>

<div id="script-text">
</div>

<h2>D3.js hyrarchical edge bundling</h2>
<ul>
  <li>Example 1</li>
  <li>Example 2</li>
  <li>Example 3</li>
</ul>



<script>
data_gemmer_1 = {// create object
    gene                : 'CLB1',
    cluster             : 'No_clustering',
    color               : 'No_coloring',
    int_type            : 'physical_regulation_genetic',
    experiments         : 1,
    publications        : 1,
    methods             : 1,
    method_types        : 'Affinity_Capture-Luminescence,Affinity_Capture-MS,Affinity_Capture-RNA,Affinity_Capture-Western,Biochemical_Activity,chromatin_immunoprecipitation_evidence,chromatin_immunoprecipitation-chip_evidence,chromatin_immunoprecipitation-seq_evidence,Co-crystal_Structure,Co-fractionation,Co-localization,Co-purification,combinatorial_evidence,computational_combinatorial_evidence,Dosage_Growth_Defect,Dosage_Lethality,Dosage_Rescue,Far_Western,FRET,microarray_RNA_expression_level_evidence,Negative_Genetic,PCA,Phenotypic_Enhancement,Phenotypic_Suppression,Positive_Genetic,Protein-peptide,Protein-RNA,Reconstituted_Complex,Synthetic_Growth_Defect,Synthetic_Haploinsufficiency,Synthetic_Lethality,Synthetic_Rescue,Two-hybrid',
    process             : "Cell_cycle,Cell_division,DNA_replication,Metabolism,Signal_transduction,None",
    compartment         : "all",
    expression          : "G1(P),G1/S,S,G2,G2/M,M,M/G1,G1,No_data",
    max_nodes           : 25,
    filter_condition    : 'Eigenvector_centrality',
    unique_str          : randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'),
    layout              : 'D3js', 
}

var gemmer_1 = document.getElementById('gemmer_1');
gemmer_1.onclick = function() {return execute_visualization(data_gemmer_1);}
</script>