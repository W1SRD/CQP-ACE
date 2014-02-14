#!/bin/env ruby
#
# File:        EditDist.rb
# Copyright:   (c) 2014 Tom Epperly
# Author:      Tom Epperly NS6T <tepperly@gmail.com>
# Description: Use dynamic programming to calculate minimum edits
#
# This work is inspired by this web article:
# http://www.csse.monash.edu.au/~lloyd/tildeAlgDS/Dynamic/Edit/
#

class EditDistance 
  include Comparable
  attr_reader :deletes, :inserts, :correct, :mistakes
  def initialize(deletes=0, inserts = 0, correct=[], mistakes=[])
    @deletes = deletes
    @inserts = inserts
    @correct = correct
    @mistakes = mistakes
  end
  
  def clone
    EditDistance.new(@deletes, @inserts, @correct.clone, @mistakes.clone)
  end

  def distance
    @deletes + @inserts + @mistakes.length
  end

  def <=>(other)
    cmp = (distance <=> other.distance)
    if (cmp == 0)
      cmp = (mistakes.length <=> other.mistakes.length)
      if (cmp == 0)
        cmp = (correct.length <=> other.correct.length)
      end
    end
    return cmp
  end

  def addDel
    @deletes = @deletes +1
  end

  def addIns
    @inserts = @inserts + 1
  end

  def addChar(a, b)
    if a == b
      @correct.push(a)
    else
      @mistakes.push(a+b)
    end
  end
end

def DetailedCompare(standard, recorded)
  m = Array.new(standard.length)
  m[0] = Array.new(recorded.length) { EditDistance.new }
  standard.length.times { |i|
    
  }
end
