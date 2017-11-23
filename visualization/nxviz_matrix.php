<?php 
    $path_image = "\"./output/nxviz/matrix_{$unique_str}.png\""; 
?>

<script>
    // load the image in the visualization div
    var path_image = <?php echo $path_image; ?>;
    var image = "<img src=\"" + path_image + "\" width=90%>";
    $("#vis_inner").html(image)
</script>