# Laravel + Vue Starter — DB-Configurable Template

A production-ready Laravel + Inertia + Vue 3 starter that is **fully configurable from the database** — branding, locales, theme, authentication providers, and UI copy — with **Spatie Laravel Permission** for roles and fine-grained permissions.

## Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3+, Laravel 13, Inertia 2, Fortify |
| Frontend | Vue 3, TypeScript, Tailwind CSS v4, Reka UI |
| Auth | Fortify (email/password) + Zoho OAuth (Socialite) |
| Authorization | [spatie/laravel-permission](https://github.com/spatie/laravel-permission) (roles & permissions) |
| i18n | `vue-i18n` v11, JSON files under `lang/` |
| Database | Any Laravel-supported driver (SQLite by default) |

---

## Quick start

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Configure `.env` for your database, then:

```bash
php artisan migrate --seed
npm install
npm run dev
```

**Public uploads:** ensure the storage link exists so logos and fonts are web-accessible:

```bash
php artisan storage:link
```

### What the seed creates

| Item | Details |
|------|---------|
| **Roles & permissions** | See [Roles & permissions](#roles--permissions). |
| **App settings** | Defaults from `AppSettingsSeeder` (branding, locales, theme, auth toggles). |
| **Demo admin user** | `test@example.com` / `password` (change in production). |

---

## Roles & permissions (Spatie)

The app uses **Spatie Laravel Permission** with the `web` guard.

### Default permissions

| Permission | Purpose |
|------------|---------|
| `access_admin` | Enter any `/admin/*` route (settings, translations, design guide). |
| `manage_settings` | Reserved for app settings (currently granted to `admin`; use for route-level splits later). |
| `manage_translations` | Reserved for translation UI (same as above). |

### Default role

| Role | Permissions |
|------|-------------|
| `admin` | All of the above. |

### Middleware

Admin routes use:

```php
['auth', 'verified', 'permission:access_admin']
```

Aliases registered in `bootstrap/app.php`: `role`, `permission`, `role_or_permission`.

### Legacy `is_admin` column

Older databases had a boolean `is_admin` on `users`. Migration `2026_03_25_180000_migrate_is_admin_to_spatie_roles`:

1. Creates the permissions and `admin` role (if missing).
2. Assigns `admin` to every user who had `is_admin = true`.
3. Drops the `is_admin` column.

The `User` model exposes **`is_admin` as a computed attribute** for JSON/Inertia: it reads the legacy column if still present, otherwise `can('access_admin')`.

### Grant or revoke admin in Tinker

```bash
php artisan tinker
```

```php
use App\Models\User;
use Spatie\Permission\Models\Role;

$user = User::where('email', 'you@example.com')->first();
$user->assignRole('admin');

// Or sync a single role:
$user->syncRoles(['admin']);

$user->removeRole('admin');
```

Clear the permission cache if you change roles/permissions in code or directly in the DB:

```php
app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
```

### Factories (tests)

```php
User::factory()->admin()->create(); // grants `admin` role after create
User::factory()->create();          // normal user, no admin access
```

---

## Environment variables

### Core

```env
APP_NAME="My App"
APP_URL=http://localhost
APP_LOCALE=en
```

### Zoho OAuth

Create a server OAuth client at [Zoho API Console](https://api-console.zoho.com/).

```env
ZOHO_CLIENT_ID=your_client_id
ZOHO_CLIENT_SECRET=your_client_secret
ZOHO_REDIRECT_URI="${APP_URL}/auth/zoho/callback"
```

Enable Zoho in **Admin → Authentication** after credentials are set.

---

## Database-configurable settings

Settings are stored in `app_settings` and accessed via `AppSettingsService` / `settings()` helper.

### Groups

| Group | Keys (examples) | Description |
|-------|-------------------|-------------|
| `branding` | `app_name`, `logo_url`, `primary_color`, `primary_foreground_color`, `sidebar_primary_color`, fonts | Visual identity |
| `localization` | `default_locale`, `supported_locales`, `timezone` | Language & region |
| `theme` | `default_appearance`, `force_appearance` | Dark/light defaults |
| `auth` | `zoho_enabled`, `password_login_enabled`, Zoho client fields | Login methods |

### PHP

```php
settings('branding.app_name', 'Default');

app(AppSettingsService::class)->group('branding');

settings()->set('branding.app_name', 'New Name');
```

### Frontend (Inertia)

Shared prop `appSettings` plus composables in `@/composables/useAppSettings` (`useBranding`, `useLocalization`, etc.).

---

## Admin panel

Accessible under **`/admin`** (redirects to `/admin/settings`) for users with the **`access_admin`** permission (default: `admin` role).

**Tabs:** Branding · Localization · Appearance · Authentication.

**Features:**

- Logo upload, HSL color pickers (Sketch-style), Google Fonts or uploaded font.
- Locale list, timezone, default appearance / forced theme.
- Zoho + password login toggles; Zoho client secret stored encrypted.
- After saving settings or uploading logo/font, the SPA **reloads** so branding, CSS variables, and fonts apply immediately.

**Translations UI:** `/admin/translations` — edit `lang/{locale}.json` as flat key/value rows per locale.

**Design guide:** `/admin/design-guide` — live reference for typography and colors.

---

## File storage (logos & fonts)

Uploads use the **`public`** disk (`storage/app/public`), so URLs look like `/storage/branding/...`. The **`local`** default disk points at `storage/app/private`; public assets must not be stored there.

---

## Internationalization (i18n)

- Source strings: `lang/{locale}.json` (nested keys; Vue uses dot paths, e.g. `auth.login`).
- **`I18nLiveSync`** (in `BrandProvider`) merges `liveLocaleMessages` from the disk file for the current locale so edits apply without a full frontend rebuild (full reload still recommended after admin saves for consistency).

### Locale switching

`POST /locale/{locale}` with `SetLocale` middleware; `LocaleSwitcher` component.

---

## Theme & dark mode

- User preference: light / dark / system (unless admin forces a mode).
- `BrandProvider` applies CSS variables from `appSettings` (`--primary`, `--sidebar-primary`, etc.).

---

## Zoho OAuth

1. Create OAuth credentials in Zoho.
2. Redirect URI: `{APP_URL}/auth/zoho/callback`.
3. Set env vars; optionally override from DB in **Admin → Authentication**.

---

## Tests

```bash
php artisan test
```

Admin-related tests use `User::factory()->admin()->create()` so the `admin` role exists after migrations.

---

## Adding a new configurable setting

1. Default in `database/seeders/AppSettingsSeeder.php`.
2. Read/write via `AppSettingsService` / validation in `UpdateAppSettingsRequest`.
3. Expose in `HandleInertiaRequests::buildAppSettings()` if the SPA needs it.
4. Add UI under `resources/js/pages/admin/settings/Index.vue`.

---

## Project documentation map

| Area | Location |
|------|----------|
| Routes | `routes/web.php`, `routes/admin.php`, `routes/settings.php` |
| Admin controllers | `app/Http/Controllers/Admin/` |
| App settings | `app/Services/AppSettingsService.php`, `app/Models/AppSetting.php` |
| Permissions config | `config/permission.php` |
| Spatie migrations | `database/migrations/*_create_permission_tables.php`, `*_migrate_is_admin_to_spatie_roles.php` |
| Seed order | `DatabaseSeeder` → `RolePermissionSeeder`, `AppSettingsSeeder`, demo user |
