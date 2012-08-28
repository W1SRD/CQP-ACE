# Create the RXI table used by rule1-ok.qsl
# for matching QSO records

DROP TABLE IF EXISTS rxi;
CREATE TABLE rxi(
  freq varchar(6),
  mode varchar(3),
  day varchar(12),
  tod varchar(4),
  callr varchar(12),
  nr integer,
  qthr varchar(4)
);

