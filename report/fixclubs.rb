#!/usr/bin/env ruby

require 'mysql2'

db =  Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

rows = db.query("select LOG.ID as LID, LOG.CALLSIGN as CALLSIGN, OPERATOR.ID from LOG left outer join OPERATOR on LOG.ID = OPERATOR.LOG_ID where CONTEST_YEAR = 2013 and OPERATOR.ID is NULL")
rows.each { |row|
  db.query("insert into OPERATOR (LOG_ID, CALLSIGN, CLUB_ALLOCATION) values (#{row['LID']}, \"#{row['CALLSIGN']}\", 1.0)")
}

rows = db.query("select LOG.ID as LID, COUNT(*) as NUMOP from LOG, OPERATOR where LOG.ID = OPERATOR.LOG_ID and CONTEST_YEAR = 2013 and LOG.CLUB is NULL GROUP BY LOG.ID")
rows.each { |row|
  allocation = 1.0 / row["NUMOP"].to_f
  db.query("update OPERATOR set CLUB_ALLOCATION = #{allocation}, CLUB_ID = NULL where LOG_ID = #{row['LID']} limit #{row['NUMOP']}")
}

rows = db.query("select LOG.ID as LID, COUNT(*) as NUMOP, CLUB.ID as CID from LOG, OPERATOR, CLUB where LOG.ID = OPERATOR.LOG_ID and CONTEST_YEAR = 2013 and LOG.CLUB = CLUB.NAME GROUP BY LOG.ID")
rows.each { |row|
  allocation = 1.0 / row["NUMOP"].to_f
  db.query("update OPERATOR set CLUB_ALLOCATION = #{allocation}, CLUB_ID = #{row['CID']} where LOG_ID = #{row['LID']} limit #{row['NUMOP']}")
}
