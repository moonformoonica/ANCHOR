# Changelog

All notable changes to ANCHOR are recorded here. Entries state what was actually
done and verified, not what is planned.

Legal and institutional data changes are additionally recorded in
[docs/research/week-1-corpus-change-log.md](docs/research/week-1-corpus-change-log.md),
as required by SRS Section 9.1.

Status vocabulary used in this file:

- `PASS` — executed and verified, evidence available.
- `BLOCKED` — cannot proceed safely in the current environment.
- `PENDING_EXTERNAL_VALIDATION` — requires a human decision from the legal
  partner or the supervisor before it can be completed.
- `NOT_IN_SCOPE` — deliberately deferred to a later roadmap milestone.

---

## [Unreleased] — Week 1 backend hardening and finalization

Date: 2026-10-13
Scope: Week 1 backend foundation only. No Week 2 AI integration, no frontend,
no deployment work was started.

### Fixed

- **Queue worker could target the production database** (`docker-compose.yml`).
  The `worker` service had no `environment:` block, so it inherited the host
  `.env`, whose `DB_HOST` points at the Supabase project. The worker exited with
  `SQLSTATE[08006] could not translate host name`, and because
  `docker compose ps` omits exited containers by default, the failure was
  invisible in the default status output. The worker now receives the same local
  connection block as `app`, and connects to `postgres`. Verified with
  `RestartCount=0`, status `running`, and an empty (idle, non-crashing) log.

- **`SeverityCombinationService::recompute()` overwrote workflow status**
  (`app/Services/SeverityCombinationService.php`). The method ended with
  `$case->update(['status' => 'pending_review'])`. Because
  `CaseProcessingService::record()` also calls `recompute()`, storing a single AI
  pass could move a case that a reviewer had already approved, edited, or handed
  to manual handling back into the review queue. For an approved case this also
  removed the victim's `final_output`, because `CaseOutputService` returns null
  for any status other than `approved` or `edited`.

  The defect was latent rather than constant: Eloquent skips the UPDATE
  statement when the attribute is not dirty, so a stale in-memory model instance
  masked it. It reproduced reliably only with a freshly loaded model, which is
  exactly how the controllers invoke these services through route model binding.
  The primary production route was `POST /api/v1/internal/cases/{case}/ai-pass-result`.

  `recompute()` is now pure derivation: it reads AI pass results and evidence,
  persists a `SeverityScore`, and never touches case status. The
  `pending_review` transition is now owned explicitly by
  `CaseProcessingService::process()`, which is only reached from an explicit
  actor action (victim intake, victim submitting evidence, or reviewer-requested
  reclassification). Reviewer decisions remain owned solely by
  `ReviewStateMachine`.

  Behaviour deliberately unchanged: reprocessing after a victim submits more
  evidence still requeues the case, and the reclassification path still forces
  `reclassify_pending` after reprocessing.

- **`db:seed` was not re-runnable** (`database/seeders/DatabaseSeeder.php`).
  The demo admin account was created with an unconditional
  `User::factory()->create()`, so the second run failed with
  `SQLSTATE[23505] ... users_email_unique`. The corpus and institution seeders
  already used `updateOrCreate` and were idempotent; the user creation was the
  only blocker. It now uses `User::firstOrCreate()` with attributes drawn from
  the factory, preserving the factory's password and verification defaults while
  removing the duplicate-row failure. Verified by seeding four times in a row
  against the local container database with no duplication.

### Added

- `tests/Feature/StateIntegrityTest.php` — 9 tests covering the workflow/status
  separation: recompute on a `pending_review` case, recording an AI pass against
  an `approved` case, the internal AI endpoint against both an `approved` case
  and a `manual_handling` case, victim-facing output visibility after a late AI
  pass, `reclassify_pending` stability, repeated-recompute idempotency,
  deterministic tier independence from AI-reported severity signal, evidence
  requeue behaviour, and the state machine still refusing out-of-turn reviews.
  Verified failing before the patch (5 failures) and passing after.

