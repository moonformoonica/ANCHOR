# ANCHOR Severity-Weighting Decision Brief

**Status:** `PENDING_EXTERNAL_VALIDATION` — draft for review by the legal partner and the supervisor. Nothing in this document is an approved value, an approved formula, or a claimed validation result.

**Related documents:** [`week-1-legal-weighting.md`](week-1-legal-weighting.md) (questions for the legal partner), [`week-1-severity-operational-definitions.md`](week-1-severity-operational-definitions.md) (breadth/intensity definitions), [`week-1-corpus-change-log.md`](week-1-corpus-change-log.md) (data change record).

**Blocks:** FR-6 legal-weight contribution and FR-18 victim-facing explanation.

---

## 1. Purpose

This brief compares the two scale options raised for `legal_corpus_entries.severity_weight` so that the legal partner can choose between them on documented grounds rather than on implementation convenience. It records assumptions, benefits, risks, and technical consequences for each. It does not assign any weight to any provision.

Section 6 lists the questions that must be answered before implementation begins. Section 7 is the recommendation for the researcher to take into that discussion, not a decision.

## 2. What the system already does (verified against the code)

Verified in `app/Services/SeverityCombinationService.php` and covered by `tests/Feature/StateIntegrityTest.php`:

- `computed_severity` is produced by deterministic Laravel logic, not by a model. The formula is `round((breadth_score + intensity_score) / 2)` mapped onto `low` / `medium` / `high` / `critical` at 25 / 50 / 75.
- `breadth_score` derives from evidence count and timeline span. `intensity_score` derives from the hostility pass.
- **Legal weight does not participate in the tier at all.** The combined formula has no legal term yet.
- The AI-reported `severity_signal` inside the classification pass output is read **only** to compute `sync_flag`, which is a review-prioritization hint. It does not change `computed_severity`, `breadth_score`, or `intensity_score`. This satisfies the SRS requirement that the tier be a deterministic, non-AI result.

Residual methodological caveat, recorded rather than silently accepted: because `sync_flag` compares an AI self-reported number against a hostility score, the review-priority ordering of the prototype queue has an AI-derived input. This is acceptable under the SRS, which treats `sync_flag` as prioritization rather than as a severity measure, but it should be stated explicitly in the paper's methodology chapter. It is listed as a validation question in section 6.

## 3. Facts versus design recommendations

**Facts, taken from the Week 1 corpus baseline document and the attached source documents:**

- UU PDP Pasal 65 ayat (2) prohibits unlawfully disclosing another person's personal data; Pasal 67 ayat (2) attaches a maximum of 4 years' imprisonment and/or a fine up to Rp4,000,000,000.
- UU ITE Pasal 27A and Pasal 45 ayat (4) carry materially lighter statutory maxima than the UU PDP pair, and Pasal 27A is a complaint-based offense.
- POJK No. 40/2024 is the active instrument; POJK No. 10/2022 was revoked through its Pasal 236. Pasal 235 keeps earlier implementing provisions alive where they do not conflict.
- Putusan MK No. 105/PUU-XXII/2024 read "orang lain" in Pasal 27A as covering individuals only, not institutions.
- Post-2 January 2026, the transitional mapping for Pasal 27A is not stated explicitly in the KUHP Nasional transition table; the baseline records this as `under_review`.

