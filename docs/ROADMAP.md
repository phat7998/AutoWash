# AUTO WASH PRO — ROADMAP SLICE 00B–15

> Baseline sau Mini-Slice 00B Closure Patch. Mục tiêu cuối là hệ thống hoàn chỉnh trong phạm vi nộp đồ án, không phải prototype sơ sài.

| Slice | Mục tiêu/phạm vi | Requirement/decision chính | Phụ thuộc | Exit evidence tối thiểu |
|---:|---|---|---|---|
| 00 | Audit requirement, RTM, ERD, test plan | Toàn bộ baseline | — | Hoàn tất 75/75 trace ban đầu |
| 00B | Khóa architecture/data/research/NFR, closure decisions và Design System | DEC-012, DEC-015..033 | 00 | Docs đồng bộ; ERD/test/trace check; closure patch; không code |
| 01 | Composer/env/structure/lint, Dotenv, PHPUnit, Docker nếu dùng | NFR-04/25/26, DEC-012 | 00B Done | Autoload smoke test; env an toàn; chưa business/UI |
| 02 | PDO, migration runner, schema/constraints và seed cấu hình | ERD 00B, NFR-08/10/11 | 01 | Migrate/seed repeatable; 4 types/tier/reward config |
| 03 | Front Controller, router, request/response/view, session/CSRF/error | NFR-03/12..14/18 | 01–02; đọc Design System | Core/security tests; error/empty foundation |
| 04 | Authentication và role authorization | AUTH-01..04, NFR-15 | 03 | Auth/security tests; UI theo Design System |
| 05 | Vehicle type/vehicle CRUD, shared plate validator/ownership, manual input | VEH-01..04, LPR-01, DEC-031 | 04 | Bốn type, civilian plate scope, inactive/duplicate/IDOR tests |
| 06 | Service catalog, service-vehicle pricing, slot capacity units | CAT-01/02, SLOT-01, ADM-02/03 | 05 | Unique pair, unsupported cases, capacity fixture |
| 07 | Booking create, multi-service/multi-slot reservations, price/window/capacity race | BKG-01..03/07, SLOT-02, DEC-017 | 06 | Duration sum, capacity max, overlap holds, tamper/concurrency/rollback |
| 08 | Lifecycle, cancellation cutoff 2h, history, completion hook | BKG-04..06 partial, DEC-021 | 07 | Cancellation boundary/capacity/history tests |
| 09 | Loyalty ledger, adjustment rule, allocation foundation, earn/atomic completion | LOY-01/02, ADM-06, DEC-018/020/032 | 08 | Formula, adjust reject/no-clamp, idempotent completion, reconcile |
| 10 | Generic credit lots, reward redemption, FEFO allocations, calendar-clamp expiry | LOY-02..04, RWD-01/02/04, ADM-04, DEC-005/018/032 | 09 | Migration/backfill, credit/debit invariant, leap-day, multi-lot, rerun, concurrency |
| 11 | Monthly tier review/history | TIER-01..04, DEC-003/019 | 09–10 | AND/upgrade/downgrade/hold/history/rerun |
| 12 | Tier/perk/promotion/reward checkout integration | BKG-03, RWD-03, PRO-01..05, ADM-01/04/05/06 | 10–11 | Tier/service/type eligibility, limits, snapshots |
| 13 | Safe upload, `LprProviderInterface`, mock/external adapter, fallback | LPR-02, NFR-16, DEC-024 | 05 | Success/error/low-confidence/manual/security tests |
| 14 | Research events, ≥2k synthetic, CSV/data dictionary, dashboards | RBL-01..05, REP-01/02, DEC-025/033 | 09–13; checkpoint Q-020 trước deep Research/RBL | No-PII, four types, deterministic/idempotent export; không bịa external deliverable |
| 15 | Security/business/performance audit, demo/release | Toàn bộ MUST, DEC-026/028 | 01–14 | Full suite; 10k/20VU report; clean setup; defense docs |

## Gating bắt buộc

1. Đọc specification, RTM, decisions, assumptions, status, README và Git status/log.
2. Trước mọi UI, đọc `DESIGN_SYSTEM.md`; không tạo token/component riêng theo session.
3. Xác nhận dependency có evidence thật.
4. Nêu requirement, acceptance, files, schema/transaction/authorization/test dự kiến.
5. Không đổi schema/business/design system âm thầm.

## Điểm cần xác nhận còn lại

- Q-016..Q-019 đã đóng bằng DEC-017/005/031/032 và không còn gate kỹ thuật.
- Q-020: **External academic deliverable — Pending lecturer confirmation**. Không chặn Slice 01; checkpoint trước Slice 14 để xác nhận survey/ML/paper, cách chấm riêng và quy mô dataset.

## Cắt scope khi thiếu thời gian

Không cắt Auth/RBAC, ownership, service/type pricing, capacity units, booking/concurrency, completed idempotency, ledger/allocation/FEFO/expiry, tier review, Silver+, research export, tests/demo. Có thể không tích hợp external LPR thật nhưng phải giữ provider pattern + mock + manual fallback. AI personalization, SMS thật, animation/chart nâng cao, multi-branch và real-time vẫn ngoài core.
