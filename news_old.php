<h3>July 18: v0.9</h3>
<ul>
    <li>Peak cell cycle phase expression data now incorporated from: M. Rowicka, A. Kudlicki (equal author), B. P. Tu & Z. Otwinowski:  'High-resolution timing of the cell-cycle regulated gene expression', PNAS 104, 16892 (2007).</li>
</ul>

<h3>July 18: v0.8</h3>
<ul>
    <li>Filtering for duplicate reactions, reactions matching number of experiments etc. now done through SQL: speedup of ~80%.</li>
    <li>Also reduced the amount of processing required on evidence strings and CYCLoPs data. Speedup of ~30%. Most queries now generate results in < 2 seconds</li>
    <li>Database update time: now dynamically shown at the bottom of the page</li>
    <li>Database pages: CYCLoPs data, GO terms and interactors now shown</li>
</ul>

<h3>July 14th: v0.7</h3>
<ul>
    <li>New color scheme for the website</li>
    <li>The D3 visualization now has colored edges depending on the interaction type and a tooltip for interactions</li>
    <li>Submitted queries now produce unique output files with randomly generated IDs.</li>
</ul>

<h3>July 11th: v0.6</h3>
Site design redone using bootstrap:
<ul>
    <li>Responsive layout</li>
    <li>Output tables for the tool are collapsable</li>
    <li>The compartment filter form allows searching and the database page as well. </li>
</ul>

<h3>July 11th: v0.5</h3>
Compartment filtering now available

<h3>July 7th: v0.4</h3>
Regulation interaction type filter now functional.

<h3>July 5th: v0.3</h3>
The form now remembers the previous job submission. 

<h3>July 4th: v0.2</h3>
<ul>
    <li>The visualization now contains a color legend</li>
    <li>The Database page now shows some limited information on each protein coding gene in our database.</li>
    <li>The excel file contains information on the nodes and is now nicely formatted</li>
</ul>

<h3>June 28th: v0.1</h3>
Filtering just got better:
<ul>
    <li>Filtering based on number of unique experimental methods used vs. num. publications vs. number of experiments</li>
    <li>Filtering physical vs. genetic interactions</li>
</ul>

<h3>June 1st: v0.01</h3>
First version with all basic features. 