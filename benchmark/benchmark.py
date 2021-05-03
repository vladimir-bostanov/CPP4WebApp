#!/usr/bin/python3

# Copyright (C) 2021 Vladimir Bostanov

# This file is part of CPP4WebApp.

# CPP4WebApp is demonstration software: it is NOT intended to be used
# in production.

# CPP4WebApp is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.

# CPP4WebApp is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.

# You should have received a copy of the GNU General Public License
# along with CPP4WebApp.  If not, see <https://www.gnu.org/licenses/>.

import sys, time, hmac
from math import ceil, sqrt

if (len(sys.argv) > 1):
  N_BIT = int(float(sys.argv[1]))
  N_LAST = int(float(sys.argv[2]))
  N_COST = int(float(sys.argv[3]))
  nHashesPerTrial = int(float(sys.argv[4]))
  SampleSize = int(float(sys.argv[5]))
else :
  N_BIT = 24
  N_LAST = 1000
  N_COST = 10
  nHashesPerTrial = 1000
  SampleSize = 100

CP_C = 0

Key = b'\xAA'*32
Hash = b'\x00'*32
Pad = bytearray(b'\xFF' * (4 * (N_COST-1) * N_LAST))

print(' CPP4WebApp Benchmark: Python');
print(' CP_C:', CP_C, '  N_BIT:', N_BIT, '  N_LAST:' ,N_LAST, '  N_COST:', N_COST, '\n')
print(' 32-bit words (N_LAST * N_COST):', '{:>6}'.format(N_LAST*N_COST))
print(' Number of hashes per trial:\t',   '{:>6}'.format(nHashesPerTrial))
print(' Sample size (number of trials):', '{:>6}'.format(SampleSize))

MeanHashRate = 0
SEM = 0
t0 = time.time()

for k in range(SampleSize):

  t = time.time()
  Chain = bytearray(b'\x00' * (4 * 20 * 2**ceil(N_BIT/2)))
  isHit = False
  n = 0

  while not isHit :

    hash_n = hmac.HMAC(Key, Chain[4*n : 4*(n + N_LAST)] + Pad, 'sha256').digest()
    hash_n = hash_n[0:4]
    Chain[4 * (n + N_LAST) : 4 * (n + N_LAST + 1)] = hash_n
    hash_n = int.from_bytes(hash_n, byteorder='little', signed=False) & (2**N_BIT - 1)
    n += 1
    if n > 0 :
      if CP_C : isHit = False
      else : isHit = hash_n > 2**32 or n == nHashesPerTrial

  HashRate = nHashesPerTrial/(time.time() - t)
  MeanHashRate += HashRate;
  SEM += HashRate**2;

MeanHashRate = MeanHashRate / SampleSize;
SEM = sqrt((SEM / SampleSize - MeanHashRate * MeanHashRate) / (SampleSize-1));

print('\n Mean hash rate (kH/s):', round(MeanHashRate)/1e3, ' (SEM:', round(SEM)/1e3, '\b)\n')
print(' Last hash:', hash_n)
print(' Elapsed time (s):', round(1000*(time.time() - t0))/1000)
