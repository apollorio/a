# Apollo Login Credentials Fix

**Timestamp:** 2026-02-06T13:00:00Z

## Issue

- The `/acesso` virtual page previously relied on a mix of template and POST handlers that conflicted with the rest of Apollo Core, so the login form never reached the authentication stack.
- The old login flow did not follow the UserSWP AJAX pattern that the registry requires, which meant we were either bypassing the nonce or trying to set cookies inside an AJAX request without the proper hooks.
- There was no single audit point describing the credential failure and fix, so the RCA trail was missing.

## Fix

1. Centralized template loading inside `apollo-login.php` and stripped the duplicate `template_include`/`handle_login_post` from `src/Core/Plugin.php` so the standalone login page is controlled by one system that belongs to this plugin.
2. Reused the UserSWP login pattern (`assets/js/apollo-auth-scripts.js` + `apollo_login_ajax` handler) to run validation, call `wp_authenticate()`, log attempts, respect the `_apollo_` meta, and only set cookies once authentication succeeds. This keeps the plugin compliant with `apollo-registry.json` naming and meta requirements.
3. Added this audit file to capture the timestamped explanation because no existing timestamp/audit file was present under `wp-content/plugins` for this incident.

## Validation checklist

- [ ] Review `/acesso` with credentials `root / root`.
- [ ] Confirm the AJAX response sets `success: true` and redirects according to `apollo_login_redirect` filter.
- [ ] Observe `_apollo_last_login` update on the user record and a row inserted into the `apollo_login_attempts` table.
- [ ] Ensure the mystery credential failure notice is replaced with the configured success message.

## Timestamp search note

- Searched for files matching `*timestamp*` across the plugin folders and found no dedicated audit log, so this Markdown file now serves as the canonical timestamped fix report.
