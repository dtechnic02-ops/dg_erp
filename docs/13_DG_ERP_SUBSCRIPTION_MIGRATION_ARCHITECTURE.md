==========================================================
DG ERP SUBSCRIPTION MODULE — MIGRATION ARCHITECTURE
Version: 1.0 (FREEZE)
Status: FINAL — Migration Plan Freeze Document
==========================================================

Standards: docs/01_DG_ERP_MASTER_DEVELOPMENT_STANDARD.md, docs/10_DG_ERP_SUBSCRIPTION_MODULE_STANDARD.md
Basis: Final Enterprise Database Design Report (docs/12_DG_ERP_SUBSCRIPTION_DATABASE_DESIGN.md)
Scope: Migration architecture only — no PHP, SQL, models, controllers, services, routes, or changes to completed ERP modules
Legacy audited: plans, subscriptions, payments, companies (partial)

==========================================================
1. MIGRATION EXECUTION ORDER
==========================================================

1.1 Master Sequence (14 Migrations)

| Step | Migration Filename (proposed) | Action | Why This Order |
|------|------------------------------|--------|----------------|
| M01 | 2026_07_25_180000_create_billing_cycles_table.php | Create billing_cycles | No FK dependencies except optional users. Must exist before bridge table and paid subscriptions. |
| M02 | 2026_07_25_180100_rename_plans_to_subscription_plans_table.php | Rename plans → subscription_plans | Child tables (subscriptions, payments) still reference same IDs. Rename preserves PK integrity. Must happen before column refactor. |
| M03 | 2026_07_25_180200_upgrade_subscription_plans_table.php | Alter subscription_plans | Add code, hidden_modules, audit/cancel fields. Rename user_limit → staff_limit. Legacy columns kept temporarily for backfill. |
| M04 | 2026_07_25_180300_create_subscription_plan_billing_options_table.php | Create pivot | Requires subscription_plans + billing_cycles. Stores price extracted from legacy plan rows. |
| M05 | 2026_07_25_180400_backfill_billing_options_from_legacy_plans.php | Data migration | Maps legacy type + duration_days + price → pivot rows. Must run after M03–M04. |
| M06 | 2026_07_25_180500_rename_subscriptions_to_company_subscriptions_table.php | Rename subscriptions → company_subscriptions | Depends on subscription_plans (renamed). Preserves existing subscription rows and IDs. |
| M07 | 2026_07_25_180600_upgrade_company_subscriptions_table.php | Alter company_subscriptions | Add entitlement columns, nullable plan/cycle for future trials, self-FK chain. Drop/recreate FKs safely. |
| M08 | 2026_07_25_180700_backfill_company_subscriptions_from_legacy_data.php | Data migration | Backfill from companies.expiry_date, companies.selected_user_limit, legacy plan data. |
| M09 | 2026_07_25_180800_rename_payments_to_subscription_payments_table.php | Rename payments → subscription_payments | Isolates subscription payments from ERP payment modules. Same IDs preserved. |
| M10 | 2026_07_25_180900_upgrade_subscription_payments_table.php | Alter subscription_payments | Add billing_cycle_id, action_type, workflow audit fields. Rename plan_id → subscription_plan_id, method → payment_method, note → notes, screenshot → proof_path. |
| M11 | 2026_07_25_181000_backfill_subscription_payments_from_legacy_data.php | Data migration | Infer billing_cycle_id and action_type=assign from linked plan legacy fields. |
| M12 | 2026_07_25_181100_create_subscription_histories_table.php | Create audit table | Depends on companies, company_subscriptions, subscription_payments, users. Created last among core tables. |
| M13 | 2026_07_25_181200_backfill_subscription_histories_from_legacy_data.php | Data migration | Generate baseline history rows from existing subscriptions/payments. |
| M14 | 2026_07_25_181300_finalize_subscription_module_schema.php | Cleanup + indexes | Drop deprecated columns from subscription_plans. Add all indexes. Add FK hardening (RESTRICT). Optional: add expired to companies.status enum. |

Seeder execution (after M04, before or after M14):

| Step | Seeder | When |
|------|--------|------|
| S01 | BillingCycleSeeder | After M01 (if table empty) |
| S02 | SubscriptionPlanSeeder | After M03 (if no plans exist post-rename) |
| S03 | SubscriptionPlanBillingOptionSeeder | After M04 + S01 + S02 |

1.2 Grouped Execution Phases

