""" Executes generate_json_interactome, catches errors, cleans up old files.  """

import datetime
import os
import sqlite3
import sys
import traceback
from io import StringIO

import generate_json_interactome

SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__)) #<-- absolute dir the script is in

def create_connection(db_file):
    """ create a database connection to the SQLite database
        specified by db_file
    :param db_file: database file
    :return: Connection object or None
    """
    try:
        conn = sqlite3.connect(db_file)
        return conn
    except Exception as exc:
        print(exc.message, exc.args)

    return None

def main():
    """ Executes generate_json_interactome, catches errors, cleans up old files.  """

    # Store the reference, in case you want to show things again in standard output
    old_stdout = sys.stdout

    # This variable will store everything that is sent to the standard output
    result = StringIO()
    sys.stdout = result

    arguments = sys.argv
    if len(arguments) > 1:
        gene_string = arguments[1]

    unique_str = arguments[-1]

    database = SCRIPT_DIR+"/data/DB_genes_and_interactions.db"
    conn = create_connection(database)
    cursor = conn.execute("SELECT * from genes")
    gene_record = [x[0] for x in cursor]

    # CHECK IF THE INPUT GENE IS AN EXISTING GENE, ELSE EXIT ERROR CODE 1
    if isinstance(gene_string, str):
        if '_' in gene_string:
            genes = gene_string.split('_')
        else:
            genes = [gene_string]
    else:
        print('Input is not a string!')
        # Redirect again the std output to screen
        sys.stdout = old_stdout
        # Then, get the stdout like a string and process it
        result_string = result.getvalue()
        print(result_string)
        raise SystemExit

    gene_exists = True
    for gene in genes:
        if gene not in gene_record:
            print("The given gene does not exist in our database:", gene)
            gene_exists = False

    if not gene_exists:
        # Redirect again the std output to screen
        sys.stdout = old_stdout
        # Then, get the stdout like a string and process it
        result_string = result.getvalue()
        print(result_string)
        raise SystemExit

    # where to save the interactome
    json_output_filename = os.path.abspath(SCRIPT_DIR+'/../output/json_files/interactome_' +
                                           gene_string + '_' + unique_str + '.json')

    try: # try, except such that on failure php can show us the error

        generate_json_interactome.main(arguments[1:len(arguments)], json_output_filename)

        # Redirect again the std output to screen
        sys.stdout = old_stdout

        # Then, get the stdout like a string and process it
        result_string = result.getvalue()

        # Save the python output string as a php page to incorporate in the visualization
        filename = SCRIPT_DIR+'/../output/include_html/include_interactome_' \
            + gene_string + '_' + unique_str + '.php'
        with open(filename, "w") as text_file:
            text_file.write(result_string)

        # clean old files
        dir_to_search = SCRIPT_DIR+'/../output'
        for dirpath, dirnames, filenames in os.walk(dir_to_search):
            for file in filenames:
                curpath = os.path.join(dirpath, file)
                file_modified = datetime.datetime.fromtimestamp(os.path.getmtime(curpath))
                if datetime.datetime.now() - file_modified > datetime.timedelta(hours=1):
                    os.remove(curpath)

    except Exception:
        print((traceback.format_exc()))
        # Redirect again the std output to screen
        sys.stdout = old_stdout
        # Then, get the stdout like a string and process it
        result_string = result.getvalue()
        print(result_string)
        raise SystemExit

if __name__ == "__main__":
    main()
