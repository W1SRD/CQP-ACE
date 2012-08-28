# After a run of postloadnorm.sql, this will print all
# the remaining records that didnt get their frequency
# normalized.
SELECT * FROM qso WHERE 
  freq NOT LIKE '160' AND
  freq NOT LIKE '80' AND
  freq NOT LIKE '40' AND
  freq NOT LIKE '20' AND
  freq NOT LIKE '15' AND
  freq NOT LIKE '10' AND
  freq NOT LIKE '6' AND
  freq NOT LIKE '2';