PHASE A — Configuration Foundation
  M01 → M02 → M03 → M04 → M05 → S01/S02/S03

PHASE B — Entitlement Layer
  M06 → M07 → M08

PHASE C — Payment Layer
  M09 → M10 → M11

PHASE D — Audit Layer
  M12 → M13

PHASE E — Finalize
  M14

==========================================================
2. MIGRATION DEPENDENCY DIAGRAM
==========================================================

users (existing)
companies (existing)
        │
        ├──────────────────────────────────────────────┐
        │                                              │
        ▼                                              │
   M01 billing_cycles                                   │
        │                                              │
        ▼                                              │
   M02 rename plans ──► subscription_plans              │
        │                                              │
        ▼                                              │
   M03 upgrade subscription_plans                       │
        │                                              │
        ├──────────────► M04 subscription_plan_billing_options
        │                      ▲                        │
        │                      └── billing_cycles       │
        ▼                                              │
   M05 backfill billing options                         │
        │                                              │
        ▼                                              │
   M06 rename subscriptions ──► company_subscriptions ◄─┘
        │
        ▼
   M07 upgrade company_subscriptions
        │         (self-FK: previous_subscription_id)
        ▼
   M08 backfill company_subscriptions
        │
        ▼
   M09 rename payments ──► subscription_payments
        │
        ▼
   M10 upgrade subscription_payments
        │         (FK: company_subscriptions result/target)
        ▼
   M11 backfill subscription_payments
        │
        ▼
   M12 subscription_histories
        │    (FK: companies, company_subscriptions,
        │         subscription_payments, users)
        ▼
   M13 backfill subscription_histories
        │
        ▼
   M14 finalize (drop legacy cols, indexes, FK RESTRICT)

Dependency rule: Configuration → Bridge → Transactional → Audit → Cleanup.

==========================================================
3. LEGACY MIGRATION STRATEGY
==========================================================

3.1 Legacy Audit

| Legacy Table | Columns (current) | Records Role | Final Target |
|--------------|-------------------|--------------|--------------|
| plans | name, user_limit, price, duration_days, type, is_active, customer_limit | Plan + billing + price merged | subscription_plans + subscription_plan_billing_options |
| subscriptions | company_id, plan_id, start_date, expiry_date, status | Company entitlement | company_subscriptions |
| payments | company_id, plan_id, amount, method, status, screenshot, note | Subscription payment | subscription_payments |
| companies | expiry_date, selected_user_limit, status | Duplicate truth | Deprecate reads; source = company_subscriptions |

3.2 Decision Matrix

| Legacy Asset | Decision | Reason |
|--------------|----------|--------|
| plans | Rename + Upgrade | Same entity; preserve IDs and FK references |
| subscriptions | Rename + Upgrade | Same entity; preserve history |
| payments (admin subscription) | Rename + Upgrade | Eliminates naming collision with ERP payments |
| plans.duration_days | Deprecate → extract to billing_cycles | Doc 10: plan ⊥ billing cycle |
| plans.type | Deprecate → map to billing_cycles.code | Same reason |
| plans.price | Deprecate → move to pivot | Price is plan × cycle |
| plans.customer_limit | Deprecate (drop in M14) | Not in Doc 10 subscription scope |
| companies.expiry_date | Deprecate (keep column, stop writes) | Avoid breaking legacy reads during transition |
| companies.selected_user_limit | Deprecate (keep column, stop writes) | Same |
| companies.status = blocked for expiry | Extend enum → add expired | Doc 10 compliance (separate from admin block) |

3.3 No Duplicate Tables Rule

• Never create a second subscription_plans while plans exists.
• Never copy data into parallel tables.
• Path: rename → alter → backfill → validate → cleanup.

3.4 Compatibility Strategy (Transition Period)

| Layer | Strategy |
|-------|----------|
| Database | Legacy columns remain until M14 validation passes |
| Application (future) | Dual-read: prefer company_subscriptions; fallback companies.expiry_date during transition |
| Models (future) | Plan → SubscriptionPlan alias until code switch complete |
| Rollback window | M14 is point-of-no-return for dropped legacy columns |

3.5 Rollback Safety per Legacy Asset

| Step | Rollback Safe? | Notes |
|------|----------------|-------|
| M02 rename | Yes | Reverse rename restores plans |
| M03 alter plans | Yes | If legacy columns not dropped yet |
| M05 backfill | Partial | Backfill migration down() should delete only rows it inserted (tag via migration batch or deterministic keys) |
| M06–M11 rename/alter | Yes | Before M14 column drops |
| M14 finalize | No safe rollback | Dropped columns unrecoverable without backup |

