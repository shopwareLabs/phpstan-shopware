<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Rule\fixtures\ForbidEmptyDatabasePasswordRule;

class WrongUsage
{
    public function pdoEmptyPassword(): void
    {
        new \PDO('mysql:host=localhost;dbname=test', 'root', '');
    }

    public function mysqliEmptyPassword(): void
    {
        new \mysqli('localhost', 'root', '');
    }

    public function mysqliConnectEmptyPassword(): void
    {
        mysqli_connect('localhost', 'root', '');
    }
}
