import glob
import os,os.path
import shutil
import operator
import sqlite3
import time
import datetime
from intermine.webservice import Service
import pandas as pd
import numpy as np
import ast
import timeit
from collections import Counter
from orderedset import OrderedSet
from json_load import *


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
        print e.message, e.args
 
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
        print "ERROR: ",e.message, e.args

def store_sceptrans_data(conn):

    sceptrans_data = pd.read_csv('./data/expression_time_phase_primary.csv',sep="\t",index_col=False)
    sceptrans_data.columns = ['Systematic name', 'Standard name', 'Phase', 'Time']
    sceptrans_data = sceptrans_data.sort_values(['Time'])
    print sceptrans_data.iloc[:5]

    cursor = conn.execute("SELECT systematic_name from genes")
    gene_record = [x for x in cursor]
    gene_names = [str(x[0]) for x in gene_record]

    list_data_tuples = []
    for i in range(len(sceptrans_data)):
        row = sceptrans_data.iloc[i]
        if row['Systematic name'] in gene_names:
            list_data_tuples.append((row['Phase'],row['Time'],row['Systematic name']))
        else:
            print 'We do not have/consider:', row['Systematic name']

    conn.executemany('UPDATE genes SET expression_peak_phase = ?, expression_peak_time = ? WHERE systematic_name = ?',list_data_tuples)

def store_gfp_data(conn,gene_symbols,d={}):
    ''' Build a dictionary of gene_symbols to dictionaries of database data
    Pickle the dictionary. If it exists do not redo the computation.'''

    if d == {}:
        gfp_data = pd.read_csv('./data/GFP_database.txt',sep="\t",index_col=False)

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


        matches = [g for g in gene_symbols if g in gfp_data['gene name'].values]
        nomatches = [g for g in gene_symbols if g not in gfp_data['gene name'].values]
        for g in nomatches:
            d[g] = {'abundance':'no data','localization':'no data'}

        print '\nFound',len(matches),'matching genes in the Ghaemmaghami dataset.\n'
        
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

    for gene in gene_symbols:
        conn.execute('UPDATE genes SET GFP_abundance = ?, GFP_localization = ? WHERE standard_name = ?',(d[gene]['abundance'],str(d[gene]['localization']), gene))

    print 'Completed storing GFP data'

    return d

def store_CYCLoPs_data(conn,gene_symbols,CYCLoPs_dict={}):
    ''' Read the three WT datasets for each gene. 
        Save a dictionary with level 1 being the three WTs. 
        Level 2 being a dictionary for a WT with all non-zero compartment expression lvls. 
    '''
    if CYCLoPs_dict == {}: # no old data given
        CYCLoPs_data = [0,0,0]
        CYCLoPs_data[0] = pd.read_excel("./data/CYCLoPs_WT1.xls",header=0)
        CYCLoPs_data[1] = pd.read_excel("./data/CYCLoPs_WT2.xls",header=0)
        CYCLoPs_data[2] = pd.read_excel("./data/CYCLoPs_WT3.xls",header=0)
        cols = list(CYCLoPs_data[0]) # or: my_dataframe.columns.values.tolist()

        print 'Original columns:',cols
        cols = [str(x) for x in cols]
        for i in range(len(CYCLoPs_data)):
            CYCLoPs_data[i].columns = cols
        cols = list(CYCLoPs_data[0])
        print 'Cleaned columns:',cols


        ### ROUND to 3 
        print CYCLoPs_data[0]['Cell Periphery'].dtype
        print 'Rounding to 4 decimals'
        CYCLoPs_data[0] = CYCLoPs_data[0].round(4)
        CYCLoPs_data[1] = CYCLoPs_data[1].round(4)
        CYCLoPs_data[2] = CYCLoPs_data[2].round(4)
        print CYCLoPs_data[0].values

        count = 0
        for gene in gene_symbols:
            if count % 100 == 0:
                print 'Finished storing CYCLoPs data for',count,'genes'

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
                    print "Found multiple hits in CYCLoPs data for",gene,'Taking the first hit.'
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
    
    for gene in gene_symbols:
        sql_CYCLoPs_dict = {}

        d = ast.literal_eval(CYCLoPs_dict[gene])

        ###  store the dictionary :
        # 1. as a stringified dictionary for literal use
        # 2. as a string for the excel files
        # 3. as an html table for output
        if isinstance(d,dict):
            sql_CYCLoPs_dict['dict'] = str(d)
        else:
            print gene, d
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
                max_key = max(d[WT_string].iteritems(), key=operator.itemgetter(1))[0]
                s = str(d[WT_string][max_key]) 

                # insert blue background in the correct table cell 
                if s+'0' in html_string:
                    s = s+'0' # cases like 0.91 to 0.910
                    html_string = html_string.replace('<td>'+s+'</td>', '<td class="bg-info">'+s+'</td>')
                elif s in html_string:
                    html_string = html_string.replace('<td>'+s+'</td>', '<td class="bg-info">'+s+'</td>')
                else:
                    print 'missing:', s 
                    print 'dict:', d
                    print d_df
                    raise SystemExit

        # if gene == 'CLB4' or gene == 'Clb4':
        #     print gene
        #     print max_key
        #     print s
        #     print s in html_string, s+'0' in html_string
        #     print html_string
        #     raise SystemExit

        # change table properties: full width within column
        # # set first column width
        html_string = html_string.replace('<th></th>', '<th style=\"width:100px;\"></th>',1)
        
        sql_CYCLoPs_dict['html_string'] = html_string

        d = sql_CYCLoPs_dict
        conn.execute('UPDATE genes SET CYCLoPs_dict = ?, CYCLoPs_Excel_string = ?, CYCLoPs_html = ?  WHERE standard_name = ?',(d['dict'],d['excel_string'],d['html_string'],gene))

    print 'Completed storing CYCLoPs data'

    return CYCLoPs_dict

