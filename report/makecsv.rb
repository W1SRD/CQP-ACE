#!/usr/bin/env ruby
require 'mysql'
db = Mysql.new("localhost", "dbtest", "dbtest")
db.query("use CQPACE")
rows = db.query("select LOG.CALLSIGN, LOG.ID, SCORE.QTH, STATION_OWNER_CALLSIGN, TRUNCATE(CLAIMED_CW_Q - D2_CW - 0.5*D1_CW,0) AS CW_QSOs, TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) AS PH_QSOs, TRUNCATE(CLAIMED_CW_Q - D2_CW - 0.5*D1_CW,0)+TRUNCATE(CLAIMED_PH_Q - D2_PH - 0.5*D1_PH,0) as TOTAL_QSOs, CHECKED_MULT, CHECKED_SCORE, OPERATOR_CATEGORY, POWER_CATEGORY, MULTIPLIER.DESCRIPTION, MULTIPLIER.TYPE, ENTITY.NAME, CABRILLO_HEADER from LOG, SCORE, MULTIPLIER, ENTITY where LOG.ID = SCORE.LOG_ID and ENTITY.ID = LOG.ENTITY and MULTIPLIER.NAME = SCORE.QTH order by CALLSIGN ASC")

def getOps(db, logid)
  result = ""
  res = db.query("select OPERATOR.CALLSIGN from OPERATOR where LOG_ID=#{logid}")
  res.each { |opsign|
    result += (opsign[0] + " ")
  }
  return result.strip
end

def getName(header)
  header = header.force_encoding("iso-8859-1")
  if (header =~ /^name:\s*(.+)/i)
    return $1
  end
  return ""
end

print "\"Callsign\",\"Name\",\"Operator Calls\",\"Station Callsign\",\"CW QSOs\",\"PH QSOs\",\"Total QSOs\",\"Multipliers\",\"Score\",\"Operator Class\",\"Power\",\"QTH\",\"Type\",\"Country\"\r\n"
rows.each { |row|
  name = getName(row[14])
  ops = getOps(db, row[1].to_i)
  print "\"" + row[0].to_s + "\",\"" + name + "\",\"" + ops + "\",\"" + row[3].to_s + "\"," + row[4].to_s + "," + row[5].to_s + "," + row[6].to_s + "," + row[7].to_s + "," + row[8].to_s + ",\"" + row[9].to_s + "\",\"" + row[10].to_s + "\",\"" + row[11].to_s + "\",\"" + row[12].to_s + "\",\"" + row[13].to_s + "\"\r\n"
}
