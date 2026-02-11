<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidEmptyDatabasePasswordRule;

class CorrectUsage
{
    public function pdoWithPassword(): void
    {
        new \PDO('mysql:host=localhost;dbname=test', 'root', 'secret123');
    }

    public function pdoWithEnvPassword(): void
    {
        new \PDO('mysql:host=localhost;dbname=test', 'root', getenv('DB_PASSWORD'));
    }
}
