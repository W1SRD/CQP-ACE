#!/usr/bin/env ruby

require 'mysql'
db = Mysql.new("localhost", "dbtest", "dbtest")
db.query("use CQPACE")
db.query("create table if not exists REGIONAL (ID int not null AUTO_INCREMENT PRIMARY KEY, LOG_ID int null, INDEX(LOG_ID), MULT_ID int not null, INDEX(MULT_ID), STATION varchar(32) not null, YEAR SMALLINT not null, SCORE INT not NULL, QSOs INT not NULL, MULTIPLIERS SMALLINT not null)")
# Beware of the next line.
# db.query("delete from REGIONAL") # delete all entries in REGIONAL records table

def FindMultiplier(db, name)
  res = db.query("select ID from MULTIPLIER where DESCRIPTION = \"" + name + "\" limit 1")
  if (res and res.num_rows() == 1)
    return (res.fetch_row()[0]).to_i
  end
  res = db.query("select MULTIPLIER.ID from MULTIPLIER_ALIAS, MULTIPLIER where ALIAS=\"" + name +
                 "\" and MULTIPLIER.ID = MULTIPLIER_ID limit 1")
  if (res and res.num_rows() == 1)
    return (res.fetch_row()[0]).to_i
  end
  return nil
end

ARGF.each { |line|
  if (line =~ /^"([^"]*)","([^"]*)",(\d+),(\d+),(\d+),(\d+)/)
    if (mid = FindMultiplier(db,$1)) 
      db.query("insert into REGIONAL (MULT_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS) values (#{mid}, '#{$2}', #{$3}, #{$4}, #{$5}, #{$6})")
    else
      print "Unmatched region: " + $1 + "\n"
    end
  else
    print "Unmatched input line: " + line
  end
}

def OpName(db, id)
  info = db.query("select CALLSIGN, STATION_OWNER_CALLSIGN from LOG where ID = #{id} LIMIT 1").fetch_row()
  callsign = info[0]
  owner = info[1]
  extra = "("
  ops = db.query("select CALLSIGN from OPERATOR where LOG_ID = #{id}")
  ops.each { |op|
    if (op[0] != callsign)
      extra += op[0] + " "
    end
  }
  if (extra != "(")
    extra += "op"
  end
  if (owner and owner != callsign)
    if (extra != "(")
      extra += " @"
    else
      extra += "@"
    end
    extra += owner
  end
  if (extra == "(")
    return callsign
  else
    return callsign + " " + extra + ")"
  end
end

# Check if any new records set
res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q + CLAIMED_PH_Q - D2_PH - D2_CW - 0.5*(D1_PH + D1_CW),0) AS TOTAL, MAX(REGIONAL.SCORE) as RECORD from LOG, SCORE, REGIONAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and OPERATOR_CATEGORY = 'SINGLE-OP' and SCORE.QTH=MULTIPLIER.NAME and MULT_ID = MULTIPLIER.ID group by LOG.ID, QTH having CHECKED_SCORE > RECORD order by TYPE asc, QTH asc, CHECKED_SCORE desc")
prev = nil
res.each { |row|
  if row[1] != prev
    db.query("insert into REGIONAL (MULT_ID, LOG_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS) values (#{row[2]}, #{row[0]}, '#{OpName(db, row[0].to_i)}', 2012, #{row[3]}, #{row[5]}, #{row[4]})")
  end
  prev = row[1]
}

db.close()
