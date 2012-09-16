package CQP_RootCall;

# file CQP_RootCall.pm

#hackaroo by WX5S
#released to N5KO, WB6S  9/18/2006

use strict;
use warnings;
use vars qw(@ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $VERSION);
use Exporter;
our $VERSION=1.0;
our @ISA = qw(Exporter);
our @EXPORT = qw(get_root_call isa_dx_prefix);
our $DEBUG =0;

sub get_root_call ($) {#extract root call from a call like: 4m7/w3abc/qrp
  my ($call) = @_;
  $call =~ tr/[a-z] /[A-Z]/d;     #remove spaces and convert to uppercase
  my @tokens = split /\//, $call; #throws away "/"'s and creates token array
  my $num = scalar(@tokens);      #counts the number of tokens between slashes
  if ($num == 1) {return ($tokens[0]); } #no "/"'s found, already base callsign
  if ($tokens[0] =~ m/(\d)\z/)    #if prefix ends in a digit, then
        { return ($tokens[1]); }  #its like 4m7/w7abc, return 2nd part
  return ($tokens[1]) 
         unless     ($tokens[0] =~ m/(\d)/ )  
                 || ($tokens[1] =~ m/(\d)\z/);
                 # if no digit all in first part, then return the second part
                 # call is like DL/W8ABC. An exception is a malformed call like
                 # WABC/7 where the 2nd part would end in a digit, but no
                 # digit was in the first part
  return ($tokens[0]);  #was like w5aa/7 or w5aa/qrp or w5aa/ad6e
}

#######  WARNING - this needs a lot of work for KP4, KG4, KH2, etc...######
#### This is for CQP automated log checking. You might still be DX even
#### if this version says that you aren't, but if this says that you are
#### DX, there is 99.9% chance that you really are DX (fails for degenerate
#### cases like qrp/WX5S) but works fine for typical EU, Asia callsigns.
#### When CQP has an unrecognized QTH and this function says DX, then
#### QTH can be assumed to be DX for no mult credit (hey, you already
#### messed up with invalid name if it really was supposed to be a CA or
#### US State/VE mult name).

sub isa_dx_prefix ($) { #return true if high probability of being DX
  my ($call) = @_;
  $call =~ tr/[a-z] /[A-Z]/d;     #remove spaces and convert to uppercase
  my @tokens = split /\//, $call; #throws away "/"'s and creates token array
  my $num = scalar(@tokens);      #counts the number of tokens between slashes
  #need to handle the special case of old style portable
  #like G3ABC/W4 instead of W4/G3ABC
  if (   ($num >1)
     && ($tokens[1] =~ m/(\d)\z/) ){ #second part ends in a digit
     return 0 if ($tokens[1] =~ m/(^W|^N|^K|^A|^VE|^VA|^CY|^VY|^VO)/ );
     }   
  return 0 if ($call =~ m/(^W|^N|^K|^A|^VE|^VA|^CY|^VY|^VO)/ );
  return 1;
}

#########################
sub test_get_root_call () {
   print "testing get_root_call(\$): \n";
   my @cases = (
        "f6abc",
        "G3abc/w4",
        "w4/g3abc",
        "Kp2/WX5S",
   	"wx5s",
   	"WX5S/6",
   	"WX5S /6",
   	"WX5 s",
   	"wx5s/qrp",
   	"wx5s/dl",
   	"WX5s/dl0",
   	"dl/wx5s",
   	"dl9/wx5s",
   	"DL7/wx5s/mm2",
   	"DL7/  wx5s/mm2",
   	"4m7/w3abc",
   	"4m7/w3abc/qrp",
   	"W6YX/WX5S",	# an important case - W6YX is the answer
   	"W6OAT/yuba",
   	"Yuba/W6oat",
   	"4m7/3B8CF",
   	"3B8CF/4M7",
   	"WA6O/YUBA",
   	"3B8CF/mm", 
   	"DL/3B8CF/mm/QRP",
   	"dl/wabc",
   	"dl/wabc/qrp",
   	"3b8/wabc",
   	"ve/wabc",
   	"ve3/DL0ABC",
   	"qrp/wx5s",
   	"N6O/qrp",
   	"9v1/ja3abc/mm",
   	"ve8/wabc",
   	"wabc/7",	
   	"w7]=bc/ve8",   #sorry, you get W7]=bc with this BS callsign
   	"wabc/q5p",     #you get q5p, thinks its like DL/WX5S
   	                #the following test case currently returns 2nd part
   	"wabc/qrp",	#because if no digits at all you get 2nd part
   	"wabc/mm/qrp",
   	"WA61/YUBA",
   	"WA61X/YUBA",
   	"w3abc/4m7",
   	);
   	
    foreach (@cases) {
        my $call = get_root_call($_);
        my $flag = isa_dx_prefix ($_);
        print "  base call: $call  \tDX=$flag\tfrom: $_\n";        
        }
} #end test_get_root_call

test_get_root_call() if $DEBUG;

sub test(){test_get_root_call();};

1;
