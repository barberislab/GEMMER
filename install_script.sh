#!/bin/bash
echo "Making all output folders if they don't exist yet"
mkdir output
mkdir output/excel_files
mkdir output/json_files
mkdir output/include_html
mkdir output/nxviz

echo "Making sure permissions on python module are correct"
# chmod -R 777 ../../html/ # the whole world can rwx
sudo chmod -R a+rwx ./cgi-bin/
sudo chmod -R a+rwx ./output/

echo "Clearing the output folder of html files, json files and excel files"
rm -vf output/*.php
rm -vf output/*.html
rm -vf output/json_files/*.json
rm -vf output/excel_files/*.xlsx
rm -vf output/excel_files/*.csv
rm -vf output/include_html/*.php
rm -vf output/include_html/*.html
