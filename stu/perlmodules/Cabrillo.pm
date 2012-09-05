# Cabrillo.pm
#
# Copyright (c) 2006. Trey Garlough <trey@kkn.net>.  All rights reserved.
# This program is free software; you can redistribute it and/or
# modify it under the same terms as Perl itself.
#
# This code is based on the Mail::Internet suite of modules written by
# Graham Barr and maintained by Mark Overmeer, available on CPAN.

# The internals of this package are implemented in terms of a list of
# lines.

# Revision history:
# 2006-08-18  trey     Initial coding.
#
#
#

package Cabrillo;
use strict;

require 5.002;

use vars qw($VERSION @ISA @EXPORT @EXPORT_OK);

require Exporter;

@ISA = qw(Exporter AutoLoader);
# Items to export into callers namespace by default. Note: do not export
# names by default without a very good reason. Use EXPORT_OK instead.
# Do not simply export all your public functions/methods/constants.
@EXPORT = qw( );

$VERSION = '0.99';

##
## Private functions
##

sub _last_header_index
{
    my $me = shift;
    my $idx = 0;
    foreach (@{$me->{'log_body'}}) {
	$idx++ unless /^(QSO:|QTC:|END-OF-LOG:)/;
    }

    return $idx;
}

sub _tag_case
{
    my $tag = uc shift;
    $tag =~ s/\:$//;
    $tag =~ s/$/:/;

    return $tag;
}

my %STRUCTURE;
@STRUCTURE{ map { uc } qw{
    Start-Of-Log 
    Address ARRL-Section Callsign Claimed-Score Club Contest
    Created-By Debug-Level IOTA-Island-Name Location Name Offtime
    Operators Soapbox
    Category Category-Assisted Category-Band Category-Dxpedition
    Category-Mode Category-Operator Category-Overlay Category-Power
    Category-Station Category-Time Category-Transmitter
	Club-Name
    QSO QTC
    End-Of-Log
}} = ();

##
## Constructor
##

sub new
{
    my $class = shift;
    my $arg = shift;
    my $me =  {};

    bless($me, $class);

    if(defined $arg)
    {
	if(ref($arg) eq 'ARRAY') {
	    $me->read_array($arg);
	}
	elsif(defined fileno($arg)) {
	    $me->read_file($arg);
	}
    }
    return $me;
}

sub add
{
    my $me = shift;
    my ($tag, $text, $where) = @_;
    my $line;

    $where = _last_header_index($me)
	unless defined $where;

    return undef 
	unless $where > 0 && $where <= _last_header_index($me);

    if (not $tag) {
	$text =~ /^([-\w]+):\s/i;
	$tag = $1;
	$line = $text;
    }
    else {
	$tag = _tag_case($tag);
	$line = "$tag $text";
    }

    return undef
	unless $tag;

    splice (@{$me->{'log_body'}}, $where, 0, "$line\n");

    return $line;
}

sub as_string
{
    my $me = shift;

    join '', @{$me->{'log_body'}};
}

sub count
{
    my $me = shift;
    my $tag = shift;
    my @matches;

    $tag = _tag_case($tag);

    return scalar grep /^$tag\s/, @{$me->{'log_body'}};
}

sub delete
{
    my $me = shift;
    my $tag = shift;
    my $idx = shift;
    my $i = 0;
    my %hash;
    my @deleted = ();
    my @targets = ();

    $tag = _tag_case($tag);
    
    return () unless $idx >= 0;

    foreach (@{$me->{'log_body'}}) {
	$hash{$i}++ if /^$tag\s/;
	$i++;
	}

    @targets = sort { $a <=> $b } keys %hash;

    if (not defined $idx) {
	foreach (reverse @targets) {
	    push @deleted, splice @{$me->{'log_body'}}, $_, 1;
	}
    } 
    elsif (defined $targets[$idx]) {
	push @deleted, splice @{$me->{'log_body'}}, $targets[$idx], 1;
    }

     return reverse @deleted;
}

