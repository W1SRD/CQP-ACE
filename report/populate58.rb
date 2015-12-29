#!/usr/bin/env ruby

require 'mysql2'
require 'date'
require 'csv'
YEAR=2015

db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")


def lookupLogID(db, callsign, qth)
  rows = db.query("SELECT ID from LOG where (CALLSIGN=\"#{callsign}\" or CALLSIGN like \"#{callsign}/%\") and STATION_LOCATION=\"#{qth}\" and CONTEST_YEAR=#{YEAR} and CONTEST_NAME=\"CA-QSO-PARTY\" limit 1")
  rows.each { |row|
    return row["ID"]
  }
  nil
end

def dbTime(datetimestr)
  time = DateTime.strptime(datetimestr, '%Y-%m-%d %H:%M:%S %Z').to_time
  time.utc.strftime("%Y-%m-%d %H:%M:%S")
end

ARGV.each { |arg|
  CSV.foreach(arg) { |line|
    id = lookupLogID(db, line[0], line[1])
    if id
      db.query("update SCORE set T2_58 = \"#{dbTime(line[2])}\" where LOG_ID = #{id} and QTH=\"#{line[1]}\" limit 1;")
    else
      $stderr.puts("!!! No log entry for #{line[0]}:#{line[1]}\n")
    end
      
  }
}
db.close
