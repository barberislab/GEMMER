import ast
import datetime
import glob
import operator
import os
import os.path
import shutil
import sqlite3
import time
import timeit
from collections import Counter

import numpy as np
import pandas as pd
from bioservices import KEGG
from intermine.webservice import Service
from ordered_set import OrderedSet

import json


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
        print((e.message, e.args))
 
    return None

def create_table(conn, create_table_sql):
    """ create a table from the create_table_sql statement
    :param conn: Connection object
    :param create_table_sql: a CREATE TABLE statement
    :return:
    """
    try:
        c = conn.cursor()
        c.execute(create_table_sql)
    except Exception as e:
        print(("ERROR: ",e.message, e.args))

def find_metabolic_enzymes(conn,gene_sys_names):
    import cobra

    #model = cobra.io.read_sbml_model('./yeast_GEMM/yeast_7.6_recon.xml')
    model = cobra.io.read_sbml_model('./yeast_GEMM/yeastGEM_v8.xml')

    matches = [g for g in gene_sys_names if g in model.genes ]
    print(('Found', len(matches), 'metabolic enzymes in the yeast metabolic reconstruction.'))

    print("### Storing whether gene is a metabolic enzyme")
    for g in matches:
        model_gene = model.genes.get_by_id(g)
        catalyzed_rxns = ', '.join([r.id+' ('+r.name+')' for r in model_gene.reactions])

        conn.execute('UPDATE genes SET yeast7 = ?, catalyzed_reactions = ?  WHERE systematic_name = ?',(1,catalyzed_rxns,g))
    
    conn.commit()


def store_sceptrans_data(conn):

    sceptrans_data = pd.read_excel('./expression_time_phase_primary.xlsx',index_col=False)
    sceptrans_data.columns = ['Systematic name', 'Standard name', 'Phase', 'Time']

    sceptrans_data = sceptrans_data.sort_values(['Time'])

    cursor = conn.execute("SELECT systematic_name from genes")
    gene_record = [x for x in cursor]
    gene_sys_names = [str(x[0]) for x in gene_record]

    list_data_tuples = []
    for i in range(len(sceptrans_data)):
        row = sceptrans_data.iloc[i]
        if row['Systematic name'] in gene_sys_names:
            list_data_tuples.append((row['Phase'],str(row['Time']),row['Systematic name']))
        else:
            print('We do not have/consider:', row['Systematic name'])

    conn.executemany('UPDATE genes SET expression_peak_phase = ?, expression_peak_time = ? WHERE systematic_name = ?',list_data_tuples)


def store_gfp_data(conn,gene_std_names,d={}):
    ''' Build a dictionary of gene_std_names to dictionaries of database data
    Pickle the dictionary. If it exists do not redo the computation.'''

    if d == {}:
        gfp_data = pd.read_csv('./GFP_database.txt',sep="\t",index_col=False)

        # Set index to ORF name
        gfp_data = gfp_data.set_index('yORF')

        gfp_data = gfp_data[['gene name','abundance','localization summary']]
        gfp_data.columns = ['gene name','abundance','localization'] # rename last column

        gfp_data = gfp_data.sort_values(by='gene name') 

        # sometimes localization has nan values that are not a string > fix this
        gfp_data['localization'] = gfp_data['localization'].apply(str)
        gfp_data['abundance'] = gfp_data['abundance'].apply(str)

        # replace not visualized by no data
        gfp_data['localization'] = [x if x != 'nan' else 'no data' for x in gfp_data['localization']]
        gfp_data['localization'] = [x if ',' not in x else x.replace(',',', ') for x in gfp_data['localization']]
        gfp_data['abundance'] = [x if x != 'not visualized' else 'no data' for x in gfp_data['abundance']]


        matches = [g for g in gene_std_names if g in gfp_data['gene name'].values]
        nomatches = [g for g in gene_std_names if g not in gfp_data['gene name'].values]
        for g in nomatches:
            d[g] = {'abundance':'no data','localization':'no data'}

        print('\nFound',len(matches),'matching genes in the Ghaemmaghami dataset.\n')
        
        for gene in matches:
            rows = gfp_data[gfp_data['gene name'].values == gene]

            if len(rows) == 0:
                raise ValueError('Did not find', gene)

            del rows['gene name'] # don't need this in the database

            if len(rows) > 1: # one standard_name more than 1 systematic name
                rows = {'abundance':', '.join(rows['abundance'].values),'localization':', '.join(rows['localization'].values)}
            else:
                rows = {'abundance':rows['abundance'].values[0],'localization':rows['localization'].values[0]}

            d[gene] = rows

    for gene in gene_std_names:
        conn.execute('UPDATE genes SET GFP_abundance = ?, GFP_localization = ? WHERE standard_name = ?',(d[gene]['abundance'],d[gene]['localization'], gene))

    print('Completed storing GFP data')

    return d


