# Start here — template onboarding

This document is the **single entry point** for cloning this template and running it locally. For deeper architecture notes, see [README.md](README.md) and `.cursor/rules/project.mdc`.

## What you get

- **Laravel 13** + **Inertia 2** + **Vue 3** (Composition API, TypeScript)
- **Tailwind CSS v4** + **shadcn-vue** (Reka UI under `@/components/ui/`)
- **Fortify** (email/password) + optional **Zoho OAuth**
- **Spatie Permission** — roles and permissions stored in the database
- **DB-driven settings** — branding, locales, theme, auth toggles (`app_settings` + `AppSettingsService`)
- **vue-i18n** — JSON catalogs in `lang/*.json`
- **Global toasts** — `vue-sonner` + flash messages from `HandleInertiaRequests` (`flash.success` / `flash.error`)

---

## Prerequisites

- PHP **8.3+** with extensions Laravel needs (`openssl`, `pdo`, `mbstring`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath` recommended)
- [Composer](https://getcomposer.org/)
- Node **22+** (see `package.json` engines if added) and npm
- A database (SQLite is fine for local demo)

---

## First-time setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Edit `.env`:

- `APP_URL`, `APP_NAME` (fallback only; **display name** comes from **App Settings → branding.app_name** in the DB)
- Database: for SQLite, set `DB_CONNECTION=sqlite` and create the file:
  ```bash
  touch database/database.sqlite
  ```

Run migrations and seeders:

```bash
php artisan migrate --seed
php artisan storage:link
```

Install frontend dependencies:

```bash
npm install
```

Generate typed route helpers (after adding/changing routes):

```bash
php artisan wayfinder:generate
```

---

## Run the app (development)

**Option A — all-in-one (PHP server + queue + Vite):**

```bash
composer dev
```

**Option B — manual (two terminals):**

```bash
php artisan serve
npm run dev
```

**SSR (optional):**

```bash
composer dev:ssr
```

Open the URL shown by `php artisan serve` (usually `http://127.0.0.1:8000`).

---

## Default users (after `migrate --seed`)

| Email | Password | Role | Notes |
|--------|----------|------|--------|
| `test@example.com` | `password` | `admin` | Full admin UI, settings, impersonation |
| `developer@example.com` | `password` | `developer` | **Users** directory + impersonation only (no App Settings / Translations sidebar) |

Change or delete these in production.

---

## Roles & permissions (quick reference)

| Permission | Typical use |
|------------|-------------|
| `access_admin` | Legacy gate for most `/admin/*` pages (middleware on settings, translations, design guide, roles). |
| `impersonate_users` | `/admin/users` directory + `POST /admin/users/{user}/impersonate`. Granted to **`admin`** and **`developer`**. |
| `manage_roles` | Roles & permissions UI. |

**Sidebar behavior**

- **Admin-only (strict `admin` role):** App Settings, Translations, Design guide (and Roles if you have `manage_roles`).
- **Anyone with `impersonate_users`:** **Users** in the main nav → impersonation directory.

**Stop impersonating:** user menu → **Stop impersonating** (`POST /impersonate/stop`).

---

## Quality checks (CI-style)

```bash
composer ci:check
```

Runs Pint, ESLint/Prettier checks, `vue-tsc`, and tests (see `composer.json`).

Individual steps:

```bash
composer test
npm run lint:check
npm run format:check
npm run types:check
```

---

## Project map

| Area | Location |
|------|-----------|
| Inertia pages | `resources/js/pages/` |
| Layouts | `resources/js/layouts/` |
| shadcn-vue UI | `resources/js/components/ui/` |
| Shared components | `resources/js/components/` |
| Composables | `resources/js/composables/` |
| Wayfinder routes | `resources/js/routes/` (generated) |
| Controllers | `app/Http/Controllers/` |
| Form requests | `app/Http/Requests/` |
| Settings service | `app/Services/AppSettingsService.php` |
| Shared Inertia props | `app/Http/Middleware/HandleInertiaRequests.php` |
| Translations (JSON) | `lang/{locale}.json` |
| Seeders | `database/seeders/` |

---

## Conventions (must-follow)

- **App display name:** use DB branding — `settings('branding.app_name')` in PHP, `useBranding().appName` / `useAppName()` in Vue. Do not hardcode product name strings for UI.
- **Internal links:** `<Link>` from `@inertiajs/vue3`, not raw `<a href="/...">`.
- **UI primitives:** import from `@/components/ui/`, not Reka UI directly in pages.
- **Theme colors:** Tailwind / CSS variables (`var(--primary)`), not inline `style="color:..."`.

---

## Troubleshooting

### Language / locale does not update

- Locale resolution order: **session → cookie → default locale** in DB (`SetLocale` middleware).
- After changing locale in the **locale switcher**, the app reloads Inertia props for `appSettings` and `liveLocaleMessages`.
- After changing **default locale** in admin settings, the current session locale is updated for the user who saved settings.

### Fonts look wrong until refresh

- Saving branding in admin may trigger a **full page reload** by design so fonts and SSR-first paint stay in sync.
- Live updates on navigation rely on **reactive** `appSettings` (see `useAppSettings` composable + `BrandProvider`).

### Document title still wrong on first paint

- Root Blade title uses **`inertiaDocumentTitle`** from DB branding (see `AppServiceProvider` + `resources/views/app.blade.php`).
- Inertia document title suffix updates on each visit via `resources/js/app.ts` (keeps in sync with `appSettings.branding.appName`).

### Toasts not showing

- Ensure `GlobalToasts` is mounted from `resources/js/app.ts` (Sonner + `FlashToaster`).
- Backend should use `->with('success', '...')` or `->with('error', '...')` (shared as `flash` on every Inertia response).

### Wayfinder / `.form()` TypeScript errors

- Regenerate routes after route changes: `php artisan wayfinder:generate`.
- If your Wayfinder version’s typings differ from Inertia `<Form v-bind="route.form()">`, align with the version in this repo’s `package.json` / `composer.json`.

---

## Next steps

1. Set **branding.app_name**, logo, and locales in **App Settings** (`/admin/settings` as admin).
2. Adjust **`RolePermissionSeeder`** / migrations for your product’s roles.
3. Add features under `resources/js/pages/` and matching Laravel routes/controllers.

Welcome to the template.
