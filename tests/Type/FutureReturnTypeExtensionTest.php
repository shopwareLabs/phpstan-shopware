<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

/** @internal */
class FutureReturnTypeExtensionTest extends TypeInferenceTestCase
{
    public function testAppliesAnnouncedReturnTypeWidening(): void
    {
        foreach (static::gatherAssertTypes(__DIR__ . '/fixtures/FutureReturnTypeExtension/widens-return.php') as $args) {
            $assertType = array_shift($args);
            $file = array_shift($args);

            static::assertIsString($assertType);
            static::assertIsString($file);
            $this->assertFileAsserts($assertType, $file, ...$args);
        }
    }

    public static function getAdditionalConfigFiles(): array
    {
        return [
            __DIR__ . '/fixtures/FutureReturnTypeExtension/extension.neon',
        ];
    }
}