def store_CYCLoPs_data(conn,gene_std_names,CYCLoPs_dict={}):
    ''' Read the three WT datasets for each gene. 
        Save a dictionary with level 1 being the three WTs. 
        Level 2 being a dictionary for a WT with all non-zero compartment expression lvls. 
    '''
    if CYCLoPs_dict == {}: # no old data given
        CYCLoPs_data = [0,0,0] # init
        CYCLoPs_data[0] = pd.read_excel("./CYCLoPs_WT1.xls",header=0)
        CYCLoPs_data[1] = pd.read_excel("./CYCLoPs_WT2.xls",header=0)
        CYCLoPs_data[2] = pd.read_excel("./CYCLoPs_WT3.xls",header=0)

        ### ROUND to 3 decimals
        CYCLoPs_data[0] = CYCLoPs_data[0].round(4)
        CYCLoPs_data[1] = CYCLoPs_data[1].round(4)
        CYCLoPs_data[2] = CYCLoPs_data[2].round(4)

        count = 0
        for gene in gene_std_names:
            if count % 1000 == 0:
                print('Finished storing CYCLoPs data for',count,'out of',len(gene_std_names),'genes')

            found = False # track if gene is in these tables at all
            d = {'WT1':{},'WT2':{},'WT3':{}} # init

            for i in range(len(CYCLoPs_data)): # 3 WT datasets
                df = CYCLoPs_data[i]
                WT = 'WT'+str(i+1)

                # find the correct row
                subdf = df[df['Name'] == gene]
                if len(subdf) == 0:

                    continue
                elif len(subdf) > 1:
                    print("Found multiple hits in CYCLoPs data for",gene,'Taking the first hit.')
                    subdf = subdf[:1]
                found = True
                
                subdf = subdf.drop(subdf.columns[[0,1]], axis=1) # drop ORF and gene name

                row = subdf.iloc[0].to_dict()
                # get rid of zero compartments
                deleteThese = [k for k in row if row[k] == 0.0]
                for k in deleteThese:
                    row.pop(k,None) 

                # round the numbers
                row = {k:round(row[k],4) for k in row}
                d[WT] = row

            string_d = str(d) # for SQL this has to be TEXT
            CYCLoPs_dict[gene] = string_d

            count += 1
    
    for gene in gene_std_names:
        sql_CYCLoPs_dict = {}

        d = ast.literal_eval(CYCLoPs_dict[gene])

        ###  store the dictionary :
        # 1. as a stringified dictionary for literal use
        # 2. as a string for the excel files
        # 3. as an html table for output
        if isinstance(d,dict):
            sql_CYCLoPs_dict['dict'] = str(d)
        else:
            print(gene, d)
            raise SystemExit

        sql_CYCLoPs_dict['excel_string'] = ', '.join([k + ': ' + ( str(d[k]) if d[k] != {} else 'no data') for k in d]) # 'WT1' + '{Nucleus:0.1,Cytosol:0.8}', ....
        
        d_df = pd.DataFrame.from_dict(d)
        if all([len(d[x])==0 for x in d]):
            d = {'WT1':{'None':'no data'},'WT2':{'None':'no data'},'WT3':{'None':'no data'}}
            not_only_no_data = False
            d_df = pd.DataFrame.from_dict(d)
            html_string = d_df.to_html(index=False,classes=['table','table-condensed', 'table-bordered', 'CYCLoPs-table']) # do not print the index in this case just 3 columns with 'no data'
        else:
            not_only_no_data = True
            html_string = d_df.to_html(classes=['table','table-condensed', 'table-bordered','CYCLoPs-table'])

        html_string = html_string.replace('\n','')
        html_string = html_string.replace('<table','<table id=\"CYCLoPs_table\"')

        # color the highest compartment in each WT
        for WT_string in ['WT1','WT2','WT3']:
            if not_only_no_data and len(d[WT_string]) > 0:
                max_key = max(iter(d[WT_string].items()), key=operator.itemgetter(1))[0]
                s = str(d[WT_string][max_key]) 

                # insert blue background in the correct table cell 
                if s+'0' in html_string:
                    s = s+'0' # cases like 0.91 to 0.910
                    html_string = html_string.replace('<td>'+s+'</td>', '<td class="bg-info">'+s+'</td>')
                elif s in html_string:
                    html_string = html_string.replace('<td>'+s+'</td>', '<td class="bg-info">'+s+'</td>')
                else:
                    print('missing:', s) 
                    print('dict:', d)
                    print(d_df)
                    raise SystemExit

        # change table properties: full width within column
        # # set first column width
        html_string = html_string.replace('<th></th>', '<th style=\"width:100px;\"></th>',1)
        
        sql_CYCLoPs_dict['html_string'] = html_string

        d = sql_CYCLoPs_dict
        conn.execute('UPDATE genes SET CYCLoPs_dict = ?, CYCLoPs_Excel_string = ?, CYCLoPs_html = ?  WHERE standard_name = ?',(d['dict'],d['excel_string'],d['html_string'],gene))

    print('Completed storing CYCLoPs data')

    return CYCLoPs_dict


