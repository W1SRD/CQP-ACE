# RULE #1 => OK
#
# Required match:
#  ALL of freq, mode, day, time match
#    and sent data from one Q matches received data in another Q
#
# Should form part of an update so set the assigned category for these
# records.

SELECT * FROM qso q 
  INNER JOIN rxi r 
    ON q.freq = r.freq AND 
                q.mode = r.mode AND 
                q.day = r.day AND 
                q.tod = r.tod AND  
                q.calls = r.callr AND 
                q.ns = r.nr AND 
                q.qths = r.qthr;
