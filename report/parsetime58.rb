#!/usr/bin/env ruby


require 'mysql2'
YEAR=2014

db = Mysql2::Client.new(:host => "localhost", :username=>"dbtest", :password=>"dbtest",
                :database=>"CQPACE")

DELIM = /\s*,\s*/

def lookupLogID(db, callsign)
  result = nil
  rows = db.query("SELECT ID from LOG where CALLSIGN=\"#{callsign}\" and CONTEST_YEAR=#{YEAR} and CONTEST_NAME=\"CA-QSO-PARTY\" limit 2")
  rows.each { |row|
    if result
      result = nil # two answers means non-unique
    else
      result = row["ID"].to_i
    end
  }
  result
end

def lookupScoreID(db, logid, call)
  result = nil
  rows = db.query("SELECT ID from SCORE where CALLSIGN=\"#{call}\" and LOG_ID = #{logid} limit 2")
  rows.each { |row|
    if result
      result = nil # two answers means non-unique
    else
      result = row["ID"].to_i
    end
  }
  result
end

def baseCall(call)
  call.sub(/\/\d+$/, "")
end

def storeTimeFiftyEight(db, sid, date, time)
  if date =~ /^(\d{4})-(\d{2})-(\d{2})$/
    year = $1.to_i
    month = $2.to_i
    day = $3.to_i
    if time =~ /^(\d{2})(\d{2})$/
      hour = $1
      minute = $2
      db.query("update SCORE set T2_58 = \"#{date + " " + $1 + ':' + $2 + ':00'}\" where ID = #{sid.to_s} limit 1\n")
    else
      $stderr.puts("!! Bad time format #{time}\n")
    end
  else
    $stderr.puts("!! Incorrect date format #{date}\n")
  end
end

ARGF.each { |line|
  if not (line.start_with?("TIME58,sCall,location,CATEGORY,total_mults,last_mult,date,time,mode,band,rCall,rNr") or (line.strip.length == 0))
    line.upcase!
    fields = line.split(/\s*,\s*/)
    if (fields.length >= 13)
      call = fields[1].strip.upcase
      lid = lookupLogID(db, call)
      if lid
        sid = lookupScoreID(db, lid, baseCall(call))
        if sid
          storeTimeFiftyEight(db, sid, fields[6], fields[7])
        else
          $stderr.puts("!!! Trouble finding SCORE entry for #{call}\n")
        end
      else
        $stderr.puts("!!! Troublng finding LOG entry for #{call}\n")
      end
    end
  end
}
db.close
