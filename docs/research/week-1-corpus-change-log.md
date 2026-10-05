# Week 1 Corpus and Institution Change Log

Record each change to `legal_corpus_entries` and `referral_institutions` with its date, data version, reason, and review state. Legal candidates stay non-active until approved by the legal partner. Institution entries stay non-active until their scope and official contact channel are verified.

## 2026-10-05 — Week 1 baseline v0.2 alignment

**Sources:** `ANCHOR-Korpus-Baseline-Minggu1.md` v0.2; `ANCHOR-SRS.md` v0.3; `ANCHOR-Roadmap-Detailed.md` v0.3.

### Legal corpus

- Added UU ITE Pasal 26 ayat (1) as an `under_review` candidate because the SRS traceability section names it as a classification source. No legal effect or severity weight is approved by this entry.
- Existing UU ITE Pasal 27 ayat (3) entries using the pre-2024 numbering are marked `superseded`; their prior metadata is retained. This does not assert a confirmed replacement citation for post-2026 cases.
- Week 1 legal candidates retain `under_review`, null `last_verified_date`, and null `severity_weight` pending legal-partner review and an approved weighting method.
- The classification stub now emits no legal citation and uses an unclassified placeholder while no approved active corpus source is available.

### Referral institutions

- No institution was promoted to `active` or given a verified contact channel. Seeded baseline institutions remain `needs_verification` with null `last_verified_date` and null contact details pending validation.
- Admin CRUD and per-change audit for referral institutions remain roadmap work for Week 2 (FR-17); record each change here when that management path is introduced.

### Review state

Legal partner sign-off: pending. Supervisor sign-off: pending. Do not use these candidates as finalized legal advice or severity weights.