def find_go_annotations(conn,gene_symbols):
    go_terms = {'metabolism':'GO:0044237', # cellular metabolic process. previously #'GO:0008152' metabolic process
                'cell cycle':'GO:0007049',
                'cell division':'GO:0051301',
                'signal transduction':'GO:0007165',
                'DNA replication':'GO:0006260', # DNA replication falls under metabolic process
                }
    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    query_results = {k:None for k in go_terms.keys()}
    go_dict = {k:{} for k in go_terms.keys()}
    assigned_interactions = [] #
    print 'Querying SGD this may take a while...'
    for term in go_terms: # for each of our categories ask SGD for all protein coding genes with a GO annotation with this as a parent
        query = service.new_query("ProteinCodingGene")

        query.add_view(
            "symbol", "secondaryIdentifier",
            #"goAnnotation.ontologyTerm.parents.identifier",
            "goAnnotation.ontologyTerm.ontologyAnnotations.ontologyTerm.identifier")
        query.add_constraint("goAnnotation.ontologyTerm.parents.identifier", "=", go_terms[term], code = "A")

        # The query.rows() will contain all genes with such a parent go term but one entry for each actual go term with that parent
        rows = [ {k:row[k] for k in ['symbol','secondaryIdentifier',"goAnnotation.ontologyTerm.ontologyAnnotations.ontologyTerm.identifier"] } for row in query.rows() ] # each row is a dictionary

        query_results[term] = rows
        print term,'query returned',len(rows),'results'

    # Each parent go term query can contain similar go term entries as the others. There is bound to be some overlap
    # i.e. a go term falling under dna replication also shows up at metabolism
    # One go term in the query should belong to one of our sought-after parent go terms
    # filter based on hyrarchy of GO terms
    print "Filtering the duplicate terms and assigning them to one unique parent"

    query_results2 = {k:[] for k in go_terms.keys()} # this will hold filtered rows
    for term in query_results: # loop over our categories
        rows = query_results[term]
        print 'CHECK:',term,'query returned',len(rows),'results'

        # rows is a list of dictionaries, each key is a symbol, systematic name or GO term id
        for row in rows:
            combo = { # map each GO term to a 0 or 1 value
                'metabolism':row in query_results["metabolism"],
                'cell cycle':row in query_results["cell cycle"],
                'cell division':row in query_results["cell division"],
                'signal transduction':row in query_results["signal transduction"],
                'DNA replication':row in query_results["DNA replication"]
            }

            # unique rows belong to their unique term
            # Otherwise, leading terms: DNA rep, division and signal transduction, cell cycle, metabolism, in that order
            if combo['DNA replication']:
                query_results2['DNA replication'].append(row)
            elif combo['cell division']:
                query_results2['cell division'].append(row)
            elif combo['signal transduction']:
                query_results2['signal transduction'].append(row)
            elif combo['cell cycle']:
                query_results2['cell cycle'].append(row)
            elif combo['metabolism']:
                query_results2['metabolism'].append(row)
            else:
                print "None of the categories match..."
                return
    

    # query_results2 is now a dictionary where each go term maps to a list of "rows" which I have changed into dictionaries.
    # So dict -> list -> dictionaries with (standard_name, systematic_name, go term) as keys
    query_results = query_results2

    for term in go_terms:
        print term,'has',len(query_results[term]),'results post-filtering'

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

        print "Found ", len(go_dict[term]), "genes with GO term ", term

    print 'Assigning go terms for all genes...'
    gene_cat_counts = {}
    list_data_tuples = []
    for gene in gene_symbols:
        go = ''
        for term in go_terms: # store GO:****,GO:*****,....
            if gene in go_dict[term]:
                go += go_terms[term]+','
        go = go[:-1] # remove last ,

        # store the number of go terms for each category
        gene_cat_counts[gene] = {k:(go_dict[k][gene] if gene in go_dict[k] else 0 ) for k in go_dict}
        
        # find the maximum one and use that as majority category
        if all([gene_cat_counts[gene][k] == 0 for k in gene_cat_counts[gene]]):
            maj_cat = 'None'
        else:
            # maj_cat = max(gene_cat_counts, key=gene_cat_counts.get) # get max
            sorted_cats = sorted(gene_cat_counts[gene].items(), key=operator.itemgetter(1),reverse=True)
            cat1 = sorted_cats[0][0] # first element has most counts and take the key
            if sorted_cats[1][1] > 0: # check if there is a second non-zero category 
                cat2 = sorted_cats[1][0] # second element has 2nd most counts and take the key
            else: 
                cat2 = 'None'

        list_data_tuples.append(['',cat1,cat2,gene])

    # generate df and export to html the GO terms for each gene
    df_cat_count = pd.DataFrame.from_dict(gene_cat_counts).transpose()
    i = 0
    for gene in gene_symbols:
        list_data_tuples[i][0] = df_cat_count.loc[gene].to_frame().transpose().to_html(classes=['table','table-condensed', 'table-bordered'])
        i += 1

    list_data_tuples = map(tuple,list_data_tuples)

    # store results in database
    conn.executemany('UPDATE genes SET go_terms = ?, go_term_1 = ?, go_term_2 = ? WHERE standard_name = ?',list_data_tuples)
    

    return go_dict

