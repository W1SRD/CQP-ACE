#! /bin/bash
# Author: Steve Dyer, W1SRD
# Created: 8-Sep-2012
# Reads all files in a directory and loads the QSO records.
#
# Sed cleanup:
# Remove tab characters: sed -e "s/\t/ /g"
# Remove Windows CR: sed -e "s/\r//g"
# Replace multiple spaces with one space: sed -e "s/ \+/ /g"
# See REAMDE for MySQL configurations before running this script.

for file in /home/sdyer/Development/CQP-ACE/test/logs_12_20_2011/**
do
	cat $file | sed -e "s/\t/ /g" | sed -e "s/\r//g" | sed -e "s/ \+/ /g"  > /tmp/ace_load.log
	mysql --user=ace --password=ace CQP-ACE < ace_load.sql
	rm /tmp/ace_load.log
done
