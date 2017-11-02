// javascript zu ungenau , keine verwendung von calculate()
function calculate(base, exp, mod) {
  var z = 4;

  var count = Math.ceil(exp/z);
  var rest = exp % z;
  var i = 0;
  var ret = 1;

  for (i=0; i<count; i++) {
    if (i == count-1) {
      ret *= Math.pow(base,rest) % mod;
    }
    else {
      ret *= Math.pow(base,z) % mod;
    }
  }

  return ret % mod;
}

// http://www.nordwest.net/hgm/krypto/algo.htm
function RSA_crypt(basis, exponent, modulus) {
  var sum = 0;
  var mask = 1;
  var ergebnis = 1;

  while (sum < exponent) {
    if ((exponent&mask) != 0) {
      ergebnis = (ergebnis*basis) % modulus;
      sum = sum + (exponent&mask);
    }
    basis = (basis*basis) % modulus;
    mask = mask << 1;
  }
  return ergebnis;
}

// input string , output encrypted string
function encrypt_string(in_string) {
  if (document.cookie) {

    var c = document.cookie;
    var rsa = parseInt(c.slice(c.indexOf("rsa=")+4,c.indexOf(";")));
    var puk = parseInt(c.slice(c.indexOf("puk=")+4,c.lastIndexOf(";")));

    if ((rsa > 0) && (puk > 0)) {

      var i;
      var z;
      var out_string_encrypted = "";

      for (i=0; i<in_string.length; i++) {

        z = in_string.charCodeAt(i);
	z += (Math.round(Math.random()))*128;
        z = RSA_crypt(z,puk,rsa);

        out_string_encrypted += z.toString(16);
        if (i < in_string.length-1) {
          out_string_encrypted += "-";
        }

      }

      return out_string_encrypted;

    }
    else {
      return "error";
    }

  }
  else {
    return "error";
  }
}

function encrypt() {
  var pwd_string_encrypted = encrypt_string(document.pwd_form.password.value);
  document.pwd_form.password.value = pwd_string_encrypted;
}

function encrypt2() {
  var pwd_string_encrypted = encrypt_string(document.pwd_form.password.value);
  document.pwd_form.password.value = pwd_string_encrypted;

  pwd_string_encrypted = encrypt_string(document.pwd_form.password_new1.value);
  document.pwd_form.password_new1.value = pwd_string_encrypted;

  pwd_string_encrypted = encrypt_string(document.pwd_form.password_new2.value);
  document.pwd_form.password_new2.value = pwd_string_encrypted;
}
