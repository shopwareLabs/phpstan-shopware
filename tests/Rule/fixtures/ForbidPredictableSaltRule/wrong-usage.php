<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidPredictableSaltRule;

class WrongUsage
{
    public function cryptWithHardcodedSalt(string $password): string
    {
        return crypt($password, '$2y$10$hardcodedsaltvalue');
    }

    public function passwordHashWithSaltOption(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['salt' => 'hardcoded_salt_value']);
    }
}
