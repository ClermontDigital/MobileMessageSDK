# Contributing to Mobile Message PHP SDK

Thank you for considering contributing to the Mobile Message PHP SDK! This document outlines the process for contributing to this project.

## Code of Conduct

By participating in this project, you agree to maintain a respectful and inclusive environment for all contributors.

## How to Contribute

### Reporting Issues

If you find a bug or have a feature request:

1. Check the [Issues](https://github.com/ClermontDigital/MobileMessageSDK/issues) to see if it's already reported
2. If not, create a new issue with:
   - Clear description of the problem or feature
   - Steps to reproduce (for bugs)
   - Expected vs actual behavior
   - Your environment details (PHP version, OS, etc.)

### Contributing Code

1. **Fork the repository**
   ```bash
   git clone https://github.com/your-username/MobileMessageSDK.git
   cd MobileMessageSDK
   ```

2. **Install dependencies**
   ```bash
   composer install
   ```

3. **Create a feature branch**
   ```bash
   git checkout -b feature/your-feature-name
   ```

4. **Make your changes**
   - Follow the existing code style
   - Add tests for new functionality
   - Update documentation as needed

5. **Run tests**
   ```bash
   composer test
   ```

6. **Commit your changes**
   ```bash
   git commit -m "Add feature: description of your changes"
   ```

7. **Push to your fork**
   ```bash
   git push origin feature/your-feature-name
   ```

8. **Create a Pull Request**
   - Provide a clear description of your changes
   - Reference any related issues
   - Ensure all tests pass

## Development Guidelines

### Code Style

- Follow PSR-12 coding standards
- Use type declarations where possible
- Add comprehensive docblocks for public methods
- Keep methods focused and single-purpose

### Testing

- Write unit tests for new functionality
- Ensure all existing tests pass
- Aim for high test coverage
- Use meaningful test names

### Documentation

- Update the README if you change public API
- Add examples for new features
- Keep documentation clear and concise
- Include code examples where helpful

## Setting Up Development Environment

1. **Prerequisites**
   - PHP 7.4 or higher
   - Composer
   - Git

2. **Clone and install**
   ```bash
   git clone https://github.com/ClermontDigital/MobileMessageSDK.git
   cd MobileMessageSDK
   composer install
   ```

3. **Run tests**
   ```bash
   composer test
   ```

4. **Check code style**
   ```bash
   composer cs-fix
   ```

## Project Structure

```
src/
├── MobileMessageClient.php          # Main client class
├── DataObjects/                     # Data transfer objects
│   ├── Message.php
│   ├── MessageResponse.php
│   ├── MessageStatusResponse.php
│   └── BalanceResponse.php
└── Exceptions/                      # Custom exceptions
    ├── MobileMessageException.php
    ├── AuthenticationException.php
    ├── ValidationException.php
    └── RateLimitException.php

tests/
├── Unit/                           # Unit tests
└── Integration/                    # Integration tests

examples/                           # Usage examples
├── basic_example.php
├── bulk_example.php
├── laravel_example.php
└── codeigniter_example.php
```

## Testing Guidelines

### Unit Tests
- Test individual methods in isolation
- Use mocks for external dependencies
- Test both success and failure scenarios
- Test edge cases and validation

### Integration Tests
- Test against the real API (requires credentials)
- Use environment variables for sensitive data
- Mark tests as skipped if credentials unavailable
- Be mindful of API rate limits and costs

### Running Tests
```bash
# All tests
composer test

# Unit tests only
./vendor/bin/phpunit --testsuite Unit

# Integration tests (requires API credentials)
MOBILE_MESSAGE_USERNAME=user MOBILE_MESSAGE_PASSWORD=pass ./vendor/bin/phpunit --testsuite Integration

# With coverage
composer test-coverage
```

## API Changes

When making changes to the public API:

1. Consider backward compatibility
2. Update version numbers appropriately
3. Document changes in CHANGELOG.md
4. Update examples and documentation

## Release Process

This section is for maintainers:

1. Update version in `composer.json`
2. Update `CHANGELOG.md`
3. Create a git tag
4. Publish to Packagist

## Questions?

If you have questions about contributing:

1. Check existing issues and documentation
2. Create an issue for discussion
3. Reach out to maintainers

Thank you for contributing to make this SDK better! 