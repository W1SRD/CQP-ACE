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
res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q - D2_CW - 0.5*D1_CW,0) + TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) AS TOTAL, MAX(REGIONAL.SCORE) as RECORD from LOG, SCORE, REGIONAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and OPERATOR_CATEGORY = 'SINGLE-OP' and SCORE.QTH=MULTIPLIER.NAME and MULT_ID = MULTIPLIER.ID group by LOG.ID, QTH having CHECKED_SCORE > RECORD order by TYPE asc, QTH asc, CHECKED_SCORE desc")
prev = nil
res.each { |row|
  if row[1] != prev
    print "New regional record #{OpName(db, row[0].to_i)} for #{row[1]}\n"
    db.query("insert into REGIONAL (MULT_ID, LOG_ID, STATION, YEAR, SCORE, QSOs, MULTIPLIERS) values (#{row[2]}, #{row[0]}, '#{OpName(db, row[0].to_i)}', 2012, #{row[3]}, #{row[5]}, #{row[4]})")
  end
  prev = row[1]
}


db.query("create table if not exists SPECIAL  (ID int not null AUTO_INCREMENT PRIMARY KEY, LOG_ID int null, INDEX(LOG_ID), NAME varchar(32) not null, STATION varchar(32) not null, YEAR smallint not null, SCORE int null, QSOs int null, MULTIPLIERS smallint null, T2_58 time null, CA tinyint)")
# Beware the next line
# db.query("DELETE FROM SPECIAL")  # remove all predefined



# db.query("insert into SPECIAL (NAME, STATION, YEAR, T2_58, CA) values (\"1st to 58 state/pr\", \"W6YI(N6MJ op)\", 2009, '18:43', 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"all time high CA\", \"N6MJ (@W6YI)\", 2011, 418847, 3047, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, CA) values (\"CA club\", \"Mother Lode DX Contest Club\", 2009, 3232294, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"low power CA\", \"AE6Y\", 2010, 268185, 1810, 57, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"M/M expedition\", \"K6Z\", 2011, 657749, 4869, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"M/S expedition\", \"K6QK\", 2011, 313026, 2309, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, QSOs, CA) values (\"mobile QSOs\", \"K6AQL\", 2003, 1685,1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, QSOs, CA) values (\"most CW QSOs\", \"K6YT (W0YK)\", 2009, 1751, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, QSOs, CA) values (\"most SSB QSOs\", \"K6NA (K6LL op)\", 2001, 1995, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"multi-multi\", \"N6O (@N6RO)\", 2000, 731844, 5611, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"multi-single\", \"W6GO+\", 1996, 346086, 2596, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"novice/tech\", \"KA6PUW\", 1998, 76868, 894, 43, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"QRP\", \"N6MU\", 2000, 143964, 1048, 54, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"S/O expedition\", \"N6DE\", 2009, 290985, 2054, 57, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"school\", \"W6YX (M/M)\", 2011, 562484, 3980, 58, 1)")

# db.query("insert into SPECIAL (NAME, STATION, YEAR, T2_58, CA) values (\"1st to 58 CA cnty\", \"W1UE\", 2009, '19:30', 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"all time high non-CA\", \"K5TR\", 2003, 168113, 1309, 58, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, CA) values (\"club\", \"Contest Club Ontario\", 2010, 1191325, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"low power\", \"W8MJ\", 2002, 161385, 1188, 58, 1)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, QSOs, CA) values (\"most CW QSOs\", \"K1ZZI\", 2011, 480, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, QSOs, CA) values (\"most SSB QSOs\", \"NR5M\", 2011, 1080, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"novice/tech\", \"N4HID\", 1992, 46256, 413, 56, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"QRP\", \"ND0C\", 2011, 75824, 566, 56, 0)")
# db.query("insert into SPECIAL (NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, CA) values (\"school\", \"N9UC (S/O)\", 2000, 113274, 814, 58, 0)")