def find_all_genes(conn):
    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    # query SGD for gene info
    query = service.new_query("ProteinCodingGene")
    query.add_view("secondaryIdentifier", "symbol","description","briefDescription")

    rows =  [row for row in query.rows()]
    print 'Found',len(rows),'protein coding genes in SGD\n'

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
        list_data_tuples.append((symbol,secondary,name_desc,desc))
    
    conn.executemany('INSERT into genes(standard_name, systematic_name, name_desc, desc) VALUES (?,?,?,?)',list_data_tuples)

    df = pd.DataFrame(dict_id_to_sysname.items(), columns = ['Standard name','Systematic name'])
    df.to_excel("SGD_standard_name_to_systematic_name.xlsx")
    
    print 'Completed storing all gene records.'

    return


def find_all_interactions(conn,gene_symbols):
    ''' g (String) gene symbol, and con an sqlite database connection '''

    start_time = timeit.default_timer()

    service = Service("https://yeastmine.yeastgenome.org:443/yeastmine/service")

    unique_methods = []

    ##############################
    # Retrieving regulator genes #
    ##############################
    # query description - Retrieve <a href = "https://www.yeastgenome.org/yeastmine-help-page#gene">genes</a> that are regulators of a given target gene.
    print "retrieving all regulators"

    # Get a new query on the class (table) you will be querying:
    query = service.new_query("ProteinCodingGene")

    # Type constraints should come early - before all mentions of the paths they constrain
    query.add_constraint("regulatoryRegions", "TFBindingSite")
    query.add_constraint("organism.shortName", "=", "S. cerevisiae", code = "A")

    # The view specifies the output columns
    query.add_view(
        "secondaryIdentifier", "symbol",
        "regulatoryRegions.factor.secondaryIdentifier",
        "regulatoryRegions.factor.symbol",
        "regulatoryRegions.regEvidence.ontologyTerm.name",
        "regulatoryRegions.publications.pubMedId"
    )

    interactome = {}
    for row in query.rows():
        if row["symbol"] not in ['None',None]:
            t = row["symbol"]
        elif row["secondaryIdentifier"] not in ['None',None]:
            t =  row["secondaryIdentifier"]
        else: 
            print 'Strange case with None IDs:', row
            continue
        
        if row["regulatoryRegions.factor.symbol"] not in ['None',None]:
            s = row["regulatoryRegions.factor.symbol"]
        elif row["regulatoryRegions.factor.secondaryIdentifier"] not in ['None',None]:
            s =  row["regulatoryRegions.factor.secondaryIdentifier"]
        else: 
            print 'Strange case with None IDs:', row
            continue

        row_tuple = (s,t,'regulation',row["regulatoryRegions.regEvidence.ontologyTerm.name"],row["regulatoryRegions.publications.pubMedId"] )

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
    print 'Making tuples out of the interactome to store in SQL.'
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
                ev_pub_html = ', '.join(['<a href="https://www.ncbi.nlm.nih.gov/pubmed/' + pub[i] + '" target="blank" title="' + 'Pubmed ID: '+ pub[i] + '">'+ev[i]+'</a>' for i in range(len(ev))])

                list_data_tuples.append( (int1,int2,int_type,ev_pub,ev_pub_html,numexp,numpub,nummeth ))

    print 'We have',len(list_data_tuples),'interactions to store.'

    # update database with one command
    print 'Storing:',len(list_data_tuples),'physical/genetic interactions'
    conn.executemany('insert or ignore into interactions values(?,?,?,?,?,?,?,?)',list_data_tuples)

    print 'Unique methods so far:', unique_methods

    ####################################
    # Retrieving physical interactions #
    ####################################
    # query description - Retrieve all interactions for a specified <a href = "https://www.yeastgenome.org/yeastmine-help-page#gene">gene</a>.
    print "retrieving all interactors"

    # Get a new query on the class (table) you will be querying:
    query = service.new_query("Interaction")

    # The view specifies the output columns
    query.add_view(
        "participant1.secondaryIdentifier", "participant1.symbol", "participant2.symbol",
        "participant2.secondaryIdentifier",
        "details.relationshipType",
        "details.experiment.interactionDetectionMethods.identifier",
        "details.experiment.publication.pubMedId"
    )

    # You can edit the constraint values below
    query.add_constraint("participant1", "ProteinCodingGene")
    query.add_constraint("participant2", "ProteinCodingGene")

    print 'Query returned:',len(query.rows()),'physical/genetic interactions'
    print 'Making tuples of query output now'

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
                print 'Strange case with None IDs:', row
                continue
        if row_tuple[1] in ['None',None]:
            if row["participant2.secondaryIdentifier"] not in ['None',None]:
                row_tuple[1] = row["participant2.secondaryIdentifier"]
            else: 
                print 'Strange case with None IDs:', row
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
    print 'Making tuples out of the interactome to store in SQL.'
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
                        print 'Cases where pubs are not duplicated:',int1,int2,int_type,counts
                    elif not all([counts[x]%2==0 for x in counts]): # each experiment is counted twice supposedly
                        print 'Unexpected case!'
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
                            temp.extend((counts[x]/2)*[x]) # add the list elements to the temp list
                        else:
                            temp.extend((counts[x])*[x])
                    ev_pub = ', '.join(temp)

                    # Turn the list of strings into a list
                    # repeat uniques as often as we counted them above
                    temp = []
                    counts = Counter(ev_pub_html)
                    for x in list(OrderedSet(ev_pub_html)): # note: loop over uniques, this should preserve the order although its inconsequential
                        if counts[x]>1:
                            temp.extend((counts[x]/2)*[x]) # add the list elements to the temp list
                        else:
                            temp.extend((counts[x])*[x])
                    ev_pub_html = ', '.join(temp)
                else:
                    numexp = len(pub)
                    ev_pub = ', '.join([ev[i]+' ('+pub[i]+')' for i in range(len(ev))])
                    ev_pub_html = ', '.join(['<a href="https://www.ncbi.nlm.nih.gov/pubmed/' + pub[i] + '" target="blank" title="' + 'Pubmed ID: '+ pub[i] + '">' + ev[i]+'</a>' for i in range(len(ev))])
                    counts = Counter(ev_pub)

                if not (isinstance(ev_pub,unicode) and isinstance(ev_pub_html,unicode)):
                    print type(ev_pub), type(ev_pub_html)
                    print 'string:', ev_pub
                    print 'string html:', ev_pub_html
                    raise SystemExit

                list_data_tuples.append( (int1,int2,int_type,ev_pub,ev_pub_html,numexp,numpub,nummeth ))

    # update database with one command
    print 'Storing:',len(list_data_tuples),'regulatory interactions'
    conn.executemany('insert or ignore into interactions values(?,?,?,?,?,?,?,?)',list_data_tuples)

    # save a list of expiermental methods

    # write comps to file
    thefile = open('data/unique_experimental_methods.txt', 'w')
    for item in unique_methods:
        thefile.write("%s\n" % item)
    thefile.close()

    # That's all folks!
    print "done!"

