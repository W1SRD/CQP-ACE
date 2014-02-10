#!/usr/bin/env ruby

require 'mysql2'

db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

rows = db.query("select LOG.ID as LID from LOG, MULTIPLIER where  LOG.STATION_LOCATION = MULTIPLIER.NAME and MULTIPLIER.TYPE IN  ('COUNTY', 'STATE') and CONTEST_YEAR=2013 AND ENTITY is NULL")
rows.each { |row|
  db.query("update LOG set ENTITY = 291 where ID = #{row['LID']} limit 1")
}

rows = db.query("select LOG.ID as LID from LOG, MULTIPLIER where  LOG.STATION_LOCATION = MULTIPLIER.NAME and MULTIPLIER.TYPE = 'PROVINCE' and CONTEST_YEAR=2013 AND ENTITY is NULL")
rows.each { |row|
  db.query("update LOG set ENTITY = 1 where ID = #{row['LID']} limit 1")
}

