# CQP_info.pm
#
# Copyright (c) 2006. NCCC   All rights reserved.
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.
#
# Revision history:
# V1.1 WB6S
#
# V1.60 WB6S - gather unique calls
#
#
#

package CQP_info;
use strict;
use CQP_mults;

require 5.002;

use vars qw($VERSION @ISA @EXPORT @EXPORT_OK);

require Exporter;

@ISA = qw(Exporter AutoLoader);
# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.
@EXPORT = qw( );

$VERSION = '1.60';

my ($cw_qsos, $ph_qsos, $tot_claimed_qsos, $tot_claimed_mults);
my ($tot_claimed_score);
my ($freq, $mode, $qso_date, $qso_time, $sent_call, $sent_nr, $sent_qth);
my ($recv_call, $recv_nr, $recv_qth);
my ($bad_qsos,$warn_qsos,$fatal_qsos);
my $actual_sent_callsign;
my $log_acceptance;
my $qso_num;
my $qth_type;  # in(1)/out(0) of state log flag
my ($sentQTH_DId, $op_QTH);
my %mults_worked;
my %unique_calls;
my $tot_unique_calls;

# debug info...
my ($info_debug, $info_verbose);

##
## Private functions
##

sub _extract_qso_info {

	my $me = shift(@_);
	my $qso_line = shift(@_);
#	print "\nxt:$qso_line\n";
	return( _qso_parse ( $qso_line ) );
}

sub _validate_qso_info {

	my $me = shift(@_);

# check mode
	if ( $mode eq "CW" ) {
		$cw_qsos++;
	}
	else  { # "PH" 
		$ph_qsos++;
	}

# check date/time within contest
#
	my $qso_time_okay = "";  # assume wrong, till proven right
   	$qso_date =~ /([0-9]{4})-([0-9]{2})-([0-9]{2})/;
	if ( ($1 eq "2011") && ($2 eq "10") && ( ($3 eq "01") || ($3 eq "02") ) ) {
	# date is good...check time
		my $qso_day = $3; # save for time check
		$qso_time =~ /([0-9]{4})/;
		if ( (($qso_day eq "01") && ($1 > 1559)) || (($qso_day eq "02") && ($1 <2200)) ) {
			$qso_time_okay =1;
		}
	}
	if (! $qso_time_okay) {	
		$bad_qsos++;
		if ($bad_qsos < 10) {
			push @{$me->{'messages'}}, "Date/Time for QSO:$qso_num isn't in contest period";
		}
		elsif ($bad_qsos == 10) {
			push @{$me->{'messages'}}, "QSO error limit exceed...processing stopped";
		}
	}
# check mult and tally

	if ( ! exists ($CQP_mults::cabr_to_DennyID{uc $recv_qth}) ) {
# unknown mult
		$bad_qsos++;
		if ($bad_qsos < 10) {
			push @{$me->{'messages'}}, "Unknown Mult Name:$recv_qth for QSO:$qso_num";
		}
		elsif ($bad_qsos == 10) {
			push @{$me->{'messages'}}, "QSO error limit exceed...processing stopped";
		}
	} else { # known mult - classify for log type
		my $recvQTH_DId = $CQP_mults::cabr_to_DennyID{uc $recv_qth};
		if ( $qth_type == 1 ) { # CA, or instate log
			if ( $recvQTH_DId =~ /CA??/ ) {  # 'CA' mult
				$mults_worked{"CA"} = 1;
			} elsif (! ($recvQTH_DId =~ /DX/) ) { # all out of state mults, but not DX - isn't a mult
				$mults_worked{$recvQTH_DId} = 1;
			}
		}  else { # out of state log
			if ( $recvQTH_DId =~ /CA??/ ) {  # 'CA' type mult - okay for out of state log
				$mults_worked{$recvQTH_DId} = 1;
			} #****  else {  non-mult for out of state log - ignore
		}
	}
# look at received call - if unique - save it

# really should look at base callsign
	if (exists $unique_calls{$recv_call} ) { $unique_calls{$recv_call}++; }
	else { $unique_calls{$recv_call} = 1; $tot_unique_calls++;}

}

sub _bad_QSO_line {
	my $me = shift(@_);
	my $qso_line = shift(@_);
	chomp $qso_line;
	$bad_qsos++;
	if ($bad_qsos < 10) {
		push @{$me->{'messages'}}, "unrecognized QSO record:$qso_line";
	}
	elsif ($bad_qsos == 10) {
		push @{$me->{'messages'}}, "QSO error limit exceed...processing stopped";
	}
}


