<!DOCTYPE html>
<html lang="en">
<!--
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
-->
  <head>
    <meta charset="utf-8">
    <title>CPP4WebApp Form</title>
    <style>
      body,p {font-family: sans-serif; font-size: 4vw;}
    </style>
  </head>
  <body>
    <?php
      ini_set('display_errors',1);error_reporting(E_ALL);

      define('T_MAX', 300); // seconds

      define('FEEDBACK_OK',
        '<p style="color:green">CPP key accepted.<br>
        Form submission successful.</p>');

      define('FEEDBACK_ERR',
        '<p style="color:red">CPP key <i>not</i> accepted.<br>
        Form submission <i>failed</i>.</p>');

      $t = floor(1000*microtime(true));

      if (array_key_exists('key', $_POST) &&
         preg_match('/^[0-9a-fA-F]{48}$/', $_POST['key']) &&
         file_exists('./PoW/'.$_POST['key']) )
        {
        if (file_get_contents('./PoW/'.$_POST['key']) == "DONE" &&
            $t - hexdec(substr($_POST['key'],0,11)) < 1000 * T_MAX)

          echo FEEDBACK_OK;

        else echo FEEDBACK_ERR;

        unlink('./PoW/'.$_POST['key']);
        }
      else echo FEEDBACK_ERR;
    ?>
  </body>
</html>
