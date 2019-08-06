import pandas as pd
import sqlite3

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

def merge_records(x):
    """Merge duplicate mutant records"""
    return pd.Series({'Standard_name': x['Standard_name'].iloc[0], 
                        'viability': ', '.join(list(set(x['viability'].tolist())))})

# connect to database
database = "../DB_genes_and_interactions.db"
conn = create_connection(database)

print('=== Connection established ===')

# build and execute query
query = "SELECT standard_name, systematic_name, go_term_1, go_term_2, name_desc, desc, KEGG_description \
        FROM genes WHERE go_term_1 IN ('Cell cycle', 'Cell division', 'DNA replication') OR go_term_2 IN ('Cell cycle', 'Cell division', 'DNA replication')"
df1 = pd.read_sql_query(query, conn, index_col="systematic_name")

print('\nGO term query returned %i results.' % (df1.shape[0]))

query = "SELECT standard_name, systematic_name, go_term_1, go_term_2, name_desc, desc, KEGG_description \
        FROM genes WHERE (desc LIKE '%cell cycle%' OR desc LIKE '%cell division%' OR desc LIKE '%DNA replication%') \
        OR (KEGG_description LIKE '%cell cycle%' OR KEGG_description LIKE '%cell division%' OR KEGG_description LIKE '%DNA replication%')    "
df2 = pd.read_sql_query(query, conn, index_col="systematic_name")

# keep only new results
df2 = df2.drop(labels=[idx for idx in df2.index if idx in df1.index.tolist()])

print('\nDescription query returned %i new results.' % (df2.shape[0]))


# read mutant properties
df_mutants = pd.read_csv("./null_mutant_data_yeastmine.tsv", sep="\t", index_col=0, header=None, usecols=[0,1,2], names=['Systematic_name', 'Standard_name', 'viability'])

# join duplicate mutant records
df_mutants = df_mutants.groupby(df_mutants.index).apply(lambda x: merge_records(x))

# merge viability data
df_mutants_sub1 = df_mutants.reindex(df1.index)
df_mutants_sub2 = df_mutants.reindex(df2.index)
df1['viability'] = df_mutants_sub1['viability']
df2['viability'] = df_mutants_sub2['viability']

with pd.ExcelWriter("All_cell_cycle_related_genes.xlsx") as writer:
    # set format
    workbook = writer.book
    format_null = workbook.add_format({'text_wrap': True})
    
    df1.to_excel(writer, sheet_name="On GO")

    worksheet = writer.sheets["On GO"]

    # freeze first row and first column
    worksheet.freeze_panes(1, 1)

    # set column widths
    worksheet.set_column('A:D', 25, format_null)
    worksheet.set_column('E:E', 60, format_null)
    worksheet.set_column('F:G', 100, format_null)

    df2.to_excel(writer, sheet_name="On description")

    worksheet = writer.sheets["On description"]

    # freeze first row and first column
    worksheet.freeze_panes(1, 1)

    # set column widths
    worksheet.set_column('A:D', 25, format_null)
    worksheet.set_column('E:E', 60, format_null)
    worksheet.set_column('F:G', 100, format_null)