**Design recommendations (mine, not the partner's, not validated):** everything in sections 4, 5, and 7 concerning which option is easier to operate, audit, or defend.

**Explicitly not established:** that a heavier statutory maximum means a heavier *case*; that a victim faces worse harm under one provision than another; any mapping between rupiah or years and a numeric score; any statement that a victim will or will not prevail under a provision.

## 4. Option A — Numeric scale, 0 to 100

### Definition

Each verified provision receives one numeric weight in `[0, 100]`, stored alongside the corpus entry and versioned.

### Assumptions this option requires

- The legal partner can construct a defensible, repeatable procedure that converts legal attributes into a single number.
- The resulting number is treated as a *legal-contribution* input, never as a probability of success and never as a direct measure of the victim's harm.
- Where a case cites several provisions, a combination rule is agreed in advance (for example, maximum, or a documented diminishing combination).

### Benefits

- Fits the existing arithmetic model directly: `breadth`, `intensity`, and a legal term can be combined on one scale without an extra mapping step.
- Gives the paper a continuous variable, which supports sensitivity analysis: reviewers can vary one component and observe the tier change, which is exactly the Week 2 test the roadmap asks for.
- Allows the contribution to be displayed transparently per component in `severity_scores`.

### Risks

- A single number invites the reader to treat it as a measurement. It is a judgment compressed into a decimal, and the paper must say so.
- Granularity creates false precision. "72" implies a resolution that a legal judgment probably does not possess.
- Without a written derivation procedure, values drift into intuition and become indefensible under review.
- Normalization choices (linear mapping of years to points, logarithmic, capped) materially change results and are themselves contestable.

### Technical consequences

- `severity_scores` gains a `legal_weight_contribution` column so the third component is inspectable independently.
- The combination formula must state the weighting of the three components explicitly rather than the current flat average of two.
- Unit tests must prove that changing only the legal weight changes only the documented contribution, and that missing legal weight degrades safely.

## 5. Option B — Ordinal categories

### Definition

Each verified provision receives one of `low`, `medium`, or `high`, stored alongside the corpus entry. If the scoring model requires a number, the category maps to a fixed documented band value at computation time, not at storage time.

### Assumptions this option requires

- The partner is comfortable stating criteria in categorical terms rather than graded numbers.
- Band boundaries are defined by legal attributes (offence type, statutory maximum, complaint status, administrative versus criminal), and those criteria are written down.
- A documented mapping from category to score band is acceptable for the paper's reproducibility requirement.

### Benefits

- Closest to ordinary legal reasoning, which compares provisions rather than grading them.
- Strongly resists false precision; a reviewer cannot claim the system distinguished two provisions the partner never distinguished.
- Easier to defend to a supervisor and to a legal panel, and easier to explain in an evaluation chapter.
- Cheaper to produce and to review than a full numeric derivation.

### Risks

- Coarse. Three categories may not separate the provisions that matter for this study, for example a complaint-based defamation provision from a coercion provision.
- Combining ordinal categories with two numeric components reintroduces an arbitrary mapping anyway, so the simplicity is partly cosmetic.
- Less useful for sensitivity analysis than a continuous variable.
- Band boundaries still require judgment, so the method's defensibility still depends on written criteria.

### Technical consequences

- `severity_scores` still needs a `legal_weight_contribution` column if the band value is to be inspectable, storing the resolved numeric band plus the originating category, so a reader can trace category to contribution.
- Missing category must resolve to "no legal contribution plus mandatory review", never to a default band.

## 6. Questions that must be answered before implementation

For the legal partner:

1. Ordinal or numeric? If numeric, what does each point mean, and what procedure produces it?
2. Which attributes are admissible for comparison: offence type, maximum imprisonment, maximum fine, administrative-only status, complaint status, or something else?
3. Should complaint-based status affect weight, given that a complaint offense imposes a different evidentiary burden on the victim than a non-complaint offense?
4. How should unlike sanctions be compared when one is administrative and one criminal?
5. How should multiple cited provisions be combined, and should overlapping citations be double counted?
6. What is the correct treatment for the post-2026-transition citations whose applicable numbering is still `under_review`?
7. What must happen when the cited provision has no approved weight: no contribution plus mandatory review, or a different conservative default?
8. Who approves a weight change, what source and version is recorded, and what evidence is required to freeze one?
9. Is a weight ever appropriate to describe victim-facing language at all, or should it remain internal to severity computation only?

For the supervisor:

1. Should the weighting methodology be validated as a separate study, or documented as a stated design decision with its limitations?
2. If numeric, is a sensitivity analysis acceptable as the defence, and what magnitude of sensitivity would be considered acceptable?
3. If ordinal, is three categories sufficient discrimination for the evaluation, given the provisions under study?
4. Should the `sync_flag` dependence on an AI-reported number be documented as a limitation, or redesigned before evaluation?
5. What evidence standard must exist before any entry leaves `under_review` and `severity_weight` stops being null?

## 7. Conceptual test set (illustrative only, no values assigned)

The following are proposed as the provisions used to test whichever method is approved. They are chosen for contrast, not ranked. **No weight, category, or expected tier is stated here, because none has been validated.**

| Provision | Why it is useful as a test case |
| --- | --- |
| UU PDP No. 27/2022, Pasal 65 ayat (2) jo. Pasal 67 ayat (2) | Current primary candidate. Carries the heaviest statutory maxima in the baseline and no complaint requirement was identified. Tests whether the method can express the lead candidate coherently. |
| UU ITE, Pasal 27A jo. Pasal 45 ayat (4) | Lighter statutory maxima and complaint-based. Tests discrimination between a non-complaint and a complaint offence. |
| UU ITE, Pasal 27B ayat (2) jo. Pasal 45 ayat (10) | The baseline marks this `weak_candidate_directional_mismatch`: the provision's coercion runs the opposite direction to the debt-shaming scenario. A good test of whether the method can represent a provision that is legally serious but doctrinally ill-fitting. |
| KUHP Nasional, Pasal 448 ayat (1) huruf b | Light statutory maximum but doctrinally closer fit. Tests whether a weight derived from penalties alone can express "lighter sanction, better fit", or whether it must incorporate fit. |
| POJK No. 40/2024, Pasal 161 ayat (1) huruf c jo. Pasal 164 ayat (1) | Administrative rather than criminal. Tests cross-regime comparison. |

Counterexample to prepare alongside them: a provision that is administratively heavy but criminally trivial, and a provision that is criminally heavy but administratively irrelevant. If the method cannot separate these coherently, the method is not ready.

## 8. Missing-value and safety rules (recommended, not yet approved)

These are engineering constraints rather than legal judgments, and they hold under either option:

- An entry whose status is not `active` contributes no legal weight.
- An entry with a null weight contributes no legal weight and sets a review-required flag. No default band, no mean imputation.
- Legal weight must never raise or lower a tier on its own without the breadth and intensity components being recorded alongside it, so a reviewer can see every contribution.
- Changing only the legal weight must change only the documented legal contribution, never `breadth_score` or `intensity_score`.
- Legal weight must never appear in victim-facing language as a statement of likely outcome.

## 9. Implementation options held for Week 2

Recorded here so the decision is ready to execute, not executed now:

- Add a nullable `severity_weight` column to `legal_corpus_entries`. Today the field lives only inside the `metadata` JSON column, so it is not queryable or indexable and cannot be constrained. Type and nullability depend on the scale decision.
- Add a nullable `legal_weight_contribution` column to `severity_scores`, per the SRS data requirements, so each component's contribution is inspectable.
- Replace the flat two-term average with an explicit three-term formula only after the scale is approved.

Both migrations are Week 2 roadmap items and were deliberately not run during Week 1.