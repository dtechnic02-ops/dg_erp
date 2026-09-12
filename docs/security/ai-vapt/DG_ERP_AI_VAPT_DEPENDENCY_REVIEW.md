# DG ERP AI VAPT — Dependency Review

## Composer

`composer audit --locked --format=json` reported one advisory and exited non-zero:

| Package | Locked version | Advisory | Upstream severity | Local reachability assessment |
|---|---|---|---|---|
| maatwebsite/excel | 3.1.69 | CVE-2026-84374 / GHSA-c7r6-vx3h-w5g2; affected `<3.1.70` | High | Confirmed affected version. Located Product download uses a constant filename, mitigating the caller-controlled path prerequisite; upgrade still required. |

No package was upgraded.

## npm

`npm audit --omit=dev --json` completed successfully and reported zero production vulnerabilities. The lock includes development tooling; this result is not a browser/runtime penetration test and does not establish that future advisories are absent.

## Framework posture

- PHP requirement: `^8.2`; local test runtime observed PHP 8.4.20.
- Laravel requirement: `^12.0`.
- PHPUnit: 11.5 line.
- Vite/Tailwind/axios are development/frontend dependencies.

Advisory status is time-sensitive and must be re-run immediately before external VAPT and release.
