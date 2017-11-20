<!-- Adapted from https://bl.ocks.org/mbostock/7607999 
Most of the credit goes to the original developer. 

NOTE: this uses d3 v4.

The original example used the "Flare" dataset which contains classes where dots indicate hyrarchies. 
The dots dictate the wedges and the final class 'XXX.XXX.Name' the string in the image.
Our imports field has changed w.r.t. the original. We changed it from a array of strings to an array of dictionaries with id and other attributes 

We shaped our 'hive json file' to match this format. 
Our 'cluster' setting dictating the hyrarchy of wedges (function or compartments). The genes are shown as string ids.
-->

<body>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script>

var width = document.getElementById("vis_inner").offsetWidth,
    height = document.getElementById("vis_inner").offsetHeight,
    radius = height / 2, // use height because our div has height < width
    innerRadius = radius / 1.25 // actual size of the circle
    highlighted = false // tracks if user clicked a node
    base_link_opacity = 0.05,
    base_node_opacity = 1.0;

// setup alias to cluster function
var cluster = d3.cluster()
    .size([360, innerRadius]);

var line = d3.radialLine()
    .curve(d3.curveBundle.beta(0.85))
    .radius(function(d) { return d.y; })
    .angle(function(d) { return d.x / 180 * Math.PI; });

var svg = d3.select("#vis_inner").append("svg")
    .attr("width", width)
    .attr("height", height)
  .append("g")
    .attr("transform", "translate(" + radius + "," + radius + ")");

var link = svg.append("g").selectAll(".link");
    node = svg.append("g").selectAll(".node");

  
d3.json(<?php echo "\"output/json_files/interactome_{$gene}_{$unique_str}_d3hive.json\""; ?>, function(error, graph) {
  if (error) throw error;

  // run through custom function
  // returns layered hyrarchy of objects
  // each cluster having "data" and "children" objects
  var root = packageHierarchy(graph)
      .sum(function(d) { return d.size; });

  console.log(root)
  // root is a hyrarchy of objects (subclasses of functions)
  // run cluster on root
  cluster(root);

  link = link
    .data(packageImports(root.leaves())) // build list of links
    .enter().append("path")
      .attr("d", line)
      .each(function(d) { d.source = d[0], d.target = d[d.length - 1]; }) // assign the source/target from the array of objects
      .attr("class", "link")
      // .style("stroke","steelblue")
      .style("stroke",function(l) {if (l.type == 'regulation') {return "steelblue"} else if (l.type == 'physical') {return "darkred"} else {return "grey"} } )
      .style("stroke-opacity",base_link_opacity)
      .style("fill","none")
      .style("pointer-events","none");

  node = node
    .data(root.leaves())
    .enter().append("text")
      .attr("class", "node")
      .attr("dy", "0.31em")
      .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + (d.y + 8) + ",0)" + (d.x < 180 ? "" : "rotate(180)"); })
      .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
      .text(function(d) { return d.data.key; })
      .style('font','300 11px "Helvetica Neue", Helvetica, Arial, sans-serif')
      .style('fill','#000')
      .style("opacity", base_node_opacity)
      .on("click", click_event);
});

function click_event(d) {
  highlighted = !highlighted; // flip switch

  if (highlighted) { // highlight stuff
    highlight_event(d);
  }
  else { // remove highlighting
    unhighlight_event(d);
  }
}

function highlight_event(d) {
  console.log(d)
  node
      .each(function(n) { n.target = n.source = false; });

  // Set link classes for interactions involving clicked node
  // Also update node objects with a flag for being an interactor
  link
      .style("stroke-opacity",base_link_opacity / 2)
      .classed("link--interaction", function(l) { if (l.target === d) return l.source.interactor = true; })
      .classed("link--interaction", function(l) { if (l.source === d) return l.target.interactor = true; })
    .filter(function(l) { return l.target === d || l.source === d; })
      .raise(); // make sure link is on top 

  // assign special class to interactor nodes
  node
    .classed("node-interactor", function(n) { return n.interactor; })

  // assign special class to the clicked node
  node
    .classed("node-clicked", function(n) { return n == d; })

  d3.select("body").selectAll(".link--interaction")
    .style("stroke-opacity",base_link_opacity * 10)
    .style("stroke-width","2px");

  // set styling on all nodes (to hide the non-highlighted ones)
  node
    .style("fill", "#bbb")
    .style("opacity",base_node_opacity / 3);

  // set styling on sources and targets
  d3.select("body").selectAll(".node-interactor")
    .style("opacity",base_node_opacity)
    .style("fill", "#000")
    .style("font-weight",700);

  // the hovered node should be black to distinguish from the rest
  d3.select("body").selectAll(".node-clicked")
    .style("fill", "red")
    .style("font-weight",700);

  var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
    "<tr><th>Gene</th><td>" + d.data['key'] + " (" + d.data['Systematic name'] + ")</td></tr>" +
    "<tr><th>Name description</th><td>" + d.data['Name description'] + "</td></tr>" + 
    "<tr><th>Cluster</th><td>" + d.data.cluster + "</td></tr>" +
    "<tr><th>Cell cycle phase of peak expression</th><td>" + d.data['Expression peak'] + "</td></tr>" +
    "<tr><th>GFP abundance (localization)</th><td>" + d.data['GFP abundance'] + " (" + d.data['GFP localization'] + ")</tr></th>" +
    "<tr><th>CYCLoPs localization:</th><td>" + d.data.CYCLoPs_html + "</tr></td>" +
    "</tbody></table>";
  
  // Send table to the sidebar div
  d3.select("#info-box").html(table)
}

function unhighlight_event(d) {

  link
      .classed("link--target", false)
      .classed("link--source", false)
      .style("stroke-opacity",base_link_opacity)
      .style("stroke-width","1px");

  node
      .classed("node-highlight", false)
      .style("opacity",base_node_opacity)
      .style("fill", "#000").style("font",'300 11px "Helvetica Neue", Helvetica, Arial, sans-serif'); // reset style

  d3.select("#info-box").html("Click on a gene to highlight its connections and display detailed information here.")

}

// Lazily construct the package hierarchy from class names.
function packageHierarchy(classes) {
  var map = {};

  function find(name, data) {
    var node = map[name], i;
    if (!node) {
      node = map[name] = data || {name: name, children: []};
      if (name.length) {
        node.parent = find(name.substring(0, i = name.lastIndexOf(".")));
        node.parent.children.push(node);
        node.key = name.substring(i + 1);
      }
    }
    return node;
  }

  classes.forEach(function(d) {
    find(d.name, d);
  });

  return d3.hierarchy(map[""]);
}

// Return a list of imports for the given array of nodes.
function packageImports(nodes) {
  // take in the list of nodes, their properties and list of "imports"
  var map = {},
      imports = [];

  // Dictionary that maps from name to node object
  nodes.forEach(function(d) {
    map[d.data.name] = d;
  });

  // loop over all nodes, within each over all imports
  // For each import, construct a link from the source to target node.
  nodes.forEach(function(d) {
    if (d.data.imports) d.data.imports[0].forEach(function(i) {
      // imports is a list of dictionaries, so i is a dictionary

      // this path function produces an array with multiple objects
      // one for the source, one for the target, and one for each their parents (which may overlap)
      // when the parents are not the same also one for the ultimate parent ""
      // so this array will have a length between 3 and 5
      // object 1: source, last object: target

      // save path from d to i in imports
      var link_obj = map[d.data.name].path(map[i.id])
      link_obj.type = i.type;
      
      imports.push(link_obj);
    });
  });

  return imports;
}

</script>