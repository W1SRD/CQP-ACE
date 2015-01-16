#!/usr/bin/env ruby

require 'mysql2'
YEAR=2014

db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

DELIM = /\s*,\s*/

def lookupLogID(db, callsign, qth)
  rows = db.query("SELECT ID from LOG where (CALLSIGN=\"#{callsign}\" or CALLSIGN like \"#{callsign}/%\") and STATION_LOCATION=\"#{qth}\" and CONTEST_YEAR=#{YEAR} and CONTEST_NAME=\"CA-QSO-PARTY\" limit 1")
  rows.each { |row|
    return row["ID"]
  }
  nil
end

rows = db.query("select SCORE.ID from SCORE, LOG where LOG.CONTEST_YEAR=#{YEAR} and SCORE.LOG_ID = LOG.ID")
sidlist = [ ]
rows.each(:as => :array) { |sid|
  sidlist << sid[0]
}
db.query("delete from SCORE where ID in (#{sidlist.join(", ")}) limit #{sidlist.length}")

ARGF.each { |line|
  line.upcase!
  fields = line.split(DELIM)
  if fields.length == 18
    id = lookupLogID(db, fields[1], fields[16])
    if id
      db.query("insert into SCORE (LOG_ID, CALLSIGN, RAW_Q, DUPE_Q, CLAIMED_MULT, CLAIMED_CW_Q, CLAIMED_PH_Q, CLAIMED_SCORE, CHECKED_Q, D2_CW, D1_CW, D2_PH, D1_PH, CHECKED_MULT, CHECKED_SCORE, QTH, AREA) values (#{id}, \"#{fields[1]}\", #{fields[3]}, #{fields[4]}, #{fields[5]}, #{fields[6]}, #{fields[7]}, #{fields[8]}, #{fields[9]}, #{fields[10]}, #{fields[11]}, #{fields[12]}, #{fields[13]}, #{fields[14]}, #{fields[15]}, \"#{fields[16]}\", \"#{fields[17].strip}\")\n")
    else
      $stderr.puts("!!! No log entry for #{fields[1]}\n")
    end
  else
    $stderr.puts("!!! Error in LS report line\n")
  end
}
db.close
