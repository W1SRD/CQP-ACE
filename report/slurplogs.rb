#!/usr/bin/env ruby
#
# Ruby program to check CQP logs for syntactic complaince with the
# specification. 
#                              -------info sent-------- -----info recvd---------
#QSO: Freq  Mo Date       Time Callsign      Seq  Exch  Callsign      Seq  Exch
#QSO: ***** ** yyyy-mm-dd nnnn ************* nnnn ***** ************* nnnn *****

require 'date'
require 'set'
require 'rubygems'
require 'mysql2'
require 'getoptlong'

cmdlineopts = GetoptLong.new(
   [ '--user', '-u', GetoptLong::REQUIRED_ARGUMENT],
   [ '--password', '-p', GetoptLong::REQUIRED_ARGUMENT],
   [ '--bad', '-b', GetoptLong::REQUIRED_ARGUMENT],
   [ '--csv', '-c', GetoptLong::NO_ARGUMENT]
)

db_user = 'dbtest'
db_passwd = 'dbtest'
$bad_file = nil
csv_report = nil
cmdlineopts.each { |opt, arg|
  case opt
  when '--user'
    db_user = arg
  when '--password'
    db_passwd = arg
  when '--csv'
    csv_report = true
  when '--bad'
    $bad_file = open(arg, "w")
  end
}

if not ("foo".respond_to?(:force_encoding))
  class String
     def force_encoding(str)
        self
     end
  end
end


def lookupCollection(db, constraints)
  rows = db.query("select NAME from MULTIPLIER where " + constraints + " order by NAME asc")
  collection = [ ]
  rows.each(:as => :array) { |row|
    collection.push(row[0])
  }
  return collection
end


db = Mysql2::Client.new(:host => "localhost", :username=>db_user, :password=>db_passwd,
                :database=>"CQPACE")
CA_COUNTIES_STRICT = lookupCollection(db, " TYPE = 'COUNTY' ")
NON_CA_STRICT = lookupCollection(db, " TYPE != 'COUNTY' and NAME != 'XXXX' ")


class QSO
  def initialize(freq, mode, year, month, day, time,
                 callsent, seqsent, exchsent,
                 callrcvd, seqrcvd, exchrcvd)
    @frequency = freq.to_i
    @mode = mode.to_s
    if 2 ==  year.to_s.length
      year = 2000 + year.to_i
    else
      year = year.to_i
    end
    if 3 == time.to_s.length
      hour = time[0].to_i
      min = time[1,2].to_i
    else
      hour = time[0, 2].to_i
      min = time[2,2].to_i
    end
    begin
      @time = DateTime.new(year, month.to_i, day.to_i, hour, min, 0)
    rescue
      @time = nil
    end
    @callsent = callsent.strip.upcase
    @seqsent = seqsent.to_i
    @exchsent = exchsent.strip.upcase
    @callrcvd = callrcvd.strip.upcase
    @seqrcvd = seqrcvd.to_i
    @exchrcvd = exchrcvd.strip.upcase
    @strict = true
  end

  attr_reader :frequency, :mode, :time, :callsent, :seqsent, :exchsent,
    :callrcvd, :seqrcvd, :exchrcvd
  attr_accessor :strict
end