sub _chk_log_acceptability {
	my $me = shift(@_);
	if ($tot_claimed_qsos == 0 || $bad_qsos > 200) {
		$log_acceptance = 0;
		push @{$me->{'messages'}}, "Log not accepted due to errors.";
		push @{$me->{'messages'}}, "It will be reviewed by a CQP Volunteer and you will be contacted via email";
	}

}

sub _set_qth_type {

	$sentQTH_DId = $CQP_mults::cabr_to_DennyID{uc $sent_qth};
	$op_QTH = $CQP_mults::Did_to_CQPMultName{$sentQTH_DId};

	if ($info_debug) {
		print "Op QTH:$op_QTH  DennyID:$sentQTH_DId\n";
	}

	if ($sentQTH_DId =~ /CA??/) {
		$qth_type = 1;  # in state QTH
	} else {
		$qth_type = 0;  # out of CA (DX, USA, or CAN)
	}

}

sub _total_mults {

	my ($mult_key, $mults_count);
	$mults_count = 0;
	
	if ($info_verbose) {
		print "\n";
	}

	foreach $mult_key (sort keys %mults_worked) {
		$mults_count += $mults_worked{$mult_key};
		if ($info_verbose) {
			print " $CQP_mults::Did_to_CQPMultName{$mult_key} ";
		}
	}
	if ($info_verbose) {
		if ( exists ($mults_worked{"CA"}) ) {
			print " -CA- ";
		}
		print "\n";
	}

	return $mults_count;
}


sub _qso_parse   {

	my $line_to_parse = shift(@_);
#        1   2      3       4     5           6                   7   8               9                 10
# QSO: 21309 PH 2005-10-01 1602 KO6LU        0001                SCLA N6O             2                CCOS
#              1          2                    3                     4         5        6         7       8        9        10 
# 	/QSO:\s*([0-9]+)\s*(PH|CW)\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\s*([0-9]{4})\s*(\w+)\s*([0-9]+)\s*(\w+)\s*(\w+[\/]*[a-zA-Z0-9]*)\s+([0-9]+)\s*(\w+)/;
# 	/([0-9]+)\s*(PH|CW)\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\s*([0-9]{4})\s*(\w+)\s*([0-9]+)\s*(\w+)\s*(\w+[\/]*[a-zA-Z0-9]*)\s+([0-9]+)\s*(\w+)/;
# 	if ($line_to_parse =~ /([0-9]+)\s*(PH|CW)\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\s*([0-9]{4})\s*(\w+)\s*([0-9]+)\s*(\w+)\s*(\w+[\/]*[a-zA-Z0-9]*)\s+([0-9]+)\s*(\w+)/ ) {
 	if ($line_to_parse =~ /([0-9]+)\s*(PH|CW)\s*([0-9]{4}-[0-9]{2}-[0-9]{2})\s*([0-9]{4})\s*([0-9a-zA-Z\/]+)\s*([0-9]+)\s*(\w+)\s*(\w+[\/]*[a-zA-Z0-9]*)\s+([0-9]+)\s*(\w+)/ ) {
		$freq = $1;
		$mode = $2;
		$qso_date = $3;
		$qso_time = $4;
		$sent_call = $5;
		$sent_nr = $6;
		$sent_qth = $7;
		$recv_call = $8;
		$recv_nr = $9;
		$recv_qth = $10;

		if ($info_debug) {
			print "QSO Line:$line_to_parse\n";
			print "freq:$freq\n";
			print "mode:$mode\n";
			print "qso_date:$qso_date\n";
			print "qso_time:$qso_time\n";
			print "sent_call:$sent_call\n";
			print "sent_nr:$sent_nr\n";
			print "sent_qth:$sent_qth\n";
			print "recv_call:$recv_call\n";
			print "recv_nr:$recv_nr\n";
			print "recv_qth:$recv_qth\n";
		}
		return(1);
	} else {
		return("");
	}

}


## Constructor
##

