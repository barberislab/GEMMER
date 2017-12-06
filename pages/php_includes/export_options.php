<div id="export_options">
<?php 
    $arg_names = ['gene','cluster','color','int_type','experiments','publications','methods','method_types',
    'process','compartment','expression','max_nodes','filter_condition',
    'unique_str','excel_flag'];

    // note the use of _orig fr process and expression. The non _orig variables are arrays, these are strings.
    $args = [$gene,$cluster,$color,$int_type_string,$experiments,$publications,$methods,$methods_string,
            $process_string,$compartment,$expression_string, // Note we remove brackets here due to errors
            $max_nodes,$filter_condition,
            $unique_str,TRUE];

    for($i = 0; $i < count($args); ++$i) {
        if ($i==0) { 
            $php_args = $arg_names[$i] . "=" . $args[$i];
        }
        else{
            $php_args = $php_args . "&" . $arg_names[$i] . "=" . $args[$i];
        }
    }

    echo "<h3>Alternative network visualizations</h3>";
    echo <<<EOT
    <table class="table">
    <tr>
        <th>
            Interactive D3.js
        </th>
        <td>
            <a href="index.php?id=tool&$php_args&layout=D3js" class="alert-link">GEMMER D3.js</a>
        </td>
        <td>
            <a href="index.php?id=tool&$php_args&layout=d3_cola" class="alert-link">D3.js with cola</a>
        </td>
        <td>
            <a href="index.php?id=tool&$php_args&layout=d3_heb" class="alert-link">D3.js hierarchival edge bundles</a>
        </td>
        <td>
            <a href="index.php?id=tool&$php_args&layout=d3_large" class="alert-link">D3.js max. 250 nodes</a>
        </td>
    </tr>
    <tr>
        <th>
            Interactive Cytoscape.js (work in progress) 
        </th>
        <td>
            <a href="index.php?id=tool&$php_args&layout=circular" class="alert-link">Circular layout</a>
        </td>
        <td>
            <a href="index.php?id=tool&$php_args&layout=cytoscape_colajs" class="alert-link">CytoscapeJS-Cola layout</a>
        </td>
    </tr>
    <tr>
        <th>
            Non-interactive
        </th>
        <td>
            <a href="index.php?id=tool&$php_args&layout=nxviz_matrix" class="alert-link">Nxviz matrix plot</a>
        </td>
    </tr>
    </table>