def find_go_annotations(conn,gene_std_names):
    # The GO term tree: http://amigo.geneontology.org/amigo/dd_browse
    # We have selected certain "high level" GO terms to classify genes 
    # This selection seems to provide complete coverage in the sense that each gene has a "primary" GO term
    # i.e. each gene has a GO term annotation that may be traced back to at least one of these parents.

    # the GO tree splits first into biological_process, cellular_component and molecular_function
    # we focus on biological_process
    # > cellular process: contains "cellular metabolic process","cell cycle", "cell division", "Signal transduction"
    # DNA replication falls under cellular metabolic process a couple levels down.
    # the cellular metabolic process term comprises roughly 80% of the genome. But is difficult to split up. 

    go_terms = {'Metabolism':'GO:0044237', # cellular metabolic process
                'Cell cycle':'GO:0007049',
                'Cell division':'GO:0051301',
                'Signal transduction':'GO:0007165',
                'DNA replication':'GO:0006260', # DNA replication falls under metabolic process
                }

    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    query_results = {k:None for k in list(go_terms.keys())}
    go_dict = {k:{} for k in list(go_terms.keys())}

    # NEW APPROACH
    # reproduce the numbers from the SGD pages for manually curated annotations
    # query for all genes, constrain for a given parent go term and ask for publication to get multiple hits per sub go term
    # Exclude already checked parents

    # for each of our "high level GO terms" ask SGD for all protein coding genes with a GO annotation with this as a parent
    # generates 'query_results' which is a dictionary where the keys are our parent go terms
    # the values are lists of (gene, go term) pairs where the go term falls under this parent
    # these may contain multiple hits for each gene
    # make sure to assign each hit to only one category
    print('Querying SGD this may take a while...')

    for term in go_terms: 
        query = service.new_query("ProteinCodingGene")

        query.add_view(
            "symbol", "secondaryIdentifier",
            "goAnnotation.ontologyTerm.ontologyAnnotations.ontologyTerm.identifier", # unique GO terms
            "goAnnotation.evidence.publications.pubMedId", # each publication
            "goAnnotation.evidence.code.code" # each method within a publication
        )
        query.add_constraint("goAnnotation.evidence.code.annotType", "=", "manually curated", code = "A")
        query.add_constraint("goAnnotation.ontologyTerm.ontologyAnnotations.ontologyTerm.parents.identifier", "=", go_terms[term], code = "B")

        # The query.rows() will contain all genes with such a parent go term but one entry for each actual go term with that parent
        rows = [ {k:row[k] for k in ['symbol','secondaryIdentifier',"goAnnotation.ontologyTerm.ontologyAnnotations.ontologyTerm.identifier",
                "goAnnotation.evidence.publications.pubMedId","goAnnotation.evidence.code.code"] } for row in query.rows() ] # each row is a dictionary

        print(term,'query returned',len(rows),'results')

        query_results[term] = rows


    # unique identification: ordering from least to greatest number of hits in the loop above
    query_results['DNA replication'] = [x for x in query_results['DNA replication'] if x not in query_results['Cell division']]
    query_results['Signal transduction'] = [x for x in query_results['Signal transduction'] if x not in query_results['Cell division'] 
                                            and x not in query_results['DNA replication']]
    query_results['Cell cycle'] = [x for x in query_results['Cell cycle'] if x not in query_results['Cell division'] 
                                    and x not in query_results['DNA replication']
                                    and x not in query_results['Signal transduction']]
    query_results['Metabolism'] = [x for x in query_results['Metabolism'] if x not in query_results['Cell division']
                                    and x not in query_results['DNA replication']
                                    and x not in query_results['Signal transduction']
                                    and x not in query_results['Cell cycle']]

    for term in go_terms:
        print(term,'query returned',len(query_results[term]),'results after filtering')

    # Store list of unique genes for each go_term (post-filter) in 'go_dict' and count the results
    for term in go_terms:
        rows = query_results[term]

        # go_dict[term] should just contain the genes (as keys) and the number such terms as a value
        for row in rows:
            # print row # this is now a dictionary 
            if row["symbol"] not in ['None',None]:
                s = row["symbol"]
            elif row["secondaryIdentifier"] not in ['None',None]:
                s = row["secondaryIdentifier"]
            else:
                continue
            if s in go_dict[term]:
                go_dict[term][s] += 1
            else:
                go_dict[term][s] = 1

        print("Found ", len(go_dict[term]), "genes with GO term ", term)

    print('Assigning go terms for all genes...')
    gene_cat_counts = {}
    list_data_tuples = []
    for gene in gene_std_names:
        go = ''
        for term in go_terms: # store GO:****,GO:*****,....
            if gene in go_dict[term]:
                go += go_terms[term]+','
        go = go[:-1] # remove last ,

        # store the number of go terms for each category
        gene_cat_counts[gene] = {k:(go_dict[k][gene] if gene in go_dict[k] else 0 ) for k in go_dict}
        
        # find the maximum one and use that as majority category
        # print [gene_cat_counts[gene][k] for k in gene_cat_counts[gene]]
        # print [gene_cat_counts[gene][k] == 0 for k in gene_cat_counts[gene]]
        # print all([gene_cat_counts[gene][k] == 0 for k in gene_cat_counts[gene]])

        if all([gene_cat_counts[gene][k] == 0 for k in gene_cat_counts[gene]]):
            cat1 = 'None'
            cat2 = 'None'
        else:
            sorted_cats = sorted(list(gene_cat_counts[gene].items()), key=operator.itemgetter(1),reverse=True) # list of tuples: ('GO term':count)

            if sorted_cats[1][1] > 0: # check if there is a second non-zero category 
                if sorted_cats[0][1] == sorted_cats[1][1]: # equal GO term count
                    if sorted_cats[0][0] == 'Metabolism':
                        cat1 = sorted_cats[1][0] # avoid catch-all nature of metabolism
                        cat2 = sorted_cats[0][0]
                    else: # primary GO is not metabolism
                        cat1 = sorted_cats[0][0] # avoid catch-all nature of metabolism
                        cat2 = sorted_cats[1][0]
                else:
                    cat1 = sorted_cats[0][0]
                    cat2 = sorted_cats[1][0] 
            else: 
                cat1 = sorted_cats[0][0] # first element has most counts and take the key
                cat2 = 'None'

        list_data_tuples.append(['',cat1,cat2,gene]) # the first element is a placeholder

    # generate df and export to html the GO terms for each gene
    df_cat_count = pd.DataFrame.from_dict(gene_cat_counts).transpose()
    i = 0
    for gene in gene_std_names:
        list_data_tuples[i][0] = df_cat_count.loc[gene].to_frame().transpose().to_html(classes=['table','table-condensed', 'table-bordered'])
        i += 1

    list_data_tuples = list(map(tuple,list_data_tuples))

    # store results in database
    conn.executemany('UPDATE genes SET go_terms = ?, go_term_1 = ?, go_term_2 = ? WHERE standard_name = ?',list_data_tuples)
    

    return go_dict


