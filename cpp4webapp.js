/*
Copyright (C) 2021 Vladimir Bostanov

This file is part of CPP4WebApp.

CPP4WebApp is demonstration software: it is NOT intended for use
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

async function cpp(STATS) {

  const HMAC = {name:'HMAC',hash:'SHA-256',length:256};
  const REhex = new RegExp('^[0-9a-fA-F]{48}$');
  const REdec = new RegExp('^[0-9]+$');
  var T_MIN, N_CP, N_BIT, H_MAX, N_LAST, N_COST, N_L2, MSG_PAD,
      KeyHEX, KeyRaw, PoW, AllPoW, AllHits, AllIter, t0, tElapsed;
  //##########################################################
  if (STATS) {
    setCP();
    await solveCP();
    doStats();
  }
  else {
    document.getElementById('cpp4webapp_status').innerHTML = 'processing...';
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'cpp4webapp_key.php');
    xhr.onreadystatechange = async function () {
      if (xhr.readyState === xhr.DONE && xhr.status === 200 &&
          REhex.test(xhr.responseText) ) {
        KeyHEX = xhr.responseText;
        setCP();
        await solveCP();
        setTimeout(sendPoW, 1000 * T_MIN - tElapsed);
      }
    };
    xhr.send();
  }
  //##########################################################

  function setCP() {

    if (STATS) {
      KeyRaw = new Uint8Array(32);
      crypto.getRandomValues(KeyRaw);
      T_MIN   = 0;
      CP_C    = Boolean(Number(document.getElementById('CP_C').value));
      N_CP    = Number(document.getElementById('N_CP').value);
      N_BIT   = Number(document.getElementById('N_BIT').value);
      N_LAST  = Number(document.getElementById('N_LAST').value);
      N_COST  = Number(document.getElementById('N_COST').value);
      H_MAX   = Math.round(Math.sqrt(2**(N_BIT+1) / Math.PI));
      AllHits = new Uint32Array(N_CP);
      }
    else {
      KeyRaw = new Uint8Array(32);
      for (var n = 0; n < 48; n++)
        KeyRaw[n] = Number('0x' + KeyHEX.substring(2*n, 2*(n+1)));
      KeyRaw  = new Uint32Array(KeyRaw.buffer);
      T_MIN   = Number('0x' + KeyHEX.substring(30, 33));
      CP_C    = Boolean(Number('0x' + KeyHEX.substring(33, 34)));
      N_CP    = Number('0x' + KeyHEX.substring(34, 37));
      N_BIT   = Number('0x' + KeyHEX.substring(37, 39));
      H_MAX   = Number('0x' + KeyHEX.substring(39, 43));
      N_LAST  = Number('0x' + KeyHEX.substring(43, 46));
      N_COST  = Number('0x' + KeyHEX.substring(46, 48));
      MSG_PAD = 0xFFFFFFFF;
      AllHits = '0-';
    }
    N_L2    = 2 * N_LAST - 1;
    PoW     = new Uint32Array(N_L2);
    AllPoW  = new Uint32Array(2 * N_CP * N_L2);
  }

  //##########################################################

  async function solveCP() {

    var Chain = new Uint32Array(20 * 2**Math.ceil(N_BIT/2)); // error probability ~ 1e-80
    var Done = new Uint32Array(2**(N_BIT-5));
    var Msg = new Uint32Array(N_COST * N_LAST);
    var Hit = 0;
    t0 = new Date().getTime();

    for (var n_P = 0; n_P < N_CP; n_P++) {
      KeyRaw[6] = n_P;  KeyRaw[7] = Hit;
      var Key = await crypto.subtle.importKey('raw', KeyRaw, HMAC, false, ['sign']);
      Chain.fill(0);
      Done.fill(0);
      Msg.fill(MSG_PAD);
      Hit = await mPuzzle();
    }
    //========================================================

    async function mPuzzle() {

      var isHit = Boolean(0), n = 0, hash_n;
      while (!isHit) {
        Msg.set(Chain.slice(n, n + N_LAST), 0);
        hash_n = new Uint32Array(await crypto.subtle.sign('HMAC', Key, Msg))[0]
        hash_n = hash_n & (2**N_BIT - 1);
        Chain[n + N_LAST] = hash_n;
        n++;
        if (n > N_LAST) {
          if (CP_C) isHit = setBit32(hash_n);
          else isHit = hash_n < H_MAX;
        }
      }
      AllPoW.set(Chain.slice(n - N_LAST, n + N_LAST - 1), 2 * n_P * N_L2);
      if (CP_C) {
        var n0 = Chain.indexOf(hash_n) - N_LAST + 1;
        AllPoW.set(Chain.slice(n0 - N_LAST, n0 + N_LAST - 1), (2 * n_P + 1) * N_L2 );
      }
      if (STATS) AllHits[n_P] = n;
      else  AllHits += hash_n + '-';
      document.getElementById('cpp4webapp_progress').innerHTML = (n_P+1) + ' / ' + N_CP;
      tElapsed = new Date().getTime() - t0;
      document.getElementById('cpp4webapp_time').innerHTML = tElapsed / 1000;
      return hash_n;
      //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

      function setBit32(n) {

        var b = (n+1) % 32;
        var b32 = (n+1-b)/32;
        if (b) b--
        else { b = 31; b32-- }
        var B32 = Done[b32].toString(2).padStart(32,0);
        Done[b32] = Done[b32] | 2**b;
        return Number(B32[31-b])
      }
      //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    }
    //========================================================
  }
  //##########################################################

  function doStats() {

    var SumN = 0;
    var StDevN = 0;
    for (var n_P = 0; n_P < N_CP; n_P++) {
      SumN += AllHits[n_P];
      StDevN += AllHits[n_P]**2;
    }
    var MeanN = SumN/N_CP;
    StDevN = Math.round(Math.sqrt( (StDevN - N_CP * MeanN**2) / (N_CP-1) ));
    MeanN = Math.round(MeanN);
    document.getElementById('cpp4webapp_ntot').innerHTML = SumN;
    document.getElementById('cpp4webapp_result').innerHTML = MeanN + ' &plusmn; ' + StDevN;
    MeanN = N_LAST + Math.round(Math.sqrt( Math.PI * 2**(N_BIT-1) ));
    if (CP_C) StDevN = Math.round(Math.sqrt( (4 - Math.PI) * 2**(N_BIT-1) ));
    else StDevN = MeanN - N_LAST;
    document.getElementById('cpp4webapp_theory').innerHTML = MeanN + ' &plusmn; ' + StDevN;
    document.getElementById('cpp4webapp_khps').innerHTML = Math.round(1000*SumN/tElapsed)/1000;
  }

  //##########################################################

  function sendPoW() {

    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'cpp4webapp_hits.php');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function () {
      if (xhr.readyState === xhr.DONE && xhr.status === 200)
        if (REdec.test(xhr.responseText)) sendFullPoW(xhr.responseText);
        else document.getElementById('cpp4webapp_status').innerHTML = xhr.responseText;
    };
    xhr.send('key=' + KeyHEX + '&hits=' + AllHits);
    //========================================================

    function sendFullPoW(nPoW) {

      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'cpp4webapp_pow.php');
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
      xhr.onreadystatechange = function () {
        if(xhr.readyState === xhr.DONE && xhr.status === 200) {
          document.getElementById('cpp4webapp_status').innerHTML = xhr.responseText;
          if (xhr.responseText == 'DONE')
          document.getElementById('cpp4webapp_key').value = KeyHEX;
          document.getElementById('cpp4webapp_submit').type = 'submit';
        }
      };
      var PoW1 = base64PoW((2 * nPoW - 2) * N_L2);
      if (CP_C) var PoW2 = base64PoW((2 * nPoW - 1) * N_L2);
      else var PoW2 = 0;
      xhr.send('key=' + KeyHEX + '&PoW1=' + PoW1 + '&PoW2=' + PoW2);
      //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

      function base64PoW(n) {

        PoW.set(AllPoW.slice(n, n + N_L2), 0);
        var PoWbytes = new Uint8Array(PoW.buffer);
        var PoWbase64 = '';
        for (var m of PoWbytes) PoWbase64 += String.fromCodePoint(m);
        PoWbase64 = btoa(PoWbase64);
        return encodeURIComponent(PoWbase64);
      }
      //~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
    }
    //========================================================
  }
  //##########################################################
}
//////////////////////////////////////////////////////////////