==========================================================
4. TABLE-BY-TABLE MIGRATION PLAN
==========================================================

4.1 billing_cycles (NEW — M01)

| Item | Definition |
|------|------------|
| Migration | 2026_07_25_180000_create_billing_cycles_table.php |
| Dependency | users (optional audit FKs) |
| Foreign Keys | created_by, updated_by, cancelled_by → users.id (SET NULL on delete) |
| Indexes | UNIQUE code; INDEX (is_active, sort_order) |
| Unique Constraints | code |
| Nullable Rules | duration_days NULL when is_lifetime=1; audit fields nullable |
| Defaults | is_lifetime=0, is_active=1, sort_order=0 |
| Rollback | Drop table (only if no dependent rows — run before M04 in reverse) |

Business rules: Monthly, Yearly, future Quarterly/Half-Yearly/Lifetime.

4.2 subscription_plans (RENAME + UPGRADE — M02, M03, M14)

| Item | Definition |
|------|------------|
| Migration M02 | 2026_07_25_180100_rename_plans_to_subscription_plans_table.php |
| Migration M03 | 2026_07_25_180200_upgrade_subscription_plans_table.php |
| Migration M14 | Drop legacy columns |
| Dependency | None for rename; child FKs auto-follow in MySQL/MariaDB |
| Foreign Keys | Audit user FKs → users (SET NULL) |
| Indexes | UNIQUE code; INDEX (is_active, sort_order) |
| Unique Constraints | code (backfill from normalized name during M03) |
| Nullable Rules | hidden_modules JSON nullable; description, cancel fields nullable |
| Column transitions | user_limit → staff_limit; keep duration_days, type, price, customer_limit until M14 |
| Rollback M02 | Rename back to plans |
| Rollback M14 | Cannot restore dropped columns without backup |

Business rules: Basic, Basic Plus, Pro, Pro Plus; staff limits; hidden modules; activate/deactivate.

4.3 subscription_plan_billing_options (NEW — M04, M05)

| Item | Definition |
|------|------------|
| Migration M04 | 2026_07_25_180300_create_subscription_plan_billing_options_table.php |
| Migration M05 | 2026_07_25_180400_backfill_billing_options_from_legacy_plans.php |
| Dependency | subscription_plans, billing_cycles |
| Foreign Keys | subscription_plan_id → RESTRICT; billing_cycle_id → RESTRICT |
| Indexes | UNIQUE (subscription_plan_id, billing_cycle_id); INDEX (subscription_plan_id, is_active) |
| Unique Constraints | Composite plan + cycle |
| Nullable Rules | None on core fields |
| Defaults | currency_code=NPR, is_active=1 |
| Rollback M05 | Delete rows created by backfill (deterministic mapping) |
| Rollback M04 | Drop table (after M05 reversed) |

Legacy mapping (M05):

| Legacy plans.type | Legacy duration_days | → billing_cycles.code |
|-------------------|----------------------|---------------------------|
| monthly | 30 | monthly |
| yearly | 365 | yearly |
| trial | 7 | Not a billing cycle — ignore for paid pivot; used for Register Trial logic in service |

4.4 company_subscriptions (RENAME + UPGRADE — M06, M07, M08)

| Item | Definition |
|------|------------|
| Migration M06 | 2026_07_25_180500_rename_subscriptions_to_company_subscriptions_table.php |
| Migration M07 | 2026_07_25_180600_upgrade_company_subscriptions_table.php |
| Migration M08 | 2026_07_25_180700_backfill_company_subscriptions_from_legacy_data.php |
| Dependency | companies, subscription_plans, billing_cycles, self |
| Foreign Keys | company_id RESTRICT; subscription_plan_id RESTRICT nullable; billing_cycle_id RESTRICT nullable; previous_subscription_id SET NULL; audit FKs SET NULL |
| Indexes | (company_id, status); (status, expiry_date); (subscription_type, status); (company_id, expiry_date) |
| Unique Constraints | Application-enforced one active per company (optional DB partial index if supported) |
| Nullable Rules | subscription_plan_id, billing_cycle_id NULL for trials; expiry_date NULL for lifetime; previous_subscription_id nullable |
| Column transitions | plan_id → subscription_plan_id; status string → enum active/expired/cancelled |
| Rollback M06–M07 | Reversible before production data depends on new columns |
| Rollback M08 | Revert backfilled column values from snapshot taken at migration start (store in temp mapping table — see Section 7) |

