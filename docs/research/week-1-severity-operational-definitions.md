# ANCHOR Severity Inputs: Operational Definitions (Draft)

**Status:** Prototype definitions for discussion and evaluation; not validated or approved by a legal or research partner.
**Scope:** Existing breadth/intensity behavior in `SeverityCombinationService`; legal weight is intentionally excluded until the legal-weighting method is approved.

## Breadth

Current implementation uses the number of submitted evidence items and the span between their non-null `timeline_at` timestamps:

- Add 20 points per evidence item.
- Add up to 40 points for elapsed timeline days.
- Cap the result at 100.
- If fewer than two evidence items have timeline timestamps, the elapsed span is 0.

Formula currently implemented: `min(100, evidence_count * 20 + min(40, timeline_span_days))`.

This is a prototype proxy for evidence/timeline breadth, not a measure of harm, truth, or legal strength. It currently treats every evidence item equally and does not deduplicate items or account for platform/source diversity. Validate these assumptions before research evaluation.

## Intensity

Current implementation reads the hostility pass `score`, converts it to an integer, clamps it to 0-100, and stores it as `intensity_score`. The stub returns 68, which is test data and not a calibrated score.

For a real scorer, the team must define what score anchors mean, what content is scored, how missing/invalid scores are handled, and how inter-rater agreement is measured. Hostility must not be presented as clinical distress or as a determination that a legal offense occurred.

## Current disagreement flag

The prototype compares the classification pass `severity_signal` (also clamped to 0-100) with the hostility score. `sync_flag` becomes true when the absolute difference is at least `ANCHOR_SEVERITY_DISAGREEMENT_THRESHOLD` (default 35). This is a review-prioritization signal only; neither input independently determines a legal conclusion.

## Pending validation

Before treating these definitions as research measures, record a rationale for the unit weights and caps, test representative and edge-case timelines, compare scoring against human-coded examples, and obtain supervisor/research-partner approval. Keep the thresholds versioned so results in the paper can be reproduced.

Legal weight, a final combined formula, and final tier boundaries remain blocked on the legal-partner methodology decision documented in `week-1-legal-weighting.md`.
