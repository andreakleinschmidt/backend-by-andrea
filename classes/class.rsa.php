<?php

// *****************************************************************************
// *** RSA ***
// *****************************************************************************

// php stellt alle rsa komponenten
// public key - verschlüsseln/verifizieren - für java-client
// private key - entschlüsseln/signieren - php-server - geheim

class RSA {

  const MD5SUM = "853b682fb9331ab76bb84676c44e4b9d";	// md5 summe von primtab.txt
  const PRIMTAB = "../morgana81/primtab.txt";
  const MINPRIME = 17;		// 0xff bzw 255 größter zu verschlüsselnder wert , sqrt(255)=15.9 < N=p*q
  const MAXPRIME = 8191;	// maximal int in javascript = 2^52 , alles darüber wird von js gerundet (js intern float)
				// 67108864 = 2^26 = 2^13 * 2^13 = 8192^2 [>] rsa = N = p*q = 8179*8191 = 66994189
				// 1 < public key < f(N) , euler = (8179-1)*(8191-1) = 66977820
				// maximal prim = 8191 | anzahl prim 1028 | log2(1028^2) ~ 20 bit

  // primzahltest nach fermat
  // base^primzahl kongruent 1 (modulo primzahl)
  // return 0 - ok - ist (vermutlich) primzahl
  // return 1 - nok - ist sicher keine primzahl
  // *** wird nicht mehr verwendet, änderung auf primtab.txt ***
  protected function primetest($var) {
    $base = array(2,3,5,7,11,13);	// MINPRIME ist 17

    // teilerfremd?
    foreach ($base as $b) {
      if ($var % $b == 0) {
        return 1;
      }
    }

    // fermat primzahltest
    //  foreach ($base as $b) {
    //    if ((pow($b,$var-1) % $var) != (1 % $var)) {
    //      return 1;
    //    }
    //  }

    return 0;
  }

  // ermittelt größten gemeinsamen teiler zweier zahlen, euklid algorithmus
  protected function ggT($var_a, $var_b) {

    $temp = $var_a % $var_b;	// berechne rest
    if ($temp != 0) {		// rest 0?
      return $this->ggT($var_b,$temp);	// nein, rekursiver aufruf
    }
    else {
      return $var_b;		// ja, ggt erreicht, zurück
    }

  }

  // erweiterter euklidischer algorithmus
  // ggT(a,b) = s*a + t*b
  // input zwei zahlen a,b
  // output array(ggT,s,t)
  protected function extended_euclid($a, $b) {

    if ($b == 0) {
      return array("d" => $a, "s" => 1, "t" => 0);	// ggT = 1*a + 0*t = a
    }

    $array1 = $this->extended_euclid($b, $a%$b);	// berechne ggT

    // berechnung koeffizienten s und t
    // s_neu = t_alt

    // t_neu = s_alt - (a/b)*t_alt
    $array2["s"] = $array1["t"];
    $array2["t"] = $array1["s"] - floor($a/$b)*$array1["t"];

    return $array2;
  }

  // x^key % rsa (251^66977819 % 66994189) -> viel zu groß , aufteilen in kleinere rechenoperationen
  // fremdalgorithmus , eigene funktion "calculate()" zu ungenau in javascript
  // http://www.nordwest.net/hgm/krypto/algo.htm
  // und http://php.net/manual/de/function.bcpowmod.php
  // exponent als binärzahl, 2^9 = 2^1001b = 2^1000b * 2^1b
  public function RSA_crypt($basis, $exponent, $modulus) {
    $sum = 0;
    $mask = 1;
    $ergebnis = 1;

    while ($sum < $exponent) {
      if (($exponent&$mask) != 0) {
        $ergebnis = bcmod(bcmul($ergebnis, $basis), $modulus);	// ($ergebnis*$basis) % $modulus;
        $sum = $sum + ($exponent&$mask);
      }
      $basis = bcmod(bcmul($basis, $basis), $modulus);	// ($basis*$basis) % $modulus;
      $mask = $mask << 1;
    }
    return $ergebnis;
  }

