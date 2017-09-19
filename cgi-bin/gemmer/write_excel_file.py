import pandas as pd
import sys
import os

arguments = sys.argv

file_id = arguments[1]

# prepend the path
script_dir = os.path.dirname(os.path.abspath(__file__))  #<-- absolute dir the script is in

filename_base = os.path.abspath(script_dir+'../../../output/excel_files/')

# read the csv's
df_user_input = pd.read_csv(filename_base+'/user_input_'+file_id+'.csv')
df_network = pd.read_csv(filename_base+'/network_'+file_id+'.csv')
df_nodes = pd.read_csv(filename_base+'/nodes_'+file_id+'.csv')
df_interactome = pd.read_csv(filename_base+'/interactome_'+file_id+'.csv')

writer = pd.ExcelWriter(filename_base+'/interactome_'+file_id+'.xlsx', engine='xlsxwriter')
workbook = writer.book

format_null = workbook.add_format({'text_wrap': True,'align':'left','font_size':10})

### User input
df_user_input.to_excel(writer,sheet_name='user input', index=True)
worksheet = writer.sheets['user input']
worksheet.set_column('A:B',30,format_null)

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
worksheet.set_column('J:L',15,format_null)

### Interactome
df_interactome.to_excel(writer,sheet_name='interactome',index=False)
worksheet = writer.sheets['interactome']
worksheet.set_column('A:C',10,format_null)
worksheet.set_column('D:D',100,format_null)
worksheet.set_column('E:G',15,format_null)

worksheet.set_column('A:G',None,format_null)

# SAVE
writer.save()

print 'Completed generating the Excel file.'