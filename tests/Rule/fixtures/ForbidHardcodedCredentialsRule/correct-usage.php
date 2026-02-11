<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidHardcodedCredentialsRule;

class CorrectUsage
{
    public function envVariablePattern(): array
    {
        return [
            'password' => '%env(DATABASE_PASSWORD)%',
        ];
    }

    public function envFunctionPattern(): array
    {
        return [
            'secret' => 'env(APP_SECRET)',
        ];
    }

    public function emptyValue(): array
    {
        return [
            'api_key' => '',
        ];
    }

    public function zeroValue(): array
    {
        return [
            'apikey' => '0',
        ];
    }

    public function nullValue(): array
    {
        return [
            'token' => null,
        ];
    }

    public function nonSensitiveKey(): array
    {
        return [
            'username' => 'admin',
        ];
    }
}