- `tests/Feature/BaselineDataIntegrityTest.php` — 6 tests guarding the Week 1
  baseline: exact entry counts, no corpus entry promoted to `active`, every
  `severity_weight` still null, `last_verified_date` still null on every entry,
  all four institutions still `needs_verification` with no contact channel,
  `review_note`/`rationale`/`notes` retained, double-seeding creating no
  duplicates, and legacy pre-2024 ITE numbering superseded with its prior
  metadata preserved.

- `docs/research/week-1-severity-weighting-decision-brief.md` — written draft
  comparing a numeric 0–100 scale against ordinal low/medium/high categories,
  with assumptions, benefits, risks, technical consequences, a fact-versus-
  recommendation split, validation questions for the legal partner and the
  supervisor, and five candidate provisions for conceptual testing. **No weight,
  category, formula, or validation result is assigned.**

### Verified

| Item | Evidence |
| --- | --- |
| Clean image build | `docker compose build --no-cache` succeeded for `app` and `worker` |
| App service healthy | `docker compose ps` reports `Up (healthy)` |
| PostgreSQL healthy | `docker compose ps` reports `Up (healthy)` |
| Worker running | `RestartCount=0`, `Status=running`, idle log with no crash |
| Health endpoint | `curl.exe -i http://localhost:8000/up` returned `HTTP/1.1 200 OK` |
| Container database target | `DB_HOST=postgres`, `DB_DATABASE=anchor` for both `app` and `worker` |
| Test database isolation | `phpunit.xml` forces `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` |
| Migrations | all 10 migrations `Ran` on the local container database |
| Corpus baseline | 13 entries, all `under_review`, all `last_verified_date` null, all `severity_weight` null, all carrying `review_note` and `baseline=week_1_v0.2` |
| Institutions baseline | 4 entries, all `needs_verification`, all `contact_channel` null, all `last_verified_date` null |
| Seeder reproducibility | four consecutive `db:seed` runs produced identical counts with no duplication |
| Full test suite | 26 tests / 169 assertions passed |

Test count history for this repository, so that earlier figures are not
mistaken for current state: before this work the suite was **11 tests / 73
assertions**. A previously reported figure of "26 tests / 123 assertions" does
not match any commit in the history. The test count now happens to be 26, but
the assertion count is 169; the two figures are unrelated and the earlier
number should not be cited as a baseline.

### Corrected documentation

- `doxing-support-system-project-context.txt` and
  `prosiding-scopus-personal-notes.md` (both outside this repository) referred to
  `POJK No. 10/POJK.05/2022` — a malformed merge of two instruments — and
  presented it as the direct legal basis for classifying debt-shaming as doxing.
  Corrected to POJK No. 40/2024 as the active instrument, with the revocation of
  POJK No. 10/2022 through its Pasal 236 and the survival of earlier implementing
  provisions under Pasal 235. The claim that a POJK provision alone classifies
  all debt-shaming as doxing was removed; UU PDP Pasal 65 ayat (2) jo. Pasal 67
  ayat (2) remains the primary candidate basis, still `under_review`.

### PENDING_EXTERNAL_VALIDATION

- `severity_weight` scale and methodology. All 13 corpus entries keep a null
  weight. The score storage type and the combination formula are deliberately
  undecided pending the legal partner's decision between the numeric and ordinal
  options set out in the decision brief. **FR-6 legal weighting and FR-18 are not
  functionally complete.**
- Breadth and intensity operational definitions remain prototype values awaiting
  research-partner approval.
- Referral institution scopes, official contact channels, and the AFPI "Kebijakan
  Penagihan" document remain unverified.

### NOT_IN_SCOPE (Week 2+)

- Real Voyage AI / Claude API integration; all three AI passes remain stubs bound
  in `AppServiceProvider`.
- Corpus chunking and embedding pipeline.
- Final severity formula and the `severity_weight` / `legal_weight_contribution`
  schema migrations.
- FR-18 victim-facing explanation generation and its exposure gating.
- FR-17 admin CRUD and audit for `referral_institutions`; the table, baseline
  entries, and `rationale` field exist, but there is no controller, route, or
  policy for it, so per-institution audit logging cannot yet be recorded.
- React frontend.
- Deployment or any Supabase migration.