# Normalize the frequency field into bands
# NOT exhaustive, there will still likely be records
# that this doesnt capture that should be found using
# findnonnorm.sql

UPDATE qso
  SET freq = '160'
  WHERE freq LIKE '18__' OR freq='19__';

UPDATE qso
  SET freq = '80'
  WHERE freq LIKE '3___';

UPDATE qso
  SET freq = '40'
  WHERE freq LIKE '7___';

UPDATE qso
  SET freq = '20'
  WHERE freq LIKE '14___' OR freq = '14';

UPDATE qso
  SET freq = '15'
  WHERE freq LIKE '21___';

UPDATE qso
  SET freq = '10'
  WHERE freq LIKE '28___';

UPDATE qso
  SET freq = '6'
  WHERE freq LIKE '50___' OR freq = '50';

UPDATE qso
  SET freq = '2'
  WHERE freq LIKE '144___' OR freq = '144';


