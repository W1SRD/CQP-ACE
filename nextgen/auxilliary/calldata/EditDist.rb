#!/bin/env ruby
# -*- coding: utf-8 -*-
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

def mymin(*args)
  result = nil
  if args.length > 0
    result = args[0]
    1.upto(args.length-1) { |i|
      if args[i] < result
        result = args[i]
      end
    }
  end
  return result
end

def DetailedCompare(standard, recorded)
  m = Array.new(standard.length+1)
  m[0] = Array.new(recorded.length+1) { |i| EditDistance.new(0,i) }
  standard.length.times { |i|
    m[i+1] = Array.new(recorded.length+1)
    m[i+1][0] = EditDistance.new(i+1)
    recorded.length.times { |j|
      choice1 = m[i][j].clone
      choice1.addChar(standard[i], recorded[j])
      choice2 = m[i][j+1].clone
      choice2.addDel
      choice3 = m[i+1][j].clone
      choice3.addIns
      m[i+1][j+1] = mymin(choice1, choice2, choice3)
    }
  }
  m[standard.length][recorded.length]
end