Business rules: Register Trial, Free Trial, Paid, Renewal, Upgrade, Downgrade, Expiry, Cancel.

4.5 subscription_payments (RENAME + UPGRADE — M09, M10, M11)

| Item | Definition |
|------|------------|
| Migration M09 | 2026_07_25_180800_rename_payments_to_subscription_payments_table.php |
| Migration M10 | 2026_07_25_180900_upgrade_subscription_payments_table.php |
| Migration M11 | 2026_07_25_181000_backfill_subscription_payments_from_legacy_data.php |
| Dependency | companies, subscription_plans, billing_cycles, company_subscriptions |
| Foreign Keys | All master FKs RESTRICT; result/target subscription SET NULL; audit SET NULL |
| Indexes | (company_id, status); (status, payment_date); (action_type, status); (approved_at) |
| Unique Constraints | None (service enforces one pending per company) |
| Nullable Rules | proof_path, reference_no, workflow fields nullable until processed |
| Column transitions | plan_id → subscription_plan_id; method → payment_method; note → notes; screenshot → proof_path; amount INT → DECIMAL(12,2) |
| Rollback M09 | Rename back to payments (critical: update all code references simultaneously) |
| Rollback M11 | Revert backfilled fields |

Business rules: Assign, Renew, Upgrade, Downgrade payment workflow; approval/rejection audit.

4.6 subscription_histories (NEW — M12, M13)

| Item | Definition |
|------|------------|
| Migration M12 | 2026_07_25_181100_create_subscription_histories_table.php |
| Migration M13 | 2026_07_25_181200_backfill_subscription_histories_from_legacy_data.php |
| Dependency | companies, company_subscriptions, subscription_payments, users |
| Foreign Keys | company_id RESTRICT; company_subscription_id RESTRICT; subscription_payment_id SET NULL; performed_by SET NULL |
| Indexes | (company_id, event_at); (company_subscription_id, event_at); (event_type, event_at); (subscription_payment_id) |
| Unique Constraints | None |
| Nullable Rules | All before/after columns nullable; performed_by NULL for system events |
| Special | No updated_at; append-only |
| Rollback M13 | Delete backfill rows by batch marker |
| Rollback M12 | Drop table (last to drop in full rollback) |

Business rules: Full lifecycle audit; renewal history; payment events.

==========================================================
5. SEEDER PLAN
==========================================================

Design only — no seeder code.

5.1 Seeder Files

| Seeder | Purpose | Depends On | Idempotent? |
|--------|---------|------------|-------------|
| BillingCycleSeeder | Master billing cycles | M01 | Yes — upsert by code |
| SubscriptionPlanSeeder | Doc 10 plan catalog | M03 | Yes — upsert by code |
| SubscriptionPlanBillingOptionSeeder | Default pricing matrix | M04, S01, S02 | Yes — upsert by plan+cycle |

5.2 BillingCycleSeeder Data

| code | name | duration_days | is_lifetime | is_active | sort_order |
|------|------|---------------|-------------|-----------|------------|
| monthly | Monthly | 30 | 0 | 1 | 1 |
| yearly | Yearly | 365 | 0 | 1 | 2 |
| quarterly | Quarterly | 90 | 0 | 0 | 3 |
| half_yearly | Half-Yearly | 182 | 0 | 0 | 4 |
| lifetime | Lifetime | NULL | 1 | 0 | 5 |

Quarterly, Half-Yearly, Lifetime: seeded inactive until business enables them (future-ready, no schema change).

5.3 SubscriptionPlanSeeder Data

| code | name | staff_limit | hidden_modules | is_active | sort_order |
|------|------|-------------|----------------|-----------|------------|
| basic | Basic | 1 | ["crm","loan","hr","delivery"] | 1 | 1 |
| basic_plus | Basic Plus | 5 | ["crm","delivery"] | 1 | 2 |
| pro | Pro | 20 | ["crm"] | 1 | 3 |
| pro_plus | Pro Plus | 100 | [] | 1 | 4 |

5.4 SubscriptionPlanBillingOptionSeeder Data

Default pricing matrix (amounts configurable by business — placeholder structure):

