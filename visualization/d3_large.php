<svg>
    <defs>
        <!-- viewbox contains (startx, starty, width, height) -->
        <marker id="blue_arrow" viewbox="0 -5 10 10" refX="28" refY="0"
                markerWidth="5" markerHeight="5" orient="auto"
                fill = #66F stroke=none >
            <!-- (0,0) is the middle
            start in (0,-5), draw to (10,0), draw to (0,5) fill in the shape and you have a triangle -->
            <path d="M0,-5L10,0L0,5Z"> 
        </marker>
    </defs>
</svg>

<script type="text/javascript">
    function getAvg(x) {
        return x.reduce(function (p, c) {
            return p + c;
        }) / x.length;
    }

    /*
        One-time initialization
    */
    // $(document).ready(function() { // execute upon completion of document loading
        $(function() { // Run immediately
        create_d3js_drawing(); 
        $("#save_as_svg").click(writeDownloadLink); 
    });

    /*
        Generate the d3js drawing from the generated JSON file
    */
    
    function create_d3js_drawing() {

        var width = document.getElementById("vis_inner").offsetWidth
            height = document.getElementById("vis_inner").offsetHeight,
            base_link_opacity = 0.4,
            base_node_opacity = 0.9,
            highlighted = false,
            highlighted_link = false;

        // Put the visualiation in the right div
        var svg = d3.select("#vis_inner").append("svg")
            .attr("width", width)
            .attr("height", height)

        d3.json(<?php 
                    echo "\"output/json_files/interactome_{$gene}_{$unique_str}_full.json\""; 
                ?>, function(error, graph) {   

            // define clusters and nodes
            var nodes = graph.nodes;
                links = graph.links;
                num_nodes = nodes.length;
                num_links = links.length;

            // IF links are too many display a maximum number
            // If compareFunction(a, b) is less than 0, sort a to an index lower than b, i.e. a comes first.
            if (links.length > 1000) {
                links.sort(function(a, b) {
                    return parseFloat(a["#Experiments"]) - parseFloat(b["#Experiments"]);
                });
                links = links.slice(1, 1000);
            }

            // structured as a pyramid with "no data" added to the end

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
            
            var d_interactions = {"genetic":"grey","physical":"#8f0000","regulation":"blue"}
            var color_scheme_interactions = d3.scale.ordinal()
                .domain(Object.keys(d_interactions))
                .range(Object.values(d_interactions));

            if (nodes[0].color in d_functions) {
                var clusters_list = Object.keys(d_functions);
                var color = d3.scale.ordinal()
                    .domain(Object.keys(d_functions))
                    .range(Object.values(d_functions));
            }
            else if (nodes[0].color in d_compartments) {
                var clusters_list = Object.keys(d_compartments);
                var color = d3.scale.ordinal()
                    .domain(Object.keys(d_compartments))
                    .range(Object.values(d_compartments));
            }
            else {
                console.log("Cannot identify which color scheme to use")
            }

            // how many clusters are there?
            var num_clusters = 0;
            for (var i = 0; i < num_nodes; i++) {
                num = nodes[i].cluster
                if (num > num_clusters) num_clusters = num;
            }
             // define clusters array
            var clusters = new Array();

            // based on number of nodes set minimal node radius
            // at 100 nodes the minimum is reached, at 0 nodes the maximum is reached.
            var minimal_radius = 20 - ((20-5)/4)*((num_nodes)/25)// [5 - 20]

            // maximum radisu decreases with number of nodes
            var maximum_radius = 40 - num_nodes/10;

            // normalize the dc's so that the minimum maps to zero and the maxium to 1
            // when all dc's are equal the max_norm_dc = 0: problem! 
            console.log(nodes);
            var min_dc = Math.min.apply(Math,nodes.map(function(o){return o['Degree centrality'];}))
            var max_norm_dc = Math.max.apply(Math,nodes.map(function(o){return o['Degree centrality'] - min_dc;}))
            if (max_norm_dc == 0) {
                max_norm_dc = 1; // to avoid division by zero. 
            }
            var dc_array = nodes.map(function(o){return o['Degree centrality'] - min_dc})
            var mean_dc = getAvg(dc_array)

            // based on network properties set the hill curve parameters
            // n and k_d make the curve steeper. i.e. more nodes with smaller radius
            var n = 1 + 5*num_nodes/100; // [1-6]
            var K_d = Math.min(1,Math.max(0.1,mean_dc + 0.1*num_nodes/100)) // at least 0.1, max 1

            console.log("n:",n,"K_d",K_d)
            console.log("Minimal radius: ", minimal_radius, "Maximal radius: ", maximum_radius)
            console.log("Minimum degree centrality: ", min_dc)
            console.log("Maximum normalized degree centrality: ", max_norm_dc)
            console.log("Average normalized degree centrality",mean_dc)

            // loop over nodes
            m = clusters_list.length;
            r = 6;
            for (var i = 0; i < num_nodes; i++) {
                var c = nodes[i].cluster; // cluster of this node
                var j = clusters_list.indexOf(c); // assign unique cluster number to each node to set coherent starting point

                nodes[i].x = Math.cos(j / m * 2 * Math.PI) * 200 + width / 2 + Math.random();
                nodes[i].y = Math.sin(j / m * 2 * Math.PI) * 200 + height / 2 + Math.random();

                nodes[i].radius = r;
                if (!clusters[c] || (r > clusters[c].radius)) {
                    clusters[c] = nodes[i];
                    clusters[c].index = i;
                }
            }
            
            num_clusters = Object.keys(clusters).length;
            console.log("The number of clusters:",num_clusters)
            console.log("These nodes are the cluster centres: ",clusters,clusters.length)

            // less padding for lots of nodes
            if (num_nodes > 100) {
                var padding = 2;
            }
            else {
                var padding = 15;
            }

            // less padding for many clusters
            if (num_clusters == 1) {
                var padding = 60 - 0.3*num_nodes; // note that in this case we increase general padding
            }
            else if (num_clusters > 5) {
                var clusterPadding = 150;
            }
            else if (num_clusters > 10) {
                var clusterPadding = 100;
            }
            else {
                clusterPadding = 200;
            }

            var force = d3.layout.force()
                .nodes(nodes)
                .links(links)
                .size([width, height])
                .on("tick", tick)
                .linkDistance(250) // How far appart should connected node be (not exactly achieved) as a function of div height
                .linkStrength(0.02) //
                .charge(-100) // negative charge pushes nodes away
                .theta([0.5]) // 
                .gravity([0.3]) // a force that can push nodes towards the center of the layout. Setting gravity to 0 disables it. gravity can also be negative. This gives the nodes a push away from the center.
                .friction([0.9]) // At each step of the physics simulation (or tick as it is called in D3), node movement is scaled by this friction. The recommended range of friction is 0 to 1, with 0 being no movement and 1 being no friction.
                .start(20);

            var link = svg.append("g")
                .attr('class', 'link')
                .selectAll(".link") // seems essential ??
                .data(links)
                .enter().append("path")
                .style("fill","none")
                .style("stroke-width", function(d) { return 1 + d['#Experiments']/1.5; } )
                .style("stroke", function (d) { return d_interactions[d.type] })
                .attr("marker-end", function (d) {if (d.type == "regulation") { return "url(#blue_arrow)" }})
                .style("stroke-opacity", base_link_opacity)
                .on("click", fade_link(0));
                // .on("mouseover", mouseovered_link)
                // .on("mouseout", mouseouted_link);

            var drag = force.drag()
                .on("dragstart", dragstart);

            var circle = svg.selectAll("circle")
                .data(nodes)
                .enter().append("circle")
                .attr("r", function(d) { return d.radius; }) // the node diameter
                .style("fill", function(d) { return color(d.color); })
                .style("stroke","#555")
                .style("stroke-width","2px")
                .on("dblclick", dblclick)
                .on("click", fade(0))
                .call(drag)
                .on("mouseover", mouseovered)
                .on("mouseout", mouseouted);

            circle.transition()
                .duration(750)
                .delay(function(d, i) { return i * 5; })
                .attrTween("r", function(d) {
                var i = d3.interpolate(0, d.radius);
                return function(t) { return d.radius = i(t); };
                });
            
            function mouseovered(d) {
                // empty
            }
            function mouseouted(d) {
                // empty
            }
            function mouseovered_link(d) {
                // empty
            }
            function mouseouted_link(d) {
                // empty
            }
            var linkedByIndex = {};
                links.forEach(function(d) {
                linkedByIndex[d.source.index + "," + d.target.index] = 1;
            });

            function isConnected(a, b) {
                return linkedByIndex[a.index + "," + b.index] || linkedByIndex[b.index + "," + a.index] || a.index == b.index;
            }

            function fade(opacity) {
                return function(d) {
                    // highlighted is True when we are highlighting the interactors of a gene
                    // if this is triggered we switch states
                    highlighted = !highlighted; 

                    if (highlighted) {
                        // Build a table of properties
                        var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
                            "<tr><th>Gene</th><td>" + d['Standard name'] + " (" + d['Systematic name'] + ")</td></tr>" +
                            "<tr><th>Name description</th><td>" + d['Name description'] + "</td></tr>" + 
                            "<tr><th>Cluster</th><td>" + d.cluster + "</td></tr>" +
                            "<tr><th>Cell cycle phase of peak expression</th><td>" + d['Expression peak'] + "</td></tr>" +
                            "<tr><th>GFP abundance (localization)</th><td>" + d['GFP abundance'] + " (" + d['GFP localization'] + ")</tr></th>" +
                            "<tr><th>CYCLoPs localization:</th><td>" + d.CYCLoPs_html + "</tr></td>" +
                            "</tbody></table>";
                        
                        // Send table to the sidebar div
                        d3.select("#info-box").style("display","block")
                        d3.select("#info-box").html(table)
                    }
                    else {
                        d3.select("#info-box").style("display","table")
                        d3.select("#info-box").html("<span>Click on a gene to highlight its connections and display detailed information here.</span>")
                    }

                    if (highlighted) { link_opacity_on = 0.8; link_opacity_off = 0.1; node_opacity = 0.1} else { link_opacity_on = base_link_opacity; link_opacity_off = base_link_opacity; node_opacity = base_node_opacity}

                    circle.style("stroke-opacity", function(o) {
                        thisOpacity = isConnected(d, o) ? base_node_opacity : node_opacity;
                        this.setAttribute('fill-opacity', thisOpacity);
                        return thisOpacity;
                    });

                    // Suppose we go to highlighted. Then all links are at 0.3
                    // All links from selected node should go on, all others off
                    // If toward unhighlited everything becomes 0.3 again
                    link.style("stroke-opacity", function(o) {
                        return o.source === d || o.target === d ? link_opacity_on : link_opacity_off;
                    });
                };
            }

            function fade_link(opacity) {
                return function(d) {
                    highlighted_link = !highlighted_link; 
                    
                    if (highlighted_link) {
                        var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
                        "<tr><th>Source</th><td>" + d['source']['Standard name'] + "</td></tr>" + 
                        "<tr><th>Target</th><td>" + d['target']['Standard name'] + "</td></tr>" +
                        "<tr><th>Type</th><td>" + d['type'] + "</td></tr>" +
                        "</tbody></table>";

                        // Send table to the sidebar div
                        d3.select("#info-box").html(table)
                    }
                    else {
                        d3.select("#info-box").html("Click on a gene to highlight its connections and display detailed information here.")
                    }

                    if (highlighted_link) { link_opacity_on = 0.8; link_opacity_off = 0.1; node_opacity = 0.1} else { link_opacity_on = base_link_opacity; link_opacity_off = base_link_opacity; node_opacity = base_node_opacity}

                    circle.style("stroke-opacity", function(o) {
                        thisOpacity = (o['Standard name'] == d.source['Standard name'] || o['Standard name'] == d.target['Standard name']) ? base_node_opacity : node_opacity;
                        this.setAttribute('fill-opacity', thisOpacity);
                        return thisOpacity;
                    });

                    link.style("stroke-opacity", function(o) {
                        return o.source === d.source && o.target === d.target ? link_opacity_on : link_opacity_off;
                    });
                }
            }

            function dblclick(d) {
                d3.select(this).classed("fixed", d.fixed = false);
            }
            function dragstart(d) {
                d3.select(this).classed("fixed", d.fixed = true);
            }

            function tick(e) {
                // e is an object of type 'tick'
                
                if (num_clusters > 1){
                    circle.each(cluster(10 * e.alpha * e.alpha))
                }
                circle
                    .each(collide(.5))
                    // KEEP NODE POSITION WITHIN BOUNDING BOX                   
                    .attr("cx", function(d) {  
                        // x must be minimally the node diameter + margin
                        // maximally the width - diameter - 5
                        // 5 is an extra margin from the edge including the 1 px border
                        return d.x = Math.max(d.radius + 5, Math.min(width - d.radius - 5, d.x));
                    })
                    .attr("cy", function(d) {
                        return d.y = Math.max(d.radius + 5, Math.min(height - d.radius - 5, d.y));
                    });

                // Define where to start and stop the line segments: from source to target
                link.attr("d", function(d) {
                    var s = d.source,
                        t = d.target,
                        x1 = d.source.x,
                        y1 = d.source.y,
                        x2 = d.target.x,
                        y2 = d.target.y,
                        dx = x2 - x1,
                        dy = y2 - y1,
                        dr = Math.sqrt(dx * dx + dy * dy),

                        // Defaults for normal edge.
                        drx = dr,
                        dry = dr,
                        xRotation = 0, // degrees
                        largeArc = 0, // 1 or 0
                        sweep = 1; // 1 or 0

                        // Self edge
                        if ( x1 === x2 && y1 === y2 ) {
                            // Fiddle with this angle to get loop oriented.
                            xRotation = -45;

                            // Needs to be 1.
                            largeArc = 1;

                            // Change sweep to change orientation of loop. 
                            //sweep = 0;

                            // Make drx and dry different to get an ellipse
                            // instead of a circle.
                            drx = 30;
                            dry = 20;
                            
                            // For whatever reason the arc collapses to a point if the beginning
                            // and ending points of the arc are the same, so kludge it.
                            x2 = x2 + 1;
                            y2 = y2 + 1;
                        } 

                    return "M" + x1 + "," + y1 + "A" + drx + "," + dry + " " + xRotation + "," + largeArc + "," + sweep + " " + x2 + "," + y2;
                });
            }
            
            // Move d to be adjacent to the cluster node.
            function cluster(alpha) {
                return function(d) {
                    var cluster = clusters[d.cluster];
                    if (cluster === d) return;
                    var x = d.x - cluster.x,
                        y = d.y - cluster.y,
                        l = Math.sqrt(x * x + y * y),
                        r = d.radius + cluster.radius;
                    if (l != r) {
                    l = (l - r) / l * alpha;
                    d.x -= x *= l;
                    d.y -= y *= l;
                    cluster.x += x;
                    cluster.y += y;
                    }
                };
            }

            // Resolves collisions between d and all other circles.
            function collide(alpha) {
                var quadtree = d3.geom.quadtree(nodes);
                return function(d) {
                    var r = d.radius + maximum_radius + Math.max(padding, clusterPadding),
                        nx1 = d.x - r,
                        nx2 = d.x + r,
                        ny1 = d.y - r,
                        ny2 = d.y + r;
                    quadtree.visit(function(quad, x1, y1, x2, y2) {
                        if (quad.point && (quad.point !== d)) {
                            var x = d.x - quad.point.x,
                                y = d.y - quad.point.y,
                                l = Math.sqrt(x * x + y * y),
                                r = d.radius + quad.point.radius + (d.cluster === quad.point.cluster ? padding : clusterPadding);
                            if (l < r) {
                                l = (l - r) / l * alpha;
                                d.x -= x *= l;
                                d.y -= y *= l;
                                quad.point.x += x;
                                quad.point.y += y;
                            }
                        }
                    return x1 > nx2 || x2 < nx1 || y1 > ny2 || y2 < ny1;
                    });
                };
            }

            // Legend
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
    }
</script>