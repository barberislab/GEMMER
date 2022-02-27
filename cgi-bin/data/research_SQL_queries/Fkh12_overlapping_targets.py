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
        print((e.message, e.args))
 
    return None

# connect to database
database = "../DB_genes_and_interactions.db"
conn = create_connection(database)

print('=== Connection established ===')

###################################################
# Start with all genes
###################################################
query = "SELECT standard_name, systematic_name FROM genes"
df_conv = pd.read_sql_query(query, conn).set_index('standard_name')

query = "SELECT standard_name, systematic_name, name_desc, desc, is_enzyme \
        FROM genes"
df_genes = pd.read_sql_query(query, conn).set_index('systematic_name')


###################################################
# Indicate target status in Mondeel et al., Venters et al., Ostrow et al. and MacIsaac et al.
###################################################
query = "SELECT * \
        FROM interactions \
        WHERE   type = 'regulation' \
        AND source = 'FKH1'"
df_fkh1 = pd.read_sql_query(query, conn)

query = "SELECT * \
        FROM interactions \
        WHERE   type = 'regulation' \
        AND source = 'FKH2'"
df_fkh2 = pd.read_sql_query(query, conn)

df_genes['Fkh1 MacIsaac'] = False
df_genes['Fkh1 Venters'] = False
df_genes['Fkh1 Ostrow'] = False
df_genes['Fkh1 Mondeel'] = False
df_genes['Fkh2 MacIsaac'] = False
df_genes['Fkh2 Venters'] = False
df_genes['Fkh2 Ostrow'] = False
df_genes['Fkh2 Mondeel'] = False

MacIsaac = df_conv.loc[df_fkh1.loc[df_fkh1['evidence'].str.contains('16522208'),'target'],'systematic_name']
Venters = df_conv.loc[df_fkh1.loc[df_fkh1['evidence'].str.contains('21329885'),'target'],'systematic_name']
Ostrow = df_conv.loc[df_fkh1.loc[df_fkh1['evidence'].str.contains('24504085'),'target'],'systematic_name']
Mondeel = df_conv.loc[df_fkh1.loc[df_fkh1['evidence'].str.contains('31299083'),'target'],'systematic_name']

df_genes.loc[MacIsaac,'Fkh1 MacIsaac'] = True
df_genes.loc[Venters,'Fkh1 Venters'] = True
df_genes.loc[Ostrow,'Fkh1 Ostrow'] = True
df_genes.loc[Mondeel,'Fkh1 Mondeel'] = True

MacIsaac = df_conv.loc[df_fkh2.loc[df_fkh2['evidence'].str.contains('16522208'),'target'],'systematic_name']
Venters = df_conv.loc[df_fkh2.loc[df_fkh2['evidence'].str.contains('21329885'),'target'],'systematic_name']
Ostrow = df_conv.loc[df_fkh2.loc[df_fkh2['evidence'].str.contains('24504085'),'target'],'systematic_name']
Mondeel = df_conv.loc[df_fkh2.loc[df_fkh2['evidence'].str.contains('31299083'),'target'],'systematic_name']

df_genes.loc[MacIsaac,'Fkh2 MacIsaac'] = True
df_genes.loc[Venters,'Fkh2 Venters'] = True
df_genes.loc[Ostrow,'Fkh2 Ostrow'] = True
df_genes.loc[Mondeel,'Fkh2 Mondeel'] = True


print(df_genes.head())

df_genes.to_excel("Fkh1,2_regulation_summary.xlsx")