def find_all_genes(conn):
    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    # query SGD for gene info
    query = service.new_query("ProteinCodingGene")
    query.add_view("secondaryIdentifier", "symbol","description","briefDescription")

    rows =  [row for row in query.rows()]
    print('Found records for',len(rows),'protein coding genes in SGD\n')

    dict_id_to_sysname = {} # also store the mappings in a dictionary to write to excel for handy refs
    list_data_tuples = []
    for row in rows:
        if row['symbol'] not in ['None',None]:
            symbol = row['symbol']
        else:
            symbol = row['secondaryIdentifier']
        secondary = row['secondaryIdentifier']

        name_desc = row['briefDescription']
        desc = row['description']

        # save in dictionary for excel export
        dict_id_to_sysname[symbol] = secondary

        # INSERT GENE INTO DATABASE IF IT DOES NOT EXIST YET
        list_data_tuples.append((symbol,secondary,name_desc,desc,0,0))
    
    conn.executemany('INSERT into genes(standard_name, systematic_name, name_desc, desc, yeast7, is_enzyme) VALUES (?,?,?,?,?,?)',list_data_tuples)

    df = pd.DataFrame(list(dict_id_to_sysname.items()), columns = ['Standard name','Systematic name'])
    df.to_excel("SGD_standard_name_to_systematic_name.xlsx")
    
    return


