# Unit Tests for cleaner_tokens\clean

## Overview

This directory contains comprehensive unit tests for the `cleaner_tokens\clean` class, which is responsible for cleaning security-sensitive data from Moodle databases (tokens, passwords, OAuth2 credentials, etc.).

## Test File

- **File**: `clean_test.php`
- **Test Class**: `cleaner_tokens\tests\clean_test`
- **Total Tests**: 19 test methods

## Test Coverage

### 1. User Password History Tests
- **`test_clean_user_password_history_execute()`** - Verifies records are deleted when dryrun is disabled
- **`test_clean_user_password_history_dryrun()`** - Verifies records are NOT deleted when dryrun is enabled

### 2. User Password Resets Tests
- **`test_clean_user_password_resets_execute()`** - Verifies reset tokens are deleted
- **`test_clean_user_password_resets_dryrun()`** - Verifies reset tokens are preserved in dryrun mode

### 3. External Tokens Tests
- **`test_clean_external_tokens_execute()`** - Verifies web service tokens are deleted
- **`test_clean_external_tokens_dryrun()`** - Verifies web service tokens are preserved in dryrun mode

### 4. Registration Hubs Tests
- **`test_clean_registration_hubs_execute()`** - Verifies hub registration data is deleted
- **`test_clean_registration_hubs_dryrun()`** - Verifies hub data is preserved in dryrun mode

### 5. User Private Key Tests
- **`test_clean_user_private_key_execute()`** - Verifies private keys are deleted
- **`test_clean_user_private_key_dryrun()`** - Verifies private keys are preserved in dryrun mode

### 6. OAuth2 Tests
- **`test_clean_oauth2_execute()`** - Verifies all OAuth2 tables are cleaned (issuers, system accounts, access tokens, refresh tokens)
- **`test_clean_oauth2_dryrun()`** - Verifies OAuth2 data is preserved in dryrun mode

### 7. Extra Tables Tests
- **`test_clean_extra_tables_valid_execute()`** - Verifies configured extra tables are deleted
- **`test_clean_extra_tables_valid_dryrun()`** - Verifies configured extra tables are preserved in dryrun mode
- **`test_clean_extra_tables_invalid_table()`** - Verifies non-existent tables are skipped gracefully
- **`test_clean_extra_tables_empty_config()`** - Verifies behavior when no extra tables are configured
- **`test_clean_extra_tables_multiple()`** - Verifies multiple extra tables can be cleaned simultaneously

### 8. Execute Method Tests
- **`test_execute()`** - Verifies all cleaning methods are called and all tables are cleaned
- **`test_execute_dryrun()`** - Verifies execute respects dryrun mode

## Test Data Helpers

The test class includes helper methods to create test data:

- `create_password_history_records()` - Creates 3 password history records
- `create_password_resets_records()` - Creates 2 password reset records
- `create_external_tokens_records()` - Creates 2 external token records
- `create_registration_hubs_records()` - Creates 2 hub registration records
- `create_user_private_key_records()` - Creates 2 private key records
- `create_oauth2_issuer_records()` - Creates 2 OAuth2 issuer records
- `create_oauth2_system_account_records()` - Creates 1 OAuth2 system account
- `create_oauth2_access_token_records()` - Creates 1 OAuth2 access token
- `create_oauth2_refresh_token_records()` - Creates 1 OAuth2 refresh token

## Test Scenarios Covered

### Dryrun Mode
Each table cleaning method is tested with `dryrun=true` to ensure records are NOT deleted and appropriate messages are generated.

### Execute Mode
Each table cleaning method is tested with `dryrun=false` to ensure records ARE deleted.

### Edge Cases
- Invalid/non-existent tables in extra tables configuration
- Empty extra tables configuration
- Multiple extra tables configuration
- Complex OAuth2 data relationships

## Running the Tests

From the Moodle root directory:

```bash
php vendor/bin/phpunit local/datacleaner/cleaner/tokens/tests/clean_test.php
```

Or for a specific test:

```bash
php vendor/bin/phpunit --filter test_clean_user_password_history_execute local/datacleaner/cleaner/tokens/tests/clean_test.php
```

## Key Features

1. **Database Isolation** - Uses `resetAfterTest()` to clean up after each test
2. **Proper Namespacing** - Follows Moodle's namespace conventions
3. **PHPUnit Attributes** - Uses modern `#[CoversClass()]` annotation
4. **Reflection-based Options** - Uses reflection to set static options for testing
5. **Comprehensive Coverage** - Tests both success and dryrun modes for all methods

## Tables Tested

- `user_password_history` - User password change history
- `user_password_resets` - User password reset tokens
- `external_tokens` - Web service API tokens
- `registration_hubs` - Hub registration data
- `user_private_key` - User private API keys
- `oauth2_issuer` - OAuth2 identity provider configuration
- `oauth2_system_account` - OAuth2 system account credentials
- `oauth2_access_token` - OAuth2 access tokens
- `oauth2_refresh_token` - OAuth2 refresh tokens
