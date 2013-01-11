require 'mysql'
db = Mysql.new("localhost", "dbtest", "dbtest")
db.query("use CQPACE")
rows = db.query("select ID, CALLSIGN from LOG where CONTEST_YEAR=2012 and CONTEST_NAME='CA-QSO-PARTY'")
rows.each { |row|
  db.query("update QSO set LOG_ID = " + row[0] + " where CALLSIGN_SENT=\"" + row[1] + "\"")
}
