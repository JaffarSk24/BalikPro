<?php

namespace BalikPro\Utils;

use Firebase\JWT\JWT as FirebaseJWT;
use Firebase\JWT\Key;

class JWT
{
    private static $secret;
    private static $algorithm = 'HS256';

    public static function init(): void
    {
        if (!self::$secret) {
            $config = require __DIR__ . '/../../config/app.php';
            self::$secret = $config['jwt_secret'];
        }
    }

    public static function encode(array $payload, int $ttl = null): string
    {
        self::init();
        
        $config = require __DIR__ . '/../../config/app.php';
        $ttl = $ttl ?: $config['jwt_ttl'];
        
        $payload['iat'] = time();
        $payload['exp'] = time() + $ttl;
        
        return FirebaseJWT::encode($payload, self::$secret, self::$algorithm);
    }

    public static function decode(string $token): ?array
    {
        try {
            self::init();
            
            $decoded = FirebaseJWT::decode($token, new Key(self::$secret, self::$algorithm));
            return (array) $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public static function validateToken(string $token): bool
    {
        return self::decode($token) !== null;
    }

    public static function getPayload(string $token): ?array
    {
        return self::decode($token);
    }
}
