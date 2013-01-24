#!/usr/bin/env ruby
require 'mysql'
db = Mysql.new("localhost", "dbtest", "dbtest")
db.query("use CQPACE")
rows = db.query("select LOG.ID, SUM(CLUB_ID is not NULL) as CLUBMEMBERS, COUNT(*) as NUMOPS from LOG, OPERATOR where LOG.ID=OPERATOR.LOG_ID group by ID having NUMOPS > 1 and CLUBMEMBERS > 0")
rows.each { |row|
  db.query("update OPERATOR set CLUB_ALLOCATION = #{1.0/row[2].to_f} where CLUB_ID is not NULL and LOG_ID = " + row[0] + " limit " + row[1])
}
