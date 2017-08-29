import networkx as nx
from bs4 import BeautifulSoup
import csv
import re
import sys
reload(sys)
sys.setdefaultencoding('utf-8')

hashmap= {}
G = nx.DiGraph()
filename1 = "pagerank.csv"

with open(filename1, "r") as lines:
    for line in lines:
        urls = line.split(',')
        if urls[0] not in hashmap:
        	hashmap[urls[0]] = []
		if len(urls)>1:
        		for url in urls[1:]:
           			hashmap[urls[0]].append(url)

for key in hashmap:
    G.add_node(key)
    for url in hashmap[key]:
        G.add_edge(key, url)
pr = nx.pagerank(G,alpha=0.85,personalization=None,max_iter=30,tol=1e-06,nstart=None,weight='weight',dangling=None)

output = open("external_pageRankFile.txt", "w")
for key in pr:
	if(key in hashmap):
    		output.write("/var/www/html/LATimesDownloadData/" + key.strip() + "=" +("%f" % pr[key])+'\n')
output.close()


