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


# Add additional (Venters, 2011) interactions not in SGD
df_venters = pd.read_excel('./Fkh12_additional_data/Venters_2011_25C_UTmax.xls', header=0, skiprows=[0,1,2,4,5,6,7,8,9,10,11,12,13], usecols=[0,8,9])
df_venters = df_venters.set_index('Factor') # pandas automatically labels the first column with systematic names 'Factor'

venters_fkh1 = df_venters[df_venters['Fkh1'] > 0.8].index.tolist()
venters_fkh2 = df_venters[df_venters['Fkh2'] > 1.13].index.tolist()