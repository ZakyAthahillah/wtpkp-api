<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Hash;

class PasswordVerifier
{
    public static function check(string $plainPassword, object $auth): bool
    {
        $storedHash = (string) $auth->password_hash;
        $salt = $auth->salt ? (string) $auth->salt : null;

        if (str_starts_with($storedHash, '$2y$') || str_starts_with($storedHash, '$argon')) {
            return Hash::check($plainPassword, $storedHash);
        }

        if ($salt) {
            $saltBytes = hex2bin($salt);

            if ($saltBytes !== false) {
                $legacyHash = strtoupper(hash('sha256', $saltBytes.mb_convert_encoding($plainPassword, 'UTF-16LE', 'UTF-8')));

                return hash_equals(strtoupper($storedHash), $legacyHash);
            }
        }

        return hash_equals($storedHash, $plainPassword);
    }
}
