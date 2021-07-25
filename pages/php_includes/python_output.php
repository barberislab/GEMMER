<div id="python_output">
    <?php 
        echo "<h3>Detailed information on network properties, nodes and interactions </h3>";
        echo "Click the panels below to expand the corresponding section.";

        # set maximum length for genes in filename
        # assume gene name length = 4, plus 1 separator, max. 10 genes: 50 characters
        if (strlen($gene) > 50) {
            $gene = substr($gene, 0, 50);
        }

        include(DOCUMENT_PATH . '/output/include_html/include_interactome_' . $gene . '_' . $unique_str . '.php'); 
    ?>
</div>