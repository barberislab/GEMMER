import pandas as pd
import sqlite3
import ast
import os
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt


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

script_dir = os.path.dirname(os.path.abspath(__file__))  #<-- absolute dir the script is in

database = script_dir+"/../cgi-bin/DB_genes_and_interactions.db"
conn = create_connection(database)

### build dataframe of protein coding genes
cursor = conn.execute('SELECT standard_name,systematic_name,go_term_1,go_term_2,is_enzyme,\
					GFP_localization,GFP_abundance,expression_peak_phase,expression_peak_time,\
					name_desc,desc FROM genes')
data = [list(x) for x in cursor]

# dataframe of all genes in GEMMER
df = pd.DataFrame(data,columns=['Standard name','Systematic name','Primary GO term','Secondary GO term',
					'is enzyme','GFP localization','GFP abundance','Expression peak phase','Expression peak time',
					'Name description','Description'])

### Make pie chart
df_go_terms = pd.DataFrame()

total_count = df['Secondary GO term'].value_counts() / len(df) * 100
df_go_terms['Genome-wide secondary GO term %'] = total_count

total_count = df['Primary GO term'].value_counts() / len(df) * 100
df_go_terms['Genome-wide primary GO term %'] = total_count

df_go_terms = df_go_terms.fillna(0)

x = df_go_terms['Genome-wide primary GO term %'].index
y = df_go_terms['Genome-wide primary GO term %'].values

# take colors from GEMMER and align order with x
# green, yellow, blue, purple, orange, snow
colors_dict = {"Cell cycle":"#2ca02c" ,"Cell division":"#ffe119", 
               "DNA replication":"#0080ff", "Signal transduction":"#cc33cc",
               "Metabolism":"#ff7f0e","None":"#F8F8FF"}
colors = [colors_dict[func] for func in x]

# primary
fig1, (ax1, ax2) = plt.subplots(1,2)
patches, texts = ax1.pie(y,startangle=110, colors=colors)
for w in patches:
    w.set_linewidth(0.5)
    w.set_edgecolor('black')
ax1.axis('equal')  # Equal aspect ratio ensures that pie is drawn as a circle.

labels = ['{0} - {1:1.2f} %'.format(i,j) for i,j in zip(x, y)]

sort_legend = True
if sort_legend:
    patches, labels, dummy = zip(*sorted(zip(patches, labels, y),
                                          key=lambda x: x[2],
                                          reverse=True))
ax1.legend(patches, labels, loc='center left', bbox_to_anchor=(-0.1, 1.1),
           fontsize=8)

# secondary
x = df_go_terms['Genome-wide secondary GO term %'].index
y = df_go_terms['Genome-wide secondary GO term %'].values

colors = [colors_dict[func] for func in x]

patches, texts = ax2.pie(y,startangle=110, colors=colors)
for w in patches:
    w.set_linewidth(0.5)
    w.set_edgecolor('black')
ax2.axis('equal')  # Equal aspect ratio ensures that pie is drawn as a circle.

labels = ['{0} - {1:1.2f} %'.format(i,j) for i,j in zip(x, y)]

sort_legend = True
if sort_legend:
    patches, labels, dummy = zip(*sorted(zip(patches, labels, y),
                                          key=lambda x: x[2],
                                          reverse=True))
ax2.legend(patches, labels, loc='center left', bbox_to_anchor=(-0.1, 1.1),
           fontsize=8)


fig1.set_size_inches(10,5)
plt.show()
fig1.savefig('pie_chart_go_term_genome.png', bbox_inches='tight')