sub get
{
    my $me = shift;
    my $tag = shift;
    my $idx = shift;
    my @matches;

    $tag = _tag_case($tag);

    return wantarray ? () : undef
	unless @matches = grep /^$tag\s/, @{$me->{'log_body'}};

    $idx = 0
	unless defined $idx || wantarray;
    foreach (@matches) { s/^$tag\s+//; }

    if (defined $idx) {
	return defined $matches[$idx] ? $matches[$idx] : undef;
    }

    return @matches;
}

sub headers
{
    my $me = shift;
    my $tag = shift;
    my @matches = ();

    $tag = _tag_case($tag);

    @matches = grep !/^(START-OF-LOG|QSO|QTC|END-OF-LOG):\s/, @{$me->{'log_body'}};

    return @matches;
}

sub messages
{
    my $me = shift;

    return defined @{$me->{'messages'}} ? @{$me->{'messages'}} : ();
}

sub print {
    my $me = shift;
    my $fd = shift || \*STDOUT;

    foreach my $ln (@{$me->{'log_body'}}) {
	print $fd $ln or
	    return 0;
    }
    1;
}

sub read
{
    my $me = shift;
    my $ln = shift;

    if ($ln =~ /^([-\w]+)(:\s)/i && exists $STRUCTURE{ uc $1 }) {
	push @{$me->{'log_body'}}, $ln;
    } 
    elsif ($ln =~ /^(X-[-\w]+):\s/i) {       
	push @{$me->{'log_body'}}, $ln;
    }
    else {
	my $msg = sprintf(">>> I do not understand what '%s' means, so I am ignoring this line.", $1);
    push @{$me->{'messages'}}, "$ln$msg\n";
    }

 }

sub read_array
{
    my $me = shift;
    my $log = shift;

    foreach (@$log) {
	$me->read($_);
     }
 }

sub read_file {
    my $me = shift;
    my $fd = shift;

    while (<$fd>) {
	$me->read($_);
     }
 }

sub tags
{
    my $me = shift;
    my %seen;

    foreach (@{$me->{'log_body'}}) {
        m/^([-\w]+):\s/i;
	$seen{uc($1)}++;
    }
    return keys %seen;
}

sub v2_to_v3
{
    my $me = shift;
    my $category;
    my $ln;
    my $msg;

#
#  ARRL Section
#
    my $arrl = $me->get('ARRL-Section');
    chomp $arrl;
    if ($arrl) {
	$me->add('Location', $arrl)
	    unless defined $me->get('Location');
	$me->delete('ARRL-Section');    
    }
#
#  DXpedition Category
#
    my $cat_dxpedition = $me->get('Category-DXpedition');
    chomp $cat_dxpedition;
    if (uc $cat_dxpedition eq 'DXPEDITION') {
	$me->add('Category-Station', 'DXPEDITION')
	    unless defined $me->get('Category-Station');
    }
    if (uc $cat_dxpedition eq 'DXPEDITION') {
	$me->add('Category-Station', 'FIXED')
	    unless defined $me->get('Category-Station');
    }
    $me->delete('Category-DXpedition');
#
#  IOTA Island Name
#
    my $iota = $me->get('IOTA-Island-Name');
    chomp $iota;
    if ($iota) {
	$me->add('Location', $iota)
	    unless defined $me->get('Location');
	$me->delete('IOTA-Island-Name');
    }
#
# Category
#
    $category = $me->get('Category');
    return
	unless defined $category;

    my $ln = "CATEGORY: $category";
    my $msg = ("FYI: I am converting your log from Cabrillo v2 to Cabrillo v3.");
    splice @{$me->{'messages'}}, 0, 0, "$msg\n";
    my ($cat_op, $cat_band, $cat_power, $cat_mode, @kruft) 
	= split ' ', $category;
    my $kruft = join ' ', @kruft;
    if ($kruft) {
	my $msg = sprintf(">>> I do not understand what '%s' means, so I am ignoring this line.", $kruft);
	push @{$me->{'messages'}}, "$ln$msg\n";
	return;
    }
    $me->delete('Category');
#
#  Operator Category
#
    if (uc $cat_op eq 'SINGLE-OP') {
	$me->add('Category-Operator', 'SINGLE-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
	$me->add('Category-Assisted', 'NON-ASSISTED')
	    unless defined $me->get('Category-Assisted');
    }
    if (uc $cat_op eq 'SINGLE-OP-ASSISTED') {
	$me->add('Category-Operator', 'SINGLE-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
	$me->add('Category-Assisted', 'ASSISTED')
	    unless defined $me->get('Category-Assisted');
    }
    if (uc $cat_op eq 'SINGLE-OP-PORTABLE') {
	$me->add('Category-Operator', 'SINGLE-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
	$me->add('Category-Station', 'PORTABLE')
	    unless defined $me->get('Category-Assisted');
    }
    if (uc $cat_op eq 'ROVER') {
	$me->add('Category-Operator', 'SINGLE-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
	$me->add('Category-Station', 'MOBILE')
	    unless defined $me->get('Category-Assisted');
    }
    if ((uc $cat_op eq 'MULTI-ONE') || (uc $cat_op eq 'MULTI-SINGLE')) {
	$me->add('Category-Operator', 'MULTI-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'MULTI-TWO') {
	$me->add('Category-Operator', 'MULTI-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'TWO')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'MULTI-MULTI') {
	$me->add('Category-Operator', 'MULTI-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'UNLIMITED')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'MULTI-LIMITED') {
	$me->add('Category-Operator', 'MULTI-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'LIMITED')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'MULTI-UNLIMITED') {
	$me->add('Category-Operator', 'MULTI-OP')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'UNLIMITED')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'SCHOOL-CLUB') {
	$me->add('Category-Operator', 'SCHOOL')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'ONE')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'SWL') {
	$me->add('Category-Operator', 'SWL')
	    unless defined $me->get('Category-Operator');
	$me->add('Category-Transmitter', 'SWL')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_op eq 'CHECKLOG') {
	$me->add('Category-Operator', 'CHECKLOG')
	    unless defined $me->get('Category-Operator');
    }
    $me->add('Category-Operator', $cat_op)
		unless defined $me->get('Category-Operator');
#
#  Band Category
#
    if (uc $cat_band eq 'LIMITED') {
	$me->add('Category-Transmitter', 'LIMITED')
	    unless defined $me->get('Category-Transmitter');
    }
    if (uc $cat_band eq 'UNLIMITED') {
	$me->add('Category-Transmitter', 'UNLIMITED')
	    unless defined $me->get('Category-Transmitter');
    }
    $me->add('Category-Band', $cat_band)
		unless defined $me->get('Category-Band');
#
#  Power Category
#
    if ($cat_power) {
	$me->add('Category-Power', $cat_power)
	    unless defined $me->get('Category-Power');
    }
#
#  Mode Category
#
    if ($cat_mode) { 
	$me->add('Category-Mode', $cat_mode)
	    unless defined $me->get('Category-Mode');
    }

    return;
}


1;

=head1 NAME

Cabrillo - manipulate ARRL Format (aka Cabrillo) logs

=head1 SYNOPSIS

    use Cabrillo;

    $log = new Cabrillo;
    $log = new Cabrillo \*STDIN;
    $log = new Cabrillo \@log_lines;

=head1 DESCRIPTION

This package provides a class object which can be used for reading,
manipulating and writing Cabrillo compliant logs.

=head1 CONSTRUCTOR

=over 4

=item new ( [ ARG ] )

C<ARG> may be either a file descriptor (reference to a GLOB) or a
reference to an array. If given the new object will be initialized
with log lines either from the array or read from the file descriptor.

=back

=head1 METHODS

=over 4

=item add ( TAG, LINE [, INDEX ] )

Add a new line to the log.  If C<TAG> is I<undef> the the tag will be
extracted from the beginning of the given line.  If C<INDEX> is given
the new line will be inserted into the log header at the given point,
otherwise the new line will be appended to the end of the header.

=item as_string ()

Returns the log as a single string.

=item count ( TAG )

Returns the number of times the given tag appears in the log.

=item delete ( TAG [, INDEX ] )

Delete a tag from the log.  If C<INDEX> id given then the Nth instance
of the tag will be removed.  If C<INDEX> is not given all instances
of tag will be removed.

=item get ( TAG [, INDEX ] )

Get the value of a line. If C<INDEX> is given then the value of the
Nth instance will be returned.  If it is not given the return value
depends on the context in which C<get> was called.  In an array
context a list of all the text from all the instances of C<TAG> will
be returned.  In a scalar context the text for the first instance will
be returned.

=item headers ()

Returns an array of all the Cabrillo header lines that exist in the
log.

=item messages ()

Returns an array of all the errors/warnings generated while reading or
processing the Cabrillo log.

=item print ( [ FILEHANDLE ] )

Print the entire log to file descriptor I<FILEHANDLE>.  I<$fd> should
be a reference to a GLOB. If I<FILEHANDLE> is not given the output
will be sent to STDOUT.

    $log->print( \*STDOUT );  # Print message to STDOUT

=item read ( [ ARG ] )

C<ARG> may be either a file descriptor (reference to a GLOB) or a
reference to an array.  Read a Cabrillo file from the given file
descriptor or array.

=item replace ( TAG, LINE [, INDEX ] )

NOT IMPLEMENTED.  

Replace a line in the header.  If C<TAG> is I<undef> the the tag will
be extracted from the beginning of the given line. If C<INDEX> is
given the new line will replace the Nth instance of that tag,
otherwise the first instance of the tag is replaced. If the tag does
not appear in the header then a new line will be appended to the
header.

=item tags ()

Returns an array of all the tags that exist in the log.  Each tag
will only appear in the list once.  The order of the tags is not
specified.

=item v2_to_v3 ()

For backwards compatibility purposes does an in-place conversion of of
a log from Cabrillo v2 to Cabrillo v3, removing the deprecated tags
CATEGORY:, CATEGORY-DXPEDITION:, IOTA-ISLAND-NAME:, and ARRL-SECTION:
and adding tags CATEGORY-OPERATOR:, CATEGORY-TRANSMITTER:,
CATEGORY-ASSISTED:, CATEGORY-STATION:, CATEGORY-POWER:,
CATEGORY-MODE:, and LOCATION: as necessary.

=back

=head1 AUTHOR

Trey Garlough <trey@kkn.net>.

=head1 COPYRIGHT

Copyright (c) 2006 Trey Garlough.  All rights reserved.  This program
is free software; you can redistribute it and/or modify it under the
same terms as Perl itself.

=cut
