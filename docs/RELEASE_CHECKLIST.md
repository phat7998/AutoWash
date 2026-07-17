# AUTO WASH PRO — RELEASE CHECKLIST

## Source và requirement

- [x] Slice 00–14 đã Complete; Slice 15 không thêm domain lớn hoặc đổi decision/schema.
- [x] Toàn bộ requirement MUST có code + automated test hoặc demo evidence và RTM `Done`.
- [x] OPTIONAL survey/ML/paper giữ `Deferred bonus work`; không có kết quả giả.
- [x] `git diff --check`, senior diff review, TODO/debug/dead/secret scan.

## Security và business invariant

- [x] Prepared statement/native PDO/utf8mb4; Controller/View không có SQL/formula.
- [x] Output escape, CSRF global, session regenerate/invalidate, backend RBAC/ownership.
- [x] Upload MIME/size/random/outside-public; production error không lộ stack/secret.
- [x] Price/point/tier/reward/promotion tải lại từ DB.
- [x] Slot race, duplicate completion, concurrent redeem/adjust/promotion, expiry và review idempotency pass.
- [x] Service/price, tier/perk, promotion, point adjustment và booking exception có audit phù hợp.

## Verification

- [x] `composer validate --strict`.
- [x] PHP 8.2/MySQL 8 `AUTOWASH_DB_TESTS=1 composer check`, không skip.
- [x] Fresh `database/reset.php --force --seed`; migration rerun và seed rerun.
- [x] Loyalty reconcile, expiry/review idempotency, synthetic/export privacy checks.
- [x] HTTP smoke customer/admin và route/security matrix.
- [x] NFR-02 workload 10.000 booking/20 VU đạt mọi P95 và error rate.
- [x] Responsive CSS breakpoint/focus/table overflow và màn hình chính được review desktop/mobile cơ bản.

## Artifact và phát hành

- [x] README clean setup, demo account, route, CLI và performance command.
- [x] `DEMO_SCRIPT`, `DEFENSE_QA` đúng 30 câu, `KNOWN_LIMITATIONS`, performance report.
- [x] Không commit `.env`, runtime log/upload/research/performance output.
- [x] Commit Slice 15 trên `main`; không push trong session nếu chưa được yêu cầu.
- [ ] Nhóm review commit và chạy demo trên máy trình bày.
- [ ] Sau review, tạo annotated tag đề xuất: `v1.0.0-defense`.
- [ ] Push commit/tag chỉ khi nhóm cho phép.

Lệnh tag đề xuất sau khi được duyệt:

```bash
git tag -a v1.0.0-defense -m "Phát hành bản bảo vệ AutoWash Pro"
git push origin main
git push origin v1.0.0-defense
```
