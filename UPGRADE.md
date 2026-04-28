# Security Upgrade Guide

**Plugin:** golem15/apparatus
**Projected version bump:** MAJOR
**Security audit phase:** 7 (Cross-Plugin Analysis & Remediation Planning)
**Generated:** 2026-04-27

> This guide documents breaking changes from the security audit remediation.
> Each section corresponds to a finding in .planning/audit/plugins/golem15/apparatus/FINDINGS.md.

## APP-001: Arbitrary class instantiation via ListToggle AJAX handler

**Severity:** CRITICAL
**Breaking change:** The `index_onSwitchInetisListField` AJAX handler no longer accepts arbitrary model class names; only models registered via the ListToggle column configuration are permitted.

### What changed

The ListToggle form widget's AJAX handler previously accepted any PHP class name via the `model` POST parameter, instantiated it, and toggled any field by ID. The fix restricts the `model` parameter to an allow-list derived from the list widget's column configuration and validates the `field` parameter against the column definition that registered the listtoggle column type. An explicit permission check now requires the backend user to hold the controller's `$requiredPermissions`.

### Migration steps

1. If your plugin passes custom model class names to the `index_onSwitchInetisListField` handler programmatically (e.g., via JavaScript), ensure the model is registered through a ListToggle column in the controller's `columns.yaml` configuration.
2. Review any custom JavaScript that constructs POST requests to this handler -- the `model` parameter must exactly match a class registered in the current controller's list column config.
3. If you have custom controllers that rely on the toggle handler working for models not listed in `columns.yaml`, register those models explicitly in the column configuration.

### Before / after code

```php
// Before (vulnerable) -- any class name accepted
// POST data: model=Backend\Models\User&field=is_superuser&id=1
// Handler blindly instantiates and toggles:
$modelClass = post('model');
$model = new $modelClass;  // arbitrary class instantiation
$model = $model->find(post('id'));
$model->{post('field')} = !$model->{post('field')};
$model->save();

// After (secure) -- model validated against column config allow-list
// Handler checks model is in the controller's list column definitions
// and validates field is a registered listtoggle column.
// Unauthorized model/field combinations are rejected with 403.
```

### Required env / config changes

None.

### Composer constraint changes (if any)

Update `golem15/apparatus` to `^2.0` in downstream composer.json (MAJOR version bump due to breaking change in AJAX handler behavior).

### Verification

- Run `vendor/bin/phpunit --configuration plugins/golem15/apparatus/phpunit.xml --group security` -- the `test_app_001_listtoggle_arbitrary_class_instantiation` test should PASS after applying the fix.
- Manually verify in the backend that ListToggle columns still function correctly on all controllers that use them (Payments, Orders, Jobs, etc.).
- Attempt to POST a non-registered model class to the handler and verify a 403 response is returned.

---

## Phase 12 (Security Remediation)

### Breaking Changes

- **New composer dependency:** `ezyang/htmlpurifier ^4.17` -- auto-installed via composer-merge-plugin when running `composer install` from the project root. Required by the new `|raw_safe` Twig filter (D-11/D-12).
- **API token AJAX handlers (`onCreateApiToken`, `onRevokeApiToken`) now reject unauthenticated requests with HTTP 403** (UTIL-07). Previously the handlers retrieved `BackendAuth::getUser()` but did not throw if it returned null. Any tooling that probed these endpoints without a backend session will now receive 403 instead of a partial response. Backend users editing their own "My Account" page are unaffected.

### New Features

- **`|raw_safe` Twig filter** -- sanitizes HTML through HTMLPurifier with a conservative allowlist (p, br, strong, em, a[href], h1-h6, ul, ol, li, blockquote, img[src|alt], iframe[src]). Iframe `src` is restricted to youtube.com / youtube-nocookie.com / vimeo.com via `URI.SafeIframeRegexp`. URL schemes restricted to http/https/mailto. Use as `{{ html|raw_safe }}` in templates that previously used `|raw`.

- **`RedactCredentialsTap`** -- Monolog tap class at `Golem15\Apparatus\Classes\Logging\RedactCredentialsTap`. Wire into `config/logging.php` channels you want to scrub:

  ```php
  'single' => [
      'driver' => 'single',
      'path' => storage_path('logs/laravel.log'),
      'level' => env('LOG_LEVEL', 'debug'),
      'tap' => [\Golem15\Apparatus\Classes\Logging\RedactCredentialsTap::class],
  ],
  ```

  Strips `api_key`, `Bearer ...`, `sk-...`, `x-api-key:` patterns from log messages and recursively from context/extra arrays. Recommended for production deploys with Golem (AI), GitHub, and PgStripe plugins.

### Operational Notes

- **HTMLPurifier cache directory:** `storage/framework/cache/htmlpurifier/` is auto-created with mode 0755 on first sanitize call. If your deployment uses stricter permissions, ensure this path is writable by the web user (`chmod -R 775 storage/framework/cache`).
- **APP_KEY rotation:** Other plugins (Golem, GitHub) now use `Crypt::encryptString` for at-rest secret storage. Rotating `APP_KEY` will make those values unreadable. Document `APP_KEY` carefully in your secrets management.
