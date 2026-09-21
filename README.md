# Reward Gate

Reward Gate is a self-hosted PHP application for creating and managing
website content gates and reward-based interactions.

## Status

MVP development.

The core application, administration panel, Popup Gate, Content Gate,
timer/click unlock flow, server-side verification, and basic anti-abuse
mechanisms are implemented.

The project is currently being prepared for a complete MVP demo and
production-ready release.

## Requirements

* PHP 8.2+
* Apache or Nginx
* MySQL or MariaDB
* Composer

## Development

Install PHP dependencies:

```bash
composer install
```

Run database migrations:

```bash
vendor/bin/phinx migrate
```

Create the demo administrator and demo campaigns:

```bash
vendor/bin/phinx seed:run
```

The demo seed is idempotent. Running it again does not recreate existing
demo data or replace the existing administrator password.

## Demo

After the application and database are configured, the demo pages are
available at:

* `/demo-popup` - Popup Gate demonstration
* `/demo-content` - Content Gate demonstration

The demo seed creates:

* `Demo Popup Gate`
* `Demo Content Gate`
* a demo administrator account if one does not already exist

If the demo administrator account is created by the seed, the generated
password is displayed by the seed command. If the account already exists,
its existing password is preserved.

## Database

Reward Gate uses Phinx for database migrations and seeds.

Run migrations with:

```bash
vendor/bin/phinx migrate
```

Run the demo seed with:

```bash
vendor/bin/phinx seed:run
```

## Project Structure

```text
config/       Application configuration and routes
database/     Migrations and database seeds
docs/         Project documentation
private/      Files that must not be publicly accessible
public/       Public web root and frontend assets
src/          Application source code
storage/      Runtime storage when required
tests/        Automated tests
```

## Testing

Run the complete PHPUnit test suite with:

```bash
vendor/bin/phpunit
```

The project also uses PHPStan and PHP-CS-Fixer for static analysis and
code formatting.

## Documentation

Project documentation is maintained in the `docs/` directory.

* `docs/specification.md` - Product specification
* `docs/architecture.md` - Application architecture
* `docs/database.md` - Database structure
* `docs/todo.md` - Development checklist

## Demo Setup

A typical local or partner demo setup is:

```text
Configure PHP + web server + MySQL/MariaDB
        ↓
Deploy Reward Gate
        ↓
composer install
        ↓
vendor/bin/phinx migrate
        ↓
vendor/bin/phinx seed:run
        ↓
Open /demo-popup or /demo-content
```

The migration and seed commands prepare the Reward Gate database and demo
application data. Server, PHP, web-server, and database configuration remain
environment-specific and are not performed by the application seed.

