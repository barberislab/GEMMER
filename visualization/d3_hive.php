<!-- Adapted from https://bl.ocks.org/mbostock/7607999 
Most of the credit goes to the original developer. 

NOTE: this uses d3 v4.

The original example used the "Flare" dataset which contains classes where dots indicate hyrarchies. 
The dots dictate the wedges and the final class 'XXX.XXX.Name' the string in the image.
In the original imports generated green links, and being import red. 

We shaped our 'hive json file' to match this format. 
Our 'cluster' setting dictating the hyrarchy of wedges (function or compartments). The genes are shows as string ids.


-->

<style>
.node {
  font: 300 11px "Helvetica Neue", Helvetica, Arial, sans-serif;
  fill: #bbb;
}

.node:hover {
  fill: #000;
}

.link {
  stroke: steelblue;
  stroke-opacity: 0.4;
  fill: none;
  pointer-events: none;
}

.node:hover,
.node--source,
.node--target {
  font-weight: 700;
}

.node--source {
  fill: #2ca02c;
}

.node--target {
  fill: #d62728;
}

.link--source,
.link--target {
  stroke-opacity: 1;
  stroke-width: 2px;
}

.link--source {
  stroke: #d62728;
}

.link--target {
  stroke: #2ca02c;
}

</style>
<body>
<script src="https://d3js.org/d3.v4.min.js"></script>
<script>

var diameter = 700,
    radius = diameter / 2,
    innerRadius = radius - 120;

var cluster = d3.cluster()
    .size([360, innerRadius]);

var line = d3.radialLine()
    .curve(d3.curveBundle.beta(0.85))
    .radius(function(d) { return d.y; })
    .angle(function(d) { return d.x / 180 * Math.PI; });

var svg = d3.select("#vis_inner").append("svg")
    .attr("width", diameter)
    .attr("height", diameter)
  .append("g")
    .attr("transform", "translate(" + radius + "," + radius + ")");

var link = svg.append("g").selectAll(".link"),
    node = svg.append("g").selectAll(".node");

  
d3.json(<?php echo "\"output/json_files/interactome_{$gene}_{$unique_str}_d3hive.json\""; ?>, function(error, graph) {
  if (error) throw error;

  // run through custom function
  var root = packageHierarchy(graph)
      .sum(function(d) { return d.size; });

  // root is a hyrarchy of objects (subclasses of functions)
  console.log("root:",root);

  cluster(root);

  console.log("post-cluster root:",root);

  link = link
    .data(packageImports(root.leaves()))
    .enter().append("path")
      .each(function(d) { d.source = d[0], d.target = d[d.length - 1]; })
      .attr("class", "link")
      .attr("d", line);

  node = node
    .data(root.leaves())
    .enter().append("text")
      .attr("class", "node")
      .attr("dy", "0.31em")
      .attr("transform", function(d) { return "rotate(" + (d.x - 90) + ")translate(" + (d.y + 8) + ",0)" + (d.x < 180 ? "" : "rotate(180)"); })
      .attr("text-anchor", function(d) { return d.x < 180 ? "start" : "end"; })
      .text(function(d) { return d.data.key; })
      .on("mouseover", mouseovered)
      .on("mouseout", mouseouted);
});


function mouseovered(d) {
  node
      .each(function(n) { n.target = n.source = false; });

  link
      .classed("link--target", function(l) { if (l.target === d) return l.source.source = true; })
      .classed("link--source", function(l) { if (l.source === d) return l.target.target = true; })
    .filter(function(l) { return l.target === d || l.source === d; })
      .raise();

  node
      .classed("node--target", function(n) { return n.target; })
      .classed("node--source", function(n) { return n.source; });
}

function mouseouted(d) {
  link
      .classed("link--target", false)
      .classed("link--source", false);

  node
      .classed("node--target", false)
      .classed("node--source", false);
}

// Lazily construct the package hierarchy from class names.
function packageHierarchy(classes) {
  console.log('This:',classes)
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
  console.log("map:",map)
  return d3.hierarchy(map[""]);
}

// Return a list of imports for the given array of nodes.
function packageImports(nodes) {
  console.log("nodes",nodes)
  var map = {},
      imports = [];

  // Compute a map from name to node.
  nodes.forEach(function(d) {
    map[d.data.name] = d;
  });

  // For each import, construct a link from the source to target node.
  nodes.forEach(function(d) {
    console.log("d:",d);
    if (d.data.imports) d.data.imports.forEach(function(i) {
      console.log("i",i);
      console.log("map[d]",map[d.data.name])
      console.log("map[i]",map[i])
      console.log("the issue:",map[d.data.name].path(map[i]))

      imports.push(map[d.data.name].path(map[i]));
    });
  });

  console.log("imports:", imports)
  return imports;
}

</script>