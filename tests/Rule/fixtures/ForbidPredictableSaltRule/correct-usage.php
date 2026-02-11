<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidPredictableSaltRule;

class CorrectUsage
{
    public function cryptWithDynamicSalt(string $password): string
    {
        return crypt($password, $this->generateSalt());
    }

    public function passwordHashWithoutSalt(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    private function generateSalt(): string
    {
        return '$2y$10$' . bin2hex(random_bytes(11));
    }
}
