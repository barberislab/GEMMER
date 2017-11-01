from hiveplot import HivePlot
import os
import networkx as nx
import matplotlib.pyplot as plt 

script_dir = os.path.dirname(os.path.abspath(__file__))  #<-- absolute dir the script is in

def make_hiveplot(G):

    # ## assume that you have a graph called G
    # build a dictionary with groups
    nodes = dict()
    nodes['group1'] = [(n,d) for n, d in G.nodes(data=True) if d == some_criteria()]
    nodes['group2'] = [(n,d) for n, d in G.nodes(data=True) if d == other_criteria()]
    nodes['group3'] = [(n,d) for n, d in G.nodes(data=True) if d == third_criteria()]
    print nodes

    # # You may wish to sort your nodes by some criteria.
    # for group, nodelist in nodes.items():
    #     nodes[group] = sorted(nodelist, key=keyfunc())

    # # Finally, you will need a color map for the nodes and edges respectively.
    # nodes_cmap = dict()
    # nodes_cmap['group1'] = 'green'
    # nodes_cmap['group2'] = 'red'
    # nodes_cmap['group3'] = 'blue'

    # edges_cmap = dict()
    # edges_cmap['group1'] = 'green'

    # h = HivePlot(nodes, edges, nodes_camp, edges_cmap)
    # h.draw()

    # plt.savefig(script_dir+'/../output/hiveplot.pdf')