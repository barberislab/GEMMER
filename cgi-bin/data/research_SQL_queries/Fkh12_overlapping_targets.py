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

# build and execute query
query = "SELECT * \
        FROM interactions \
        WHERE   type = 'regulation' \
            AND source in ('FKH1', 'FKH2') \
            AND num_publications >= 3"
df = pd.read_sql_query(query, conn)

fkh1_targets = df.query("source == 'FKH1'").target.values
fkh2_targets = df.query("source == 'FKH2'").target.values
overlapping_targets = list(set(fkh1_targets).intersection(fkh2_targets))
print(', '.join(overlapping_targets))
print(len(overlapping_targets))

df_overlap = df.query("target in @overlapping_targets").sort_values('target')
df_overlap.to_excel("Fkh1,2_overlapping_targets_3x.xlsx")

query = "SELECT * \
        FROM interactions \
        WHERE   type = 'regulation' \
            AND source in ('FKH1', 'FKH2') \
            AND num_publications >= 4"
df = pd.read_sql_query(query, conn)

fkh1_targets = df.query("source == 'FKH1'").target.values
fkh2_targets = df.query("source == 'FKH2'").target.values

overlapping_targets = list(set(fkh1_targets).intersection(fkh2_targets))
print(', '.join(overlapping_targets))
print(len(overlapping_targets))

df_overlap = df.query("target in @overlapping_targets").sort_values('target')
df_overlap.to_excel("Fkh1,2_overlapping_targets_4x.xlsx")