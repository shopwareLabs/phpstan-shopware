<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidHardcodedCredentialsRule;

class WrongUsage
{
    public function getConfig(): array
    {
        return [
            'password' => 'secret123',
        ];
    }
}
