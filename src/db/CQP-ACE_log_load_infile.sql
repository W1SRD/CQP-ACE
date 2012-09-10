-- Load Cabrillo logs into CQP-ACE QSO table
-- Author: Steve Dyer, W1SRD
-- Created: 8-Sep-2012

set FOREIGN_KEY_CHECKS = 0;
load data infile '/tmp/ace_load.log'
into table `QSO` 
fields terminated by ' '
lines starting by 'QSO: '
(@frequency, @mode, @day, @utc, @callsign_sent, @serial_sent, @qth_sent, @callsign_received, @serial_received, @qth_received)
set 
FREQUENCY = trim(@frequency),
MODE = trim(@mode),
QSO_DATE = str_to_date(concat(@day,@utc),'%Y-%m-%d%H%i'),
CALLSIGN_SENT = trim(@callsign_sent),
SERIAL_SENT = trim(trim(leading '0' from @serial_sent)),
QTH_SENT = trim(@qth_sent),
CALLSIGN_RECEIVED = trim(@callsign_received),
SERIAL_RECEIVED = trim(trim(leading '0' from @serial_received)),
QTH_RECEIVED = trim(@qth_received),
LOG_ID = (select ID from LOG where CALLSIGN = CALLSIGN_SENT),
BAND = case when(FREQUENCY between '1800' and '2000') then '160'
            when(FREQUENCY between '3500' and '4000') then '80'
            when(FREQUENCY between '7000' and '7300') then '40'
            when(FREQUENCY between '14000' and '14350') then '20'
            when(FREQUENCY between '21000' and '21450') then '15'
            when(FREQUENCY between '28000' and '29700') then '10'
            when(FREQUENCY between '50000' and '54000') then '6'
            when(FREQUENCY between '144000' and '148000') then '2'
            when(FREQUENCY like '144') then '2'
            end,
QSO_STATUS = 'NEW',
LAST_UPDATED = CURRENT_TIMESTAMP,
CREATE_DATE = CURRENT_TIMESTAMP;
set FOREIGN_KEY_CHECKS = 1;
