<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidWeakCryptoKeyRule;

class CorrectUsage
{
    public function strongRsaKey(): \OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    }

    public function defaultKey(): \OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_new();
    }

    public function ecKey(): \OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_new([
            'curve_name' => 'prime256v1',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
        ]);
    }
}
