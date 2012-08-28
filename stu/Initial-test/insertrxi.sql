# Load the data into a table for the application of 
# rule1-ok.sql

INSERT rxi
  SELECT freq,mode,day,tod,callr,nr,qthr FROM qso;