sub new
{
    my $class = shift;
    my $qsos = shift;
    my $me =  {};
	my $first_qso = 1;

    bless($me, $class);

	$cw_qsos = 0;
	$ph_qsos = 0;
	$bad_qsos = 0;
	$warn_qsos = 0;
	$fatal_qsos = 0;
	$log_acceptance = 1; # assume okay
	$tot_claimed_qsos = 0;
	$tot_claimed_mults = 0;
	$tot_claimed_score = 0;
	$qso_num = 0;
	%mults_worked = ();
	$tot_unique_calls = 0;

#   set to one for more output
	$info_debug = "";
	$info_verbose = "";


	foreach (@$qsos) {
		$qso_num++;
		if ( _extract_qso_info($me, $_) ) {
			if ($first_qso) {
				$first_qso = "";
			# look at sentQTH and decide in/out CA log
			# set flag
				_set_qth_type;
				$actual_sent_callsign = $sent_call;
			}
			_validate_qso_info($me);
		} else { # didn't recognize QSO line
			_bad_QSO_line($me, $_);
		}
		push @{$me->{'qsos'}}, $_;
	}
	$tot_claimed_mults = _total_mults;
	$tot_claimed_qsos = $cw_qsos + $ph_qsos;
	$tot_claimed_score = ($cw_qsos*3 + $ph_qsos*2) * $tot_claimed_mults;
	_chk_log_acceptability($me);

    return $me;
}

use Data::Dumper;

sub normalize_qso_data
{
    my $me = shift;
    my $nqsos = \@{$me->{'qsos'}};
    my $qso_cnt = scalar(@$nqsos);

    #print "QSO Count: ", $qso_cnt, "\n";
    #print @$nqsos[0], "\n";
    #print Dumper(@$nqsos);

    my $i;
    my $rec;

    for ($i=0; $i < $qso_cnt; $i++) {
        if (_qso_parse(@$nqsos[$i])) {

        } else {
            return ("0");
        }

        # Normalize the sent and received QTH using the DennyID tables
        # If the abbreviation doesn't exist, leave it as is

        if (exists ($CQP_mults::cabr_to_DennyID{uc $recv_qth}) ) { 
            my $rxDid = $CQP_mults::cabr_to_DennyID{uc $recv_qth};
            $recv_qth = $CQP_mults::Did_to_CQPMultName{$rxDid} 
        } 

        if (exists ($CQP_mults::cabr_to_DennyID{uc $sent_qth}) ) {
            my $txDid = $CQP_mults::cabr_to_DennyID{uc $sent_qth};
            $sent_qth = $CQP_mults::Did_to_CQPMultName{$txDid}      
        }

        # Update the record in the QSO array
        @$nqsos[$i] = "QSO: $freq $mode $qso_date $qso_time $sent_call $sent_nr $sent_qth $recv_call $recv_nr $recv_qth";
	
        #print "QSO: $freq $mode $qso_date $qso_time $sent_call $sent_nr $sent_qth $recv_call $recv_nr $recv_qth\n";

    }
}


sub summarize_qso_data
{
    my $me = shift;
    my %hash;

    %hash =
    	(
	 'CW QSOs'				=> $cw_qsos,
	 'Phone QSOs'			=> $ph_qsos,
	 'Total Claimed QSOs'	=> $tot_claimed_qsos,
	 'Total Claimed Mults'	=> $tot_claimed_mults,
	 'Total Claimed Score'	=> $tot_claimed_score,
	 'Bad QSOs'				=> $bad_qsos,
	 'Mult of Operation'	=> $CQP_mults::Did_to_FullName{$sentQTH_DId}.":".$sentQTH_DId,
 	);

}

sub qso_errors
{
    my $me = shift;

    return defined @{$me->{'messages'}} ? @{$me->{'messages'}} : ();
}

sub qso_totals
{

	return ($cw_qsos,$ph_qsos,$tot_claimed_qsos,$tot_claimed_mults,$tot_claimed_score,$tot_unique_calls);

}

sub error_counts
{

	return($bad_qsos,$warn_qsos,$fatal_qsos);

}


sub instate 
{

	return $qth_type;
}


sub outofstate 
{

	return ($qth_type == 1 ? 0 : 1);
}

sub opQTH
{

	return ($CQP_mults::Did_to_CQPMultName{$sentQTH_DId});

}

sub firstQSO_sentcallsign
{

	return $actual_sent_callsign;

}

sub log_accepted
{

	return $log_acceptance;

}

sub callsigns_works
{

	my @calls_worked;
	@calls_worked = sort keys %unique_calls;
	return(@calls_worked);

}


1;
