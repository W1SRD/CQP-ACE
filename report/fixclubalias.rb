#!/usr/bin/env ruby
require 'mysql2'
db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

rows = db.query("select CLUB.ID AS CID, CLUB.NAME as NAME from CLUB LEFT OUTER JOIN CLUB_ALIAS on CLUB.NAME = CLUB_ALIAS.ALIAS where CLUB_ALIAS.ALIAS is NULL")
rows.each { |row|
  print row + "\n"
  db.query("insert into CLUB_ALIAS (CLUB_NAME, ALIAS, CLUB_ID) values (\"#{row["NAME"]}\", \"#{row["NAME"]}\", #{row["CID"]})")
}
db.close
