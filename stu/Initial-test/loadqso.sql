# Load data from the raw qso records into the db

LOAD DATA LOCAL INFILE 'qsorecs.dbl'
  INTO TABLE qso
  FIELDS TERMINATED BY '|'
  (freq, mode, day, tod, calls, ns, qths, callr, nr, qthr);