def find_all_interactions(conn,gene_std_names, gene_sys_names):
    ''' g (String) gene symbol, and con an sqlite database connection '''

    gene_name_sys_to_std = {gene_sys_names[i]:gene_std_names[i] for i in range(len(gene_std_names))}

    def update_interactome_regulation(interactome, row_tuple):
        """
        Update interactome with new interaction row tuple
        row tuple = (source, target, interaction type, evidence, pubmedID)
        """
        
        if row_tuple[0] in interactome:
            if row_tuple[1] in interactome[row_tuple[0]]:
                d = interactome[row_tuple[0]][row_tuple[1]] # shorthand
                if row_tuple[2] in d:
                    # do nothing if this interaction, with this type, publication and evidence already exists
                    if row_tuple[4] in d[row_tuple[2]]['publication'] and row_tuple[3] in d[row_tuple[2]]['evidence']: 
                        return interactome
                    else:
                        d[row_tuple[2]]['evidence'].append(row_tuple[3])
                        d[row_tuple[2]]['publication'].append(row_tuple[4])
                else:
                    d[row_tuple[2]] = {'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}
            else:
                interactome[row_tuple[0]][row_tuple[1]] = {row_tuple[2]:{'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}}
        else:
            interactome[row_tuple[0]] = {row_tuple[1]:{row_tuple[2]:{'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}}}

        return interactome


    start_time = timeit.default_timer()

    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    unique_methods = []

    ##############################
    # Retrieving regulator genes #
    ##############################
    # query description - Retrieve <a href = "https://www.yeastgenome.org/yeastmine-help-page#gene">genes</a> that are regulators of a given target gene.
    print("Retrieving all regulators from SGD")

    # Get a new query on the class (table) you will be querying:
    query = service.new_query("ProteinCodingGene") 

    # Type constraints should come early - before all mentions of the paths they constrain
    query.add_constraint("regulatoryRegions", "TFBindingSite")

    # The view specifies the output columns
    query.add_view(
    "regulatoryRegions.regulator.symbol", "regulatoryRegions.regulator.secondaryIdentifier", # source
    "symbol", "secondaryIdentifier", # target
    "regulatoryRegions.regEvidence.ontologyTerm.name", # method used
    "regulatoryRegions.publications.pubMedId") # publication

    print('SGD query returned:',len(query.rows()),'regulatory interactions')
    
    print('Making tuples of query output now')
    interactome = {}
    for row in query.rows():
        if row["symbol"] not in ['None',None]:
            t = row["symbol"]
        elif row["secondaryIdentifier"] not in ['None',None]:
            t =  row["secondaryIdentifier"]
        else: 
            print('Strange case with None IDs:', row)
            continue
        
        if row["regulatoryRegions.regulator.symbol"] not in ['None',None]:
            s = row["regulatoryRegions.regulator.symbol"]
        elif row["regulatoryRegions.regulator.secondaryIdentifier"] not in ['None',None]:
            s =  row["regulatoryRegions.regulator.secondaryIdentifier"]
        else: 
            print('Strange case with None IDs:', row)
            continue

        # Turn Missing "regulatoryRegions.regEvidence.ontologyTerm.name" into a string
        if row["regulatoryRegions.regEvidence.ontologyTerm.name"] is None:
            regEvidence_ontologyTerm_name = 'Unknown' 
        else:
            regEvidence_ontologyTerm_name = row["regulatoryRegions.regEvidence.ontologyTerm.name"]

        row_tuple = (s,t,'regulation',regEvidence_ontologyTerm_name,row["regulatoryRegions.publications.pubMedId"])

        interactome = update_interactome_regulation(interactome, row_tuple)
    
    
    print('Making tuples of 25C UTmax (Venters, 2011) interactions because SGD only contains the ones newly activate due to heat shock.')

    # Add additional (Venters, 2011) interactions not in SGD
    df_venters = pd.read_excel('./Fkh12_additional_data/Venters_2011_25C_UTmax.xls', header=0, skiprows=[0,1,2,4,5,6,7,8,9,10,11,12,13], usecols=[0,8,9])
    df_venters = df_venters.set_index('Factor') # pandas automatically labels the first column with systematic names 'Factor'

    venters_fkh1 = df_venters[df_venters['Fkh1'] > 0.8].index.tolist()
    venters_fkh2 = df_venters[df_venters['Fkh2'] > 1.13].index.tolist()

    for target in venters_fkh1:
        if target not in gene_sys_names:
            continue

        row_tuple = ('FKH1',gene_name_sys_to_std[target],'regulation',"chromatin immunoprecipitation-chip evidence", '21329885')
        interactome = update_interactome_regulation(interactome, row_tuple)
    
    for target in venters_fkh2:
        if target not in gene_sys_names:
            continue

        row_tuple = ('FKH2',gene_name_sys_to_std[target],'regulation',"chromatin immunoprecipitation-chip evidence", '21329885')
        interactome = update_interactome_regulation(interactome, row_tuple)

    ### Generate the list of tuples to store from the interactome dictionary
    print('Making tuples out of the interactome to store in SQL.')
    list_data_tuples = []
    for int1 in interactome:
        for int2 in interactome[int1]:
            for int_type in interactome[int1][int2]:
                ev = interactome[int1][int2][int_type]['evidence'] #list
                pub =  interactome[int1][int2][int_type]['publication'] #list

                unique_methods.extend([item for item in list(set(ev)) if item not in unique_methods])

                # ASSUME: regulations do not duplicate from x -> and y -> x these are different interactions for regulations
                numpub = len(list(set(pub)))
                nummeth = len(list(set(ev)))
                numexp = len(pub)
                ev_pub = ', '.join([ev[i]+' ('+pub[i]+')' for i in range(len(ev))])
                ev_pub_html = ', '.join(['<a href="https://www.ncbi.nlm.nih.gov/pubmed/' + pub[i] + '" target="blank" title="' \
                    + 'Pubmed ID: '+ pub[i] + '">'+ev[i]+'</a>' for i in range(len(ev))])

                list_data_tuples.append((int1,int2,int_type,ev_pub,ev_pub_html,numexp,numpub,nummeth))

    # update database with one command
    print('Storing:',len(list_data_tuples),'regulatory interactions')
    conn.executemany('insert or ignore into interactions values(?,?,?,?,?,?,?,?)',list_data_tuples)


    ####################################
    # Retrieving physical interactions #
    ####################################
    # query description - Retrieve all interactions for a specified <a href = "https://www.yeastgenome.org/yeastmine-help-page#gene">gene</a>.
    print("retrieving all physical/genetic interactors")

    # Get a new query on the class (table) you will be querying:
    query = service.new_query("Interaction")

    # Constraints
    query.add_constraint("participant1", "ProteinCodingGene")
    query.add_constraint("participant2", "ProteinCodingGene")

    # The view specifies the output columns
    query.add_view(
        "participant1.secondaryIdentifier", "participant1.symbol", "participant2.symbol",
        "participant2.secondaryIdentifier",
        "details.relationshipType",
        "details.experiment.interactionDetectionMethods.identifier",
        "details.experiment.publication.pubMedId"
    )

    print('Query returned:',len(query.rows()),'physical/genetic interactions')

    # NOTE THAT THE SYSTEMATIC NAMES ARE NOT SAVED IN THE INTERACTIONS DATABASE
    interactome = {}
    for row in query.rows():
        row_tuple = [row["participant1.symbol"],
                    row["participant2.symbol"],
                    row["details.relationshipType"],row["details.experiment.interactionDetectionMethods.identifier"],
                    row["details.experiment.publication.pubMedId"]]
        # make sure the standard name is not none
        if row_tuple[0] in ['None',None]:
            if row["participant1.secondaryIdentifier"] not in ['None',None]:
                row_tuple[0] = row["participant1.secondaryIdentifier"]
            else: 
                print('Strange case with None IDs:', row)
                continue
        if row_tuple[1] in ['None',None]:
            if row["participant2.secondaryIdentifier"] not in ['None',None]:
                row_tuple[1] = row["participant2.secondaryIdentifier"]
            else: 
                print('Strange case with None IDs:', row)
                continue

        # make sure the source is first in the alphabetical order
        # this makes sure that we never have the same interaction flipped
        row_tuple[0],row_tuple[1] = sorted([row_tuple[0],row_tuple[1]])

        # Update interactome
        # dict of lowest in alphabet interactors -> highest in alphabet interactor
        # -> physical/genetic
        # -> {evidence:..., publication: ...}
        if row_tuple[0] in interactome:
            if row_tuple[1] in interactome[row_tuple[0]]:
                d = interactome[row_tuple[0]][row_tuple[1]] # shorthand
                if row_tuple[2] in d:
                    # this does NOT account for the fact that this once may already be present, i.e. duplicates
                    # that is taken care of with the Counter below
                    d[row_tuple[2]]['evidence'].append(row_tuple[3])
                    d[row_tuple[2]]['publication'].append(row_tuple[4])
                else:
                    d[row_tuple[2]] = {'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}
            else:
                interactome[row_tuple[0]][row_tuple[1]] = {row_tuple[2]:{'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}}
        else:
            interactome[row_tuple[0]] = {row_tuple[1]:{row_tuple[2]:{'evidence':[row_tuple[3]],'publication':[row_tuple[4]]}}}


    ### Generate from the interactome the list of tuples to store
    print('Making tuples out of the interactome to store in SQL.')
    list_data_tuples = []
    for int1 in interactome:
        for int2 in interactome[int1]:
            for int_type in interactome[int1][int2]:
                ev = interactome[int1][int2][int_type]['evidence'] #list
                pub =  interactome[int1][int2][int_type]['publication'] #list

                unique_methods.extend([item for item in list(set(ev)) if item not in unique_methods])

                # count unique exp, pubs and methods taking into account the x <=> y, y <=> x duplication in SGD
                # this only matters for numexp because it is not the set of uniques
                numpub = len(list(set(pub)))
                nummeth = len(list(set(ev)))

                # when int1 is int2: SGD does not contain duplicate entries for each experiment. Otherwise it does
                # Remove the duplicate
                if int1 != int2: 
                    counts = Counter(pub) 

                    # some interactions occur once
                    # HYPOTHESIS: one of the interactors has strange properties. Examples:
                    # DID4 YER121W, the 2nd is unverified ORF
                    # AAD16 CRD1 (multiple AAD16) merged ORF
                    if not all([counts[x]>1 for x in counts]):
                        print('Cases where pubs are not duplicated:',int1,int2,int_type,counts)
                    elif not all([counts[x]%2==0 for x in counts]): # each experiment is counted twice supposedly
                        print('Unexpected case!')
                        raise SystemExit

                    # I catch the count == 1 cases with the if and else statement and just count them once
                    numexp = sum([counts[x]/2 if counts[x]>1 else counts[x] for x in list(set(pub))]) # loop over the unique entries

                    # Combine all the evidence and publication duo's into strings & html links
                    ev_pub = [ev[i]+' ('+pub[i]+')' for i in range(len(ev))]
                    ev_pub_html = ['<a href="https://www.ncbi.nlm.nih.gov/pubmed/' + pub[i] + '" target="blank" title="' + 'Pubmed ID: '+ pub[i] + '">' + ev[i]+'</a>' for i in range(len(ev))] # list of: 'yeast two-hybrid (714123)' strings]
                    counts = Counter(ev_pub)
                    
                    # Turn the list of strings into a list
                    # repeat uniques as often as we counted them above
                    temp = [] # all appropriate exp (pubmed id) strings
                    for x in list(OrderedSet(ev_pub)): # note: loop over uniques, this should preserve the order although its inconsequential
                        if counts[x]>1:
                            temp.extend(int(counts[x]/2)*[x]) # add the list elements to the temp list
                        else:
                            temp.extend((counts[x])*[x])
                    ev_pub = ', '.join(temp)

                    # Turn the list of strings into a list
                    # repeat uniques as often as we counted them above
                    temp = []
                    counts = Counter(ev_pub_html)
                    for x in list(OrderedSet(ev_pub_html)): # note: loop over uniques, this should preserve the order although its inconsequential
                        if counts[x]>1:
                            temp.extend(int(counts[x]/2)*[x]) # add the list elements to the temp list
                        else:
                            temp.extend((counts[x])*[x])
                    ev_pub_html = ', '.join(temp)
                else:
                    numexp = len(pub)
                    ev_pub = ', '.join([ev[i]+' ('+pub[i]+')' for i in range(len(ev))])
                    ev_pub_html = ', '.join(['<a href="https://www.ncbi.nlm.nih.gov/pubmed/' + pub[i] + '" target="blank" title="' + 'Pubmed ID: '+ pub[i] + '">' + ev[i]+'</a>' for i in range(len(ev))])
                    counts = Counter(ev_pub)

                if not (isinstance(ev_pub,str) and isinstance(ev_pub_html,str)):
                    print(type(ev_pub), type(ev_pub_html))
                    print('string:', ev_pub)
                    print('string html:', ev_pub_html)
                    raise SystemExit

                list_data_tuples.append( (int1,int2,int_type,ev_pub,ev_pub_html,numexp,numpub,nummeth ))

    # update database
    print('Storing:',len(list_data_tuples),'physical/genetic interactions')
    conn.executemany('insert or ignore into interactions values(?,?,?,?,?,?,?,?)',list_data_tuples)

    # save a list of experimental methods

    # write comps to file
    thefile = open('unique_experimental_methods.txt', 'w')
    unique_methods = [str(x) for x in unique_methods] # unicode to string
    unique_methods = sorted(unique_methods, key=str.lower)
    for item in unique_methods:
        thefile.write("%s\n" % item)
    thefile.close()


def query_kegg(kegg_gene):
    ''' query kegg with gene identifier and return the parsed result. '''
    res = KEGG().get(kegg_gene)
    parsed_res = KEGG().parse(res)
    return parsed_res


def get_KEGG_info_genes(conn):    
    print("""
    #########################################
    # Part I: Get the data from KEGG. Store as JSON. 
    # Don't redo this unless files are emptied. 
    #########################################
    """)
    # get any data we already saved
    # to reset this: make kegg_gene_dict an empty file with '{}'
    # and gene_not_found_list: '[]'
    kegg_dict = json.load(open('kegg_gene_dict.json'))
    kegg_not_found_list = json.load(open('kegg_gene_not_found_list.json'))

    print('We loaded', len(kegg_dict), 'previously checked kegg pathways and', len(kegg_not_found_list), 'genes that do not exist in KEGG' )

    cursor = conn.execute('SELECT systematic_name FROM genes')
    data = [list(x) for x in cursor]

    # dataframe of all genes in GEMMER
    df = pd.DataFrame(data,columns=['Systematic name'])

    l = list(df['Systematic name'].values) # list of systematic names
    l = [gene for gene in l if gene not in kegg_not_found_list and gene not in kegg_dict]

    num = len(l)
    nmax = min(1e6,len(l)) # limit number of genes to lookup each time the script is run

    print('Attempting to add', nmax,
        'genes to the KEGG gene dictionary. Could take a while...')

    count = 0.
    for gene in l[:nmax]:
        if ((count / nmax) * 100) % 10 == 0.:  # multiple of 10%
            print((count / nmax) * 100, 'percent done.')

        parsed_res = query_kegg('sce:' + gene) # a dictionary

        if isinstance(parsed_res, dict):  # otherwise it was not found
            kegg_dict[gene] = {} # init

            if 'NAME' in parsed_res:
                name = parsed_res['NAME'] # a dictionary: key is the KO, value is a description
                kegg_dict[gene]['KEGG gene names'] = ', '.join(name) # I assume there is 1
            else:
                kegg_dict[gene]['KEGG gene names'] = None
            if 'ORTHOLOGY' in parsed_res:
                orth = parsed_res['ORTHOLOGY'] # a dictionary: key is the KO, value is a description
                kegg_dict[gene]['KO'] = list(orth.keys())[0] # I assume there is 1
                kegg_dict[gene]['KEGG description'] = list(orth.values())[0] # I assume there is 1
            else:
                kegg_dict[gene]['KO'] = None
                kegg_dict[gene]['KEGG description'] = None
            if 'PATHWAY' in parsed_res:
                p = parsed_res['PATHWAY'] # a dictionary where the values are the pathway names
                p = [p[k] for k in p]
                kegg_dict[gene]['KEGG pathway'] = ', '.join(p)
            else:
                kegg_dict[gene]['KEGG pathway'] = None
        else:
            print((gene, 'was not found in kegg'))
            kegg_not_found_list.append(gene)
            kegg_dict[gene] = None

        count += 1
    

    # save updated dictionary and list
    with open('kegg_gene_dict.json', 'w') as fp:
        json.dump(kegg_dict, fp)
    with open('kegg_gene_not_found_list.json', 'w') as fp:
        json.dump(kegg_not_found_list, fp)

    print("""
    #########################################
    # Part II: Store data in SQL 
    #########################################
    """)
    kegg_dict = json.load(open('kegg_gene_dict.json'))
    kegg_not_found_list = json.load(open('kegg_gene_not_found_list.json'))

    # build tuples of data to store
    list_data_tuples = []
    for gene in kegg_dict:
        if type(kegg_dict[gene]) == dict:
            d = kegg_dict[gene]
            list_data_tuples.append((d['KEGG gene names'],d['KO'],d['KEGG description'],d['KEGG pathway'],gene))

    conn.executemany('UPDATE genes SET KEGG_name = ?, KEGG_KO = ?, KEGG_description = ?, KEGG_pathway = ? WHERE systematic_name = ?',list_data_tuples)

    return


def main():

    SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__)) #<-- absolute dir the script is in
    database = "DB_genes_and_interactions.db"

    # delete old DB
    if os.path.isfile(database):
        os.remove(database)
 
    # SQL command to create the tables we need
    sql_create_genes_table = """ CREATE TABLE IF NOT EXISTS genes (
                                        standard_name TEXT PRIMARY KEY,
                                        systematic_name TEXT NOT NULL,
                                        name_desc TEXT NOT NULL,
                                        desc TEXT NOT NULL,
                                        go_term_1 TEXT,
                                        go_term_2 TEXT,
                                        go_terms TEXT,
                                        CYCLoPs_dict TEXT,
                                        CYCLoPs_Excel_string TEXT,
                                        CYCLoPs_html TEXT,
                                        GFP_abundance TEXT,
                                        GFP_localization TEXT,
                                        expression_peak_phase TEXT,
                                        expression_peak_time TEXT,
                                        yeast7 INT NOT NULL,
                                        is_enzyme INT NOT NULL,
                                        catalyzed_reactions TEXT,
                                        KEGG_name TEXT, 
                                        KEGG_KO TEXT, 
                                        KEGG_description TEXT, 
                                        KEGG_pathway TEXT
                                    ); """
 
    sql_create_interactions_table = """CREATE TABLE IF NOT EXISTS interactions (
                                    source TEXT NOT NULL,
                                    target TEXT NOT NULL,
                                    type TEXT NOT NULL,
                                    evidence TEXT NOT NULL,
                                    evidence_html TEXT NOT NULL,
                                    num_experiments INT NOT NULL,
                                    num_publications INT NOT NULL,
                                    num_methods INT NOT NULL,
                                    FOREIGN KEY (source) REFERENCES genes (standard_name),
                                    FOREIGN KEY (target) REFERENCES genes (standard_name),
                                    PRIMARY KEY (source,target,type,evidence)
                                );"""
 
    # create a database connection
    conn = create_connection(database)
    print("connection established")
    if conn is not None:
        # create projects table
        create_table(conn, sql_create_genes_table)
        print("Genes table initialised.")
        # create tasks table
        create_table(conn, sql_create_interactions_table)
        print("Interactions table initialised.")
    else:
        print("Error! cannot create the database connection.") 


    # STORE ALL PROTEIN CODING GENES
    find_all_genes(conn)
    conn.commit()


    # find the genes in the database NOW
    cursor = conn.execute("SELECT * from genes")
    gene_record = [x for x in cursor]
    gene_std_names = [str(x[0]) for x in gene_record]
    gene_sys_names = [str(x[1]) for x in gene_record]
    print('Stored records for',len(gene_std_names),'genes. Here are the first 10:')
    print(gene_std_names[:10])

    
    # communicate with SGD and get interactions
    find_all_interactions(conn,gene_std_names,gene_sys_names)


    # store sceptrans data
    store_sceptrans_data(conn)


    # incorporate GFP data for all genes in the database
    if os.path.isfile('./gfp_dict.json'):
        print('Loading pre-existing GFP dataset')
        with open(SCRIPT_DIR+'/gfp_dict.json','r') as fp:
            gfp_dict = json.load(fp)
        store_gfp_data(conn,gene_std_names,d=gfp_dict)
    else:
        gfp_dict = store_gfp_data(conn,gene_std_names)
        with open(SCRIPT_DIR+'/gfp_dict.json', 'w') as fp:
            json.dump(gfp_dict, fp)
    conn.commit()


    # incorporate CYCLoPs data for all genes in the database
    # don't always regenerate CYCLoPs data because it is slow
    if os.path.isfile('./CYCLoPs_dict.json'):
        print('Loading pre-existing CYCLoPs dataset')
        with open(SCRIPT_DIR+'/CYCLoPs_dict.json','r') as fp:
            CYCLoPs_dict = json.load(fp)
        store_CYCLoPs_data(conn,gene_std_names,CYCLoPs_dict)
    else:
        CYCLoPs_dict = store_CYCLoPs_data(conn,gene_std_names)
        with open(SCRIPT_DIR+'/CYCLoPs_dict.json', 'w') as fp:
            json.dump(CYCLoPs_dict, fp)
    conn.commit()


    # Incorporate KEGG info: KO, description, gene names, pathway maps they are a part
    get_KEGG_info_genes(conn)

    # Assign boolean state: enzyme
    # based on yeastGEM reaction catalysis
    find_metabolic_enzymes(conn, gene_sys_names)

    # Also assign enzyme == True based on presence of EC number in KEGG description
    cursor = conn.execute("SELECT * from genes")
    gene_record = [x for x in cursor]

    yeastGEM_enzymes = [x for x in gene_record if x[14] == True ]
    print('Enzymes in YeastGEM:', len(yeastGEM_enzymes))

    has_kegg_name = [x for x in gene_record if x[19] != None ]
    has_EC = [x[1] for x in has_kegg_name if '[EC' in x[19] ]
    print('Number of genes with EC:', len(has_EC))

    # enzymes either have an EC or are present in yeastGEM
    enzymes = [x for x in has_EC]
    enzymes.extend([x[1] for x in yeastGEM_enzymes])
    enzymes = list(set(enzymes))
    print('Number of genes we consider enzymes (EC or in YeastGEM):', len(enzymes))
    list_data_tuples = [(1,g) for g in enzymes]

    # switch these to enzymes in database
    conn.executemany('UPDATE genes SET is_enzyme = ? WHERE systematic_name = ?',list_data_tuples)
    conn.commit()

    # assign categories based on GO terms
    go_dict = find_go_annotations(conn,gene_std_names)
    conn.commit()


    # store a file with all unique compartments: GFP and CYCLoPs together
    cursor = conn.execute("SELECT GFP_localization,CYCLoPs_dict from genes")
    records = [x for x in cursor] # list of tuples

    # loop over all genes: GFP data is a string, CYCLoPs a dictionary
    unique_comps = ['Any','no data']
    for rec in records:
        if '{' in rec[0]: # it is a dictionary
            gfp_rec = ast.literal_eval(rec[0]) # dict {sysname:[comps]}
            if isinstance(gfp_rec,dict):
                gfp_rec2 = list(set([gfp_rec[k] for k in list(gfp_rec.keys())]))
                gfp_rec3 = []
                for x in gfp_rec2:
                    if ', ' in x:
                        y = x.split(', ')
                    else:
                        y = x
                    if len(y) == 1:
                        print('Short:', y, x, gfp_rec3, gfp_rec2, gfp_rec, rec[0])
                        return
                    else:
                        if isinstance(y,list):
                            gfp_rec3.extend(y)
                        else:
                            gfp_rec3.append(y)

                gfp_rec = gfp_rec3
            else:
                print('Not a dictionary:', type(gfp_rec), gfp_rec)
        else: # just a string
            gfp_rec = rec[0].split(', ') # list of strings

        gfp_rec = ['GFP: ' + x for x in gfp_rec] # add database ID
        unique_comps.extend(gfp_rec)

        if rec[1] != 'no data':
            CYCLoPs_rec = ast.literal_eval(rec[1]) # dict {WT1:dict, WT2: dict ...} the keys are the compartments
            if not isinstance(CYCLoPs_rec,dict):
                print('Not a dictionary:', CYCLoPs_rec)
        else:
            continue

        if all([k in ['WT1','WT2','WT3'] for k in list(CYCLoPs_rec.keys())]):
            for k in CYCLoPs_rec:
                CYCLoPs_rec2 = [x for x in list(CYCLoPs_rec[k].keys())] # in py2 we used to remove the unicode: x.encode('ascii','ignore') 
        else:
            print('Unexpected keys:', CYCLoPs_rec)
            return

            # we have a dictionary with multiple systematic names on top of this
            for k_up in CYCLoPs_rec:
                CYCLoPs_rec = CYCLoPs_rec[k_up]
                for k in CYCLoPs_rec:
                    CYCLoPs_rec2 = [x for x in list(CYCLoPs_rec[k].keys())] # in py2 we used to remove the unicode: x.encode('ascii','ignore') 

        CYCLoPs_rec2 = ['CYCLoPs: '+x for x in CYCLoPs_rec2] # add database ID

        unique_comps.extend(CYCLoPs_rec2)

        unique_comps = list(set(unique_comps))
    if '' in unique_comps:
        unique_comps.remove('')
    if ' ' in unique_comps:
        unique_comps.remove(' ')

    unique_comps = [str(x) for x in unique_comps] # unicode to string
    unique_comps = sorted(unique_comps, key=str.lower) # sort alphabetically regardless of capitalization

    print('Done storing a list of compartments in GFP and CYCLoPs.')
    print(unique_comps)

    # write comps to file
    thefile = open('unique_compartments.txt', 'w')
    for item in unique_comps:
        thefile.write("%s\n" % item)
    thefile.close()

    ### GENERATE A MATRIX OF O'S AND 1'S INDICATING INTERACTIONS BETWEEN PROTEINS
    # print 'Generating an interactome matrix.'
    # cursor = conn.execute("SELECT standard_name from genes")
    # gene_record = [x for x in cursor]
    # gene_std_names = [str(x[0]) for x in gene_record]

    # # start interactome with all protein coding genes and all zeros
    # index = gene_std_names; columns = gene_std_names
    # df_yeast_interactome = pd.DataFrame(index=index, columns=columns)
    # df_yeast_interactome = df_yeast_interactome.fillna(0)

    # print df_yeast_interactome.iloc[1990:2000][:10]

    # cursor = conn.execute("SELECT source,target from interactions")
    # ints = [x for x in cursor]
    # for interaction in ints:
    #     if str(interaction[1]) in gene_std_names and str(interaction[0]) in gene_std_names:
    #         df_yeast_interactome.loc[str(interaction[0])][str(interaction[1])] = 1
    #         df_yeast_interactome.loc[str(interaction[1])][str(interaction[0])] = 1

    # df_yeast_interactome.to_csv('export/SGD_proteinCodingGenes_interactome.csv')

    conn.commit()
    print("Records created successfully")

    # backup this DB
    # include the current time 
    t = datetime.datetime.now().strftime("%Y-%m-%d_%H:%M:%S")
    shutil.copyfile(database,'db_backups/backup_database_'+t+'.db')

if __name__ == '__main__':
    main()
