-- Full match of BAND, MODE, SERIAL SENT/RCVD, QSO Date/Time +- 60 minutes, 
select q1.ID, q1.CALLSIGN_SENT, q1.CALLSIGN_RECEIVED, q1.QSO_DATE, q1.QTH_SENT, q1.BAND, q1.SERIAL_SENT, q1.SERIAL_RECEIVED, q1.QSO_STATUS
  ,q2.ID, q2.QSO_DATE, q2.CALLSIGN_SENT, q2.CALLSIGN_RECEIVED, q2.QTH_RECEIVED, q2.BAND, q2.SERIAL_RECEIVED, q2.SERIAL_SENT
  from QSO q1, (select * from QSO where QSO.CALLSIGN_RECEIVED = 'W6YX') as q2
  where q1.CALLSIGN_SENT = q2.CALLSIGN_RECEIVED
  and q1.BAND = q2.BAND
  and q1.MODE = q2.MODE
  and q1.QTH_SENT = q2.QTH_RECEIVED
  and q2.SERIAL_SENT = q1.SERIAL_RECEIVED
  and q1.QSO_DATE <= addtime(q2.QSO_DATE,'01:00:00')
  and q1.QSO_DATE >= subtime(q2.QSO_DATE,'01:00:00')
  and q1.CALLSIGN_SENT = 'W6YX'
;

-- Correlated update to set STATUS to OK for full match query 
select q1.ID, q1.CALLSIGN_SENT, q1.CALLSIGN_RECEIVED, q1.QSO_DATE, q1.QTH_SENT, q1.BAND, q1.SERIAL_SENT, q1.SERIAL_RECEIVED, q1.QSO_STATUS
  ,q2.ID, q2.QSO_DATE, q2.CALLSIGN_SENT, q2.CALLSIGN_RECEIVED, q2.QTH_RECEIVED, q2.BAND, q2.SERIAL_RECEIVED, q2.SERIAL_SENT
  from QSO q1, (select * from QSO where QSO.CALLSIGN_RECEIVED = 'W6YX') as q2
  where q1.CALLSIGN_SENT = q2.CALLSIGN_RECEIVED
  and q1.BAND = q2.BAND
  and q1.MODE = q2.MODE
  and q1.QTH_SENT = q2.QTH_RECEIVED
  and q2.SERIAL_SENT = q1.SERIAL_RECEIVED
  and q1.QSO_DATE <= addtime(q2.QSO_DATE,'01:00:00')
  and q1.QSO_DATE >= subtime(q2.QSO_DATE,'01:00:00')
  and q1.CALLSIGN_SENT = 'W6YX'
