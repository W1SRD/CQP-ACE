#!/usr/bin/env ruby

require 'mysql2'
YEAR=2015

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

ARGF.each { |line|
  line.upcase!
  fields = line.split(DELIM)
  if fields.length == 19
    id = lookupLogID(db, fields[1], fields[16])
    if id
      db.query("update LOG set ENTITY = #{fields[18].to_i} where ID = #{id} limit 1;")
    else
      $stderr.puts("!!! No log entry for #{fields[1]}\n")
    end
  else
    $stderr.puts("!!! Error in LS report line\n")
  end
}
db.close
