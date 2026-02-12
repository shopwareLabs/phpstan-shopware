<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidWeakCryptoKeyRule;

class WrongUsage
{
    public function generateKey(): \OpenSSLAsymmetricKey|false
    {
        return openssl_pkey_new([
            'private_key_bits' => 1024,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
    }
}