class CQPLog

  CA_QTH_STRICT = Set.new(CA_COUNTIES_STRICT)

  # CA is invalid -- must log county

  NON_CA_QTH_STRICT = Set.new(NON_CA_STRICT)

  # define what's workable
  CA_VALID = Set.new(NON_CA_STRICT + CA_COUNTIES_STRICT)
  NON_CA_VALID = CA_QTH_STRICT

  QSOSTRICT=/^QSO: +(\d+) +([A-Z]{2}) +(\d{4})-(\d{2})-(\d{2}) +(\d{4}) +([\/A-Z0-9]+) +(\d{4}) +([A-Z]{2}|[A-Z]{4}) +([\/A-Z0-9]+) +(\d{4}) +([A-Z]{2}|[A-Z]{4})\s*$/
  QSOLOOSE=/^QSO: +(\d+) +([A-Z]+) +(\d{4})-(\d{1,2})-(\d{1,2}) +(\d{3,4}) +([\/A-Z0-9]+) +(\d{1,5}) +([A-Z]{1,5}) +([\/A-Z0-9]+) +(\d{1,5}) +([A-Z]{1,5})/i

  def calcTimeBounds(year)
    octStart = DateTime.new(year, 10, 1, 16, 0, 0)
    octStart = octStart + (6 - octStart.wday)
    octStop = octStart + Rational(5, 4) # 30 hours
    return octStart, octStop
  end

  def initialize(year)
    @county = nil
    @claimed = nil
    @cabrillo_header = ""
    @soapbox = ""
    @club = nil
    @youth = nil
    @location = nil
    @newcontester = nil
    @operators = nil
    @startTime, @endTime = calcTimeBounds(year)
    @version = nil
    @callsign = nil
    @callsign_sent = Set.new
    @assisted = nil
    @female = nil
    @optype = nil
    @power = nil
    @stationtype = nil
    @numtransmitter = nil
    @contestname = nil
    @email = nil
    @curTime = @startTime
    @strictQs = 0
    @looseQs = 0
    @unparseableQs = 0
    @wrongTime = 0
    @nonSequential = 0
    @badQTH = 0
    @badExch = 0
    @badDateTime = 0
    @badExchanges = Set.new
    @qthExchConflict = 0
  end

  def makeQSO(m, isStrict)
    qso = QSO.new(m[1], m[2], m[3], m[4], m[5], m[6],
                  m[7], m[8], m[9], # sent: call, sequence, exchange
                  m[10], m[11], m[12]) # rcvd: call, sequence, exchange
    qso.strict = isStrict
    return qso
  end

  def parseQSO(line)
    qso = nil
    if (m = QSOSTRICT.match(line))
      qso = makeQSO(m, true)
      @strictQs = @strictQs + 1
    elsif (m = QSOLOOSE.match(line))
      qso = makeQSO(m, nil)
      @looseQs = @looseQs + 1
    end
    if qso
      @callsign_sent.add(qso.callsent)
      if not @location
        @location = qso.exchsent
      end
      if qso.time
        if (qso.time < @startTime) or (qso.time > @endTime)
          @wrongTime = @wrongTime + 1
        end
        if (qso.time >= @curTime)
          @curTime = qso.time
      	else
          @nonSequential = @nonSequential + 1
        end
      else
        @badDateTime = @badDateTime + 1
      end
      if (CA_QTH_STRICT.include?(qso.exchsent) or
          NON_CA_QTH_STRICT.include?(qso.exchsent))
        if (CA_VALID.include?(qso.exchrcvd) or
            NON_CA_VALID.include?(qso.exchrcvd))
          if not ((CA_QTH_STRICT.include?(qso.exchsent) and
                CA_VALID.include?(qso.exchrcvd)) or
              (NON_CA_QTH_STRICT.include?(qso.exchsent) and
                NON_CA_VALID.include?(qso.exchrcvd)))
            @qthExchConflict = @qthExchConflict + 1
          end
        else
          @badExchanges.add(qso.exchrcvd)
          @badExch = @badExch + 1
        end
      else
        @badQTH = @badQTH + 1
      end
    end
    return qso
  end

  def parseCategories(str, filename)
    str.split.each { |cat|
      cat = cat.upcase
      if ("SINGLE-OP" == cat) or ("SO" == cat) or ("SINGLE-OP-ALL" == cat) or ("SINGLE-OP-UNASSISTED" == cat) or ("SINGLE-OP-NON-ASSISTED" == cat) or ("SINGLE" == cat)
        @optype = "SINGLE-OP"
        @assisted = "NON-ASSISTED"
        @numtransmitter = "ONE"
      elsif ("MOBILE" == cat)
        @stationtype = "MOBILE"
      elsif ("MULTI-SINGLE-OP-ASSISTED" == cat)
        @optype = "SINGLE-OP"
        @assisted = "ASSISTED"
        @numtransmitter = "UNLIMITED"
      elsif ("SINGLE-OP-ASSISTED" == cat)
        @optype = "SINGLE-OP"
        @assisted = "ASSISTED"
        @numtransmitter = "ONE"
      elsif ("MULTI-ONE" == cat) or ("MULTI-SINGLE" == cat) or ("MILTI-SINGLE" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "ONE"
      elsif ("MULTI-TWO" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "TWO"
      elsif ("MULTI-MULTI" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "UNLIMITED"
      elsif ("MM-HP" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "UNLIMITED"
        @power = "HIGH"
      elsif ("MULTI-OP" == cat)
        @optype = "MULTI-OP"
      elsif ("SCHOOL-CLUB" == cat)
        @optype = "MULTI-OP"
        @stationtype = "SCHOOL"
        @numtransmitter = "UNLIMITED"
      elsif ("NONASSISTED" == cat) or ("UNASSISTED" == cat) or ("NON-ASSISTED" == cat)
        @assisted = "NON-ASSISTED"
      elsif ("ASSISTED" == cat)
        @assisted = "ASSISTED"
      elsif ("YL" == cat)
        @female = true
      elsif ("COUNTY" == cat) or ("EXPEDITION" == cat) or ("CCE" == cat)
        @county = true
      elsif ("SCHOOL" == cat)
        @optype = "MULTI-OP"
        @stationtype = "SCHOOL"
        @numtransmitter = "UNLIMITED"
      elsif ("SWL" == cat)
        @optype = "SINGLE-OP"
        @stationtype = "FIXED"
        @numtransmitter = "SWL"
      elsif ("MS-LP" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "ONE"
        @power = "LOW"
      elsif ("CHECKLOG" == cat)
        @optype = "CHECKLOG"
      elsif ("SINGLE-OP-PORTABLE" == cat)
        @optype = "SINGLE-OP"
        @assisted = "NON-ASSISTED"
      elsif ("ROVER" == cat)
        @stationtype = "ROVER"
      elsif ("MULTI-LIMITED" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "LIMITED"
      elsif ("MULTI-UNLIMITED" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "UNLIMITED"
      elsif ("ALL" == cat)
      elsif (cat =~ /(160|80|40|20|15|10)M/)
      elsif ("HIGH" == cat) or ("LOW" == cat) or ("QRP" == cat)
        @power = cat
      elsif ("LOW-POWER" == cat) or ("LOW" == cat) or ("LO" == cat)
        @power = "LOW"
      elsif ("MEDIUM" == cat)
        @power = "HIGH"
      elsif ("SO-LP" == cat) or ("SOLP" == cat)
        @power = "LOW"
        @optype = "SINGLE-OP"
        if not @assisted
          @assisted = "NON-ASSISTED"
        end
      elsif ("SO-QRP" == cat) or ("SOQRP" == cat)
        @power = "QRP"
        @optype = "SINGLE-OP"
        if not @assisted
          @assisted = "NON-ASSISTED"
        end
      elsif ("SO-HP" == cat) or ("SOHP" == cat)
        @power = "HIGH"
        @optype = "SINGLE-OP"
        if not @assisted
          @assisted = "NON-ASSISTED"
        end
      elsif ("CW" == cat) or ("SSB" == cat) or ("MIXED" == cat) or ("PHONE" == cat) or ("PH" == cat) or ("CLUB" == cat) or ("CALIFORNIA" == cat) or ("POWER" == cat) or ("AND" == cat) or ("OP" == cat) or ("SVENT" == cat) or ("MIXT" == cat) or ("MULTI" == cat) or ("ALPINE" == cat) or ("OPERATOR" == cat)
      else
        $stderr.puts "!!Unknown category (#{filename}): " + cat + "\n"
      end
    }
  end

  def parse(infile,filename)
    in_header = true
    infile.each_line { |line|
      line = line.force_encoding("iso-8859-1")
      if (not parseQSO(line))
        if in_header
          @cabrillo_header  = @cabrillo_header + line.gsub("\r\n", "\n")
        end
        if (line =~ /^\s*QSO:/)
          @unparseableQs = @unparseableQs + 1
          if $bad_file
            $bad_file.puts(line)
          end
        elsif (line =~ /^START-OF-LOG: +(\S+)/)
          @version = $1
        elsif (line =~ /^CALLSIGN: +([\/A-Z0-9]+)/)
          @callsign = $1.upcase.strip
          elsif (line =~ /^CLUB:\s+(.*)$/)
          club = $1.strip.upcase
          if (club.length > 0) and (club != "NONE")
            @club = $1.strip.gsub(/\s\s+/, " ")
          end
        elsif (line =~ /^OPERATORS:\s+(.*)$/)
          ops = $1.strip
          if ops and (ops.length > 0)
            @operators = ops.split(/\s*,\s*|\s+/)
          end
        elsif (line =~ /^CATEGORY-ASSISTED: +([A-Z]+(-[A-Z]+))/)
          @assisted = $1.upcase
        elsif (line =~ /^CATEGORY-OPERATOR: +(SINGLE-OP|MULTI-OP|CHECKLOG)/)
          @optype = $1.upcase
        elsif (line =~ /^CATEGORY-OPERATOR: +(SO-LP)/)
          @optype = "SINGLE-OP"
          @power = "LOW"
        elsif (line =~ /^CATEGORY-OPERATOR: +(MULTI-SINGLE)/)
          @optype = "MULTI-OP"
          @numtransmitter = "ONE"
        elsif (line =~ /^CATEGORY-OPERATOR: +(SINGLE)/)
          @optype = "SINGLE-OP"
        elsif (line =~ /^CATEGORY-OPERATOR: +(MULTI-MULTI)/)
          @optype = "MULTI-OP"
          @numtransmitter = "MULTI"
        elsif (line =~ /^CATEGORY-POWER: +(HIGH|LOW|QRP)/)
          @power = $1.upcase
        elsif (line =~ /^CATEGORY-STATION: +(FIXED|MOBILE|PORTABLE|ROVER|EXPEDITION|HQ|SCHOOL)/)
          @stationtype = $1.upcase
        elsif (line =~ /^CATEGORY-TRANSMITTER: +(ONE|TWO|LIMITED|UNLIMITED|SWL)/)
          @numtransmitter = $1.upcase
        elsif (line =~ /^CONTEST: +(CA-QSO-PARTY)/)
          @contestname = $1.upcase
        elsif (line =~ /^EMAIL:\s+(\S+)/)
          @email = $1
        elsif (line =~ /^CATEGORY:(( +([A-Z]+(-[A-Z]+)*|\d+M))+)/)
          parseCategories($1, filename)
        elsif (line =~ /^X-CQP-EMAIL:\s+(\S+)/)
          if @email and @email != $1
            $stderr.puts("Cabrillo email '" + @email + "' doesn't match X-CQP-EMAIL '" + $1 + "'\n")
          end
          @email = $1
        elsif (line =~ /X-CQP-COMMENTS:\s+(.*)/)
          comment = $1
          if (comment =~ /\bYOUTH\b/i)
            @youth = true
          end
          if (comment =~ /\bYL\b/i)
            @female = true
          end
          if (comment =~ /\bNEW\s+CONTEST[EO]R\b/i)
            @newcontester = true
          end
        elsif (line =~ /^CLAIMED-SCORE:\s+(.*)/)
          @claimed = $1.strip
        elsif (line =~ /^SOAPBOX:\s+(.*)/)
          comment = $1
          @soapbox = @soapbox + comment.gsub("\r\n","\n")
          if (comment =~ /\bYOUTH\b/i)
            @youth = true
          end
          if (comment =~ /\bYL\b/i)
            @female = true
          end
          if (comment =~ /\bNEW\s+CONTEST[EO]R\b/i)
            @newcontester = true
          end
        elsif (line =~ /^OVERLAY:\s+(.*)/)
          comment = $1
          if (comment =~ /\bYL\b/i)
            @female = true
          end
        elsif (line =~ /^X-CQP-SPECIAL-05:\s+(.*)/)
          comment = $1
          if (comment =~ /\bYL\b/i)
            @female = true
          end
        elsif (line =~ /^X-CQP-SPECIAL-06:\s+(.*)/)
          comment = $1
          if (comment =~ /\bNEW\s+CONTEST[EO]R\b/i)
            @newcontester = true
          end
        elsif (line =~ /X-CQP-CONFIRM1:\s+(\S+)/)
          if not @email
            @email = $1
          end
          if @email != $1
            $stderr.puts("Confirmaion '" + $1 + "' doesn't match email '" +
                         @email.to_s + "'\n")
          end
        end
      else
        in_header = nil
      end
    }
  end

  def callFromFilename(filename)
    # removing leading directory stuff and .cbr suffix is present
    filename = File.basename(filename, ".cbr")
    filename = filename.upcase
    # remove trailing digits
    filename.gsub!(/-\d{10,}\z/, "")
    # convert - and _ to "/"
    filename.gsub!(/[-_]/, "/")
    filename
  end
    
    

  def csv(filename)
    print "\"" + filename + "\","
    if @callsign
      print "\"" + @callsign + "\","
    else
      print "\"\","
    end
    print @strictQs.to_s + "," + @looseQs.to_s + "," + @wrongTime.to_s + ","
    print @badDateTime.to_s + "," + @badQTH.to_s + ","
    print @qthExchConflict.to_s + "," + @unparseableQs.to_s + ","
    print @nonSequential.to_s + ","
    if (callFromFilename(filename) == @callsign) and
        (@callsign_sent.size == 1) and
        @callsign_sent.include?(@callsign)
      print "TRUE\n"
    else
      print "FALSE\n"
    end
  end

  def report(filename)
    print "File: " + filename + "\n"
    print "  Strict QSOs: " + @strictQs.to_s + "\n"
    print "   Loose QSOs: " + @looseQs.to_s + "\n"
    print "   Wrong time: " + @wrongTime.to_s + "\n"
    print " Illegal time: " + @badDateTime.to_s + "\n"
    print "      Bad QTH: " + @badQTH.to_s + "\n"
    print "     Bad exch: " + @badExch.to_s
    @badExchanges.each { |exch|
      print " " + exch.to_s
    }
    print "\n"
    print "     QTH/Exch: " + @qthExchConflict.to_s + "\n"
    print "Unparseable Q: " + @unparseableQs.to_s + "\n"
    print "Nonsequential: " + @nonSequential.to_s + "\n"
    print "\n"
    if not @version
      print "Missing START-OF-LOG:\n"
    end
    if not @callsign
      print "Missing CALLSIGN:\n"
    end
    if not @assisted
      print "Missing assisted specification\n"
    end
    if not @optype
      print "Missing single/multi specification\n"
    end
    if not @power
      print "Missing power specification\n"
    end
    if not @stationtype
      print "Missing station type specification\n"
    end
    if not @numtransmitter
      print "Missing number of stations specification\n"
    end
    if not @email
      print "Missing email specification\n"
    end
  end

  def checkField(value, column, filename)
    if not value
      $stderr.puts("!!Required column #{column} is lacking a value for file #{filename}.\n")
    end
  end

  def normalizeLocation(db)
    if @location
      if CA_QTH_STRICT.include?(@location) or NON_CA_QTH_STRICT.include?(@location)
        return @location
      end
      rows = db.query("select MULTIPLIER_NAME from MULTIPLIER_ALIAS where ALIAS=\"#{@location}\" limit 1")
      if rows
        rows.each(:as => :array) { |row|
          return row[0].to_s
        }
      end
    end
    nil
  end

  def calcOwner
    if @operators
      @operators.each { |op|
        if op.start_with?("@")
          return op[1..-1]
        end
      }
    end
    nil
  end

  def calcOpType
    if @optype == "CHECKLOG"
      return "CHECK"
    end
    if (@optype == "SINGLE-OP") and (@assisted == "ASSISTED")
      @optype = "MULTI-OP"
    end
    if (@optype == "SINGLE-OP")
      return "SINGLE-OP"
    end
    if (@optype == "MULTI-OP") and (("ONE" == @numtransmitter) or (nil == @numtransmitter))
      return "MULTI-SINGLE"
    end
    if (@optype == "MULTI-OP")
      return "MULTI-MULTI"
    end
    nil
  end

  def numTrans
    if @numtransmitter
      @numtransmitter
    else
      "ONE"
    end
  end

  def calcStationType
    if (@stationtype == "SCHOOL") or (@stationtype == "MOBILE")
      return @stationtype
    end
    if @county or ("EXPEDITION" == @stationtype)
      return "CCE"
    end
    if ("ROVER" == @stationtype)
      return "MOBILE"
    end
    "FIXED"
  end

  def calcPower
    @power ? @power : "LOW"     # default to LOW
  end

  def lookupClub(db)
    if @club and (@club.length > 0)
      rows = db.query("select CLUB.NAME from CLUB, CLUB_ALIAS where (CLUB.NAME = \"#{@club}\") or ((ALIAS=\"#{@club}\") and (CLUB_ID = CLUB.ID)) limit 1")
      if rows
        rows.each(:as => :array) { |row|
          return row[0]
        }
      end
      $stderr.puts("!! No match for club #{@club}.\n")
    end
    nil
  end

  def lookupClubID(db, club)
    if club
      rows = db.query("select ID from CLUB where CLUB.NAME = \"#{club}\" limit 1")
      if rows
        rows.each(:as => :array) { |row|
          return row[0].to_i
        }
      end
    end
    nil
  end

  def nullOrQuote(db, str)
    if str
      return "'" + db.escape(str) + "'"
    end
    return "NULL"
  end

  CALLSIGNREGEX = /^([A-Z]+\d*\/)?[A-Z]+\d+[A-Z]+(\/[A-Z]*\d*)?$/

  def addOperators(db, id, club)
    if @operators
      filter = [ ]
      @operators.each { |op|
        if op =~ CALLSIGNREGEX
          filter.push(op)
        end
      }
      if filter.length > 0
        allocation = 1.0 / filter.length
        clubID = lookupClubID(db, club)
        filter.each { |op|
          # disabled
          db.query("insert into OPERATOR (LOG_ID, CALLSIGN, CLUB_ID, CLUB_ALLOCATION) values (#{id}, \"#{op}\", #{clubID ? clubID : "NULL"}, #{allocation})")
        }
      end
    end
  end
    
  def getID(db)
    rows = db.query("select last_insert_id() as ID")
    rows.each { |row|
      return row["ID"].to_i
    }
    throw "Broken"
  end

  def addToDB(db, filename)
    callsign = @callsign
    checkField(callsign, "CALLSIGN", filename)
    owner = calcOwner()
    email = @email
   # checkField(email, "EMAIL_ADDRESS", filename)
    location = normalizeLocation(db)
    checkField(location, "STATION_LOCATION", filename)
    optype = calcOpType()
    checkField(optype, "OPERATOR_CATEGORY", filename)
    power = calcPower()
    checkField(power, "POWER_CATEGORY", filename)
    stationtype = calcStationType()
    club = lookupClub(db)
    db.query("insert into LOG (CALLSIGN, CONTEST_NAME, STATION_OWNER_CALLSIGN, CONTEST_YEAR, EMAIL_ADDRESS, STATION_LOCATION, OPERATOR_CATEGORY, POWER_CATEGORY, STATION_CATEGORY, TRANSMITTER_CATEGORY, CLUB, SUBMISSION_DATE, OVERLAY_YL, OVERLAY_YOUTH, OVERLAY_NEW_CONTESTER, CLAIMED_SCORE, LOG_FILENAME, SOAPBOX, CABRILLO_HEADER, NUMBER_QSO_RECS, QSO_RECS_PRESENT, LAST_UPDATED) values ('#{callsign}', 'CA-QSO-PARTY', #{nullOrQuote(db,owner)}, 2013, #{nullOrQuote(db, email)}, #{nullOrQuote(db, location)}, #{nullOrQuote(db, optype)}, #{nullOrQuote(db, power)}, #{nullOrQuote(db, stationtype)}, #{nullOrQuote(db, numTrans())}, #{nullOrQuote(db, club)}, NOW(), #{@female ? "1" : "0"}, #{@youth ? "1" : "0"}, #{@newcontester ? "1" : "0"}, #{nullOrQuote(db, @claimed)}, #{nullOrQuote(db, filename)}, #{nullOrQuote(db, @soapbox)}, #{nullOrQuote(db, @cabrillo_header)}, #{@strictQs + @looseQs}, 1, NOW())")
    id = getID(db)
    addOperators(db, id, club)
  end
end

if csv_report
  print "\"Filename\",\"Callsign\",\"# Strict\",\"# Loose\",\"Wrong time\",\"Illegal time\",\"Bad QTH\",\"QTH/Exch conflicts\",\"Unparseable Qs\",\"Nonsequential\",\"Filename/Callsign/Sent Match\"\n"
end

ARGV.each { |filename|
  if File::file?(filename) and File::readable?(filename)
    open(filename) { |io|
      log = CQPLog.new(2013)
      log.parse(io, filename)
      log.addToDB(db, filename)
      if (csv_report)
        log.csv(filename)
      else
        log.report(filename)
      end
    }
  else
    $stderr.puts("!!Skipping #{filename}\n")
  end
}

db.close
db = nil
