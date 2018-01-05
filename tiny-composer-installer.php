#!/usr/bin/env php
<?php

// Tiny Composer Installer by FastBill
// <https://github.com/fastbill/tiny-composer-installer>

// This is free and unencumbered software released into the public domain.
// For more information, please refer to <http://unlicense.org/>

$my_version = '0.1.0';
$base_url = 'https://getcomposer.org';

// Retrieved from <https://composer.github.io/pubkeys.html>, only valid for tagged releases.
$signature_key = <<<END
-----BEGIN PUBLIC KEY-----
MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEA0Vi/2K6apCVj76nCnCl2
MQUPdK+A9eqkYBacXo2wQBYmyVlXm2/n/ZsX6pCLYPQTHyr5jXbkQzBw8SKqPdlh
vA7NpbMeNCz7wP/AobvUXM8xQuXKbMDTY2uZ4O7sM+PfGbptKPBGLe8Z8d2sUnTO
bXtX6Lrj13wkRto7st/w/Yp33RHe9SlqkiiS4MsH1jBkcIkEHsRaveZzedUaxY0M
mba0uPhGUInpPzEHwrYqBBEtWvP97t2vtfx8I5qv28kh0Y6t+jnjL1Urid2iuQZf
noCMFIOu4vksK5HxJxxrN0GOmGmwVQjOOtxkwikNiotZGPR4KsVj8NnBrLX7oGuM
nQvGciiu+KoC2r3HDBrpDeBVdOWxDzT5R4iI0KoLzFh2pKqwbY+obNPS2bj+2dgJ
rV3V5Jjry42QOCBN3c88wU1PKftOLj2ECpewY6vnE478IipiEu7EAdK8Zwj2LmTr
RKQUSa9k7ggBkYZWAeO/2Ag0ey3g2bg7eqk+sHEq5ynIXd5lhv6tC5PBdHlWipDK
tl2IxiEnejnOmAzGVivE1YGduYBjN+mjxDVy8KGBrjnz1JPgAvgdwJ2dYw4Rsc/e
TzCFWGk/HM6a4f0IzBWbJ5ot0PIi4amk07IotBXDWwqDiQTwyuGCym5EqWQ2BD95
RGv89BPD+2DLnJysngsvVaUCAwEAAQ==
-----END PUBLIC KEY-----
END;

if (PHP_SAPI !== 'cli') {
    throw new \RuntimeException('this script needs to run on the command line');
}

$too_many_args = $_SERVER['argc'] > 2;
if ($too_many_args || ($_SERVER['argc'] === 2 && preg_match('#^(--?|/)(h|help|\?)$#', $_SERVER['argv'][1]))) {
    fwrite($too_many_args ? STDERR : STDOUT, <<<END
usage: tiny-composer-installer.php [filename]
version: {$my_version}

Download Composer, check the download against a hardcoded signature key and save
it to a file. The filename parameter is optional, if you don't supply one, a
temp file will be used.

On success, the file name will be written to stdout, for easy handling in
scripts. On failure, nothing will be written to stdout, the return code will be
non-zero and there will be an error message in stderr.

Please note that the output file will be overwritten without asking, but only if
the download and signature check were successful.

Most useful example:
  sudo php tiny-composer-installer.php /usr/local/bin/composer
END
    . PHP_EOL);
    exit($too_many_args ? 1 : 0);
}

$version = select_version(fetch_versions($base_url), 'stable');
$phar = fetch_phar($base_url, $version);
$signature = fetch_signature($base_url, $version);
check_signature($phar, $signature, $signature_key);
echo write_phar($phar) . PHP_EOL;

function check_signature($subj, $sig, $key)
{
    $rc = openssl_verify($subj, $sig, openssl_pkey_get_public($key), defined('OPENSSL_ALGO_SHA384') ? OPENSSL_ALGO_SHA384 : 'SHA384');
    switch ($rc) {
        case 1:
            return; // success
        case 0:
            throw new \RuntimeException('signature check failed');
        case -1:
            throw new \RuntimeException('error while checking signature');
        default:
            throw new \RuntimeException("unexpected return code $rc while checking signature");
    }
}

function fetch($url)
{
    $result = file_get_contents($url);
    if (!is_string($result)) {
        throw new \RuntimeException("could not fetch from $url");
    }
    return $result;
}

function fetch_phar($base_url, $version)
{
    return fetch("$base_url/{$version['path']}");
}

function fetch_signature($base_url, $version)
{
    $array = json_decode_or_exception(fetch("$base_url/{$version['path']}.sig"), 'signature');
    if (!($sig = base64_decode(get_field_or_exception($array, 'sha384', 'SHA384 signature')))) {
        throw new \RuntimeException('could not decode signature');
    }
    return $sig;
}

function fetch_versions($base_url)
{
    return json_decode_or_exception(fetch("$base_url/versions"), 'versions');
}

function get_field_or_exception($array, $key, $what)
{
    if (!array_key_exists($key, $array)) {
        throw new \RuntimeException("there is no $what");
    }
    return $array[$key];
}

function json_decode_or_exception($json, $what)
{
    if (!is_array($array = json_decode($json, true))) {
        throw new \RuntimeException("could not parse $what");
    }
    return $array;
}

function select_version($versions, $channel)
{
    if (count($version = get_field_or_exception($versions, $channel, "'$channel' channel in the available versions")) !== 1) {
        throw new \RuntimeException('there are ' . count($version) . " info arrays in the $channel channel, expected exactly one");
    }
    get_field_or_exception($version[0], 'path', 'path defined in the channel'); // sanity checking only, return ignored
    return $version[0];
}

function write_phar($phar)
{
    $file = ($_SERVER['argc'] >= 2) ? $_SERVER['argv'][1] : tempnam(sys_get_temp_dir(), 'composer-');
    if (!file_put_contents($file, $phar)) {
        throw new \RuntimeException("could not write PHAR to $file");
    }
    chmod($file, 0755);
    return $file;
}
