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
    - vendor/shopwarelabs/phpstan-shopware/rules.neon
```

or you use PHPStan Extension Installer

## Features

- Custom rules for Shopware 6.5 specific patterns
- Improved type inference for Shopware core classes
- Additional checks for common Shopware development pitfalls

### Available Rules

Here's a comprehensive list of all available rules:

1. **NoSuperglobalsRule**: Prevents usage of superglobals (`$_GET`, `$_POST`, `$_FILES`, `$_REQUEST`). Use proper request objects instead.

2. **DisallowFunctionsRule**: Prevents usage of certain disallowed functions in the codebase.

3. **NoEntityRepositoryInLoopRule**: Prevents EntityRepository method calls within loops to avoid N+1 query problems.

4. **NoSessionInPaymentHandlerAndStoreApiRule**: Prevents usage of session in payment handlers and Store API contexts.

5. **NoSymfonySessionInConstructorRule**: Prevents injection of Symfony Session in constructor to avoid early session starts.

6. **ForbidGlobBraceRule**: Prevents usage of glob brace expansion for better cross-platform compatibility.

7. **InternalClassExtendsRule**: Ensures proper extension of internal classes.

8. **NoUserEntityGetStoreTokenRule**: Prevents direct access to store tokens from User entities.

9. **MethodBecomesAbstractRule**: Checks for methods that should be abstract.

10. **ClassExtendUsesAbstractClassWhenExisting**: Enforces the use of abstract classes when they exist.

11. **NoDALFilterByID**: Prevents direct ID filtering in DAL queries.

12. **ScheduledTaskTooLowIntervalRule**: Ensures scheduled tasks don't have too low intervals.

13. **DisallowDefaultContextCreation**: Prevents creation of default contexts in inappropriate places.

14. **SetForeignKeyRule**: Enforces proper foreign key handling.

15. **InternalFunctionCallRule**: Controls usage of internal functions.

16. **InternalMethodCallRule**: Controls usage of internal methods.

17. **DisallowSessionFunctionsRule**: Prevents usage of session functions (`session_write_close`, `session_start`, `session_destroy`). Use the Symfony Session component instead.

18. **ForbidLocalDiskWriteRule**: Prevents local disk write operations (`file_put_contents`, `fopen` with write mode, `mkdir`, `unlink`, etc.). Use the temporary directory or Flysystem instead.

19. **ForwardSalesChannelContextToSystemConfigServiceRule**: Ensures that when a method has a SalesChannelContext parameter, it is forwarded to SystemConfigService methods as the salesChannelId argument.

20. **ForbidEmptyDatabasePasswordRule**: Detects empty passwords in `PDO`, `mysqli`, and `mysqli_connect()` calls. Use secure credentials for database connections.

## Configuration

You can customize the behavior of these rules by adding configuration to your `phpstan.neon` file. See the [configuration section](#configuration) for more details.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
