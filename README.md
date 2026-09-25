# ANCHOR backend

Laravel 12 REST API for human-reviewed case classification and routing. AI outputs remain drafts until a legal reviewer explicitly approves or edits them.

## Setup

Run `composer install`, copy `.env.example` to `.env`, configure Supabase PostgreSQL credentials, then run `php artisan migrate --seed`. For local PostgreSQL, run `docker compose up` after installing project dependencies in the app image or host environment. `POST /api/v1/auth/token` issues Sanctum tokens for seeded/admin-managed users.

Victim endpoints require `Authorization: Bearer <secret-token>`. The secret is returned only by case creation, stored as a hash, and is never accepted in a URL or body.

## Design notes

- Every API access is recorded through `AuditCaseAccess`; it records route, actor, case ID, method, and response status without copying narrative or evidence content into general logs.
- Reject actions require `reclassify_pending` or `manual_handling`. Reclassification writes a reviewer-attributed rejection record then runs the stub passes again; no terminal `rejected` status is used.
- Queue triage computes a historical similarity score from approved, unedited cases. It can prioritize and suggest a one-click `approve`, but it never changes case state or writes a review. The reviewer must still submit the review action.
- `review_recommendation_confidence` and its source-case column are reserved schema hooks for phase 2 evaluation. No fast-track or auto-approval behavior exists.
- Replace the three stub bindings in `app/Providers/AppServiceProvider.php` with real service implementations when the AI stream is ready. Keep the same interfaces and have the integration write through the internal endpoint or `CaseProcessingService`.

## API contract

See [openapi.yaml](openapi.yaml). Reviewer and admin endpoints use Sanctum plus policies. The internal AI endpoint additionally requires a token with the `internal:ai` ability.

## TODO

`AGENTS.md` references `RTK.md`, but the file was never committed. Its contents were not inferred or recreated.

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
