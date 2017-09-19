<p><font color="red">NOTE THAT THIS IS UNDER CONSTRUCTION AND CURRENTLY INSANELY INCOMPLETE...</font></p>


<h2> Table of contents</h2>
<ul>
<li><a href="#intro">Introduction</a></li>
<ul>
    <li>Aims</li>
    <li>Scope</li>
</ul>
<li><a href="#Methods">Methods</a></li>
<ul>
    <li><a href="#intro">Dependencies</a></li>
    <ul>
        <li><a href="#d3">D3js</a></li>
        <li><a href="#yeastmine">Yeastmine</a></li>
    </ul>
</ul>
<li><a href="#Results">Results</a></li>
<ul>
    <li>...</li>
</ul>
<li><a href="#Discussion">Discussion</a></li>
<ul>
    <li>...</li>
</ul>
</ul>

<h3 id="intro">Introduction</h3>
This tool aims to generate publication-quality visualizations of regulatory and physical interactions between 
protein-coding genes in budding yeast. The visualizations will be genome-wide and multi-scale in the sense
that the visualizations allow compartment localization data to be projected onto the network from the CYCLoPs database and 
can make apparent the function of the various proteins in the interaction network. 

<h4 id="d3">D3js</h4>
<p>
For drawing the visualization we make use of D3js. 
<a href="http://d3js.org">d3js</a> is a JavaScript library for manipulating documents based on data.
The library enables stunning client-side visualization inside the webbrowser.
Commonly in science-related websites (and possibly many others), users need to save the generated 
visualization in vectorized format (e.g. PDF), to be able to incorporate the graphics in presentation or publications.
</p>

<h4 id="yeastmine">SGD API Yeastmine</h4>
To write...

<h3 id="Methods">Methods</h3>
<h4>Global overview</h4>
<p> 
The central access hub for the tool is formed by the index.php webpage. 
The form on this page allows user input, e.g. which gene(s) to center the visualization around. 
A simple AJAX script submits the form for processing and displays a brief message to the user. 
Once submitted a PHP processing page makes sure the input is not faulty and if everything checks out executes a python script
'gen_visualization'. Once this script is done it will have generated a JSON file with the necessary information for the visualization.
The user is then automatically forwarded to a new page with a "box" for the visualization with an embedded D3 visualization based on the JSON file. 
This page will also contain download links for an SVG image of the visualization and an Excel file with all the information on the visualized genes and their
interactions. Furhtermore various tables with this information are also displayed on the page. 
</p>

<p><font color="red">Change the workflow image below. 
VBA is not used anymore, there is now a "webpage" frontend based on HTML/AJAX/PHP the rest is Python and SQL and we use CYCLoPs not Sceptrans.</font>
</p>

<img src="img/workflow.jpg">

<h4>Get data from SGD</h4>
<h4>The database</h4>
<p>The tool relies on a database consisting of two parts. (I) Information on all protein coding genes and their interactions extracted from SGD. 
All this information is stored in the form of a SQLite database. 
(II) expression data in three WT experiments from the CYCLoPs database. This information is stored in Excel files that are read into Python utilizing the pandas module. 
The update_interaction_database.py script completely updates both the SGD information and reloads all the CYCLoPs data from the Excel files. 
</p>

<h3 id="Results">Results</h3>
<p>To write...</p>

<p>Maybe a worked out example with Fkh1,2 for example and then showing what you can learn this way. </p>

<p>Also, update this screenshot below...</p> 

<img src="img/example_output.jpg">


<h3 id="Discussion">Discussion</h3>
To write...
