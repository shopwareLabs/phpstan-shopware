<?php

declare(strict_types=1);

namespace Shopware\PhpStan\Tests\Type;

use PHPStan\Testing\TypeInferenceTestCase;

/** @internal */
class FuturePropertyTypeExtensionTest extends TypeInferenceTestCase
{
    public function testAppliesAnnouncedPropertyTypeWidening(): void
    {
        foreach (static::gatherAssertTypes(__DIR__ . '/fixtures/FuturePropertyTypeExtension/widens-property.php') as $args) {
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
            __DIR__ . '/fixtures/FuturePropertyTypeExtension/extension.neon',
        ];
    }
}
