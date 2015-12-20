#!/usr/bin/env ruby

require 'mysql2'
THISYEAR=2014
db = Mysql2::Client.new(:host  => "localhost", :username => "dbtest", :password => "dbtest")
db.query("use CQPACE")
db.query("create table if not exists REGIONAL (ID int not null AUTO_INCREMENT PRIMARY KEY, LOG_ID int null, INDEX(LOG_ID), MULT_ID int not null, INDEX(MULT_ID), STATION varchar(128) not null, YEAR SMALLINT not null, SCORE INT not NULL, QSOs INT, MULTIPLIERS SMALLINT)")
# Beware of the next line.
# db.query("delete from REGIONAL") # delete all entries in REGIONAL records table

def FindMultiplier(db, name)
  res = db.query("select ID from MULTIPLIER where DESCRIPTION = \"" + name + "\" limit 1")
  res.each(:as => :array) { |row|
    return row[0].to_i
  }
  res = db.query("select MULTIPLIER.ID from MULTIPLIER_ALIAS, MULTIPLIER where ALIAS=\"" + name +
                 "\" and MULTIPLIER.ID = MULTIPLIER_ID limit 1")
  res.each(:as => :array) { |row|
    return row[0].to_i
  }
  return nil
end

# ARGF.each { |line|
#   if (line =~ /^"([^"]*)","([^"]*)",(\d+),(\d+),(\d+),(\d+)/)
#     if (mid = FindMultiplier(db,$1)) 
#       db.query("insert into REGIONAL (MULT_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS) values (#{mid}, '#{$2}', #{$3}, #{$4}, #{$5}, #{$6})")
#     else
#       print "Unmatched region: " + $1 + "\n"
#     end
#   else
#     print "Unmatched input line: " + line
#   end
# }