def main():
    script_dir = os.path.dirname(os.path.abspath(__file__)) #<-- absolute dir the script is in
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
                                        GFP_abundance INT,
                                        GFP_localization TEXT,
                                        expression_peak_phase TEXT,
                                        expression_peak_time INT
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
    print "definitions made"
 
    # create a database connection
    conn = create_connection(database)
    print "connection established"
    if conn is not None:
        # create projects table
        create_table(conn, sql_create_genes_table)
        print "Genes table made"
        # create tasks table
        create_table(conn, sql_create_interactions_table)
        print "Interactions table made"
    else:
        print "Error! cannot create the database connection." 


    # STORE ALL PROTEIN CODING GENES
    find_all_genes(conn)
    conn.commit()

    # find the genes in the database NOW
    cursor = conn.execute("SELECT * from genes")
    gene_record = [x for x in cursor]
    gene_symbols = [str(x[0]) for x in gene_record]
    print 'We have records for',len(gene_symbols),'genes'
    print gene_symbols[:10]

    # store sceptrans data
    store_sceptrans_data(conn)


    # incorporate GFP data for all genes in the database
    if os.path.isfile('./data/gfp_dict.json'):
        print 'Loading pre-existing GFP dataset'
        gfp_dict = json_load_byteified(open(script_dir+'/data/gfp_dict.json'))
        store_gfp_data(conn,gene_symbols,d=gfp_dict)
    else:
        gfp_dict = store_gfp_data(conn,gene_symbols)
        with open(script_dir+'/data/gfp_dict.json', 'w') as fp:
            json.dump(gfp_dict, fp)
    conn.commit()


    # incorporate CYCLoPs data for all genes in the database
    # don't always regenerate CYCLoPs data because it is slow
    if os.path.isfile('./data/CYCLoPs_dict.json'):
        print 'Loading pre-existing CYCLoPs dataset'
        CYCLoPs_dict = json_load_byteified(open(script_dir+'/data/CYCLoPs_dict.json'))
        store_CYCLoPs_data(conn,gene_symbols,CYCLoPs_dict)
    else:
        CYCLoPs_dict = store_CYCLoPs_data(conn,gene_symbols)
        with open(script_dir+'/data/CYCLoPs_dict.json', 'w') as fp:
            json.dump(CYCLoPs_dict, fp)
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
                gfp_rec2 = list(set([gfp_rec[k] for k in gfp_rec.keys()]))
                gfp_rec3 = []
                for x in gfp_rec2:
                    if ', ' in x:
                        y = x.split(', ')
                    else:
                        y = x
                    if len(y) == 1:
                        print 'Short:', y, x, gfp_rec3, gfp_rec2, gfp_rec, rec[0]
                        return
                    else:
                        if isinstance(y,list):
                            gfp_rec3.extend(y)
                        else:
                            gfp_rec3.append(y)

                gfp_rec = gfp_rec3
            else:
                print 'Not a dictionary:', type(gfp_rec), gfp_rec
        else: # just a string
            gfp_rec = rec[0].split(', ') # list of strings
            gfp_rec = [x.encode('ascii','ignore') for x in gfp_rec]
        
        gfp_rec = ['GFP: '+x for x in gfp_rec] # add database ID

        unique_comps.extend(gfp_rec)

        if rec[1] != 'no data':
            CYCLoPs_rec = ast.literal_eval(rec[1]) # dict {WT1:dict, WT2: dict ...} the keys are the compartments
            if not isinstance(CYCLoPs_rec,dict):
                print 'Not a dictionary:', CYCLoPs_rec
        else:
            continue

        if all([k in ['WT1','WT2','WT3'] for k in CYCLoPs_rec.keys()]):
            for k in CYCLoPs_rec:
                CYCLoPs_rec2 = [x.encode('ascii','ignore') for x in CYCLoPs_rec[k].keys()] # remove the unicode 
        else:
            print 'Unexpected keys:', CYCLoPs_rec
            return

            # we have a dictionary with multiple systematic names on top of this
            for k_up in CYCLoPs_rec:
                CYCLoPs_rec = CYCLoPs_rec[k_up]
                for k in CYCLoPs_rec:
                    CYCLoPs_rec2 = [x.encode('ascii','ignore') for x in CYCLoPs_rec[k].keys()] # remove the unicode 

        CYCLoPs_rec2 = ['CYCLoPs: '+x for x in CYCLoPs_rec2] # add database ID

        unique_comps.extend(CYCLoPs_rec2)

        unique_comps = list(set(unique_comps))
    if '' in unique_comps:
        unique_comps.remove('')
    if ' ' in unique_comps:
        unique_comps.remove(' ')

    unique_comps.sort()

    print 'Done storing a list of compartments in GFP and CYCLoPs.'
    print unique_comps

    # write comps to file
    thefile = open('data/unique_compartments.txt', 'w')
    for item in unique_comps:
        thefile.write("%s\n" % item)
    thefile.close()

    # communicate with SGD and get interactions
    find_all_interactions(conn,gene_symbols)

    ### GENERATE A MATRIX OF O'S AND 1'S INDICATING INTERACTIONS BETWEEN PROTEINS
    print 'Generating an interactome matrix.'
    cursor = conn.execute("SELECT standard_name from genes")
    gene_record = [x for x in cursor]
    gene_symbols = [str(x[0]) for x in gene_record]

    # start interactome with all protein coding genes and all zeros
    index = gene_symbols; columns = gene_symbols
    df_yeast_interactome = pd.DataFrame(index=index, columns=columns)
    df_yeast_interactome = df_yeast_interactome.fillna(0)

    print df_yeast_interactome.iloc[1990:2000][:10]

    cursor = conn.execute("SELECT source,target from interactions")
    ints = [x for x in cursor]
    for interaction in ints:
        if str(interaction[1]) in gene_symbols and str(interaction[0]) in gene_symbols:
            df_yeast_interactome.loc[str(interaction[0])][str(interaction[1])] = 1
            df_yeast_interactome.loc[str(interaction[1])][str(interaction[0])] = 1

    df_yeast_interactome.to_csv('data/export/SGD_proteinCodingGenes_interactome.csv')

    # assign categories based on GO terms
    go_dict = find_go_annotations(conn,gene_symbols)
    conn.commit()

    conn.commit()
    print "Records created successfully"

    # backup this DB
    # include the current time 
    t = datetime.datetime.now().strftime("%Y-%m-%d_%H:%M:%S")
    shutil.copyfile(database,'db_backups/backup_database_'+t+'.db')

if __name__ == '__main__':
    main()
