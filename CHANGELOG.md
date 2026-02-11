# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.5] - 2026-02-11

### Fixed
- Fixed `sendMessage()` crash when API returns empty results array (undefined offset)
- Fixed `sendSimple()` and `getMessage()` silently creating objects with empty fields on missing results
- Fixed `makeRequest()` silently swallowing invalid JSON responses
- Fixed `handleHttpException()` crash on non-RequestException Guzzle errors (e.g. ConnectException)
- Fixed broken `getMessageStatus()` calls in basic and bulk example files (method does not exist, should be `getMessage()`)
- Fixed potential double-encoding of query parameters in `sendSimple()` by using `RequestOptions::QUERY`
- Fixed `strlen()` to `mb_strlen()` in message validation for correct multibyte character counting
- Fixed hardcoded User-Agent version string (was stuck at 1.0.0)
- Removed unreachable dead code block in bulk example

### Changed
- Removed overly strict ASCII-only message validation (GSM-7 and Unicode messages now accepted)
- Renamed `BASE_URL` constant to `DEFAULT_BASE_URL` for clarity (overridable via `$httpOptions['base_uri']`)
- Added strict comparison to all `in_array()` calls in data objects
- Extracted shared `loadEnv()` helper for example files

### Added
- Constructor validation for empty credentials
- Input validation for empty message ID in `getMessage()`
- Automatic message validation before sending in `sendMessages()`
- 16 new unit tests covering: `getBalance()`, `getMessage()`, `sendSimple()`, HTTP error handling (401/400/429/500), network failures, empty results, invalid JSON, and input validation

### Deprecated
- `sendSimple()` method (credentials exposed in query string; use `sendMessage()` instead)

## [1.0.1] - 2025-01-29

### Fixed
- Corrected API endpoint for account balance from `/v1/account/balance` to `/v1/account`
- Fixed message status lookup endpoint to use query parameters `/v1/messages?message_id=X`
- Updated BalanceResponse to handle `credit_balance` field from API response
- Corrected method name references in test examples (`getMessage()` vs `getMessageStatus()`)
- Fixed simple API endpoint response parsing

### Testing
- All 48 tests now passing with real API integration
- Verified SMS sending functionality with live API
- Confirmed message status tracking works correctly
- Validated account balance retrieval

## [1.0.0] - 2025-01-29

### Added
- Initial release of the Mobile Message PHP SDK
- Support for sending single and bulk SMS messages
- Account balance checking functionality
- Message status tracking
- Message validation
- Comprehensive error handling with specific exception types
- Support for both standard and simple API endpoints
- Laravel integration examples and documentation
- CodeIgniter integration examples and documentation
- Full test suite with unit and integration tests
- Complete documentation with usage examples
- PSR-4 autoloading compliance
- PHP 7.4+ compatibility

### Features
- `MobileMessageClient` - Main SDK client class
- `Message` - Data object for SMS messages
- `MessageResponse` - Response object for sent messages
- `MessageStatusResponse` - Response object for message status
- `BalanceResponse` - Response object for account balance
- Custom exceptions for different error types
- Validation for message content and parameters
- Support for custom reference tracking
- HTTP client configuration options

### Documentation
- Comprehensive README with examples
- Laravel integration guide
- CodeIgniter integration guide
- API reference documentation
- Test examples and integration instructions

### Testing
- Unit tests for all major components
- Integration tests for API functionality
- Mock HTTP client testing
- 100% test coverage for core functionality 