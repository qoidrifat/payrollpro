# Security Policy

## Supported Versions

Only the latest release on the `main` branch receives security fixes. Older versions are not actively maintained.

| Version | Supported          |
| ------- | ------------------ |
| main    | ✅ Yes             |
| < main  | ❌ No              |

## Reporting a Vulnerability

We take security seriously. If you discover a vulnerability in **PayrollPro**, please **do not open a public issue**.

Instead, report it privately by emailing the maintainers. Include:

- A description of the vulnerability.
- Steps to reproduce it.
- The affected component/version.
- Any suggested mitigation (optional).

You can expect an acknowledgement within **72 hours** and a detailed response (fix plan or a rationale if the report is declined) within **7 days**. We will keep you informed of progress toward a fix.

## Security Practices in this Project

The application follows these security measures (see `docs/reports/` for audit history):

- **RBAC** via Spatie Permission with case-insensitive role middleware.
- **Encrypted sensitive fields** (NIK, NPWP, bank accounts, BPJS numbers) using Laravel encrypted casts.
- **CSP & security headers** enabled outside local/testing environments.
- **Rate limiting** on demo login, QR attendance, and attendance API endpoints.
- **Sanctum token expiry** configurable via `SANCTUM_TOKEN_EXPIRATION` (default 30 days).
- **Scheduled dependency audits** via GitHub Actions (`composer audit` + `npm audit`).

## Dependency Audits

Run locally to verify dependency health:

```bash
composer audit
npm audit
```
