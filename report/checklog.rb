#!/usr/bin/env ruby
#
# Ruby program to check CQP logs for syntactic complaince with the
# specification. 
#                              -------info sent-------- -----info recvd---------
#QSO: Freq  Mo Date       Time Callsign      Seq  Exch  Callsign      Seq  Exch
#QSO: ***** ** yyyy-mm-dd nnnn ************* nnnn ***** ************* nnnn *****

require 'date'
require 'set'

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
    @time = DateTime.new(year, month.to_i, day.to_i, hour, min, 0)
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
  CA_COUNTIES_STRICT = %w( ALAM ALPI AMAD BUTT CALA CCOS COLU DELN
    ELDO FRES GLEN HUMB IMPE INYO KERN KING LAKE LANG LASS MADE MARN
    MARP MEND MERC MODO MONO MONT NAPA NEVA ORAN PLAC PLUM RIVE SACR
    SBAR SBEN SBER SCLA SCRU SDIE SFRA SHAS SIER SISK SJOA SLUI SMAT
    SOLA SONO STAN SUTT TEHA TRIN TULA TUOL VENT YOLO YUBA )

  CA_QTH_STRICT = Set.new(CA_COUNTIES_STRICT)

  # CA is invalid -- must log county

  STATES_PROVINCES_STRICT = %w(AB AK AL AR AZ BC CO CT DE FL GA HI IA ID
    IL IN KS KY LA MA MB MD ME MI MN MO MR MS MT NC ND NE NH NJ NM NT NV
    NY OH OK ON OR PA QC RI SC SD SK TN TX UT VA VT WA WI WV WY )

  NON_CA_QTH_STRICT = Set.new(STATES_PROVINCES_STRICT + %w( DX ) )

  # define what's workable
  CA_VALID = Set.new(STATES_PROVINCES_STRICT + %w( DX ) + CA_COUNTIES_STRICT)
  NON_CA_VALID = CA_QTH_STRICT

  QSOSTRICT=/^QSO: +(\d+) +([A-Z]{2}) +(\d{4})-(\d{2})-(\d{2}) +(\d{4}) +([\/A-Z0-9]+) +(\d{4}) +([A-Z]{2}|[A-Z]{4}) +([\/A-Z0-9]+) +(\d{4}) +([A-Z]{2}|[A-Z]{4})\s*$/
  QSOLOOSE=/^QSO: +(\d+) +([A-Z]+) +(\d{4})-(\d{1,2})-(\d{1,2}) +(\d{3,4}) +([\/A-Z0-9]+) +(\d{1,4}) +([A-Z]{1,5}) +([\/A-Z0-9]+) +(\d{1,4}) +([A-Z]{1,5})/i

  def calcTimeBounds(year)
    octStart = DateTime.new(year, 10, 1, 16, 0, 0)
    octStart = octStart + (6 - octStart.wday)
    octStop = octStart + Rational(5, 4) # 30 hours
    return octStart, octStop
  end

  def initialize(year)
    @startTime, @endTime = calcTimeBounds(year)
    @version = nil
    @callsign = nil
    @assisted = nil
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
      if (qso.time < @startTime) or (qso.time > @endTime)
        @wrongTime = @wrongTime + 1
      end
      if (qso.time >= @curTime)
        @curTime = qso.time
      else
        @nonSequential = @nonSequential + 1
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

  def parseCategories(str)
    str.split.each { |cat|
      cat = cat.upcase
      if ("SINGLE-OP" == cat)
        @optype = "SINGLE-OP"
        @assisted = "NON-ASSISTED"
        @numtransmitter = "ONE"
      elsif ("SINGLE-OP-ASSISTED" == cat)
        @optype = "SINGLE-OP"
        @assisted = "ASSISTED"
        @numtransmitter = "ONE"
      elsif ("MULTI-ONE" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "ONE"
      elsif ("MULTI-TWO" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "TWO"
      elsif ("MULTI-MULTI" == cat)
        @optype = "MULTI-OP"
        @numtransmitter = "UNLIMITED"
      elsif ("SCHOOL-CLUB" == cat)
        @optype = "MULTI-OP"
        @stationtype = "SCHOOL"
        @numtransmitter = "MULTI-OP"
      elsif ("SWL" == cat)
        @optype = "SINGLE-OP"
        @stationtype = "FIXED"
        @numtransmitter = "SWL"
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
      elsif ("CW" == cat) or ("SSB" == cat) or ("MIXED" == cat)
      else
        print "Unknown category: " + cat + "\n"
      end
    }
  end

  def parse(infile)
    infile.each_line { |line|
      line = line.force_encoding("iso-8859-1")
      if (not parseQSO(line))
        if (line =~ /^\s*QSO:/)
          @unparseableQs = @unparseableQs + 1
        elsif (line =~ /^START-OF-LOG: +([^ ]+)/)
          @version = $1
        elsif (line =~ /^CALLSIGN: +([\/A-Z0-9]+)/)
          @callsign = $1.upcase
        elsif (line =~ /^CATEGORY-ASSISTED: +([A-Z]+(-[A-Z]+))/)
          @assisted = $1.upcase
        elsif (line =~ /^CATEGORY-OPERATOR: +(SINGLE-OP|MULTI-OP|CHECKLOG)/)
          @optype = $1.upcase
        elsif (line =~ /^CATEGORY-POWER: +(HIGH|LOW|QRP)/)
          @power = $1.upcase
        elsif (line =~ /^CATEGORY-STATION: +(FIXED|MOBILE|PORTABLE|ROVER|EXPEDITION|HQ|SCHOOL)/)
          @stationtype = $1.upcase
        elsif (line =~ /^CATEGORY-TRANSMITTER: +(ONE|TWO|LIMITED|UNLIMITED|SWL)/)
          @numtransmitter = $1.upcase
        elsif (line =~ /^CONTEST: +(CA-QSO-PARTY)/)
          @contestname = $1.upcase
        elsif (line =~ /^EMAIL: +([^ ]+)/)
          @email = $1
        elsif (line =~ /^CATEGORY:(( +([A-Z]+(-[A-Z]+)*|\d+M))+)/)
          parseCategories($1)
        end
      end
    }
  end

  def report(filename)
    print "File: " + filename + "\n"
    print "  Strict QSOs: " + @strictQs.to_s + "\n"
    print "   Loose QSOs: " + @looseQs.to_s + "\n"
    print "   Wrong time: " + @wrongTime.to_s + "\n"
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
end

ARGV.each { |filename|
  open(filename) { |io|
    log = CQPLog.new(2013)
    log.parse(io)
    log.report(filename)
  }
}
