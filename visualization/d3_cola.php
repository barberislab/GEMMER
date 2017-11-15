<!-- Cola CDN -->
<script src="http://marvl.infotech.monash.edu/webcola/cola.v3.min.js"></script>

<svg>
    <defs>
        <marker id="blue_arrow" viewbox="0 -5 10 10" refX="28" refY="0"
                markerWidth="5" markerHeight="5" orient="auto"
                fill = #66F stroke=none >
            <path d="M0,-5L10,0L0,5Z">
        </marker>
        <marker id="red_arrow" viewbox="0 -5 10 10" refX="28" refY="0"
                markerWidth="5" markerHeight="5" orient="auto"
                fill = #F66 stroke=none >
            <path d="M0,-5L10,0L0,5Z">
        </marker>
    </defs>
</svg>

<script type="text/javascript">    
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

        var box_width = document.getElementById("vis_inner").offsetWidth //980 // width of the bordered box (includes legend and controls)
            width = document.getElementById("vis_inner").offsetWidth - document.getElementById("moveGUI").offsetWidth -1, // the actual d3 visualization's width - width of the GUI -1
            height = document.getElementById("vis_inner").offsetHeight,
            base_link_opacity = 0.4,
            base_node_opacity = 0.9;

        // SEEMS TO DEFINE THE IMAGE AS THE CONTAINING DIV
        var svg = d3.select("#chart").append("svg")
            .attr("width", box_width)
            .attr("height", height)

        var tip = d3.tip()
            .attr('class', 'd3-tip')
            .direction('s')

        svg.call(tip);

        d3.json(<?php 
                    echo "\"output/json_files/interactome_{$gene}_{$unique_str}{$full}.json\""; 
                ?>, function(error, graph) {   
            // CONFIG PANEL 
            //######################################################
            //# CONFIG PANEL 
            //######################################################
            // FRICTION particle velocity is scaled by the specified friction. 
            //      Thus, a value of 1 corresponds to a frictionless environment, while a value of 0 freezes all particles in place.
            // LINKDISTANCE sets the target distance between linked nodes to the specified value.
            // LINKSTRENGTH sets the strength (rigidity) of links to the specified value in the range [0,1]
            // CHARGE A negative value results in node repulsion, while a positive value results in node attraction. 
            // GRAVITY gravity is implemented as a weak geometric constraint similar to a virtual spring connecting each node to the center of the layout's size.
            // THETA For clusters of nodes that are far away, the charge force is approximated by treating the distance cluster of 
            // nodes as a single, larger node. Theta determines the accuracy of the computation: if the ratio of the area of a 
            // quadrant in the quadtree to the distance between a node to the quadrant's center of mass is less than theta, 
            // all nodes in the given quadrant are treated as a single, larger node rather than computed individually.
            var config = {"friction": .9,  "linkDistance": 250, "linkStrength": 0.5, "charge": 100, "gravity": .3, "theta": .5 };
            var gui = new dat.GUI({ autoPlace: false });

            var fl = gui.addFolder('Force Layout');
            // fl.open() // this opens the setings by default

            var frictionChanger = fl.add(config, "friction", 0, 1);
            frictionChanger.onChange(function(value) {
            force.friction(value)
            force.start()
            });

            var linkDistanceChanger = fl.add(config, "linkDistance", 0, 1000);
            linkDistanceChanger.onChange(function(value) {
            force.linkDistance(value)
            force.start()
            });

            var linkStrengthChanger = fl.add(config, "linkStrength", 0, 1);
            linkStrengthChanger.onChange(function(value) {
            force.linkStrength(value)
            force.start()
            });

            var chargeChanger = fl.add(config,"charge", 0, 500);
            chargeChanger.onChange(function(value) {
            force.charge(-value)
            force.start()
            });

            var gravityChanger = fl.add(config,"gravity", 0, 1);
            gravityChanger.onChange(function(value) {
            force.gravity(value)
            force.start()
            });

            var thetaChanger = fl.add(config,"theta", 0, 1);
            thetaChanger.onChange(function(value) {
            force.theta(value)
            force.start()
            });

            var customContainer = $('.moveGUI').append($(gui.domElement));

            //######################################################
            //# END CONFIG PANEL 
            //######################################################

            // define clusters and nodes
            var nodes = graph.nodes;
                links = graph.links;
                num_nodes = nodes.length;
                num_links = links.length;
        
            // structured as a pyramid with "no data" added to the end
            // var compartments = ["Bud","Budsite","Nucleus","Cytoplasm","Peroxisome","SpindlePole","Cell Periphery","Vac/Vac Memb",
            //                     "Nuc Periphery","Cort. Patches","Endosome","Nucleolus","Budneck","Golgi","Mito","ER",
            //                     "No data"];
            // var functions = ["Cell cycle","Cell division","DNA replication","Signal transduction","Metabolism","None"];

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
            
            // var in_compartments = compartments.indexOf(nodes[0].color);
            // var in_functions = functions.indexOf(nodes[0].color);

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
            // min = 1, max = for 1 node: 21. every 25 nodes the minimum decreases by 5px
            // at 25 nodes nametags might disappear
            // at 100 nodes the minimum is 1
            var minimal_radius = Math.max(1,20 - 5*((num_nodes)/25))
            console.log("Minimal radius: ", minimal_radius)

            // normalize the dc's so that the minimum maps to zero and the maxium to 1
            // when all dc's are equal the max_norm_dc = 0: problem! 
            console.log(nodes);
            var min_dc = Math.min.apply(Math,nodes.map(function(o){return o['Degree centrality'];}))
            console.log("Minimum degree centrality: ", min_dc)
            var max_norm_dc = Math.max.apply(Math,nodes.map(function(o){return o['Degree centrality'] - min_dc;}))
            if (max_norm_dc == 0) {
                max_norm_dc = 1; // to avoid division by zero. 
            }
            console.log("Maximum normalized degree centrality: ", max_norm_dc)

            // loop over nodes
            // var n = 4;
            // var K_d = mean_dc/max_dc; 
            // console.log("Kd for the radius calculation: ", K_d)
            var max_r = 40;

            // loop over nodes
            
            m = clusters_list.length;
            for (var i = 0; i < num_nodes; i++) {
                var c = nodes[i].cluster; // cluster of this node
                var j = clusters_list.indexOf(c); // assign unique cluster number to each node to set coherent starting point

                nodes[i].x = Math.cos(j / m * 2 * Math.PI) * 200 + width / 2 + Math.random();
                nodes[i].y = Math.sin(j / m * 2 * Math.PI) * 200 + height / 2 + Math.random();

                if (nodes.length > 200) {
                    var perc = (nodes[i]['Degree centrality'] - min_dc)/(max_norm_dc); // percentage of the maximum distance to the min. dc in the network
                    var n = 4;
                    var K_d = 0.8;
                    var r = minimal_radius + (max_r - minimal_radius) * (perc**n/(K_d**n + perc**n)); // non-linear Hill curve
                }
                else {
                    var c = nodes[i].cluster; // cluster of this node
                    // percentage of the maximum distance to the min. dc in the network
                    var perc = (nodes[i]['Degree centrality'] - min_dc)/(max_norm_dc); 
                    var r = minimal_radius + (max_r - minimal_radius) * perc; // linear
                }

                nodes[i].radius = r;
                if (!clusters[c] || (r > clusters[c].radius)) {
                        clusters[c] = nodes[i];
                        clusters[c].index = i;
                    } 
            }
            
            num_clusters = Object.keys(clusters).length;
            console.log("The number of clusters:",num_clusters)
            console.log("These nodes are the cluster centres: ",clusters,clusters.length)

            // Artificially increase node size of clusters to a minimum of 15?
            for (var i = 0; i < num_clusters; i++) {
                var c = Object.keys(clusters)[i];
                var ind = clusters[c].index;
                nodes[ind].radius = Math.max(nodes[ind].radius,15)
            }

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

            var constraints = [];
            var force = cola.d3adaptor()
                .nodes(nodes)
                .links(links)
                .size([width, height])
                .constraints(constraints)
                .avoidOverlaps(true)
                .jaccardLinkLengths(50,10)
                .on("tick", tick)
                .start(100);

            var link = svg.append("g")
                .attr('class', 'link')
                .selectAll(".link") // seems essential ??
                .data(links)
                .enter().append("path")
                .style("fill","none")
                .style("stroke-width", function(d) { return 1 + d['#Experiments']/1.5; } )
                .style("stroke", function (d) {
                    if (d.type == "regulation") { console.log('regulation',d); return "blue" } // blue
                    else if (d.type == "physical") { return "#8f0000"} // red
                    else { return "grey" } // red
                    })
                .attr("marker-end", function (d) {
                    if (d.type == "regulation") { return "url(#blue_arrow)" }
                    else { return }
                    })
                .style("stroke-opacity", base_link_opacity)
                .on("mouseover", mouseovered_link)
                .on("mouseout", mouseouted_link);

            var drag = force.drag()
                .on("dragstart", dragstart);

            var highlighted = false
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
                // Build a table of properties
                var table = "<table class=\"table table-condensed table-bordered\"><tbody>" +
                    "<tr><th>Gene</th><td>" + d['Standard name'] + " (" + d['Systematic name'] + ")</td></tr>" +
                    "<tr><th>Name description</th><td>" + d['Name description'] + "</td></tr>" + 
                    "<tr><th>Cluster</th><td>" + d.cluster + "</td></tr>" +
                    "<tr><th>Cell cycle phase of peak expression</th><td>" + d['Expression peak'] + "</td></tr>" +
                    "<tr><th>GFP abundance (localization)</th><td>" + d['GFP abundance'] + " (" + d['GFP localization'] + ")</tr></th>" +
                    "<tr><th>CYCLoPs localization:</th><td>" + d.CYCLoPs_html + "</tr></td>" +
                    "</tbody></table>";
                // Send table to the tip div and the box div
                tip.html(table);
                d3.select("#info-box").html(table)
                circle
                .classed("mouseover", tip.show);
            }
            function mouseouted(d) {
                circle
                .classed("mouseover", tip.hide);
            }
            function mouseovered_link(d) {
                tip.html(
                    "<table class=\"table table-condensed table-bordered\"><tbody>" +
                    "<tr><th>Source</th><td>" + d['source']['Standard name'] + "</td></tr>" + 
                    "<tr><th>Target</th><td>" + d['target']['Standard name'] + "</td></tr>" +
                    "<tr><th>Type</th><td>" + d['type'] + "</td></tr>" +
                    "</tbody></table>"
                    );
                link
                .classed("mouseover", tip.show);
            }
            function mouseouted_link(d) {
                link
                .classed("mouseover", tip.hide);
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
                    highlighted = !highlighted; 

                    console.log("Click!");
                    console.log(highlighted);

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

            function dblclick(d) {
                d3.select(this).classed("fixed", d.fixed = false);
            }
            function dragstart(d) {
                d3.select(this).classed("fixed", d.fixed = true);
            }

            // Create text for nametags in nodes
            var nametags = svg.append("g").selectAll("text")
                .data(force.nodes()) 
                .enter().append("text")
                .style("font", "8px sans-serif")
                .style("letter-spacing", "1px")
                .style("font-weight", "bold")
                .text(function(d) { if (d.radius >= 15) { return d['Standard name']; }
                })
                .style("pointer-events", "none"); // CAN I DELETE THIS?

            function tick(e) {
                // e is an object of type 'tick'
                
                if (num_clusters > 1){
                    circle.each(cluster(10 * e.alpha * e.alpha))
                }
                circle
                    .each(collide(.5))
                    .attr("cx", function(d) { return d.x; })
                    .attr("cy", function(d) { return d.y; })
                    // KEEP NODE POSITION WITHIN BOUNDING BOX                   
                    .attr("cx", function(d) {  // x must be minimally the node diameter on the left, and maximally the width-diameter
                        return d.x = Math.max(d.radius, Math.min(width - d.radius, d.x));
                    })
                    .attr("cy", function(d) {
                        return d.y = Math.max(d.radius, Math.min(height - d.radius, d.y));
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

                // Where to place the gene names
                nametags
                    .attr("x", function(d) { return d.x - 13; })
                    .attr("y", function(d) { return d.y + 3; });
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
                    var r = d.radius + max_r + Math.max(padding, clusterPadding),
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
            var legend = svg.selectAll(".legend")
                .data(color.domain()) // determines the contents of the legend
                .enter().append("g")
                .attr("class", "legend")
                .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });

            legend.append("rect")
                .attr("x", box_width - 22) // width is 18 and taking 2 for the border this gives 2 px space
                .attr("y", 225)
                .attr("width", 18)
                .attr("height", 18)
                .style("fill", color);

            legend.append("text")
                .attr("x", box_width - 24)
                .attr("y", 234) // note the extra 9 to align in the middle (18/2)
                .attr("dy", ".35em")
                .style("text-anchor", "end")
                .style("font-size", "18px")
                .text(function(d) { return d; });

        });
    }
</script>