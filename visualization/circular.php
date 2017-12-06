<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.2.5/cytoscape.js"></script>
<script src="js/base64toBlob.js"></script>
<script src="js/FileSaver.js"></script>



<?php
  $link_to_json = '../../output/json_files/interactome_' . $gene . '_' . $unique_str . '_csjs.json';
?>

<!-- Load appplication code at the end to ensure DOM is loaded -->
<script>

fetch(<?php echo "'$link_to_json'"; ?>, {mode: 'no-cors'})
.then(function(res) {
  return res.json()
})

.then(function(data) {
  var d_interactions = {"genetic":"grey","physical":"#8f0000","regulation":"blue"}

  // there are 16 compartments for CYCLoPs
  // color source: https://sashat.me/2017/01/11/list-of-20-simple-distinct-colors/
  // first 8: red, green, yellow, blue, orange, purple, cyan, magenta, 
  // second 8: lime, pink, teal, lavender, brown, beige, grey, mint
  // snow
  var d_compartments = {"Bud":"#e6194b","Budsite":"#3cb44b","Nucleus":"#ffe119","Cytoplasm":"#0082c8",
                      "Peroxisome":"#f58231","SpindlePole":"#911eb4","Cell Periphery":"#46f0f0",
                      "Vac/Vac Memb":"#f032e6","Nuc Periphery":"#d2f53c","Cort. Patches":"#fabebe",
                      "Endosome":"#008080","Nucleolus":"#e6beff","Budneck":"#aa6e28","Golgi":"#fffac8",
                      "Mito":"#cccccc","ER":"#aaffc3","No data":"#F8F8FF"};

  // green, yellow, blue, purple, orange, snow
  var d_functions = {"Cell cycle":"#2ca02c","Cell division":"#ffe119","DNA replication":"#0080ff",
                  "Signal transduction":"#cc33cc","Metabolism":"#ff7f0e","None":"#F8F8FF"};

  var cy = window.vis_inner = cytoscape({
    container: document.getElementById('vis_inner'),

    boxSelectionEnabled: false,
    autounselectify: true,

    layout: {
      name: 'circle'
    },

    style: [
      {
        selector: 'node',
        style: {
          'height': 30,
          'width': 30,
          'background-color': function(ele){ 
            if (ele.data("color") in d_functions) {
              return d_functions[ele.data("color")]
            } 
            else if (ele.data("color") in d_compartments) {
              return d_compartments[ele.data("color")]
            } 
            else { 
              return "#e8e406" 
            } 
          },
          label: 'data(id)'
        }
      },

      {
        selector: 'edge',
        style: {
          'curve-style': 'haystack',
          'haystack-radius': 0,
          'width': 5,
          'opacity': 0.5,
          'line-color': function(ele){ 
            if (ele.data("type") in d_interactions) {
              return d_interactions[ele.data("type")]
            } 
            else { 
              return "#f2f08c" 
            } 
          },
        }
      }
    ],

    elements: data
  });

  cy.userZoomingEnabled( false ); // no zooming with mouse

  // click node event
  cy.on('click', 'node', function(evt){
      d = this; // grab the data for the clicked node

      var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
          "<tr><th>Gene</th><td>" + d.data("id") + " (" + d.data('Systematic name') + ")</td></tr>" +
          "<tr><th>Name description</th><td>" + d.data('Name description') + "</td></tr>" + 
          "<tr><th>Cluster</th><td>" + d.data('cluster') + "</td></tr>" +
          "<tr><th>Cell cycle phase of peak expression</th><td>" + d.data('Expression peak') + "</td></tr>" +
          "<tr><th>GFP abundance (localization)</th><td>" + d.data('GFP abundance') + " (" + d.data('GFP localization') + ")</tr></th>" +
          "<tr><th>CYCLoPs localization:</th><td>" + d.data('CYCLoPs_html') + "</tr></td>" +
          "</tbody></table>";
      
      // Send table to the sidebar div
      document.getElementById("info-box").style.display = "block";
      document.getElementById("info-box").innerHTML = table;
  });

  // click edge event
  cy.on('click', 'edge', function(evt){
      d = this; // grab the data for the clicked node

      var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
          "<tr><th>Source</th><td>" + d.data('source') + "</td></tr>" + 
          "<tr><th>Target</th><td>" + d.data('target') + "</td></tr>" +
          "<tr><th>Type</th><td>" + d.data('type') + "</td></tr>" +
          "</tbody></table>";
      
      // Send table to the sidebar div
      document.getElementById("info-box").style.display = "block";
      document.getElementById("info-box").innerHTML = table;
  });

  var png_download_button = document.getElementById('png_download_button');
  png_download_button.onclick = function() {
    var b64key = 'base64,';
    var b64 = cy.png().substring( cy.png().indexOf(b64key) + b64key.length );
    var imgBlob = base64toBlob( b64, 'image/png' );
    saveAs( imgBlob, 'graph.png' );
  }

  // Legend
  // set the color schemes d3 style
  var color_scheme_interactions = d3.scale.ordinal()
      .domain(Object.keys(d_interactions))
      .range(Object.values(d_interactions));

  // make sure to use a node that has a color attribute that isn't None
  // Rare bug: what if all nodes have "None"? This will go wrong here.
  for (i = 0; i < cy.nodes().length; i++) {
    if (cy.nodes()[i].data("color") != "No data") {
      test_node = cy.nodes()[i];
      break;
    }
  }
  if (test_node.data("color") in d_functions) {
      var clusters_list = Object.keys(d_functions);
      var color = d3.scale.ordinal()
          .domain(Object.keys(d_functions))
          .range(Object.values(d_functions));
  }
  else if (test_node.data("color") in d_compartments) {
      var clusters_list = Object.keys(d_compartments);
      var color = d3.scale.ordinal()
          .domain(Object.keys(d_compartments))
          .range(Object.values(d_compartments));
  }
  else {
      console.log("Cannot identify which color scheme to use")
  }

  var legendWidth = document.getElementById("legend-nodes").offsetWidth
      legendHeight = 20 * color.domain().length;

  // ******* Node legend ************
  var svgLegend = d3.select("#legend-nodes").append("svg")
  .style("width",legendWidth)
  .style("height",legendHeight);

  var legend = svgLegend.selectAll(".legend")
  .data(color.domain()) // determines the contents of the legend
  .enter().append("g")
  .attr("class", "legend")
  .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

  legend.append("rect")
  .attr("x", legendWidth - 18) // rectangles have width of 18 and taking 2 for the border this gives 2 px space
  .attr("y", 2) // slight margin from the tip of the div
  .attr("width", 18)
  .attr("height", 18)
  .style("fill", color);

  legend.append("text")
  .attr("x", legendWidth - 18 - 5)
  .attr("y", 2 + 9) // note the extra 9 to align in the middle (18/2)
  .attr("dy", ".35em")
  .style("text-anchor", "end")
  .style("font-size", "14px")
  .text(function(d) { return d; });

  // ******* line legend ************
  var svgLineLegend = d3.select("#legend-lines").append("svg")
  .style("width",legendWidth);

  var linelegend = svgLineLegend.selectAll(".legend")
  .data(color_scheme_interactions.domain()) // determines the contents of the legend
  .enter().append("g")
  .attr("class", "legend")
  .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

  linelegend.append("rect")
  .attr("x", 0) 
  .attr("y", 10) // slight margin from the tip of the div
  .attr("width", 18)
  .attr("height", 2)
  .style("fill", color_scheme_interactions);

  linelegend.append("text")
  .attr("x", 0 + 18 + 5)
  .attr("y", 10) // note the extra 9 to align in the middle (18/2)
  .attr("dy", ".35em")
  .style("text-anchor", "start ")
  .style("font-size", "14px")
  .text(function(d) { return d });

});
</script>
