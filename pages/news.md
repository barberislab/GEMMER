## News on GEMMER development
---

#### December 7th 2017: v1.0 beta VI
This release includes many updates performed after reviewer comments. 

- Github repository public
- New hierarchical edge bundling layout
- Better working layouts with: cytoscape.js (circular), nxviz (matrixplot) and Cola.js (constraint-based, clustered with boxes)
- New export options: JSON + GEXF
- We added a landing page when opening GEMMER that briefly explains what GEMMER is
- We added an examples page to showcase GEMMER's options and utility
- Redesigned side-bar with info for clicked genes and interactions
- We added tooltips to the menu options to explain what they do
- Many documentation updates

---


#### November 12th 2017: v1 beta V
We are working on integration with CytoscapeJS and ColaJS
- We have a functioning circular layout from cytoscapeJS. This required adding a new JSON output file that cytoscape understands
- We also have a functional implementation of a force layout with ColaJS.

---

#### October 31st 2017: v1 beta III
We improved the section of the database-update script that handles GO term assignment. GEMMER should now consider all GO annotations listed in SGD. 

---

#### October 13th 2017: v1 beta II
We reorganized the cgi-bin and added some new data to the database. The database pages for metabolic enzymes contained in the Yeast 7.6 metabolic reconstruction will now display the reactions in that model that the enzyme catalyzes. Additionally we included direct links to SGD and KEGG for all genes. 

--- 

#### October 6th 2017: v1 beta I
This beta version introduces some new features, bug fixes and UI improvements. All critical bugs are now fixed. 
- New features: 
    - we introduced the option to not cluster or color the visualization. 
    - Gene pages in the database now contain a button to immediately visualize that gene's interactome. 
- Bug fixes: 
    - method types filter should now work correctly
    - most missing blue highlighting of the highest expression compartment are now fixed.
    - menu items are now alphabetical where appropriate.
    - Firefox/Safari Bootstrap alert issues where solved. 
- UI improvements: 
    - Excel file output has been cleaned up
    - We cleaned up the error messages that are reported to the user when something goes wrong.

---

#### October 3rd 2017: v1 alpha III
- New features:
    - We separated the visualization from excel file generation in the Python code. As a consequence we now provide export of an Excel file for the filtered and unfiltered networks.
- Bug fixes: 
    - The visualization of the network up to 250 nodes is now working. 

---

#### September 30th 2017: v1 alpha II
Improved user-friendliness. We increased the legend font, replaced MKK1 with NTH1 in default vizualization and added unique colours for all compartments in CYCLoPs. 

---


#### September 19th 2017: v1 alpha I
This is the first release candidate with full functionality but with a considerable collection of bugs and a general lack of documentation. 

---


#### September 2nd 2017: We're live
[GEMMER](http://gemmer.barberislab.com) is now live on its own domain. This is still a (partially functional) development version however.

---
