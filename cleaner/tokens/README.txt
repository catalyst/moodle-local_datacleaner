This sub-plugin is responsible for cleaning tokens and other sensitive data.

It does so by simply truncating (deleting all records) the following tables.

- user_private_key
- external_tokens
- registration_hubs
- oauth2_issuer
- oauth2_system_account
- oauth2_access_token
- oauth2_refresh_token
- user_password_history
- user_password_resets

The user can also specify any other tables to be truncated.