def CheckForNewSpecial(db, award, query, selection="having CHECKED_SCORE > RECORD order by CHECKED_SCORE desc", isCA)
  res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW,0) AS CW_QSOS, TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) as PH_QSOS, SCORE.T2_58, MAX(SPECIAL.SCORE) as RECORD, MIN(SPECIAL.T2_58) as TIMERECORD from LOG, SCORE, SPECIAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and SCORE.QTH=MULTIPLIER.NAME and SPECIAL.NAME = \"#{award}\" #{query} group by LOG.ID, QTH #{selection} limit 1")
  res.each { |row|
    name = OpName(db, row[0].to_i)
    db.query("insert into SPECIAL (LOG_ID, NAME, STATION, YEAR, SCORE, QSOs, MULTIPLIERS, T2_58, CA) values (#{row[0]}, \"#{award}\", \"#{name}\", 2012, #{row[3]}, #{row[5].to_i + row[6].to_i}, #{row[4]}, #{row[7] ? ("\"" + row[7] + "\"") : "NULL"}, #{isCA})")
    print "New score for #{award} by #{name}\n"
  }
end

def CheckForMostQSOs(db, award, qsotype, region, isCA)
  res = db.query("select LOG.ID, QTH, MULTIPLIER.ID, CHECKED_SCORE, CHECKED_MULT, TRUNCATE(#{qsotype},0) AS TQSOS, MAX(SPECIAL.QSOs) AS RQSOS, SCORE.T2_58, MAX(SPECIAL.SCORE) as RECORD, MIN(SPECIAL.T2_58) as TIMERECORD from LOG, SCORE, SPECIAL,MULTIPLIER where LOG.ID=SCORE.LOG_ID and OPERATOR_CATEGORY = 'SINGLE-OP' and SCORE.QTH=MULTIPLIER.NAME and SPECIAL.NAME = \"#{award}\" #{region} group by LOG.ID, QTH having TQSOS > RQSOS order by TQSOS desc limit 1")
  res.each { |row|
    name = OpName(db, row[0].to_i)
    print "New score for #{award} by #{name}\n"
  }
end

# CheckForNewSpecial(db, "1st to 58 state/pr", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and SCORE.T2_58 is not NULL", "having TIME(SCORE.T2_58) < TIMERECORD order by TIME(SCORE.T2_58) asc")
CheckForNewSpecial(db, "all time high CA", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForNewSpecial(db, "YL", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OVERLAY_YL", 1)
CheckForNewSpecial(db, "low power CA", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and LOG.POWER_CATEGORY in (\"LOW\", \"QRP\")", 1)
CheckForNewSpecial(db, "QRP", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and LOG.POWER_CATEGORY = \"QRP\"", 1)
CheckForNewSpecial(db, "M/M expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='MULTI-MULTI' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "M/S expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='MULTI-SINGLE' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "S/O expedition", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and OPERATOR_CATEGORY='SINGLE-OP' and LOG.STATION_CATEGORY='CCE'", 1)
CheckForNewSpecial(db, "school", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\" and STATION_CATEGORY='SCHOOL'", 1)
CheckForMostQSOs(db, "most CW QSOs", "CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)
CheckForMostQSOs(db, "most PH QSOs", "CLAIMED_PH_Q +  - D2_PH - 0.5*D1_PH", "and SPECIAL.CA and MULTIPLIER.TYPE = \"COUNTY\"", 1)


# CheckForNewSpecial(db, "1st to 58 CA cnty", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OPERATOR_CATEGORY=\"SINGLE-OP\" and SCORE.T2_58 is not NULL", "having TIME(SCORE.T2_58) < TIMERECORD order by TIME(SCORE.T2_58) asc")
CheckForNewSpecial(db, "all time high non-CA", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForNewSpecial(db, "YL", "and OPERATOR_CATEGORY=\"SINGLE-OP\" and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OVERLAY_YL", 0)
CheckForNewSpecial(db, "low power non-CA", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and OPERATOR_CATEGORY=\"SINGLE-OP\" and LOG.POWER_CATEGORY in (\"LOW\", \"QRP\")", 0)
CheckForNewSpecial(db, "QRP", "and not SPECIAL.CA and OPERATOR_CATEGORY=\"SINGLE-OP\" and MULTIPLIER.TYPE <> \"COUNTY\" and LOG.POWER_CATEGORY = \"QRP\"", 0)
CheckForMostQSOs(db, "most CW QSOs", "CLAIMED_CW_Q +  - D2_CW - 0.5*D1_CW", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForMostQSOs(db, "most PH QSOs", "CLAIMED_PH_Q +  - D2_PH - 0.5*D1_PH", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\"", 0)
CheckForNewSpecial(db, "school", "and not SPECIAL.CA and MULTIPLIER.TYPE <> \"COUNTY\" and STATION_CATEGORY='SCHOOL'", 0)




db.close()
