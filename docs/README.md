# 📚 PayrollPro Documentation

Welcome to the PayrollPro documentation. This directory holds API specifications, engineering reports, and screenshots used in the project README.

## Contents

| Document | Description |
| --- | --- |
| [`mobile-api.yaml`](./mobile-api.yaml) | OpenAPI 3 specification for the mobile attendance API (clock-in/out, sync-offline, status) |
| [`reports/`](./reports/) | Point-in-time engineering reports: audits, feature analyses, performance & security reviews |

## Images

[`images/`](./images/) contains the 1920×1080 screenshots used in the [README](../README.md#-screenshots). They are regenerable via the capture tooling in [`scripts/`](../scripts/) (`capture-screenshots.mjs`, `capture-mobile.mjs`).

## API Documentation

The mobile attendance API is served with three authentication modes — see [`mobile-api.yaml`](./mobile-api.yaml) for the full OpenAPI spec. It's also browsable in-app at `/developer/api-docs` (admin only).

## Engineering Reports

The [`reports/`](./reports/) folder collects point-in-time documents produced during development, including:

- **Audit** — full application audit
- **Performance** — page-navigation & rendering optimization analysis
- **Security** — Supabase Advisor remediation report
- **Feature reports** — My QR, manual attendance, mobile settings, real-time WIB clock, layout optimization
- **Deployment** — free public deployment recommendations
- **Data** — Bangkalan UMK salary adjustments & demo login accounts

These are historical records and may reference older versions of the code.
