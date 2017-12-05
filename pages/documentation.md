# GEMMER Documentation

## Introduction
GEMMER (**GE**nome-wide tool for **M**ulti-scale **M**odeling data **E**xtraction and **R**epresentation) aims to generate publication-quality visualizations of interactions between protein-coding genes in [Saccharomyces cerevisiae](https://en.wikipedia.org/wiki/Saccharomyces_cerevisiae) and serve as a data-integration hub. 

In addition to being an acronym as described above, the word "gemmer" refers to mining of gems ([see the Merriam-webster definition](https://www.merriam-webster.com/dictionary/gemmer)), e.g. precious stones, which we thought apt to the process of discovering new biological insights which we hope GEMMER facilitates. 

The visualizations are genome-wide and multi-scale in the sense that the visualizations allow compartment localization, cell cycle transcription and functional data to be projected onto the network using data from various other databases. Furthermore, all this data may be inspected online and downloaded at the user's convienence. After generating datasets and visualizations using GEMMER, the user may download the data and readily import it into for example [Cytoscape](http://cytoscape.org) for further analysis and ultimately model building.

GEMMER aids (modeling) research by providing:
- Effortless data integration from 3 databases
    - <a href="https://www.yeastgenome.org/" target="blank">SGD</a> (Saccharomyces Genome Database)
    - <a href="http://cyclops.ccbr.utoronto.ca/" target="blank">CYCLoPs</a> (Collection of Yeast Cells Localization Patterns)
    - <a href="https://yeastgfp.yeastgenome.org/" target="blank">YeastGFP</a> (Yeast GFP Fusion Localization Database)
    - <a href="http://www.sceptrans.org" target="blank">SCEPTRANS</a> (Saccharomyces Cerevisiae Periodic Transcription Server)
        - Specifically, we use the peak cell cycle phase transcription data from: M. Rowicka, A. Kudlicki (equal author), B. P. Tu & Z. Otwinowski:  'High-resolution timing of the cell-cycle regulated gene expression', PNAS 104, 16892 (2007). <a href="http://doi.org/10.1073/pnas.0706022104" target="blank">doi: 10.1073/pnas.0706022104</a>
- Interaction networks with localization, abundance and transcription timing information incorporated 
    - Centered around one or more genes
- Data visualization using interactive D3 (not 3D!) drawings
    - With various filtering possibilities
- Data export for interaction networks to SVG and Excel

## GEMMER Workflow
![Workflow](img/GEMMER_workflow.png)

## The database
All of the data GEMMER uses to visualize interaction networks is stored in an SQLite database. 

### Building the database
#### Assignment of primary and secondary function through GO terms
GEMMER predicts functions for protein-coding genes using GO term annotations in SGD. Using YeastMine we collect all GO term annotations for each protein-coding gene in SGD that trace back to one of the following "high-level" GO terms:
* Cellular metabolic process GO:0044237
* Cell cycle GO:0007049
* Cell division GO:0051301
* Signal transduction GO:0007165
* DNA replication GO:0006260

All these terms fall under the GO term cellular process, and DNA replication falls under cellular metabolic process. 

We assign each such GO term annotation to one of the high-level terms listed above. For each gene we add up how many annotations fall under each high-level GO term. The one with the highest count is then assigned as the gene's primary function, the second-highest count gets assigned as the secondary function. The pie chart below displays the distribution of the primary and secondary functions/GO terms across the genome-wide collection of ~6800 protein-coding genes.

![Pie chart of GO term distribution in the genome.](img/pie_chart_go_term_genome.png)

### Interacting with the database
#### Genome-wide search
Through the [database page](index.php?id=database) users may inspect the data GEMMER collected from SGD, CYCLoPs, YeastGFP and SCEPTRANS on a per gene basis. The entire genome-wide collection of protein-coding genes may be searched through the search form on the database page. 

#### Information for a specific gene
On the page for a specific gene, i.e. [SIC1](index.php?id=database&gene=SIC1), users have access to descriptions of the gene (from SGD), GEMMER's functionality predictions based on GO terms from SGD, CYCLoPs/YeastGFP localization and abundance data if available, timing and cell cycle phase of peak transcription from SCEPTRANS and the set of genes it interacts with. The table of interactions allows the user to move on to pages specific for the interactors, search the interactors, and links directly to PubMed to retrieve the original publication where the interaction was shown. 

## Visualizing a network
The central access hub for the tool is formed by the index.php webpage. The form on this page allows user input, e.g. which gene(s) to center the visualization around. An AJAX script submits the form for processing and displays a brief message to the user. Once submitted a PHP processing page makes sure the input is not faulty and if everything checks out executes a Python script 'gen_visualization'. Once this script is done it will have generated a JSON file with the necessary information for the visualization. The user is then automatically forwarded to a new page with a "box" for the visualization with an embedded D3 visualization based on the JSON file. This page also contains download links for an SVG image of the visualization and an Excel file with all the information on the visualized genes and their
interactions. Furthermore various tables with this information are also displayed on the page. 

<!-- ### The form (basic)
![Basic form input](img/input_form_basic.png)

### The form (advanced)
![Advanced form input](img/input_form_advanced.png)

### The visualization page and output


### Exporting the image


### Exporting the data in Excel format

### Visualizing multiple genes together -->


## Dependencies
GEMMER would not be possible without the following open-source projects:
* [YeastMine](https://yeastmine.yeastgenome.org/yeastmine/begin.do)
* [D3.js](https://d3js.org/)
* [PHP](http://php.net/)
* [APACHE](https://www.apache.org/)
* [SQLite](https://www.sqlite.org/)
* [Python](http://python.org)
    * [Pandas](http://pandas.pydata.org/)
    * [NetworkX](https://networkx.github.io/)
* [Bootstrap](http://getbootstrap.com/)
    * [Bootstrap-select](https://silviomoreto.github.io/bootstrap-select/)
* [jQuery](https://jquery.com/)
    * [DataTables](https://datatables.net/)