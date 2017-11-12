<script src="https://cdnjs.cloudflare.com/ajax/libs/cytoscape/3.2.5/cytoscape.js"></script>
<script src="https://cdn.polyfill.io/v2/polyfill.min.js?features=Promise,fetch"></script>

<script>
// css
</script>

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
  var d_types = {"physical":"#8f0000", // red
    "regulation":"blue", // blue
    "genetic":"grey", // grey
  }
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
          'height': 20,
          'width': 20,
          // 'background-color': '#e8e406',
          'background-color': function(ele){ 
            console.log(ele.data);
            if (ele.data("color") in d_functions) {
              return d_functions[ele.data("color")]
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
            console.log(ele.data);
            if (ele.data("type") in d_types) {
              return d_types[ele.data("type")]
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
});

</script>
