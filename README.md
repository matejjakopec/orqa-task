# Orqa

Laravel 13 application for organisation accounts and private file storage.

## Requirements

Docker and DDEV. ClamAV is configured with a 4 GiB memory limit; its first startup may take several minutes.

## Fresh installation

Clone the repository, open its root directory, and run:

```bash
ddev start
ddev setup
ddev exec php artisan demo:seed --no-interaction
ddev exec php artisan users:list --no-interaction
ddev launch
```

`ddev setup` installs dependencies, creates `.env`, generates a missing application key, runs migrations and role seeding, and builds frontend assets. Existing environment files and application keys are preserved.

DDEV starts MariaDB, the queue worker, scheduler, Mailpit, and ClamAV automatically.

## Accounts

Demo accounts have random usernames and profile data. All generated accounts use password **`local-demo-password`**. Use `users:list` to see usernames and types.

Default seeding creates 10 users, 2 admins, 1 admin/user, 1 superadmin, 3 organisations, and 7 files per storage account (77 files total).

| Type | Access |
| --- | --- |
| User | Upload, download, and delete personal files. |
| Admin | View and create ordinary users in their organisation. |
| Admin + User | Organisation administration and personal file storage. |
| Superadmin | Create organisations and accounts; list, download, and delete files across organisations. |

New accounts created through the UI receive an email to set their password and activate their account. Open local email with `ddev launch -m`.

## Custom demo data

```bash
ddev exec php artisan demo:seed --users=10 --admins=2 --admin-users=1 --superadmins=1 --organisations=3 --files=7 --no-interaction
```

Counts control each account type. `--files` adds files per account with personal storage. Each run adds new records. Plain `db:seed` creates roles only.

## Useful commands

```bash
ddev start                                           # Start the project again
ddev stop                                            # Stop the project
ddev npm run dev                                     # Frontend development
ddev npm run build                                   # Rebuild frontend assets
ddev exec php artisan migrate --no-interaction        # Apply new migrations
ddev exec php artisan files:scanner-status --no-interaction # Check the scanner
ddev exec php artisan optimize:clear --no-interaction # Clear application caches
```
