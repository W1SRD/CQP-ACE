#!/usr/bin/env ruby
#
# Ruby program to parse CQP 2014 logs with Green information (after scoring)
# and add them to the report generation database.
#
require 'set'
require 'mysql2'
require 'date'
require 'htmlentities'
require 'getoptlong'
require 'levenshtein'

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

def lookupCollection(db, constraints)
  rows = db.query("select NAME from MULTIPLIER where " + constraints + " order by NAME asc")
  collection = [ ]
  rows.each(:as => :array) { |row|
    collection.push(row[0])
  }
  return collection
end

def makeClubMap(db)
  rows = db.query("select ID, NAME from CLUB")
  map = Hash.new
  rows.each(:as => :array) { |row|
    map[row[1]] = row[0].to_i
  }
  rows = db.query("select ALIAS, CLUB_ID from CLUB_ALIAS")
  rows.each(:as => :array) { |row|
    map[row[0]] = row[1].to_i
  }
  map
end


db = Mysql2::Client.new(:host => "localhost", :username=>db_user, :password=>db_passwd,
                        :database=>"CQPACE")
CA_COUNTIES_STRICT = lookupCollection(db, " TYPE = 'COUNTY' ")
NON_CA_STRICT = lookupCollection(db, " TYPE != 'COUNTY' and NAME != 'XXXX' and NAME != 'CA' ")
CLUB_MAP = makeClubMap(db)

def lookupClub(clubName, filename)
  clubName = clubName.upcase
  if CLUB_MAP.has_key?(clubName)
    return CLUB_MAP[clubName]
  end
  close_enough = [ 4, clubName.length / 5 ].min
  if close_enough > 0
    CLUB_MAP.each { |key, value|
      if Levenshtein.distance(key, clubName) <= close_enough
        if $bad_file
          $bad_file.puts("Filename: #{filename}\nUsing close match #{key} for #{clubName}\n")
        end
        return value
      end
    }
  end
  nil
end

