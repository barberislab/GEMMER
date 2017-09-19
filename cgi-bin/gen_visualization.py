import datetime
import os
import sys
import traceback
from StringIO import StringIO  # Python2

import gemmer
from gemmer.update_interaction_database import create_connection

# from io import StringIO  # Python3


script_dir = os.path.dirname(os.path.abspath(__file__)) #<-- absolute dir the script is in

# Store the reference, in case you want to show things again in standard output
old_stdout = sys.stdout
 
# This variable will store everything that is sent to the standard output
result = StringIO()
sys.stdout = result

cwd = os.getcwd()
# print 'Current folder:',cwd,'\n\n'

arguments = sys.argv
if len(arguments) > 1:
    gene_string = arguments[1]

unique_str = arguments[-1]

database = script_dir+"/gemmer/DB_genes_and_interactions.db"
conn = create_connection(database)
cursor = conn.execute("SELECT * from genes")
gene_record = [x[0] for x in cursor]

# CHECK IF THE INPUT GENE IS AN EXISTING GENE, ELSE EXIT ERROR CODE 1
if isinstance(gene_string,str):
    if '_' in gene_string:
        genes = gene_string.split('_')
    else:
        genes = [gene_string]
else:
    print 'Input is not a string!'
    # Redirect again the std output to screen
    sys.stdout = old_stdout
    # Then, get the stdout like a string and process it
    result_string = result.getvalue()
    print result_string
    raise SystemExit

gene_exists = True
for g in genes:
    if g not in gene_record:
        print "The given gene does not exist in our database:", g
        gene_exists = False

if not gene_exists:
    # Redirect again the std output to screen
    sys.stdout = old_stdout
    # Then, get the stdout like a string and process it
    result_string = result.getvalue()
    print result_string
    raise SystemExit


json_output_filename = os.path.abspath(script_dir+'/../output/json_files/interactome_'+gene_string+'_'+unique_str+'.json') # where to save the interactome
excel_file_base = os.path.abspath(script_dir+'../../output/excel_files/')
file_id = gene_string+'_'+unique_str
output_filenames = [json_output_filename,excel_file_base,file_id]

try: # try, except such that on failure php can show us the error
    gemmer.generate_json_interactome.main(arguments[1:len(arguments)],output_filenames)

    # Redirect again the std output to screen
    sys.stdout = old_stdout
    
    # Then, get the stdout like a string and process it
    result_string = result.getvalue()

    # Save the python output string as a php page to incorporate in the visualization
    filename = script_dir+'/../output/include_html/include_interactome_'+gene_string+'_'+unique_str+'.php'
    with open(filename, "w") as text_file:
        text_file.write(result_string)

    # clean old files
    dir_to_search = script_dir+'/../output'
    for dirpath, dirnames, filenames in os.walk(dir_to_search):
        for file in filenames:
            curpath = os.path.join(dirpath, file)
            file_modified = datetime.datetime.fromtimestamp(os.path.getmtime(curpath))
            if datetime.datetime.now() - file_modified > datetime.timedelta(hours=24):
                os.remove(curpath)

except Exception: 
    print(traceback.format_exc())
    # Redirect again the std output to screen
    sys.stdout = old_stdout
    # Then, get the stdout like a string and process it
    result_string = result.getvalue()
    print result_string
    raise SystemExit