def OpName(db, id)
  rows = db.query("select CALLSIGN, STATION_OWNER_CALLSIGN from LOG where ID = #{id} LIMIT 1")
  info = nil
  rows.each(:as => :array) { |row|
    info = row
  }
  callsign = info[0]
  owner = info[1]
  extra = "("
  ops = db.query("select CALLSIGN from OPERATOR where LOG_ID = #{id}")
  ops.each(:as => :array) { |op|
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

OPCLASSES = { "SINGLE-OP" => "single-operator",
  "MULTI-SINGLE" => "multi-single",
  "MULTI-MULTI" => "multi-multi"
}

POWERS = { "LOW" => "low",
  "HIGH" => "high",
  "QRP" => "QRP"
}

def getRegions(db, criteria)
  results = [ ]
  rows = db.query("SELECT ID from MULTIPLIER where " + criteria + " order by ID asc")
  rows.each(:as => :array) { |row|
    results << row[0].to_i
  }
  return results
end

CA_COUNTIES = getRegions(db, " TYPE='COUNTY'")
USA_CANADA = getRegions(db, " TYPE in ('STATE', 'PROVINCE') and not NAME = 'CA' ")

def getRegionalRecord(db, id, opclass, power=nil)
  if power
    powerclause=" and POWER_CATEGORY = \"#{power}\" "
  else
    powerclause=""
  end
  res = db.query("select SCORE from REGIONAL where MULT_ID = #{id} and OPERATOR_CATEGORY = \"#{opclass}\" #{powerclause} order by SCORE desc limit 1")
  res.each(:as => :array) { |row|
    return row[0].to_i
  }
  0
end

# Check if any new regional records set
OPCLASSES.keys.each { |opclass|
  CA_COUNTIES.each { |mult_id|
    POWERS.keys.each { |power|
      res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q - D2_CW - 0.5*D1_CW,0) + TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) AS TOTAL FROM LOG, SCORE, MULTIPLIER where LOG.ID = SCORE.LOG_ID and LOG.OPERATOR_CATEGORY = '#{opclass}' and LOG.POWER_CATEGORY = '#{power}' and SCORE.QTH = MULTIPLIER.NAME and #{mult_id} = MULTIPLIER.ID and LOG.CONTEST_YEAR=#{THISYEAR}  and CHECKED_SCORE > #{getRegionalRecord(db, mult_id, OPCLASSES[opclass], POWERS[power])} order by CHECKED_SCORE desc limit 1")
      res.each(:as => :array) { |row|
        print "New regional record #{OpName(db, row[0].to_i)} for #{row[1]}\n"
        db.query("insert into REGIONAL (MULT_ID, LOG_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, OPERATOR_CATEGORY, POWER_CATEGORY) values (#{row[2]}, #{row[0]}, '#{OpName(db, row[0].to_i)}', #{THISYEAR}, #{row[3]}, #{row[5]}, #{row[4]}, \"#{OPCLASSES[opclass]}\", \"#{POWERS[power]}\")")
      }
    }
  }
}
[ "SINGLE-OP" ].each { |opclass|
  USA_CANADA.each { |mult_id|
    res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q - D2_CW - 0.5*D1_CW,0) + TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) AS TOTAL, LOG.POWER_CATEGORY FROM LOG, SCORE, MULTIPLIER where LOG.ID = SCORE.LOG_ID and LOG.OPERATOR_CATEGORY = '#{opclass}' and SCORE.QTH = MULTIPLIER.NAME and #{mult_id} = MULTIPLIER.ID and LOG.CONTEST_YEAR=#{THISYEAR} and CHECKED_SCORE > #{getRegionalRecord(db, mult_id, OPCLASSES[opclass])} order by CHECKED_SCORE desc limit 1")
    res.each(:as => :array) { |row|
      print "New regional record #{OpName(db, row[0].to_i)} for #{row[1]}\n"
      db.query("insert into REGIONAL (MULT_ID, LOG_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, OPERATOR_CATEGORY, POWER_CATEGORY) values (#{row[2]}, #{row[0]}, '#{OpName(db, row[0].to_i)}', #{THISYEAR}, #{row[3]}, #{row[5]}, #{row[4]}, \"#{OPCLASSES[opclass]}\", \"#{POWERS[row[6]]}\")")
    }
  }
}

db.query("create table if not exists SPECIAL  (ID int not null AUTO_INCREMENT PRIMARY KEY, LOG_ID int null, INDEX(LOG_ID), NAME varchar(32) not null, STATION varchar(32) not null, YEAR smallint not null, SCORE int null, QSOs int null, MULTIPLIERS smallint null, T2_58 time null, CA tinyint)")
# Beware the next line
# db.query("DELETE FROM SPECIAL")  # remove all predefined





def CheckForNewSpecial(db, award, query, selection="having CHECKED_SCORE > RECORD order by CHECKED_SCORE desc", isCA)
  res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW,0) AS CW_QSOS, TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) as PH_QSOS, SCORE.T2_58, MAX(SPECIAL.SCORE) as RECORD, MIN(SPECIAL.T2_58) as TIMERECORD from LOG, SCORE, SPECIAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and SCORE.QTH=MULTIPLIER.NAME and LOG.CONTEST_YEAR=#{THISYEAR} and SPECIAL.NAME = \"#{award}\" #{query} group by LOG.ID, QTH #{selection} limit 1")
  res.each(:as => :array) { |row|
    name = OpName(db, row[0].to_i)
    db.query("insert into SPECIAL (LOG_ID, NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, T2_58, CA) values (#{row[0]}, \"#{award}\", \"#{name}\", #{THISYEAR}, #{row[3]}, #{row[5].to_i + row[6].to_i}, #{row[4]}, #{row[7] ? ("\"" + row[7] + "\"") : "NULL"}, #{isCA})")
    print "New score for #{award} by #{name}\n"
  }
end

