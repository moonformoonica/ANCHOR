# ANCHOR Legal-Weighting Methodology Worksheet

**Status:** Draft for discussion; not approved for implementation or victim-facing use.
**Owner:** Researcher with the legal partner.
**Blocks:** Final `severity_weight` values, FR-6 legal contribution, and FR-18 explanation.

## Decision rule

Do not assign a numeric value or tier to a provision until the legal partner has reviewed the source text, applicability, and comparison method. Statutory maximum penalties are evidence to consider, not a direct measure of the seriousness of an individual case or its likely outcome.

## Questions to resolve with the legal partner

1. Should the stored weight be ordinal (`low`/`medium`/`high`) or numeric? If numeric, define the scale and what each point means.
2. Which legal attributes are admissible: offense type, maximum imprisonment, maximum fine, administrative-only status, complaint offense, or other factors?
3. How should unlike sanctions be compared, and how should overlapping or alternative citations be combined?
4. What should happen when the cited provision is `under_review`, superseded, or has no approved weight? The safe initial behavior is no legal contribution and mandatory review, not an inferred default.
5. Who approves changes, what source/version is recorded, and what evidence is required to freeze a weight?

## Candidate comparison set

Use the corpus candidates below to test whether the agreed method produces defensible and internally consistent results. These are not pre-assigned weights.

| Candidate | Current research note | Partner decision / rationale |
|---|---|---|
| UU PDP No. 27/2022, Pasal 65 ayat (2) jo. Pasal 67 ayat (2) | Candidate primary basis; verify elements and enforcement context | Pending |
| UU ITE, Pasal 29 jo. Pasal 45B | Direct-threat candidate | Pending |
| UU ITE, Pasal 27A jo. Pasal 45 ayat (4) | Post-2026 transition and replacement citation require confirmation | Pending |
| KUHP Nasional, Pasal 433 jo. Pasal 441 ayat (1) | Candidate electronic-defamation treatment; expert confirmation required | Pending |
| KUHP Nasional, Pasal 448 ayat (1) huruf b | Exploratory candidate; examine full explanation and elements | Pending |
| POJK No. 40/2024, Pasal 161 ayat (1) huruf c jo. Pasal 164 ayat (1) | Administrative-routing candidate; confirm applicability | Pending |

## Validation and change record

Before implementation, record the agreed scale, formula, worked examples for at least three provisions, counterexamples, reviewer sign-off, and version/date. Check that the method does not imply case outcome certainty and that changing only the approved legal weight changes only the documented FR-6 contribution. Keep unverified entries out of automatic weight calculations. Record each later change alongside the corpus version and rationale.

The `severity_weight` storage type and combination formula are intentionally deferred until the scale decision above is approved.