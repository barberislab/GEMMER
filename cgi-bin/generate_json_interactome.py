import ast
import collections
import json
import os
import sqlite3
import timeit
import traceback
from collections import Counter

import matplotlib
matplotlib.use('Agg') # must be before further matplotlib imports
import matplotlib.pyplot as plt
import networkx as nx
import numpy as np
import pandas as pd

import nxviz as nv  # only py3
import simplejson as js

pd.set_option('display.max_colwidth', -1)


script_dir = os.path.dirname(os.path.abspath(__file__))  #<-- absolute dir the script is in

def create_connection(db_file):
    """ create a database connection to the SQLite database
        specified by db_file
    :param db_file: database file
    :return: Connection object or None
    """
    try:
        conn = sqlite3.connect(db_file)
        return conn
    except Exception as e:
        print(e.message, e.args)
 
    return None

def convert(data):
    if isinstance(data, str):
        return str(data)
    elif isinstance(data, collections.Mapping):
        return dict(list(map(convert, iter(data.items()))))
    elif isinstance(data, collections.Iterable):
        return type(data)(list(map(convert, data)))
    else:
        return data

def write_excel_file(df_user_input,df_network,df_nodes,df_interactome, file_id):

    filename_base = os.path.abspath(script_dir+'/../output/excel_files/')

    writer = pd.ExcelWriter(filename_base+'/interactome_'+file_id+'.xlsx', engine='xlsxwriter')
    workbook = writer.book

    format_null = workbook.add_format({'text_wrap': True,'align':'left','font_size':10})

    df_nodes = df_nodes.drop(['CYCLoPs_html',	'CYCLoPs_dict'],1)
    df_interactome = df_interactome.drop(['Evidence HTML'],1)

    ### User input
    df_user_input.to_excel(writer,sheet_name='user input', index=True)
    worksheet = writer.sheets['user input']
    worksheet.set_column('A:A',30,format_null)
    worksheet.set_column('B:B',200,format_null)

    ### Network
    df_network.transpose().to_excel(writer,sheet_name='network properties', index=True)
    worksheet = writer.sheets['network properties']
    worksheet.set_column('A:B',30,format_null)

    ### Nodes
    df_nodes.to_excel(writer,sheet_name='nodes', index=False)
    worksheet = writer.sheets['nodes']
    worksheet.set_column('A:B',15,format_null)
    worksheet.set_column('C:C',40,format_null)
    worksheet.set_column('D:D',75,format_null)
    worksheet.set_column('E:H',15,format_null)
    worksheet.set_column('I:I',40,format_null)
    worksheet.set_column('J:M',15,format_null)
    worksheet.set_column('N:O',30,format_null)

    ### Interactome
    df_interactome.to_excel(writer,sheet_name='interactome',index=False)
    worksheet = writer.sheets['interactome']
    worksheet.set_column('A:C',10,format_null)
    worksheet.set_column('D:D',100,format_null)
    worksheet.set_column('E:G',15,format_null)

    worksheet.set_column('A:G',None,format_null)

    # SAVE
    writer.save()

    print('Completed generating the Excel file.')

def calc_network_props(df_nodes, df_interactome, df_network, filter_condition):
  ''' Use NetworkX to calculate degree centrality etc. '''

  node_list = df_nodes['Standard name'].values
  edge_list = df_interactome[['source','target']].values

  # build the networkx graph
  # we use graph instead of multiGraph because centrality measures don't exist for those. 
  # So here an edge just means there is an interaction, there is no way of indicating that a physical and a regulatory interaction exist
  G = nx.Graph() 
  G.add_nodes_from(node_list)
  G.add_edges_from(edge_list)

  start = timeit.default_timer()

  # The degree centrality for a node v is the fraction of nodes it is connected to
  d = nx.degree_centrality(G)
  df_nodes['Degree centrality'] = df_nodes['Standard name'].map(d)

  ### Drop nodes without interactions
  df_nodes = df_nodes[df_nodes['Degree centrality'] != 0.0]
  df_nodes = df_nodes.reset_index(drop=True)

  # calculate eigenvector and katz centrality
  if filter_condition != 'Katz centrality':
    d = nx.eigenvector_centrality(G,max_iter=400) # this takes about 40%-50% of the time the stuff below this most of the rest
    df_nodes['Eigenvector centrality'] = df_nodes['Standard name'].map(d)
  else: # don't calculate this unless asked for
    d = nx.katz_centrality_numpy(G)
    df_nodes['Katz centrality'] = df_nodes['Standard name'].map(d)

  # clustering coefficient
  # df_network['Average clustering coefficient'] = nx.average_clustering(G) # this takes siginficant time

  return df_nodes, df_interactome, df_network, G