  // erzeuge rsa modul , N
  // erzeuge public key , e
  // erzeuge private key , d

  // return 0/1 , rsa debug string (by reference)
  public function RSA_init(&$rsa_debug_str, &$rsa, &$public_key, &$private_key, $br_debug=1) {
    if ($br_debug == 1) {
      $br = "<br>";
    }
    elseif ($br_debug == 2) {
      $br = "<br />";
    }
    else {
      $br = "";
    }

    // *** neue version, änderung auf primtab.txt ***

    if (file_exists(self::PRIMTAB)) {
      // primtab md5 ueberpruefen
      if (md5_file(self::PRIMTAB) == self::MD5SUM) {
        // primtab einlesen
        if ($handle = fopen (self::PRIMTAB, "r")) {
          // 32 byte char , while not eof
          while (($buffer = fgets($handle, 32)) !== false) {
            $prime_tab[] = intval(trim($buffer));
          }
          fclose ($handle);
        }
        else {
          $rsa_debug_str .= $br."rsa handle error";
        }
      }
      else {
        $rsa_debug_str .= $br."rsa md5 error";
      }
    }
    else {
      $rsa_debug_str .= $br."rsa file error";
    }

    if (isset($prime_tab) AND count($prime_tab) > 1) {

      // finde zwei zufällige (große) primzahlen p und q (ungleich)

      // *** alte version ***
      // do {
      //   $p = mt_rand(self::MINPRIME,self::MAXPRIME);
      // } while ($this->primetest($p));
      //
      // do {
      //   $q = mt_rand(self::MINPRIME,self::MAXPRIME);
      // } while (($p == $q) OR $this->primetest($q));

      // *** neue version ***
      $line = mt_rand(1,count($prime_tab));	// prime_tab zufaellige zeile auswaehlen
      $p = $prime_tab[$line-1];

      do {
        $line = mt_rand(1,count($prime_tab));	// prime_tab zufaellige zeile auswaehlen
        $q = $prime_tab[$line-1];
      } while ($p == $q);

      $rsa_debug_str .= $br."rsa p = ".$p."\n";
      $rsa_debug_str .= $br."rsa q = ".$q."\n";

      // rsa-modul N=p*q
      $rsa = $p * $q;
      $rsa_debug_str .= $br."rsa rsa = ".$rsa."\n";

      // eulersche funktion f(N)=(p-1)*(q-1)
      $euler = ($p-1) * ($q-1);
      $rsa_debug_str .= $br."rsa euler = ".$euler."\n";

      // public key e, teilerfremd zu f(N), 1 ist größter gemeinsamer teiler

      do {
        $public_key = mt_rand(2,$euler-1);	// 1 < e < f(N)
      } while ($this->ggT($euler,$public_key) > 1);
      $rsa_debug_str .= $br."rsa puk = ".$public_key."\n";

      // private key d (multiplikativ inverses von e)
      // e*d kongruent 1 (modulo f(N))
      // ggT(e,f(N)) = 1 = e*d + f(N)*k
      // berechnung mit erweiterten euklidischen algorithmus
      // function extended_euclid, input (e, f(N)), output array(ggT,d,k) (d,s,t)
      // falls d negativ , 1 = e*d + f(N)*k = e*d2 + f(N)*k2
      // d2 = d + x*f(N) , k2 = k - x*e , x=1 gewählt

      $ee_array = $this->extended_euclid($public_key, $euler);
      $private_key = $ee_array["s"];
      if ($private_key < 0) {
        $private_key += $euler;
      }
      $rsa_debug_str .= $br."rsa prk = ".$private_key."\n";

      // p,q,f(N),[d,s,t] löschen/überschreiben
      unset($p, $q, $euler, $ee_array);

      return 0;
    }
    else {	// rsa prime_tab error
      return 1;
    }
  }

}

?>