class CQPLog
  CALLSIGNREGEX = /^([A-Z]{1,2}\/)?\d?[A-Z]{1,2}\d{1,4}[A-Z]{1,4}(\/(\d+|[A-Z]+\d*))?$/
  CA_QTH_STRICT = Set.new(CA_COUNTIES_STRICT)

  # CA is invalid -- must log county

  NON_CA_QTH_STRICT = Set.new(NON_CA_STRICT)

  # define what's workable
  CA_VALID = Set.new(NON_CA_STRICT + CA_COUNTIES_STRICT)
  NON_CA_VALID = CA_QTH_STRICT

  QSOSTRICT=/^QSO: +(\d+) +([A-Z]{2}) +(\d{4})-(\d{2})-(\d{2}) +(\d{4}) +([\/A-Z0-9]+) +(\d+) +([A-Z]{2}|[A-Z]{4}) +([\/A-Z0-9]+) +(\d+) +([A-Z]+)\s*((\d+)\s*)?(\{[^\}]*\}\s*)?$/

  def initialize
    @logID = nil
    @filename = nil
    @callsign = nil
    @header = nil
    @assisted = nil
    @opcat = nil
    @opclass = nil
    @power = nil
    @stationcat = "FIXED"
    @transcat = "ONE"
    @submitted = nil
    @claimed = nil
    @club = nil
    @clubID = nil
    @email = nil
    @name = nil
    @operators = [  ]
    @stationOwner = nil
    @sentQTH = Set.new
    @qsoCount = 0
    @soapbox = [ ]
    @youth = 0
    @female = 0
    @newContester = 0
  end

  def parse(infile, filename)
    @filename = filename
    inHeader = true
    hdr = ""
    infile.each_line { |line|
      line = line.force_encoding("iso-8859-1")
      case line
      when /^(ADDRESS|ADDRESS-CITY|ADDRESS-COUNTRY|ADDRESS-POSTALCODE|ADDRESS-STATE-PROVINCE|CERTIFICATE|CREATED-BY|OFFTIME|START-OF-LOG|CONTEST|END-OF-LOG|LOCATION|CATEGORY-TIME|X-SUMMARY|X-RADIOS|X-ANTENNAS|X-VERSION|CATEGORY-BAND|CATEGORY-MODE|X-CQP-ID|X-CQP-PHONE):/
        # ignore all these
      when /^CALLSIGN:\s*(.*)$/
        if not @callsign
          @callsign = $1.strip.upcase
          if @callsign.empty?
            @callsign = nil
          else
            if not CALLSIGNREGEX.match(@callsign) and $bad_file
              $bad_file.puts("Filename: #{filename}\nBad CALLSIGN: #{@callsign}\n")
            end
          end
        elsif @callsign != $1.strip.upcase
          if $bad_file
            $bad_file.puts("Filename: #{filename}\nCallsigns don't match '#{$1.strip.upcase}' and '#{@callsign}'\n")
          end
        end
      when /^CATEGORY-ASSISTED:\s*((NON-)?ASSISTED)\s*/
        @assisted = ("ASSISTED" == $1)
      when /^CATEGORY-OPERATOR:\s*(SINGLE-OP|MULTI-OP|CHECKLOG)\s*/
        if ("SINGLE-OP" == $1)
          @opcat = :single
        elsif ("MULTI-OP" == $1)
          @opcat = :multi
        elsif ("CHECKLOG" == $1)
          @opcat = :checklog
          @power = "CHECK"
        end
      when /^X-CQP-TIMESTAMP:\s*(.*)\s*/
        @submitted = DateTime.parse($1).to_time
      when /^CATEGORY-POWER:\s*(HIGH|LOW|QRP)\s/
        if (@opcat == :checklog)
          @power = "CHECK"
        else
          @power = $1.upcase
        end
      when /^CATEGORY-STATION:\s*(FIXED|MOBILE|PORTABLE|ROVER|EXPEDITION|HQ|SCHOOL)\s*/
        if ("EXPEDITION" == $1)
          @stationcat = "CCE"
        elsif ("SCHOOL" == $1)
          @stationcat = "SCHOOL"
        elsif ("FIXED" == $1 or "HQ" == $1 or "PORTABLE" == $1)
          @stationcat = "FIXED"
        elsif ("ROVER" == $1 or "MOBILE" == $1)
          @stationcat = "MOBILE"
        end
      when /^CATEGORY-TRANSMITTER:\s*(ONE|TWO|LIMITED|UNLIMITED|SWL)\s*/
        if ([ "ONE", "TWO", "UNLIMITED"].include?($1))
          @transcat = $1
        else
          if $bad_file
            $bad_file.puts("Filename: #{filename}\nLine: #{line.strip}\n")
          end
        end
      when /^CLAIMED-SCORE:\s*(\d+)\s*$/
        @claimed = $1.to_i
      when /^CLUB:\s*(.*)\s*/i
        @club = $1.strip.gsub(/\s+/, ' ')
        if @club.empty?
          @club = nil
        else
          @clubID = lookupClub(@club, filename)
          if not @clubID and $bad_file
            $bad_file.puts("Filename: #{filename}\nClub: #{@club} not known\n")
          end
        end
      when /^EMAIL:\s*(.*)\s*/
        @email = $1.strip
        if @email.empty?
          @email = nil
        end
      when /^NAME:\s*(.*)\s*/
        @name = $1.strip.gsub(/s{2,}/, " ")
        if @name.empty?
          @name = nil
        end
      when /^OPERATORS:\s*(.*)\s*/
        ops = $1.strip.upcase.split
        ops.each { |call|
          if call.start_with?("@")
            @stationOwner = call[1..-1]
          else
            if CALLSIGNREGEX =~ call
              @operators.push(call)
            else
              if $bad_file
                $bad_file.puts("Filename: #{filename}\nBad operator call: #{call}\n")
              end
            end
          end
        }
      when QSOSTRICT
        inHeader = false
        qth = $9.strip.upcase
        if CA_VALID.include?(qth) or NON_CA_VALID.include?(qth)
          @sentQTH << qth
        else
          if $bad_file
            $bad_file.puts("Filename: #{filename}\nUnknown QTH: #{qth}\n")
          end
        end
        @qsoCount = @qsoCount + 1
      when /^SOAPBOX:\s*(.*)\s*/, /^X-CQP-COMMENTS:\s*(.*)\s*/
        text = $1.strip
        if text.empty?
          if not @soapbox.empty?
            @soapbox << ""
          end
        else
          @soapbox << text
        end
      when /^X-CQP-CALLSIGN:\s*(.*)\s*/
        txt = $1.strip.upcase
        if txt.index('&')
          txt = HTMLEntities.new.decode(txt)
        end
        if CALLSIGNREGEX.match(txt)
          if @callsign and @callsign != txt
            if $bad_file
              $bad_file.puts("Filename: #{filename}\nCallsigns don't match #{@callsign} and #{txt}\n")
            end
          else
            @callsign = txt
          end
        else
          if $bad_file
            $bad_file.puts("Filename: #{filename}\nBad X-CQP-CALLSIGN: #{txt}\n")
          end
        end
      when /^X-CQP-CATEGORIES:\s*(.*)\s*/
        cats = $1.strip.split
        cats.each { |cat|
          if "YOUTH" == cat
            @youth = 1
          elsif "NEW_CONTESTER" == cat
            @newContester = 1
          elsif "FEMALE" == cat
            @female = 1
          elsif "MOBILE" == cat
            @stationcat = "MOBILE"
          elsif "SCHOOL" == cat
            @stationcat = "SCHOOL"
          elsif "COUNTY" == cat
            @stationcat = "CCE"
          end
        }
      when /^X-CQP-CONFIRM1:\s*(.*)\s*/
        if @email != $1.strip and $bad_file
          $bad_file.puts("Filename: #{filename}\nEmail and confirm mismatch: #{$1.strip} #{@email}\n")
        end
      when /^X-CQP-EMAIL:\s*(.*)\s*/
        @email = $1.strip
        if @email.empty?
          @email = nil
        end
      when /^X-CQP-OPCLASS:\s*([-A-Z]+)\s*/
        @opclass = $1
        if $1 == "CHECKLOG"
          @opclass = "CHECK"
          @power = "CHECK"
        end
        if  "SINGLE-ASSISTED" == @opclass
          @opclass = "SINGLE-OP-ASSIST"
        elsif "SINGLE" == @opclass
          @opclass = "SINGLE-OP"
        end
          
      when /^X-CQP-POWER:\s*(.*)\s*/
        if @opclass == "CHECK"
          @power = "CHECK"
        else
          @power = $1.upcase.strip
        end
      when /^X-CQP-SENTQTH:\s*(.*)\s*/
        qths = $1.strip.upcase.split
        qths.each { |qth|
          if CA_VALID.include?(qth) or NON_CA_VALID.include?(qth)
            @sentQTH << qth
          else
            if $bad_file
              $bad_file.puts("Filename: #{filename}\nUnknown X-CQP-SENTQTH: #{qth}\n")
            end
          end
        }
      else
        if $bad_file
          $bad_file.puts("Filename: #{filename}\nLine: '#{line.strip}'\n")
        end
      end
      if inHeader               # accumulate the header
        hdr = hdr + line
      end
    }
    @header = hdr
  end

  def opclass
    if @opclass
      "\"" + @opclass + "\""
    else
      case @opcat
      when :checklog
        "\"CHECK\""
      when :single
        @assisted ? "\"SINGLE-OP-ASSIST\"" : "\"SINGLE-OP\""
      when :multi
        ("ONE" == @transcat) ? "\"MULTI-SINGLE\"" : "\"MULTI-MULTI\""
      else
        "NULL"
      end
    end
  end

  def submitted
    if @submitted
      @submitted.strftime("\"%Y-%m-%d %H:%M:%S\"")
    else
      "NOW()"
    end
  end

  def str(db, str)
    if str
      "'" + db.escape(str.to_s) + "'"
    else
      "NULL"
    end
  end

  
  
  def lookupClubName(db)
    if @clubID
      rows = db.query("select NAME from CLUB where ID = #{@clubID}")
      rows.each(:as => :array) { |row|
        return row[0].to_s
      }
    else
      nil
    end
  end

  def lastID(db)
    db.last_id
  end

  def addToDB(db)
    clubName = lookupClubName(db)
    @sentQTH.each { |qth|
      db.query("insert into LOG (CALLSIGN, CONTEST_NAME, STATION_OWNER_CALLSIGN, CONTEST_YEAR, EMAIL_ADDRESS, STATION_LOCATION, OPERATOR_CATEGORY, POWER_CATEGORY, STATION_CATEGORY, TRANSMITTER_CATEGORY, CLUB, SUBMISSION_DATE, OVERLAY_YL, OVERLAY_YOUTH, OVERLAY_NEW_CONTESTER, CLAIMED_SCORE, LOG_FILENAME, SOAPBOX, CABRILLO_HEADER, NUMBER_QSO_RECS, QSO_RECS_PRESENT,  LAST_UPDATED) values (#{str(db, @callsign)}, 'CA-QSO-PARTY', #{str(db, @stationOwner)}, 2015, #{str(db,@email)}, #{str(db, qth)}, #{opclass}, #{str(db, @power)}, #{str(db, @stationcat)}, #{str(db, @transcat)}, #{str(db, clubName)}, #{submitted}, #{@female}, #{@youth}, #{((@newContester and @qsoCount >= 100) ? 1 : 0) }, #{@claimed ? @claimed.to_s : "NULL"}, #{str(db, @filename)}, #{str(db, @soapbox.empty? ? nil : @soapbox.join("\n"))}, #{str(db, @header)}, #{@qsoCount ? @qsoCount.to_s : "NULL"}, 1, NOW())\n")
      @logID = lastID(db)
      if @logID
        @operators.each { |op|
          db.query("insert into OPERATOR (LOG_ID, CALLSIGN, CLUB_ID, CLUB_ALLOCATION) values (#{@logID}, #{str(db, op)}, #{@clubID ? @clubID.to_s : "NULL"}, #{1.0/@operators.length})")
        }
      else
        if $bad_file
          $bad_file.puts("No log ID #{@callsign}\n")
        end
      end
    }
  end

  attr_reader :sentQTH, :filename, :callsign
end

logs = [ ]

ARGV.each { |filename|
  if File::file?(filename) and File::readable?(filename) and not filename.start_with?("XX0XX")
    open(filename) { |io|
      log = CQPLog.new
      log.parse(io, filename)
      logs << log
    }
  else
    $stderr.puts("!!Skipping #{filename}\n")
  end
}
oplist = [ ]
YEAR = 2015
rows = db.query("select OPERATOR.ID, LOG.ID from OPERATOR, LOG  where LOG.CONTEST_YEAR = #{YEAR} and LOG.ID = OPERATOR.LOG_ID")
rows.each(:as => :array) { |row|
  oplist << row[0]
}
if oplist.length > 0
  db.query("delete from OPERATOR where ID in (#{oplist.join(", ")}) limit #{oplist.length};")
end
db.query("delete from LOG where CONTEST_YEAR = #{YEAR}")
logs.each { |log|
  if log.sentQTH.length != 1
    print log.filename + " has multiple sent QTHs: " + log.sentQTH.to_a.sort.join(" ") + "\n"
  end
  begin
    log.addToDB(db)
  rescue
    print log.callsign +  " has an error\n"
    raise
  end
}
