<!-- Full credit goes to https://github.com/nicgirault/circosJS#chords -->

<script src='https://cdn.rawgit.com/nicgirault/circosJS/v2/dist/circos.js'></script>


<script>
var myCircos = new Circos({
container: '#vis_inner',
width: document.getElementById("vis_inner").offsetWidth, // the actual d3 visualization's width - width of the GUI -1
height: document.getElementById("vis_inner").offsetHeight,
});

// A circos graph is based on a circular axis layout. Data tracks appear inside and/or outside the circular layout.
// In order to place data on the circos graph, you must first specify the layout.

// The first argument of the layout function is an array of data that describe the layout regions. 
// Each layout region must have an id and a length
// the length determines the fraction of the circle occupied by the wedge
// The id parameter will be used to place data points on the layout.
var data = [
  { len: 50, color: "#8dd3c7", label: "Metabolism", id: "Metabolism" },
  { len: 20, color: "#ffffb3", label: "Cell cycle", id: "Cell cycle" },
  { len: 12, color: "#bebada", label: "Signal transduction", id: "Signal transduction" },
  { len: 10, color: "#fb8072", label: "Cell division", id: "Cell division" },
  { len: 10, color: "#80b1d3", label: "DNA replication", id: "DNA replication" },
]

var configuration = {
  innerRadius: 250,
  outerRadius: 300,
  cornerRadius: 10,
  gap: 0.04, // in radian
  labels: {
    display: true,
    position: 'center',
    size: '14px',
    color: '#000000',
    radialOffset: 20,
  },
  ticks: {
    display: true,
    color: 'grey',
    spacing: 10000000,
    labels: true,
    labelSpacing: 10,
    labelSuffix: 'Mb',
    labelDenominator: 1000000,
    labelDisplay0: true,
    labelSize: '10px',
    labelColor: '#000000',
    labelFont: 'default',
    majorSpacing: 5,
    size: {
      minor: 2,
      major: 5,
    }
  },
  events: {}
}


myCircos.layout(data, configuration);


var data = [
    {
    source: {
      id: 'Metabolism',
      start: 1,
      end: 12
    },
    target: {
      id: 'Cell cycle',
      start: 1,
      end: 2
    }
  },
  {
    source: {
      id: 'DNA replication',
      start: 1,
      end: 2
    },
    target: {
      id: 'Signal transduction',
      start: 1,
      end: 10
    }
  }
]
// The second argument of the layout function is a configuration object that control the format of the layout.
var configuration = {
    innerRadius: null,
    outerRadius: null,
    min: null,
    max: null,
    color: 'YlGnBu',
    logScale: false,
    tooltipContent: null,
    events: {}
  }

myCircos.chords('mymap', data);

console.log(myCircos)

// To visualize the result
myCircos.render();

</script>
