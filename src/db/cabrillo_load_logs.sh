#! /bin/bash
# Author: Steve Dyer, W1SRD
# Created: 8-Sep-2012
# Reads all files in a directory and loads the QSO records.
#
# Sed cleanup:
# Replace tabs with spaces: sed -e "s/\t/ /g"
# Remove Windows CR: sed -e "s/\r//g"
# Replace multiple spaces with one space: sed -e "s/ \+/ /g"
# Remove trailing white spaces before the EOL: sed 's/[ ]*$//'
# See REAMDE for MySQL configurations before running this script.

for file in $*
do	
	echo "Processing:" $file	
	cat "$file" | sed -e "s/\t/ /g" | sed -e "s/\r//g" | sed -e "s/ \+/ /g" | sed 's/[ ]*$//' > /tmp/ace_load.log
	echo "Log Records"
	echo `cat /tmp/ace_load.log | grep '^QSO: ' | wc -l`
	echo
	mysql --user=ace --password=ace CQP-ACE < CQP-ACE_log_load_infile.sql
	rm /tmp/ace_load.log
done