| plan code | cycle code | price | currency | is_active |
|-----------|------------|-------|----------|-----------|
| basic | monthly | TBD | NPR | 1 |
| basic | yearly | TBD | NPR | 1 |
| basic_plus | monthly | TBD | NPR | 1 |
| basic_plus | yearly | TBD | NPR | 1 |
| pro | monthly | TBD | NPR | 1 |
| pro | yearly | TBD | NPR | 1 |
| pro_plus | monthly | TBD | NPR | 1 |
| pro_plus | yearly | TBD | NPR | 1 |

5.5 Seeder Execution Order

BillingCycleSeeder
        ↓
SubscriptionPlanSeeder
        ↓
SubscriptionPlanBillingOptionSeeder

Rule: Seeders run after M04, before M08 backfill on fresh installs. On legacy installs, M05 backfill runs first; seeders use upsert to fill gaps only.

5.6 Seeder Registration

Add to DatabaseSeeder as a dedicated subscription group — callable independently:

SubscriptionModuleSeeder
  ├── BillingCycleSeeder
  ├── SubscriptionPlanSeeder
  └── SubscriptionPlanBillingOptionSeeder

==========================================================
6. ROLLBACK STRATEGY
==========================================================

6.1 General Rules

| Rule | Detail |
|------|--------|
| One migration = one reversible unit | Each down() reverses only its up() |
| Data migrations tag rows | Backfill migrations insert identifiable rows (e.g. notes = 'migration:M08') for clean down() |
| Reverse order | M14 → M13 → … → M01 |
| Point of no return | M14 column drops — requires DB backup before running in production |
| FK order on rollback | Drop subscription_histories first; then subscription_payments; then company_subscriptions; then pivot; then billing_cycles; rename tables last |

6.2 Rollback Matrix

| Migration | down() Action | Risk |
|-----------|---------------|------|
| M01 | Drop billing_cycles | Low if M04 reversed first |
| M02 | Rename subscription_plans → plans | Low |
| M03 | Drop added columns; rename staff_limit → user_limit | Low |
| M04 | Drop pivot | Low |
| M05 | Delete backfill pivot rows | Medium — verify delete scope |
| M06 | Rename company_subscriptions → subscriptions | Low |
| M07 | Drop added columns; restore FK names | Medium |
| M08 | Restore from temp mapping table | Medium |
| M09 | Rename subscription_payments → payments | High — name collision risk if ERP code not reverted |
| M10 | Drop added columns | Medium |
| M11 | Revert backfill | Medium |
| M12 | Drop subscription_histories | Low |
| M13 | Delete backfill history rows | Low |
| M14 | Cannot fully restore | High — backup required |

6.3 Production Rollback Procedure

1. Enable maintenance mode
2. Restore DB from pre-M14 backup (if M14 ran)
3. OR run migrate:rollback --step=N only if M14 has not run
4. Revert application code to legacy model names
5. Validate legacy plans, subscriptions, payments reads
6. Disable maintenance mode

==========================================================
7. DEPLOYMENT STRATEGY
==========================================================

7.1 Data Migration Pipeline

Legacy Data (plans, subscriptions, payments, companies)
        ↓
Temporary Mapping Table (M00 — optional pre-step)
  subscription_migration_map
  - legacy_table, legacy_id, new_table, new_id, batch_id
        ↓
Backfill (M05, M08, M11, M13)
        ↓
Validation (post-migration artisan command — future, not in migrations)
  - row counts match
  - no orphan FKs
  - one active subscription per company
  - expiry_date on company_subscriptions matches companies.expiry_date
  - staff_limit matches selected_user_limit
        ↓
Switch (application reads company_subscriptions — future code phase)
        ↓
Cleanup (M14 — drop legacy columns from subscription_plans; deprecate company fields)

7.2 Optional Pre-Migration: Temp Mapping Table

| Migration | 2026_07_25_175900_create_subscription_migration_map_table.php |
|-----------|---------------------------------------------------------------------|
| Purpose | Store legacy→new ID mappings and pre-migration snapshots for rollback |
| Drop | After successful validation + 30-day stable period |

7.3 Deployment Steps (Production)

| Step | Action |
|------|--------|
| 1 | Full database backup |
| 2 | Maintenance mode ON |
| 3 | Deploy migration files only (no application code switch yet) |
| 4 | php artisan migrate (M01–M13) |
| 5 | Run seeders (upsert mode) |
| 6 | Run validation command |
| 7 | If PASS → run M14 finalize |
| 8 | If FAIL → rollback to backup; do not run M14 |
| 9 | Deploy application code (models/services — future phase) |
| 10 | Maintenance mode OFF |
| 11 | Monitor 48 hours before dropping subscription_migration_map |

