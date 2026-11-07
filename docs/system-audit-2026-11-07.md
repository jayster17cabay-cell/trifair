# TriFair System Audit

**Audit date:** 2026-11-07  
**Scope:** Laravel application, routes, middleware, controllers, services, Eloquent models, migrations, Blade views, frontend build configuration, tests, and available application logs.

## Executive summary

TriFair is a Laravel 8.80 Blade application using Tailwind CSS, Leaflet, PostgreSQL in production, and SQLite for tests. The current implementation has a strong domain foundation: multi-TODA memberships, franchise-term history, QR-scoped ratings, complaint routing, evidence, notifications, activity logging, role middleware, and extensive feature coverage are already present.

No production database records were changed and no destructive migration was added during this audit. One safe build-configuration fix was applied: Vite now references the existing Tailwind source file instead of a missing `resources/css/app.css` entry.

## Verified architecture and business rules

- **Authentication:** session-based Laravel authentication, email verification for staff/operator flows, Google Socialite for passenger sign-in, throttled login/registration/password-reset endpoints, forced password change support.
- **Authorization:** role middleware protects route groups. Complaint actions additionally enforce assignment in `ComplaintRoutingService` and `RatingAdminService`; passenger follow-ups enforce ownership.
- **Roles:** `superadmin`, `tfrb_officer`, `operator`, `operator_president`, and `passenger` are represented in routes and tests. The codebase calls the driver-facing account `operator`.
- **QR flow:** QR codes resolve to non-archived, approved operators. Active TODA memberships are selected; suspended TODAs are hidden; invalid/inactive/expired operators receive a violation-report flow rather than a rating form.
- **Ratings:** ratings are scoped to operator, selected TODA, and current franchise term. Input is validated at the controller and database check constraints are added for PostgreSQL/MySQL. A device cookie plus transaction re-check prevents ordinary duplicate submissions.
- **Complaints:** low ratings require complaint data and evidence, are routed to the responsible TODA president or TFRB/superadmin, and support review, resolution, rejection, forwarding, follow-ups, notifications, and activity records.
- **Historical data:** franchise terms are retained and ratings reference the term, so renewal does not erase historical ratings or complaints.
- **Memberships:** `operator_toda` is the many-to-many source of truth, has a unique `(operator_id, toda_id)` key, and enforces a three-TODA application limit in the service layer.

## Findings and recommendations

