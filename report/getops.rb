#!/usr/bin/env ruby
require 'mysql'
db = Mysql.new("localhost", "dbtest", "dbtest")
callsign = %r!((@)?(([A-Z0-9]{1,4}/)?(\d?[A-Z]+)(\d+)([A-Z]+)(/[A-Z0-9]{1,4})?))!i
db.query("use CQPACE")
rows = db.query("select CALLSIGN, CABRILLO_HEADER, ID, STATION_LOCATION from LOG")
rows.each { |row|
  hdr = row[1]
  if hdr
    hdr = hdr.force_encoding("iso-8859-1")
    operators = [ ]
    station_owner = nil
    club = nil
    hdr.scan(/operators:(.*)/i) { |ops|
#      print row[0] + "\t" + ops[0] + "\n"
      ops[0].scan(callsign) { |sign|
        if sign[1] and sign[1] == "@"
#          print "Owner:" + sign[2] + "\n"
          station_owner = sign[2].upcase
        else
          operators.push(sign[2].upcase)
        end
      }
    }
    if (hdr =~ /^\s*club(-name)?:(.*)/i) 
      club = $2.strip.gsub(/\s\s+/, " ")
      if club and club.length > 0
        clubid = nil
        res = db.query("select ID from CLUB where NAME=\"" + club.upcase + "\" limit 1")
        if (res and res.num_rows() == 1)
          clubid = res.fetch_row()[0]
        else
          res = db.query("select CLUB.ID from CLUB, CLUB_ALIAS where ALIAS=\"" + club.upcase + "\" and CLUB_NAME = NAME limit 1")
          if (res and res.num_rows() == 1)
            clubid = res.fetch_row()[0]
          end
        end
        if (not clubid)
          print "No club match for \"" + club.upcase + "\" " + 
            row[0] + " " + row[3] + "\n"
          print "Add this club? (y/n/m)"
          ans = STDIN.gets.strip
          case ans
          when 'Y','y'
            print "Location (CA|OCA): "
            loc = STDIN.gets.strip.upcase
            db.query("insert into CLUB (NAME, LOCATION) values (\"" +
                     club.upcase + "\", \"" + loc + "\")")
            clubid = db.insert_id()
          when 'M','m'
            print "Official name: "
            name = STDIN.gets.strip.upcase
            print "Location (CA|OCA): "
            loc = STDIN.gets.strip.upcase
            db.query("insert into CLUB (NAME, LOCATION) values (\"" +
                     name.upcase + "\", \"" + loc + "\")")
            clubid = db.insert_id()
            db.query("insert into CLUB_ALIAS (CLUB_NAME, ALIAS, CLUB_ID) values (\"" +
                     name.upcase + "\", \"" + club.upcase + "\", " + clubid.to_s + ")")
          else
            print "Skipping club\n"
          end
        end
      end
    end
    if (station_owner)
      db.query("update LOG set STATION_OWNER_CALLSIGN=\"" + station_owner + "\" where ID="+row[2]+" and STATION_OWNER_CALLSIGN is NULL limit 1")
    end
    if not (operators and operators.length > 0)
      operators.push(row[0].upcase)
    end
    operators.each { |op|
      res = db.query("select ID from OPERATOR where CALLSIGN=\"" + op +
                     "\" and LOG_ID = " + row[2] + " limit 1")
      if not (res and res.num_rows() == 1)
        db.query("insert into OPERATOR (LOG_ID, CALLSIGN) VALUES (" + 
                 row[2] + ", \"" + op + "\")")
      end
    }
    if clubid
      db.query("update OPERATOR set CLUB_ID = " + clubid.to_s + 
               " where LOG_ID = " + row[2])
    end
  end
}
