# PHPStan Rules for Shopware 6

[![Latest Version on Packagist](https://img.shields.io/packagist/v/shopwarelabs/phpstan-shopware.svg?style=flat-square)](https://packagist.org/packages/shopwarelabs/phpstan-shopware)
[![Total Downloads](https://img.shields.io/packagist/dt/shopwarelabs/phpstan-shopware.svg?style=flat-square)](https://packagist.org/packages/shopwarelabs/phpstan-shopware)
[![License](https://img.shields.io/github/license/shopwarelabs/phpstan-shopware.svg?style=flat-square)](https://github.com/shopwarelabs/phpstan-shopware/blob/main/LICENSE.md)

This package provides additional PHPStan rules for Shopware 6 projects. It helps developers catch common mistakes and enforce best practices specific to Shopware development.

## Installation

You can install the package via composer:

```bash
composer require --dev shopwarelabs/phpstan-shopware
```

## Usage

To use these rules, include the package's configuration file in your PHPStan configuration:

```neon
includes:
    - vendor/shopwarelabs/phpstan-shopware/phpstan.neon
```

or you use PHPStan Extension Installer

## Features

- Custom rules for Shopware 6.5 specific patterns
- Improved type inference for Shopware core classes
- Additional checks for common Shopware development pitfalls

## Configuration

You can customize the behavior of these rules by adding configuration to your `phpstan.neon` file. See the [configuration section](#configuration) for more details.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