EOT;

    echo "<h3>Export options</h3>";
    // SVG export for D3
    if ($layout == 'D3js' | $layout == 'd3_cola' | $layout == 'd3_heb' | $layout == 'd3_large') {
        echo '<span style="display:inline-block; width: 150px;" data-toggle="tooltip" data-placement="top" title="Download the image in SVG format (by right-clicking Download SVG and Save as, or by opening it in a new tab).">';
        echo '<img style="vertical-align:middle" src="img/noun/svg_blue.svg" width=20%>';
        echo '<a href="#" id="download">Download SVG</a></span>'; 
    }
    elseif ($layout == 'circular') {
        echo '<span style="display:inline-block; width: 180px;">';
        echo '<button id="png_download_button" type="button" class="button btn btn-primary"><span class="glyphicon glyphicon-download-alt"></span>Download PNG</button>';
        echo '</span>';

        // script to download the png through cytoscape.js API
        // see: https://stackoverflow.com/questions/39168928/cytoscape-save-graph-as-image-by-button
//         echo <<<EOT
//         <script>
//             var png_download_button = document.getElementById('png_download_button');
//             png_download_button.onclick = function() {
//                 var b64key = 'base64,';
//                 var b64 = cy.png().substring( cy.png().indexOf(b64key) + b64key.length );
//                 var imgBlob = base64ToBlob( b64, 'image/png' );
//                 saveAs( imgBlob, 'graph.png' );
//             }
//         </script>
// EOT;
    }

    // relative to the pages/php_includes folder
    $excel_output_link = "../../output/excel_files/interactome_{$gene}_{$unique_str}.xlsx";

    $php_args_excel = "excel_link=$excel_output_link&" . $php_args;

    // Excel image
    echo '<span style="display:inline-block; width: 280px;">
    <img style="vertical-align:middle" src="img/noun/excel_green.svg" width=12%>';

    // Excel for filtered network
    echo "<a href=\"index.php?id=export_excel_file&{$php_args_excel}\" target=\"blank\"> Visualized network</a>";

    // Excel for full network
    // add the filter_flag (i.e. show all results or not) and set it to 0 (i.e. no filtering)
    $php_args_excel = $php_args_excel . "&filter_flag=0";
    echo " | ";
    echo "<a href=\"index.php?id=export_excel_file&{$php_args_excel}\" target=\"blank\"> Full network</a></span>";


    // json
    echo '<span style="display:inline-block; width: 300px;">';
    echo '<img style="vertical-align:middle" src="img/noun/json_purple.svg" width=13%>';
    echo "<a download href=\"output/json_files/interactome_{$gene}_{$unique_str}.json\" target=\"blank\"> D3.js</a> | ";
    echo "<a download href=\"output/json_files/interactome_{$gene}_{$unique_str}_csjs.json\" target=\"blank\">Cytoscape.js</a> | ";
    echo "<a download href=\"output/json_files/interactome_{$gene}_{$unique_str}_d3hive.json\" target=\"blank\">D3.js H.E.B.</a></span>";

    // GEFX
    echo '<span style="display:inline-block; width: 350px;">';
    echo '<img style="vertical-align:middle" src="img/noun/graph_orange.svg" width=16%>';
    echo "<a download href=\"output/networkx/{$gene}_{$unique_str}.gexf\" target=\"blank\">GEXF visualized network</a> | ";    
    echo "<a download href=\"output/networkx/{$gene}_{$unique_str}_full.gexf\" target=\"blank\">GEXF full network</a></span>";
?>

<!-- Hidden <FORM> to submit the SVG data to the server, which will convert it to SVG/PDF/PNG downloadable file.
The form is populated and submitted by the JavaScript below. -->
<form id="svgform" method="post" action="../cgi-bin/download_svg.pl">
    <input type="hidden" id="output_format" name="output_format" value="">
    <input type="hidden" id="data" name="data" value="">
</form>

<!--Javascript for downloading the SVG. The hidden form "svgform"" submits to a perl script -->
<script>
    console.log(d3.select("#download"))
    d3.select("#download")
        .on("mouseover", writeDownloadLink);

    function writeDownloadLink() {
        var html = d3.select("svg")
            .attr("title", "svg_title")
            .attr("version", 1.1)
            .attr("xmlns", "http://www.w3.org/2000/svg")
            .node().parentNode.innerHTML; // this line is essential

        d3.select(this)
            .attr("href-lang", "image/svg+xml")
            .attr("href", "data:image/svg+xml;base64,\n" + btoa(html))
            .on("mousedown", function() {
                if (event.button != 2) {
                    d3.select(this)
                        .attr("href", null)
                        .html("Use right click");
                }
            })
            .on("mouseout", function() {
                d3.select(this)
                    .html("Download SVG");
            });
    };
</script>
<script type="text/javascript">
    /*
    Utility function: populates the <FORM> with the SVG data
    and the requested output format, and submits the form.
    */
    function submit_download_form(output_format) {
        // Get the d3js SVG element
        var tmp = document.getElementById("vis_inner");
        var svg = tmp.getElementsByTagName("svg")[0];
        // Extract the data as SVG text string
        var svg_xml = (new XMLSerializer).serializeToString(svg);

        // Submit the <FORM> to the server.
        // The result will be an attachment file to download.
        var form = document.getElementById("svgform");
        form['output_format'].value = output_format;
        form['data'].value = svg_xml;
        form.submit();
    }
    </script>
</div>