#!/usr/bin/env ruby

require 'mysql2'
YEAR=2014

db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

DELIM = /\s*,\s*/

def lookupLogID(db, callsign)
  rows = db.query("SELECT ID from LOG where CALLSIGN=\"#{callsign}\" and CONTEST_YEAR=#{YEAR} and CONTEST_NAME=\"CA-QSO-PARTY\" limit 1")
  rows.each { |row|
    return row["ID"]
  }
  nil
end

def checkScoreTable(db, callsign, qth, totalq, mults, score )
  rows = db.query("select SCORE.ID as SID from SCORE, LOG where LOG.ID = LOG_ID and CONTEST_YEAR=#{YEAR} and CONTEST_NAME=\"CA-QSO-PARTY\" and SCORE.CALLSIGN=\"#{callsign}\" and SCORE.QTH=\"#{qth}\" and SCORE.CHECKED_Q = #{totalq} and SCORE.CHECKED_MULT = #{mults} and SCORE.CHECKED_SCORE = #{score}")
  rows.each(:as => :array) { |row|
    return row[0]
  }
  nil
end

ARGF.each { |line|
  if not line.start_with?("Call,Qth,2Ltrs,TotCW,TotPH,TotQs,Mults,Score")
    line.upcase!
    fields = line.split(/\s*,\s*/)
    id = checkScoreTable(db, fields[0], fields[1], fields[5].to_i, fields[6].to_i, fields[7].to_i)
    if id
    else
      $stderr.puts("No match for callsign/qth #{fields[0]}/#{fields[1]}\n")
    end
  end
}
db.close
