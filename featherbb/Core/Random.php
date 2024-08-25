<?php

/**
 * Copyright (C) 2015-2019 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Core;

class Random
{
    //
    // Compute a hash of $str
    //
    public static function hash(string $str): string
    {
        return sha1($str);
    }

    //
    // Generate a random key of length $len
    //
    public static function key(int $len, bool $readable = false, bool $hash = false): string
    {
        $key = self::secureRandomBytes($len);

        if ($hash) {
            return substr(bin2hex($key), 0, $len);
        } elseif ($readable) {
            $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';

            $result = '';
            for ($i = 0; $i < $len; ++$i) {
                $result .= substr($chars, (ord($key[$i]) % strlen($chars)), 1);
            }

            return $result;
        }

        return $key;
    }

    //
    // Generate a random password of length $len
    // Compatibility wrapper for random_key
    //
    public static function pass(int $len): string
    {
        return self::key($len, true);
    }

    public static function secureRandomBytes(int $len = 10): string
    {
        return random_bytes($len);
    }
}
