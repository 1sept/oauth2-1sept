# Changelog

## Unreleased

### Breaking changes

- `IdentityProviderException` thrown by `checkResponse()` changed contract:
  - `getCode()` now returns the HTTP status code (previously always `0`);
  - `getResponseBody()` now returns the parsed body (`array`) or the raw body
    (`string`) — previously the PSR-7 `ResponseInterface` object.
- Field getters on `SeptemberFirstUser` no longer throw for unexpected value
  types: one uniform policy coerces or degrades to `null` (previously behavior
  depended on `zend.assertions`, throwing `AssertionError` in dev and
  `TypeError` in production).

### Added

- `checkResponse()` treats any HTTP status ≥ 400 as an error (non-JSON error
  pages included), extracts readable messages from structured error objects
  (`message` / `error_description` / `description` / `code`), and tolerates
  falsy `error` markers (`""`, `0`, `false`, `[]`) in 2xx responses.
- Invalid `authBase` / `apiBase` constructor options now fail fast with
  `InvalidArgumentException` instead of a disabled-in-production `assert()`.
- Full test suites for the provider (`checkResponse` branches, scopes/PKCE
  options, URL building) and the resource owner.

### Fixed

- `getAvatarSizeUrl(0)` / negative sizes no longer emit malformed URLs — the
  size segment (including the `@Nx` ratio suffix) is omitted entirely.
- `getBirthday()` validates against the API date format (`Y-m-d`) and returns
  `null` for relative or invalid dates (`now`, `2000-02-31`, …).
- `isDefaultAvatar()` returns `null` when the API omits the flag, so
  `getAvatarUrl(rejectDefaultAvatar: true)` treats “unknown” as “not default”
  knowingly rather than accidentally.
- `getProfileUrl()` / `getSnils()` / `getLocale()` / `getTimezone()` no longer
  raise `AssertionError` in dev when the optional field is absent.
- `getAddressID()` again coerces numeric-string IDs to `int`.
- Avatar version query value is URL-encoded.
- `composer lint` / `lint-fix` work again (dangling `@cs-fixer-install`
  references removed) and CI now runs the style check.
- CI installs a PHP version matching `composer.json` (8.4/8.5 matrix) and
  caches Composer's download cache instead of a stale `vendor/` tree.
