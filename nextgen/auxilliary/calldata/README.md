Callsign Data Extraction
========================

Compare actual callsigns with reported callsigns to generate
statistical probabilities for errors. By generating accurate
probabilities for different types of errors, we can make a more more
accurate probabilistic string comparison routine.

From previously scored CQP contests, we have records what the receiver
reported and what the sender presumably sent. By comparing these, we
get an integrated picture of the types of errors that people
make. This data presumably combines errors from multiple sources
include mistakes by the sender, mistakes by the receiver, and typing
mistakes.

The goal of this exercise is to create a three 2-dimensional matrices
CW, PH, and ALL. For a character i, CW\[i\]\[i\] is the number of
times i was transcribed correctly. For characters i & j, CW\[i\]\[j\]
is the number of times that character i was transcribed as character
j. Thus, the probability that character i will be transcribed as
character j is CW\[i\]\[j\] divided by the sum of CW\[i\]\[k\] for all
k.

The CW matrix is based on statistics for the Morse code mode, and the
PH matrix is based on statistics for the phone/voice mode. ALL is a
combination of both modes. Different modes are treated differently
because each mode is expected to have different likely mistakes.