| ID | Finding / affected module | Cause | Recommendation | Risk |
|---|---|---|---|---|
| F-01 | **Fixed:** production asset build failed. Frontend/Vite. | `vite.config.js` referenced missing `resources/css/app.css`; the real source is `resources/css/tailwind.css`. | Point Vite at the existing source entry. This was applied. | Low; no runtime route or data change. |
| F-02 | Blade layouts serve committed `public/css/tailwind.css` and `public/js/app.js`, not Vite manifest assets. | The project has two asset strategies. | Choose one deployment strategy. Prefer Vite output in a planned release, or remove unused Vite wiring and keep the documented public build. Do not mix them. | Medium; changing layout asset URLs can cause a deployment-wide styling outage if not staged. |
| F-03 | Complaint queue regression tests currently fail against the working tree. Complaints UI. | An existing uncommitted edit to `resources/views/partials/admin/complaint-list.blade.php` removed copy asserted by `ComplaintRoutingTest`. | Decide whether the UI simplification is intentional; if yes, update the tests to assert behavior rather than explanatory copy. The edit was preserved. | Medium; reverting it would overwrite user work. |
| F-04 | Complaint action history is represented primarily through the general activity log. Complaint workflow. | There is no dedicated immutable complaint-status-history table. | Add a non-destructive `complaint_events` table in a separately approved migration, with rating, actor, event, prior/new status, note, and timestamp. Backfill only if an approved mapping exists. | Medium/high; requires retention and privacy decisions. |
| F-05 | Public rating POST intentionally bypasses CSRF. `VerifyCsrfToken`, QR flow. | In-app browsers reportedly lose cookies; the form must work without a session. | Keep the exemption only with layered abuse controls: strict throttle, signed/opaque QR identifiers, server-side validation, rate-limit telemetry, and monitoring. Consider a short-lived signed submission nonce for a future compatibility release. | Medium; tightening immediately could break mobile submissions. |
| F-06 | Device-cookie deduplication is not a fraud-proof identity mechanism. Rating flow. | A user can clear cookies or change devices. | Treat it as abuse reduction, not identity. If business policy requires stronger uniqueness, introduce an explicit passenger account/trip token policy rather than IP-based blocking. | Medium; stronger controls can block legitimate shared devices. |
| F-07 | File proofs are uploaded with a broad set of document/video extensions. Rating and response uploads. | Validation checks extension/MIME but storage policy is shared. | Store uploads outside executable web roots, generate server-side names, enforce an allowlist by workflow, scan where available, and serve through signed authorization-aware responses. | Medium; stricter allowlists may reject existing submissions. |
| F-08 | Signed proof URLs protect integrity but are not user/role authorization checks. `/proof/*`, `/file-storage/*`. | Signed routes allow anyone possessing a valid URL to retrieve the object. | Use short-lived signed URLs and verify that the requesting role is entitled to the related rating/violation, or proxy through an authorization controller. | Medium/high; must preserve email and review-screen links. |
| F-09 | Route/controller duplication exists between superadmin and TFRB officer surfaces. `routes/web.php`. | Separate controllers expose nearly identical workflows. | Keep the public routes for compatibility, but move shared authorization and state transitions behind services/policies; add policy tests before refactoring. | Medium; broad refactoring can change redirects or flash messages. |
| F-10 | Migration history contains legacy names and repeated date prefixes. `database/migrations`. | The schema evolved from drivers to operators and from single-TODA to memberships. | Do not rewrite applied migrations. Add a schema inventory command/check and future forward-only migrations; verify production schema before adding constraints. | Low now; high if old migrations are edited. |
| F-11 | Query/index coverage is good but should be checked against production plans. Reports/dashboards. | Existing indexes cover many filters, but PostgreSQL planner behavior depends on data volume and predicates. | Capture `EXPLAIN (ANALYZE, BUFFERS)` for dashboard, complaint, rating, notification, and membership queries; add only evidence-based composite indexes. | Low if additive; index cost affects writes/storage. |
| F-12 | Available log contains historical bootstrap/configuration and database-host errors. Operations. | Earlier invalid configuration and unavailable DB host were recorded. | Verify production `LOG_CHANNEL`, `APP_DEBUG=false`, database DNS/SSL settings, log rotation, and health monitoring. Avoid exposing logs publicly. | Low; operational configuration only. |

## Data model assessment

The current relationships are coherent and should not be replaced without approval:

```text
users 1--1 operators
operators 1--many ratings
operators many--many todas through operator_toda
operators 1--many franchise_terms
ratings many--1 todas (ride context)
ratings many--1 franchise_terms (historical context)
ratings 1--many proofs / resolution_proofs / follow-ups
ratings many--1 users (passenger, reviewer, routed president)
```

The `operator_toda` unique key prevents duplicate memberships. Historical `inactive`/`rejected` rows are retained in the model, although some service operations intentionally remove an unchecked active membership; that behavior should be confirmed against the project’s retention policy before production use.

## Security review summary

- SQL injection protection is generally provided by Eloquent/query bindings; no obvious raw user-controlled SQL interpolation was found in the inspected application code.
- CSRF remains enabled for authenticated state-changing routes, with explicit public-flow exemptions documented in middleware.
- Backend role checks exist; UI hiding is not the only protection for complaint actions.
- Complaint/passenger ownership checks are present for follow-ups and passenger dashboards.
- Security headers include MIME sniffing, clickjacking, referrer, permissions, and HSTS-on-HTTPS controls. A CSP is not currently configured and should be evaluated after cataloguing CDN/font/script dependencies.
- Upload authorization and malware scanning remain the highest-priority security hardening opportunities.

## Validation performed

- `php artisan test`: extensive suite runs; the observed failures were the two copy assertions described in F-03, while the displayed feature groups were otherwise passing before the command timeout.
- `php -l` across application, route, config, migration, and seeder PHP files: passed.
- `npm run build` before the fix: failed because `resources/css/app.css` did not exist.
- Route inspection: completed; protected admin/operator/president/passenger groups and public QR routes were enumerated.
- Existing working-tree changes: inspected and preserved; no reset, checkout, destructive migration, or production data operation was performed.

## Recommended implementation order

1. Confirm the intended complaint-list UI change and reconcile its tests.
2. Standardize the frontend asset strategy and run the built output in a staging environment.
3. Add authorization-aware, short-lived proof delivery.
4. Add immutable complaint event history after approval of retention/privacy requirements.
5. Run production query plans and add measured indexes only.
6. Perform browser/device testing with real QR codes, mobile in-app browsers, and each role before release.