def CheckForMostQSOs(db, award, qsotype, region, isCA)
  res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(#{qsotype},0) AS TQSOS, MAX(SPECIAL.QSOs) AS RQSOS, SCORE.T2_58, MAX(SPECIAL.SCORE) as RECORD, MIN(SPECIAL.T2_58) as TIMERECORD from LOG, SCORE, SPECIAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and CONTEST_YEAR=#{THISYEAR} and OPERATOR_CATEGORY = 'SINGLE-OP' and SCORE.QTH=MULTIPLIER.NAME and SPECIAL.NAME = \"#{award}\" #{region} group by LOG.ID, QTH having TQSOS > RQSOS order by TQSOS desc limit 1")
  res.each(:as => :array) { |row|
    name = OpName(db, row[0].to_i)
    db.query("insert into SPECIAL (LOG_ID, NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, T2_58, CA) values (#{row[0]}, \"#{award}\", \"#{name}\", #{THISYEAR}, #{row[3]}, #{row[5].to_i}, #{row[4]}, #{row[7] ? ("\"" + row[7] + "\"") : "NULL"}, #{isCA})")
    print "New score for #{award} by #{name}\n"
  }
end

# CheckForNewSpecial(db, "1st to 58 state/pr", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and SCORE.T2_58 is not NULL", "having TIME(SCORE.T2_58) < TIMERECORD order by TIME(SCORE.T2_58) asc")
CheckForNewSpecial(db, "all time high CA", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForNewSpecial(db, "multi-multi", "and OPERATOR_CATEGORY=\"MULTI-MULTI\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForNewSpecial(db, "multi-single", "and OPERATOR_CATEGORY=\"MULTI-SINGLE\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForNewSpecial(db, "YL", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OVERLAY_YL", 1)
CheckForNewSpecial(db, "low power CA", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and LOG.POWER_CATEGORY in (\"LOW\", \"QRP\")", 1)
CheckForNewSpecial(db, "QRP", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and LOG.POWER_CATEGORY = \"QRP\"", 1)
CheckForNewSpecial(db, "M/M expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='MULTI-MULTI' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "M/S expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='MULTI-SINGLE' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "S/O expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='SINGLE-OP' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "school", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and STATION_CATEGORY='SCHOOL'", 1)
CheckForNewSpecial(db, "single-op youth", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YOUTH", 1)
CheckForMostQSOs(db, "most CW QSOs", "CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForMostQSOs(db, "most PH QSOs", "CLAIMED_PH_Q +  - D2_PH - 0.5*D1_PH", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)


# CheckForNewSpecial(db, "1st to 58 CA cnty", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OPERATOR_CATEGORY=\"SINGLE-OP\" and SCORE.T2_58 is not NULL", "having TIME(SCORE.T2_58) < TIMERECORD order by TIME(SCORE.T2_58) asc")
CheckForNewSpecial(db, "all time high non-CA", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForNewSpecial(db, "YL", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OVERLAY_YL", 0)
CheckForNewSpecial(db, "low power non-CA", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OPERATOR_CATEGORY=\"SINGLE-OP\" and LOG.POWER_CATEGORY in (\"LOW\", \"QRP\")", 0)
CheckForNewSpecial(db, "QRP", "and not SPECIAL.CA and OPERATOR_CATEGORY=\"SINGLE-OP\" and MULTIPLIER.TYPE <> \"COUNTY\" and LOG.POWER_CATEGORY = \"QRP\"", 0)
CheckForNewSpecial(db, "single-op youth", "and not SPECIAL.CA and MULTIPLIER.TYPE <> 'COUNTY' and OPERATOR_CATEGORY='SINGLE-OP' and OVERLAY_YOUTH", 0)
CheckForMostQSOs(db, "most CW QSOs", "CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForMostQSOs(db, "most PH QSOs", "CLAIMED_PH_Q +  - D2_PH - 0.5*D1_PH", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForNewSpecial(db, "school", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and STATION_CATEGORY='SCHOOL'", 0)




db.close()
