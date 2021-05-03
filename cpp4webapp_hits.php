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
    array_key_exists('hits', $_POST) &&
    preg_match('/^[0-9a-fA-F]{48}$/', $_POST['key']) &&
    preg_match('/^[0-9\-]+$/', $_POST['hits']) &&
    file_exists('./PoW/'.$_POST['key']) &&
    file_get_contents('./PoW/'.$_POST['key']) == '')
  {
  define('T_0',   hexdec(substr($_POST['key'],  0, 11)) );
  define('T_MIN', hexdec(substr($_POST['key'], 30,  3)) );
  define('N_CP',  hexdec(substr($_POST['key'], 34,  3)) );
  $hits = explode('-', $_POST['hits']);
  if (count($hits) == N_CP + 2 &&
      ceil(1000*microtime(true)) - T_0 > 1000 * T_MIN)
    {
    $nHit = random_int(1, N_CP);
    file_put_contents('./PoW/'.$_POST['key'], $_POST['hits'].$nHit);
    echo $nHit;
    }
  else echo 'cpp4webapp_hits.php: ERROR 2';
  }
else echo 'cpp4webapp_hits.php: ERROR 1';

exit; // To make shure there are no more lines of output!
?>
