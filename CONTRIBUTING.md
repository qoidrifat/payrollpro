# Contributing to PayrollPro

First off — thank you for taking the time to contribute! 🎉

The following is a set of guidelines for contributing to **PayrollPro**, the Indonesian HR, attendance, and payroll management system. These are guidelines, not rules. Use your best judgment, and feel free to propose changes to this document in a pull request.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Environment](#development-environment)
- [Project Structure](#project-structure)
- [Coding Standards](#coding-standards)
- [Testing](#testing)
- [Commit Messages](#commit-messages)
- [Pull Request Process](#pull-request-process)
- [Issue Reporting](#issue-reporting)

## Code of Conduct

This project and everyone participating in it is governed by the [Code of Conduct](CODE_OF_CONDUCT.md). By participating, you are expected to uphold this code. Please report unacceptable behavior to the maintainers.

## Getting Started

1. **Fork** the repository and create your branch from `main`.
2. **Clone** your fork locally.
3. Set up the project (see [Development Environment](#development-environment)).
4. If you've added code that should be tested, add tests.
5. Ensure the test suite passes.
6. Update documentation (README, docs/) if you change behaviour.
7. Submit a **pull request** back to `main`.

## Development Environment

Requirements:

- PHP 8.2+
- Composer 2
- Node.js 20+ and npm
- MySQL 8, PostgreSQL/Supabase, or SQLite
- PHP extensions: `bcmath`, `ctype`, `dom`, `fileinfo`, `gd`, `json`, `mbstring`, `openssl`, `pdo`, `pdo_mysql`/`pdo_pgsql`/`pdo_sqlite`, `tokenizer`, `xml`, `zip`

Quick start:

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate

# SQLite (simplest) or configure MySQL/PostgreSQL in .env
touch database/database.sqlite

php artisan migrate --seed
php artisan storage:link
npm run build

# Run the app
php artisan serve
npm run dev
```

## Project Structure

```
app/
├── Actions/       Workflow orchestration (payroll, attendance, approvals)
├── DTOs/          Data transfer objects
├── Enums/         Type-safe domain constants
├── Exports/       Excel export classes
├── Http/          Controllers, form requests, middleware, resources
├── Jobs/          Queue jobs
├── Listeners/     Event listeners
├── Models/        Eloquent models
├── Notifications/ Notification classes
├── Policies/      Authorization policies
├── Repositories/  Repository interfaces & implementations
├── Scopes/        Global scopes
├── Services/      Core business logic (payroll, tax, BPJS, geofence)
└── Traits/        Shared model behaviour
```

## Coding Standards

- Follow **[PSR-12](https://www.php-fig.org/psr/psr-12/)** for PHP.
- Run **[Laravel Pint](https://laravel.com/docs/12.x/pint)** before committing:

```bash
./vendor/bin/pint
```

- Follow **Laravel conventions** (controllers thin, services/actions carry business logic, repositories for data access).
- Use **TypeScript-friendly, named Vue 3 `<script setup>`** components with Tailwind CSS.
- Sensitive employee data (NIK, NPWP, bank accounts, BPJS numbers) **must** use encrypted casts.

## Testing

The suite covers services, policies, auth, attendance, payroll, employee portal, reports, settings, and payslips.

```bash
# Full suite
composer run test        # or: php artisan test

# A single test file
php artisan test --filter=PayrollFeatureTest
```

Before opening a PR, ensure:

- `composer run test` passes locally.
- `npm run build` compiles without errors.
- `./vendor/bin/pint --test` reports no style violations.

## Commit Messages

Use clear, imperative-style commit messages:

- `Add attendance bulk-store endpoint`
- `Fix payroll proration for mid-month hires`
- `Refactor tax calculator to reduce duplication`
- `Document mobile API auth flow`

Keep commits focused on a single logical change.

## Pull Request Process

1. Update the **description** — explain what and why, and link related issues.
2. Add **screenshots** for UI changes.
3. Ensure the **CI pipeline** (lint, frontend build, PHPUnit, security audit) is green.
4. Request review from a maintainer. Incorporate feedback.
5. Squash-and-merge commits are preferred once approved.

## Issue Reporting

Please include:

- **Bug reports:** steps to reproduce, expected vs. actual behaviour, environment (OS, PHP/Node versions, DB), and logs.
- **Feature requests:** the problem being solved, proposed behaviour, and any relevant alternatives you've considered.

Use the provided [issue templates](.github/ISSUE_TEMPLATE/) when available.