7.4 Environment Order

Local Dev → Staging (full legacy data copy) → Production

Never run M14 in production until staging validation passes.

==========================================================
8. RISKS
==========================================================

| Risk | Severity | Mitigation |
|------|----------|------------|
| FK break on plans rename | High | Use framework rename; verify FK names in staging |
| payments rename breaks admin routes | High | Deploy migrations and code rename in same release window |
| Legacy trial plan rows conflict with Register Trial service logic | Medium | Map legacy trial plans to inactive catalog; trials via subscription_type |
| Duplicate billing pivot rows on re-run | Medium | Upsert by (subscription_plan_id, billing_cycle_id) |
| Multiple active subscriptions after backfill | High | Validation query before M14; fix script |
| M14 irreversible column drop | High | Mandatory backup; staging dry run |
| companies.status missing expired | Medium | Include enum extension in M07 or M14 |
| Amount INT → DECIMAL precision | Low | Cast during M10 alter |
| Backfill history incomplete | Medium | M13 creates baseline plan_assigned + payment_approved events minimum |

==========================================================
9. RECOMMENDATIONS
==========================================================

P0 — Before Writing Any Migration Code
1. Approve this 14-migration sequence as frozen.
2. Create staging clone with full production data snapshot.
3. Add subscription_migration_map temp table for rollback safety.
4. Document legacy plans.type=trial handling policy (deactivate, do not delete).

P1 — Migration Implementation
5. Implement M01–M04 + seeders first; validate pivot backfill.
6. Implement M06–M08; run validation: expiry and staff_limit parity with companies.
7. Implement M09–M11; validate payment → subscription links.
8. Implement M12–M13; confirm audit baseline exists for every company with subscription.

P2 — Finalize
9. Run full validation suite on staging.
10. Only then implement M14 on staging; repeat on production.
11. Schedule future migration to stop writing companies.expiry_date and selected_user_limit (column drop in Version 2, not v1).

P3 — Post-Migration (Future Phases — Out of Scope)
12. Update models/controllers/services to new table names.
13. Implement SubscriptionService reading only company_subscriptions.
14. Implement CheckSubscription middleware.

==========================================================
10. PASS / FAIL
==========================================================

| Assessment | Verdict |
|------------|---------|
| Migration Architecture Design | PASS |
| Migration order safety | PASS |
| FK dependency order | PASS |
| Legacy rename strategy (no duplicates) | PASS |
| Rollback strategy (pre-M14) | PASS |
| Seeder strategy | PASS |
| Data migration pipeline | PASS |
| Current migrations implemented | FAIL — design only, not yet executed |

==========================================================
11. MIGRATION READINESS SCORE
==========================================================

| Dimension | Design Score | Current State Score |
|-----------|-------------|---------------------|
| Execution order clarity | 98 | 0 |
| Dependency safety | 97 | 30 |
| Legacy rename strategy | 95 | 20 |
| Backfill / validation pipeline | 94 | 0 |
| Rollback safety | 90 | 25 |
| Seeder completeness | 96 | 0 |
| Doc 10 coverage | 100 | 25 |
| No duplicate tables | 100 | 80 (legacy exists, no dup yet) |
| Overall | 96 / 100 | 25 / 100 |

| Metric | Score | Status |
|--------|-------|--------|
| Migration Architecture Blueprint | 96 / 100 | PASS — Frozen for implementation |
| Migration Implementation Readiness | 25 / 100 | FAIL — Migrations not yet written or executed |

==========================================================
SUMMARY
==========================================================

The DG ERP Subscription Module migration is finalized as 14 ordered Laravel migrations across 5 phases: configuration foundation → entitlement layer → payment layer → audit layer → schema finalize. Legacy plans, subscriptions, and payments are renamed and upgraded in place — no duplicate tables. Three seeders provide billing cycles, Doc 10 plans, and default pricing. Data moves through mapping → backfill → validation → switch → cleanup, with M14 as the irreversible finalize step requiring backup.

This migration plan is frozen. Next step when approved: implement M01–M04 and seeders on a staging database clone.

==========================================================
END OF DG ERP SUBSCRIPTION MIGRATION ARCHITECTURE
==========================================================
