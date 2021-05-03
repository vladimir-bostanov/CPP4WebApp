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

define('T_MIN', 0);
define('CP_C', 1);
define('N_CP', 10);
define('N_BIT', 22);
define('H_MAX', round(sqrt(2 ** (N_BIT + 1) / M_PI)) );
define('N_LAST', 1000);
define('N_COST', 2);

$key = sprintf('%011x%19s%03x%01x%03x%02x%04x%03x%02x',
  floor(1000*microtime(true)),
  substr(bin2hex(random_bytes(10)), 1),
  T_MIN, CP_C, N_CP, N_BIT, H_MAX, N_LAST, N_COST );
/*
Key Structure:

SSSSSSSSSSSrrrrrrrrrrrrrrrrrrrTTTpNNNbbHHHHlllCC
012345678901234567890123456789012345678901234567
0        10        20        30        40
*/

touch("./PoW/$key");

echo $key;

exit; // To make shure there are no more lines of output!
?>