def write_network_to_json(nodes, interactome, filter_condition, filename, case='', primary_nodes = []):
  ''' Write the network to be visualized to a JSON file for use with D3. '''

  # remove unneeded columns to speed up json writing
  nodes = nodes.drop(['CYCLoPs_Excel_string','CYCLoPs_dict','Description'], 1)

  interactome = interactome[['source','target','type','#Experiments']]

  ### For reasonable performance we limit the number of nodes and edges
  # nodes: based on filter_condition and only non-primary nodes
  max_nodes = 250

  # edges: remove interactions between non-primary nodes
  # try to remove genetic ones first
  max_edges = 1000

  # reduce nodes
  nodes = nodes.sort_values(by=['primary node',filter_condition],ascending=False)
  nodes = nodes.iloc[:max_nodes]
  nodes.reset_index(drop=True,inplace=True)

  # reduce interactions based on remaining nodes
  n = nodes['Standard name'].values # list of remaining node IDs
  interactome = interactome[ (interactome['source'].isin(n)) & (interactome['target'].isin(n)) ]

  # remove genetic interactions first (<5 experiments)
  if case != '' and len(interactome) > max_edges:
    for i in range(5):
      interactome = interactome.drop(interactome[ (~interactome['source'].isin(primary_nodes)) & (~interactome['target'].isin(primary_nodes)) & (interactome['type'] == 'genetic') & (interactome['#Experiments'] == i)].index)

      if len(interactome) <= max_edges:
        break

    # remove physical interactions (<3 experiments)
    for i in range(3):
      if case != '' and len(interactome) > max_edges:
        interactome = interactome.drop(interactome[ (~interactome['source'].isin(primary_nodes)) & (~interactome['target'].isin(primary_nodes)) & (interactome['type'] == 'physical') & (interactome['#Experiments'] == i)].index)
      if len(interactome) <= max_edges:
        break

  interactome.reset_index(drop=True,inplace=True)

  ##########################
  # export cytoscape json
  ##########################
  filename_cs = filename[:-5]+'_csjs'+filename[-5:]

  if case != '': 
    filename_cs = filename_cs[:-5]+'_full'+filename_cs[-5:]

  nodes_cs = nodes.copy()
  nodes_cs = nodes_cs.rename(columns={'Standard name':'id'}) # cytoscape requires an ID attribute

  with open(filename_cs, 'w') as outfile:
    # generate [ {"data": {"id":bla,...} }, {"data": {...}}, ...  ]

    json_str_cs = '['

    d_nodes = nodes_cs.to_dict('records')
    d_nodes = convert(d_nodes)

    json_str_cs += ",".join(['{"data":' + str(row) + '}' for row in d_nodes])

    json_str_cs += ','
    
    d_interactome = interactome.to_dict("records")
    json_str_cs += ",".join(['{"data":' + str(row) + '}' for row in d_interactome])

    json_str_cs += ']'
    json_d = ast.literal_eval(json_str_cs)

    json.dump(json_d, outfile)

  ##########################
  # export D3 json
  ##########################
  # turn string source and target identifiers into numbers corresponding to nodes
  nodes_dict = nodes['Standard name'].to_dict()
  nodes_dict = {v: k for k, v in list(nodes_dict.items())}

  # replace string labels with node numbers
  interactome['source'] = interactome['source'].map(nodes_dict.get)
  interactome['target'] = interactome['target'].map(nodes_dict.get)

  # append to the output filename for the non-filtered network
  if case != '': 
    filename = filename[:-5]+'_full'+filename[-5:]

  with open(filename, 'w') as outfile:
      outfile.write("{\n\"nodes\":\n\n")
      nodes.to_json(outfile, orient="records")
      outfile.write(",\n\n\n\"links\":\n\n")
      interactome.to_json(outfile, orient="records")
      outfile.write("}")
  
  return


