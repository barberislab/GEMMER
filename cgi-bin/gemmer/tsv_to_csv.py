# import modules
# import modules
import pandas as pd
import simplejson as js

# Importing probes list as a dictionary
colnames = ['number', 'symbol', 'sec.symbol']
list = pd.read_csv('./Input-Output/allgenes.tsv', delimiter='\t', names=colnames)
gene_list = list.set_index('number')['symbol'].to_dict()
interactome = pd.DataFrame()

# Query loop starting here:
for g in xrange(1, len(gene_list) + 1):

# Dealing with interactors...
  # parser interactors data from tsv file
  colnames = ['source', 'sec.symbol', 'target', 'int.sec.symbol', 'method', 'pubmed.id']
  dfi = pd.io.parsers.read_csv('./Results/' + gene_list[g] + '_interactors.tsv', header=None, names=colnames, \
    delimiter='\t')

  # count occurrence of an interactor and add it as a new column
  dfi['type'] = 'i'
  dfi['cnt'] = dfi.groupby('int.sec.symbol')['int.sec.symbol'].transform(len)
  dfic = dfi.groupby('int.sec.symbol').apply(lambda obj: obj.head(n=1))

# Dealing with regulators...
  # parser regulators data from tsv file
  dfr = pd.io.parsers.read_csv('./Results/' + gene_list[g] + '_regulators.tsv', header=None, names=colnames, \
    delimiter='\t')

  # count occurrence of a regulator and add it as a new column
  dfr['type'] = 'r'
  dfr['cnt'] = dfr.groupby('int.sec.symbol')['int.sec.symbol'].transform(len)
  dfrc = dfr.groupby('int.sec.symbol').apply(lambda obj: obj.head(n=1))
  dfirc=dfic.append(dfrc)
  
# Dealing with targets...
  # parser regulators data from tsv file
  # dft = pd.io.parsers.read_csv('./Results/' + gene_list[g] + '_targets.tsv', header=None, names=colnames, \
  #  delimiter='\t')

  # count occurrence of a target and add it as a new column
  # dft['type'] = 't'
  # dft['cnt'] = dft.groupby('int.sec.symbol')['int.sec.symbol'].transform(len)
  # dftc = dft.groupby('int.sec.symbol').apply(lambda obj: obj.head(n=1))

  # dfirtc=dfic.append(dftc)
  interactome=interactome.append(dfirc)

interactome.to_csv('interactome_all.csv', sep=',', index=False)