<?php
namespace App;

class Totp {
    // Generiert ein zufälliges Secret (Base32)
    public static function generateSecret(int $length = 16): string {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    // Berechnet den aktuellen Code
    public static function getCode(string $secret, ?int $timeSlice = null): string {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }
        $secretKey = self::base32Decode($secret);
        $time = pack("N", 0) . pack("N", $timeSlice);
        $hash = hash_hmac('sha1', $time, $secretKey, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $value = (
            ((ord($hash[$offset+0]) & 0x7F) << 24) |
            ((ord($hash[$offset+1]) & 0xFF) << 16) |
            ((ord($hash[$offset+2]) & 0xFF) << 8) |
            (ord($hash[$offset+3]) & 0xFF)
        );
        return str_pad((string)($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    // Prüft den Code (mit Zeitfenster +/- 1)
    public static function verify(string $secret, string $code): bool {
        $timeSlice = floor(time() / 30);
        for ($i = -1; $i <= 1; $i++) {
            if (hash_equals(self::getCode($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    private static function base32Decode(string $base32): string {
        $base32 = strtoupper($base32);
        $l = strlen($base32);
        $n = 0; $j = 0; $binary = "";
        for ($i = 0; $i < $l; $i++) {
            $n = $n << 5;
            $n = $n + strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567', $base32[$i]);
            $j = $j + 5;
            if ($j >= 8) {
                $j = $j - 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $binary;
    }
}