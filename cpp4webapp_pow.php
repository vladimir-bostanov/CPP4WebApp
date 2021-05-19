<?php
/*
Copyright (C) 2021 Vladimir Bostanov

This file is part of CPP4WebApp.

CPP4WebApp is demonstration software: it is NOT intended to be used
in production.

CPP4WebApp is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

CPP4WebApp is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with CPP4WebApp.  If not, see <https://www.gnu.org/licenses/>.
*/

ini_set('display_errors',1);error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');

if (array_key_exists('key', $_POST) &&
    array_key_exists('PoW1', $_POST) &&
    array_key_exists('PoW2', $_POST) &&
    preg_match('/^[0-9a-fA-F]{48}$/', $_POST['key']) &&
    preg_match('/^[0-9a-zA-Z\/+=]+$/', $_POST['PoW1']) &&
    preg_match('/^[0-9a-zA-Z\/+=]+$/', $_POST['PoW2']) &&
    file_exists('./PoW/'.$_POST['key']) )
  {
  $AllHits = explode('-', file_get_contents('./PoW/'.$_POST['key']));
  define('N_CP', hexdec(substr($_POST['key'], 34, 3) ) );

  if (count($AllHits) == N_CP + 2)
    {
    define('T_MIN',  hexdec(substr($_POST['key'],30 , 3)));
    define('CP_C',   hexdec(substr($_POST['key'],33 , 1)));
    define('N_BIT',  hexdec(substr($_POST['key'],37 , 2)));
    define('H_MAX',  hexdec(substr($_POST['key'],39 , 4)));
    define('N_LAST', hexdec(substr($_POST['key'],43 , 3)));
    define('N_COST', hexdec(substr($_POST['key'],46 , 2)));
    define('MSG_PAD', str_repeat("\xFF\xFF\xFF\xFF", N_LAST*(N_COST-1)));

    $n_P = $AllHits[N_CP + 1];
    $Hit = $AllHits[$n_P];

    define('HMAC_KEY',
      substr(hex2bin($_POST['key']), 0, 24).
      strrev(hex2bin(sprintf('%08x', $n_P - 1))).
      strrev(hex2bin(sprintf('%08x', $AllHits[$n_P - 1])))
      );

    $Chain1 = base64_decode($_POST['PoW1']);
    $n1 = random_int(0, N_LAST - 2);

    if (CP_C == 1)
      {
      $Chain2 = base64_decode($_POST['PoW2']);
      $n2 = random_int(0, N_LAST - 2);
      }

    if (cpp4webapp_hmac($Chain1, N_LAST - 1) == $Hit &&
        cpp4webapp_hmac($Chain1, $n1) == cpp4webapp_b2n($Chain1, 4*($n1 + N_LAST), 4))
      {
      if ((CP_C == 0 && $Hit < H_MAX) ||
          (CP_C == 1 && cpp4webapp_hmac($Chain2, N_LAST - 1) == $Hit &&
           cpp4webapp_hmac($Chain2, $n2) == cpp4webapp_b2n($Chain2, 4*($n2 + N_LAST), 4)))
        {
        file_put_contents('./PoW/'.$_POST['key'], 'DONE');
        echo 'DONE';
        }
      else echo 'cpp4webapp_pow.php: ERROR 4';
      }
    else echo 'cpp4webapp_pow.php: ERROR 3';
    }
  else echo 'cpp4webapp_pow.php: ERROR 2';
  }
else echo 'cpp4webapp_pow.php: ERROR 1';

//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

function cpp4webapp_hmac($Chain, $n)
  {
  $msg = substr($Chain, 4 * $n, 4 * N_LAST).MSG_PAD;
  $hmac = hash_hmac('sha256', $msg, HMAC_KEY, true);
  return cpp4webapp_b2n($hmac, 0, 4) % 2**N_BIT;
  }

function cpp4webapp_b2n($Str, $n0, $l)
  {// For demonstration & debugging purposes -- all the way back to the decimal representation:
  return hexdec(bin2hex(strrev(substr($Str, $n0, $l)))); // SLOW! Not fit for production.
  }

exit; // To make sure there are no more lines of output!
?>