def main(arguments,output_filename):
    ''' Parse user input, query SQL database, generate pandas dataframes, export JSON for D3 and print HTML code '''

    ######################################################
    ### start the alert div that contains any output generated here
    ######################################################
    print("<div class=\"alert alert-dismissable alert-info\">")
    timing = {} 
    start_all = timeit.default_timer()
    
    ######################################################
    # generate an input overview table
    ######################################################
    arg_names = ['Genes','Cluster by','Color by','Interaction type',\
                'Minimal number of experiments','Minimal number of publications', 'Minimal number of methods','Method types',\
                'Process','Compartment','Expression','Max. number nodes','Filter condition']
    input_dict = { arg_names[i]:arguments[i].replace("_"," ") for i in range(len(arg_names)) } # does not include unique_str and excel_flag
    input_dict['Expression'] = input_dict['Expression'].replace('G1P','G1(P)') # brackets removed in PHP

    df_user_input = pd.DataFrame.from_dict(input_dict,orient='index')
    df_user_input = df_user_input.reindex(index = arg_names)
    df_user_input.columns = ['user input']
    df_user_input_to_print = df_user_input.to_html(classes=['table','table-condensed','table-bordered'])
    
    ### process arguments 
    primary_nodes,cluster_by,color_by,int_type,\
    min_exp,min_pub,min_methods,method_types,\
    process,compartment,expression,\
    max_nodes,filter_condition,\
    excel_flag,filter_flag,unique_str = arguments

    # make sure types are correct
    color_by = color_by.replace('_',' ')
    cluster_by = cluster_by.replace('_',' ')
    filter_condition = filter_condition.replace('_',' ')

    process = process.split(',')
    method_types = method_types.split(',')
    method_types = [x.replace('_',' ') for x in method_types]
    expression = expression.split(',')
    if 'G1P' in expression: # brackets removed in php
      ind = expression.index('G1P')
      expression[ind] = 'G1(P)'

    process = [x.replace("_"," ") for x in process]

    primary_nodes_str = primary_nodes
    if '_' in primary_nodes:
      primary_nodes = primary_nodes.split('_')  
    else:
      primary_nodes = [primary_nodes]

    min_exp = int(min_exp)
    min_pub = int(min_pub)
    min_methods = int(min_methods)
    max_nodes = int(max_nodes)
    excel_flag = bool(int(excel_flag))
    filter_flag = bool(int(filter_flag))

    split_types = int_type.split('_')

    compartment = compartment.replace('_',' ')

    timing['input'] = timeit.default_timer() - start_all
    
    ######################################################
    ### get all interactions related to the input IDs
    ######################################################
    start_initial = timeit.default_timer()

    database = script_dir+"/data/DB_genes_and_interactions.db"
    conn = create_connection(database)

    # get all interactions in which the given genes takes part
    placeholders = ', '.join('?' for unused in primary_nodes) # '?, ?, ?, ...'

    # Multiple query options
    if len(split_types) == 3:
      query = "SELECT source,target FROM interactions WHERE ( (source IN (%s) or target IN (%s)) and num_experiments >= (%s) \
        and num_publications >= (%s) and num_methods >= (%s))" % (placeholders,placeholders,min_exp,min_pub,min_methods)
      cursor = conn.execute(query,primary_nodes+primary_nodes)
    else:
      placeholders_type = ', '.join('?' for unused in split_types)
      query = "SELECT source,target FROM interactions WHERE ( (source IN (%s) or target IN (%s)) AND type IN (%s) \
        AND num_experiments >= (%s) and num_publications >= (%s) and num_methods >= (%s))" % (placeholders,placeholders, \
        placeholders_type,min_exp,min_pub,min_methods)
      cursor = conn.execute(query,primary_nodes+primary_nodes+split_types)

    # construct dataframe of interacting genes: the nodes
    node_list = [x for y in cursor for x in y]

    if len(node_list) == 0:
      raise ValueError('No interactions matching these conditions.')

    # get the info from the database for each node to make the 'nodes' dataframe
    if 'No_data' in expression:
      query = """SELECT standard_name,systematic_name,name_desc,desc,go_term_1,go_term_2,\
                            GFP_abundance,GFP_localization,CYCLoPs_Excel_string,CYCLoPs_html,expression_peak_phase,\
                            expression_peak_time,CYCLoPs_dict FROM genes \
                            WHERE standard_name in (%s) AND (expression_peak_phase in (%s) OR expression_peak_phase is NULL) AND (go_term_1 in (%s) OR go_term_2 in (%s))""" \
                            % (', '.join('?' for _ in node_list),', '.join('?' for _ in expression),', '.join('?' for _ in process),', '.join('?' for _ in process))
    
    else:
      query = """SELECT standard_name,systematic_name,name_desc,desc,go_term_1,go_term_2,\
                            GFP_abundance,GFP_localization,CYCLoPs_Excel_string,CYCLoPs_html,expression_peak_phase,\
                            expression_peak_time,CYCLoPs_dict FROM genes \
                            WHERE standard_name in (%s) AND expression_peak_phase in (%s) AND (go_term_1 in (%s) OR go_term_2 in (%s))""" \
                            % (', '.join('?' for _ in node_list),', '.join('?' for _ in expression),', '.join('?' for _ in process),', '.join('?' for _ in process))
    cursor = conn.execute(query,node_list+expression+process+process)

    data = [list(l) for l in cursor] # cursor itself is a generator, this is a list of lists
    nodes = pd.DataFrame(data,columns=['Standard name','Systematic name','Name description','Description',
                        'GO term 1','GO term 2','GFP abundance','GFP localization','CYCLoPs_Excel_string',
                        'CYCLoPs_html','Expression peak phase','Expression peak time','CYCLoPs_dict'])
    timing['Get node information from database'] = timeit.default_timer() - start_initial

    ### make actual dictionaries out of CYCLoPs_dict column
    nodes['CYCLoPs_dict'] = nodes['CYCLoPs_dict'].apply(ast.literal_eval)

    len_nodes_query = len(nodes)

    ######################################################
    ### BASED ON THE COMPARTMENT FILTER: DROP NODES
    ######################################################
    start_node_drop = timeit.default_timer()

    if 'GFP:' in compartment:
      comp_to_check = compartment.replace('GFP:','')
      print('Prior to compartment filtering:', len(nodes), 'nodes. Filtering on', comp_to_check)
      s = pd.Series([comp_to_check in x for x in nodes['GFP localization'].str.split(', ')])
      nodes = nodes[s.values]
      nodes = nodes.reset_index(drop=True)
      print('After compartment filtering:', len(nodes), 'nodes.')
    elif 'CYCLoPs:' in compartment:
      comp_to_check = compartment.replace('CYCLoPs:','')
      print('Prior to compartment filtering:', len(nodes), 'nodes. Filtering on', comp_to_check)
      l_o_l = [[list(nodes.iloc[i]['CYCLoPs_dict'][x].keys()) for x in list(nodes.iloc[i]['CYCLoPs_dict'].keys()) ] for i in range(len(nodes)) ]
      s = pd.Series([comp_to_check in [v for WT in l_o_l[i] for v in WT] for i in range(len(l_o_l))]) 
      nodes = nodes[s.values]
      nodes = nodes.reset_index(drop=True)
      print('After compartment filtering:', len(nodes), 'nodes.')
    else: #it is 'Any'
      pass

    ### Combine the expression columns
    nodes['Expression peak'] = nodes['Expression peak phase'] + " (" + nodes['Expression peak time'].map(str) + " min)"
    nodes['Expression peak'] = nodes['Expression peak'].mask(nodes['Expression peak'].isnull(), "No data")
    nodes = nodes.drop('Expression peak phase',1)
    nodes = nodes.drop('Expression peak time',1)

    # alphabetize
    nodes = nodes.sort_values(by='Standard name',ascending=True)
    nodes = nodes.reset_index(drop=True)
    node_list = list(nodes['Standard name'].values)

    nodes['primary node'] = [x in primary_nodes for x in nodes['Standard name']]

    if len(nodes) == 0:
      raise ValueError("Filtering left no nodes.")

    timing['Node filter: compartment'] = timeit.default_timer() - start_node_drop


    ######################################################
    # Clustering and coloring
    ######################################################
    start = timeit.default_timer()

    ### Clustering part
    if cluster_by in ['GO term 1','GO term 2']:
      nodes['cluster'] = nodes[cluster_by]
    elif 'CYCLoPs WT' in cluster_by:
      WT_string = 'WT' + cluster_by[-1]

      # loop over all nodes find their highest expression compartment for the WT given by WT_string 
      # NOTE: SOMETIMES A DICTIONARY WITH EXPRESSION DATA FOR A GIVEN WT IS EMPTY WE NEED TO CHECK FOR THIS
      # Example: GET1 in WT1
      l = nodes['CYCLoPs_dict'].values
      l_max_comps = [ max(l[i][WT_string], key=lambda key: l[i][WT_string][key]) if (type(l[i]) != str and len(l[i][WT_string]) > 0) else 'No data' for i in range(len(nodes))]
      nodes['cluster'] = pd.Series(l_max_comps).values
    elif cluster_by == 'No clustering':
      nodes['cluster'] = ['No clustering' for i in range(len(nodes))]
    else:
      raise SystemExit(cluster_by,"Unexpected value for clustering variable.")
    
    if color_by in ['GO term 1','GO term 2']:
      # set the color based on the color_by variable in a new column of 'nodes' DF
      nodes['color'] = nodes[color_by]

    elif 'CYCLoPs WT' in color_by:
      WT_string = 'WT' + color_by[-1]

      # loop over all nodes find their highest expression compartment for the WT given by WT_string 
      # NOTE: SOMETIMES A DICTIONARY WITH EXPRESSION DATA FOR A GIVEN WT IS EMPTY WE NEED TO CHECK FOR THIS
      # Example: GET1 in WT1
      l = nodes['CYCLoPs_dict'].values
      l_max_comps = [ max(l[i][WT_string], key=lambda key: l[i][WT_string][key]) if \
        (type(l[i]) != str and len(l[i][WT_string]) > 0) else 'No data' for i in range(len(nodes))]

      # set the color based on the maximum compartment found above in a new column in the nodes DF          
      nodes['color'] = pd.Series(l_max_comps).values
    elif color_by == 'No coloring':
      nodes['color'] = ["No data" for i in range(len(nodes))]
    else:
      print('Unexpected value for color_by',color_by)

    timing['Setting node cluster and color attributes'] = timeit.default_timer() - start

    len_nodes_filtered_comp = len(nodes)

    ######################################################
    ### GET ALL INTERACTIONS BETWEEN ALL NODES
    ######################################################
    start_final_sql = timeit.default_timer()
    max_interactions = 25000
    placeholders = ', '.join('?' for unused in node_list) # '?, ?, ?, ...'

    # Multiple query options
    if len(split_types) == 3:
      query = "SELECT * FROM interactions WHERE ( (source IN (%s) AND target IN (%s)) and num_experiments >= (%s) \
        and num_publications >= (%s) and num_methods >= (%s)) ORDER BY CASE type WHEN 'physical' OR 'regulation' THEN 1 WHEN 'genetic' THEN 2 END, \
        num_experiments DESC, num_publications DESC, num_methods DESC limit (%s)" % (placeholders,placeholders,min_exp,min_pub,min_methods,max_interactions)
      interactome = pd.read_sql_query(query, conn, params=node_list+node_list)
    else:
      placeholders_type = ', '.join('?' for unused in split_types)
      query = "SELECT * FROM interactions WHERE ( (source IN (%s) AND target IN (%s)) AND type IN (%s) \
        AND num_experiments >= (%s) and num_publications >= (%s) and num_methods >= (%s)) \
        ORDER BY CASE type WHEN 'physical' OR 'regulation' THEN 1 WHEN 'genetic' THEN 2 END, \
        num_experiments DESC, num_publications DESC, num_methods DESC limit (%s)" % (placeholders, \
        placeholders,placeholders_type,min_exp,min_pub,min_methods,max_interactions)
      interactome = pd.read_sql_query(query, conn, params=node_list+node_list+split_types)

    interactome.columns = ['source','target','type','Evidence','Evidence HTML','#Experiments',\
        '#Publications','#Methods']
    timing['Interactome SQL + dataframe + processing'] = timeit.default_timer() - start_final_sql


    ######################################################
    ### BASED ON THE METHOD TYPES FILTER: DROP INTERACTIONS
    ######################################################
    start = timeit.default_timer()

    to_drop = []
    with open(script_dir+'/data/unique_experimental_methods.txt') as f:
      read_methods = f.read().splitlines()
    total_methods = len(read_methods)
    if len(method_types) < total_methods: # some have been deselected
      print('We have on file:', total_methods, 'methods. User queried for:', len(method_types))
      len_before = len(interactome)
      interactome = interactome[interactome.apply(lambda x: any([m in x['Evidence'] for m in method_types]),1)]
      print('<br/>We dropped', len_before - len(interactome), 'interactions based on the methods.<br/>')

    if len(interactome) == 0:
      raise ValueError('No interactions matching these conditions.')
    
    timing['Filter based on methods'] = timeit.default_timer() - start
    

    ######################################################
    # Network properties with networkx: 1
    ######################################################
    start = timeit.default_timer()
    df_network = pd.Series()
    df_network['Number of nodes'] = len(nodes)
    df_network['Number of edges'] = len(interactome)

    # use networkx
    nodes, interactome, df_network, G = calc_network_props(nodes, interactome, df_network, filter_condition)

    df_network = df_network.to_frame()
    df_network = df_network.transpose()

    timing['networkx properties calculation'] = timeit.default_timer() - start


    ######################################################
    # Save the full network information
    ######################################################
    start = timeit.default_timer()
    nodes_full = nodes.copy()
    interactome_full = interactome.copy()
    timing['Save full network'] = timeit.default_timer() - start


    # ######################################################
    # # WRITE "FULL" NETWORK TO JSON
    # # this will include a filtering step for really big networks
    # ######################################################
    start_json = timeit.default_timer()
    write_network_to_json(nodes_full,interactome_full,filter_condition,output_filename,'full',primary_nodes)
    timing['json_full'] = timeit.default_timer() - start_json


    ######################################################
    # FILTER NODES TO MANAGEABLE VISUALIZATION
    ######################################################

    if (filter_flag) and (len(nodes) > max_nodes):
      start_filter = timeit.default_timer()
      len_interactome = len(interactome)

      # reduce nodes
      nodes = nodes.sort_values(by=['primary node',filter_condition],ascending=False)
      nodes = nodes.iloc[:max_nodes]
      nodes.reset_index(drop=True,inplace=True)

      # reduce interactions 
      n = nodes['Standard name'].values # list of remaining node IDs
      interactome = interactome[ (interactome['source'].isin(n)) & (interactome['target'].isin(n)) ]
      interactome.reset_index(drop=True,inplace=True)

      ### SHOW WARNING MESSAGE ABOUT FILTER STEP
      filter_message = "Note: this query returned {} nodes and {} interactions. We reduced the network to {} nodes based on {} resulting in {} interactions. \
                      All interactions and nodes are contained in the <i>full</i> Excel file. ".format(len_nodes_filtered_comp,len_interactome,max_nodes,filter_condition,len(interactome))
      # full_network_link = '<?php echo "Click <a href=\\\\\\"index_full.php?gene=$gene&unique_str=$unique_str&full=full\\\\\\" class=\\\\\\"alert-link\\\\\\">here</a> to visualize the more complete network."; ?>'
      s = filter_message #+ full_network_link
      print("<!-- Reduction message --><script>create_alert(\""+s+"\",\"alert-warning\");</script>")

      timing['filter'] = timeit.default_timer() - start_filter

      ######################################################
      # Network properties with networkx: 2
      ######################################################
      start = timeit.default_timer()
      
      # df_network = pd.Series()
      df_network['Number of nodes'] = len(nodes)
      df_network['Number of edges'] = len(interactome)

      # use networkx
      nodes, interactome, df_network, G = calc_network_props(nodes, interactome, df_network, filter_condition)


      # c = nv.CircosPlot(G)
      # c.draw()
      # plt.savefig('output/test.pdf')

      timing['networkx properties calculation'] += timeit.default_timer() - start

    ######################################################
    ### Write the network to json
    ######################################################
    start_json = timeit.default_timer()
    write_network_to_json(nodes,interactome,filter_condition,output_filename)
    timing['json'] = timeit.default_timer() - start_json

    if excel_flag:
      ######################################################
      # WRITE TO EXCEL
      ######################################################
      # THIS HAS TO HAPPEN BEFORE HTML REPLACEMENTS
      start_excel = timeit.default_timer()

      write_excel_file(df_user_input, df_network, nodes, interactome, primary_nodes_str+'_'+unique_str)

      timing['excel'] = timeit.default_timer() - start_excel

    # remove the Evidence HTML column
    interactome = interactome.drop('Evidence',1)
    interactome = interactome.rename(columns={'Evidence HTML':'Evidence'})


    if not excel_flag:
      ######################################################
      ### End output text alert div
      ######################################################
      print("</div>")

      ######################################################
      # Generate strings for the nodes and interactome dataframes to print
      ######################################################
      start_print = timeit.default_timer()

      # drop columns
      nodes = nodes.drop(['Description','CYCLoPs_Excel_string','CYCLoPs_dict','cluster','color'],1)

      # Add HTML links to database/SGD to symbols
      nodes['Standard name'] = nodes['Standard name'].apply(lambda x: "<a href='index.php?id=database&gene=" + x + "' target='blank'>" + x + "</a>")

      # change CYCLoPs column name and export html
      # escape makes the HTML links work
      nodes = nodes.rename(columns={'CYCLoPs_html':'CYCLoPs'})
      nodes = nodes.to_html(escape=False,index=False,classes=['table','table-condensed','table-bordered'])
      nodes = nodes.replace('<table','<table id=\"proteins_table\"',1)

      interactome['source'] = interactome['source'].apply(lambda x: "<a href='index.php?id=database&gene=" + x + "' target='blank'>" + x + "</a>" )
      interactome['target'] = interactome['target'].apply(lambda x: "<a href='index.php?id=database&gene=" + x + "' target='blank'>" + x + "</a>")

      # escape makes the HTML links work
      interactome = interactome.to_html(escape=False,index=False,classes=['table','table-condensed','table-bordered'])
      interactome = interactome.replace('<table','<table id=\"interactions_table\"',1)

      ######################################################
      # PRINT COLLAPSABLE BOOTSTRAP HTML CODE WITH THE DATAFRAMES
      ######################################################
      # the 'in' class makes the collapse open by default: the interactions here
      print("""
        <div class="panel-group" id="accordion">
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse1">
                User input</a>
              </h4>
            </div>
            <div id="collapse1" class="panel-collapse collapse">
              <div class="panel-body">
                <div class="table-responsive">
              """)
      print(df_user_input_to_print)
      print("""
                </div>
              </div>
            </div>
          </div>
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse2">
                Network properties</a>
              </h4>
            </div>
            <div id="collapse2" class="panel-collapse collapse">
              <div class="panel-body">
                <div class="table-responsive">
              """)
      print(df_network.to_html(classes=['table','table-condensed','table-bordered'],index=False))
      print("""
                </div>
              </div>
            </div>
          </div>
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse3">
                Network nodes (proteins)</a>
              </h4>
            </div>
            <div id="collapse3" class="panel-collapse collapse">
              <div class="panel-body">
                Use the search utility to find the gene you are looking for. The table scrolls horizontally and vertically. 
                By clicking the column headers the table will be sorted on that column. Use shift+click to sort on multiple columns. 
                Default sorting is on number of experiments, number of publications, number of methods and alphabetical on standard name, in that order.
                <div class="table-responsive">
                """)
      print(nodes)
      print("""
                </div>
              </div>
            </div>
          </div>
          <div class="panel panel-default">
            <div class="panel-heading">
              <h4 class="panel-title">
                <a data-toggle="collapse" data-parent="#accordion" href="#collapse4">
                Interactions</a>
              </h4>
            </div>
            <div id="collapse4" class="panel-collapse collapse">
              <div class="panel-body">
                Use the search utility to find the gene you are looking for. 
                By clicking the column headers the table will be sorted on that column. Use shift+click to sort on multiple columns. 
                Default sorting is on number of experiments, number of publications, number of methods and alphabetical on standard name, in that order.
                <div class="table-responsive">
              """)
      print(interactome)
      print("""
                </div>
              </div>
            </div>
          </div>
          """)
      

      ######################################################
      # Optional diagnostics
      ######################################################
      print("""
        <div class="panel panel-default">
          <div class="panel-heading">
            <h4 class="panel-title">
              <a data-toggle="collapse" data-parent="#accordion" href="#collapse5">
              Diagnostics: calculation time</a>
            </h4>
          </div>
          <div id="collapse5" class="panel-collapse collapse">
            <div class="panel-body">
              <div class="table-responsive">
      """)
      timing['print frames'] = timeit.default_timer() - start_print
      timing['all'] = timeit.default_timer() - start_all
      df_timing = pd.Series(timing)
      df_timing = df_timing.to_frame()
      df_timing.columns = ['Time']
      df_timing['Percentage'] = [v/timing['all']*100 for v in df_timing['Time'] ]
      print(df_timing.sort_values('Percentage').to_html(classes=['table','table-condensed','table-bordered']))
      print("Accounted for:", sum([timing[k] for k in timing if k != 'all' ])/timing['all'] * 100, "percent of the time spent in Python.")
      print("""
                </div>
              </div>
            </div>
          </div>
        </div>
        """)

if __name__ == "__main__":
  main()
