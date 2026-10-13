# Reward Gate Analytics

## Purpose

Define the scope, behavior, implementation sequence, and acceptance
criteria for the first analytics release.

This document is the implementation plan for analytics.
`SPECIFICATION.md` remains the source of truth for overall product
requirements. Update this document as implementation decisions change;
do not duplicate unrelated project documentation.

## Status

-   Planning: decisions approved for the initial implementation.
-   Implementation: not started.
-   Release scope: basic campaign-level analytics only.

## 1. Scope

### Included

-   Record a gate impression after the gate UI has rendered
    successfully.
-   Deduplicate impressions for the same campaign, visitor, and page
    view.
-   Display campaign-level counts for the last 7 and 30 UTC calendar
    days.
-   Display Shown, Sessions, Completed, and Unlock rate.
-   Show a Timer-specific Finished wait rate.
-   Keep analytics collection non-blocking so failures do not interfere
    with unlocking.
-   Add the database index needed for campaign/date-range session
    queries.
-   Add automated tests for recording, deduplication, reporting, and
    failure behavior.

### Explicitly excluded

-   Unique-visitor metrics or claims that event counts represent people.
-   Click-through rate in this release.
-   Revenue metrics and external revenue integrations.
-   Verification-failure logging.
-   Event logs, rollup tables, scheduled aggregation, or analytics
    infrastructure.
-   A/B testing, recommendations, attribution, geographic/device
    segmentation, or advanced dashboards.
-   Method-specific historical comparisons of sessions and completions.
-   A `page_view_id` column on `unlock_sessions`. Revisit only if a
    genuine click-through metric is later required.

## 2. Metric definitions

All metrics are event counts, not unique people.

### Reporting periods

Use UTC calendar days, consistent with the application's configured UTC
timezone.

-   **Last 7 days:** from 00:00 UTC six calendar days before today,
    inclusive, through 00:00 UTC tomorrow, exclusive.
-   **Last 30 days:** from 00:00 UTC 29 calendar days before today,
    inclusive, through 00:00 UTC tomorrow, exclusive.

Use half-open intervals:
`timestamp >= start_utc AND timestamp < end_utc`.

### Shown

Count `gate_impressions` rows whose `created_at` falls within the
selected period.

An impression means the gate UI rendered successfully. It does not prove
that a person noticed or read it.

### Sessions

Count accepted `unlock_sessions` rows whose `started_at` falls within
the selected period, regardless of session status.

A session is counted once it has been accepted and inserted. Repeated
attempts can create multiple sessions.

### Completed

Count `unlock_completions` rows whose associated session has
`started_at` within the selected period.

Join completions to `unlock_sessions` through `unlock_session_id`, and
apply the date filter to the session's `started_at`. Do not
independently filter these completion rows by `completed_at` for this
metric.

This keeps Completed and Sessions in the same cohort, so Completed
cannot exceed Sessions under the existing one-completion-per-session
constraint.

### Unlock rate

`Completed / Shown * 100`

Display a percentage only when:

-   Shown is at least 20;
-   Shown is greater than zero; and
-   Completed is not greater than Shown.

Otherwise display `—`. Continue to show the underlying counts.

The minimum of 20 impressions is a conservative product display rule,
not a claim of statistical significance.

Because an impression request can be lost while a session is still
recorded, Completed can occasionally exceed Shown. In that case,
suppress the rate rather than display a misleading percentage.

### Timer detail: Finished wait rate

For Timer campaigns:

`Completed / Sessions * 100`

Use the same reporting cohort: sessions started during the selected
period and completions associated with those sessions. If Sessions is
zero, display `—`.

This measures the share of accepted timer sessions that completed. It is
not an engagement measure because Timer sessions start automatically.

### Click campaigns

Do not show a click-through percentage in this release.
`Sessions / Shown` is not a valid click-through rate because one
displayed gate can generate multiple sessions through retries or
repeated attempts.

Show the counts without relabeling that ratio as click-through.

## 3. Data model

### New table: `gate_impressions`

Use the following schema, adjusting naming only if required to match
established project conventions:

``` sql
CREATE TABLE gate_impressions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    campaign_id BIGINT UNSIGNED NOT NULL,
    visitor_id CHAR(64) NOT NULL,
    page_view_id CHAR(36) NOT NULL,
    presentation_type VARCHAR(32) NOT NULL,
    unlock_method VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE INDEX uq_gate_impressions_page_view
        (campaign_id, visitor_id, page_view_id),
    INDEX idx_gate_impressions_campaign_created
        (campaign_id, created_at),
    CONSTRAINT fk_gate_impressions_campaign
        FOREIGN KEY (campaign_id)
        REFERENCES campaigns (id)
        ON DELETE RESTRICT
) ENGINE=InnoDB
  DEFAULT CHARACTER SET=utf8mb4
  COLLATE=utf8mb4_unicode_ci;
```

Requirements:

-   `visitor_id` must come from the existing server-side `VisitorId`
    implementation. Do not trust a visitor ID supplied by JavaScript.
-   `page_view_id` is a hyphenated UUID generated once per document and
    stored on `window`.
-   Reuse the same page-view ID for repeated initialization within a
    document, including a restored document. A fresh document load gets
    a fresh ID.
-   Reject a missing or malformed page-view ID. Do not silently generate
    a replacement on the server for invalid input, because a retry would
    bypass deduplication.
-   Set `created_at` on the server. Ignore client timestamps.
-   Snapshot `presentation_type` and `unlock_method` from the
    configuration used to render the gate. Do not derive historical
    values from the campaign's current configuration.
-   Use `ON DELETE RESTRICT`, consistent with existing foreign-key
    behavior. Do not cascade-delete impressions.

A duplicate insert caused by the expected unique-key collision must be
treated as a successful, already-recorded impression. Do not mask
unrelated database errors as duplicates.

### Existing table index

Add the following index to `unlock_sessions`:

``` sql
INDEX idx_unlock_sessions_campaign_started (campaign_id, started_at)
```

Do not remove the existing single-column `(campaign_id)` index in this
change. Consider index cleanup separately after reviewing actual query
plans and usage.

Do not add a `(campaign_id, completed_at)` index for this release. The
Completed metric filters the associated session cohort and finds
completion rows through the existing unique index on
`unlock_session_id`.

## 4. Collection behavior

1.  Render the gate UI successfully.
2.  Trigger impression registration asynchronously.
3.  Continue the existing unlock flow without waiting for impression
    registration to finish.
4.  Ignore impression-request failures for gate functionality. They must
    not prevent Timer `/start`, arm/disarm click handling, or otherwise
    block unlocking.
5.  Do not record an impression merely because campaign configuration
    was loaded, `/status` was checked, a timer started, a CTA was
    clicked, or a retry occurred.
6.  If `/status` indicates the visitor is already unlocked and no gate
    UI is shown, do not record an impression.
7.  A new page load that renders the gate is a new impression. A second
    initialization in the same document is deduplicated.
8.  Use the existing server-side visitor cookie behavior. Do not add
    fingerprinting or attempt to make the client-generated page-view ID
    tamper-proof.

## 5. Admin report

Add a simple campaign analytics view using the existing admin UI
patterns.

For each selected campaign and reporting period, show:

-   Shown
-   Sessions
-   Completed
-   Unlock rate

Provide the last 7 days and last 30 days views. Include the Timer
Finished wait rate in the Timer-specific detail area.

Use existing admin authentication and authorization patterns. Escape
output as required by established conventions. Do not introduce a new
frontend framework, reporting framework, or generic analytics
abstraction.

The report must explain, in concise UI copy or a tooltip, that values
are event counts and impressions represent successfully rendered gate
UI, not confirmed human attention.

Do not show click-through rate, unique visitors, revenue, or
method-specific historical session/completion comparisons.

## 6. Implementation plan

Complete each phase in order. Inspect existing code and tests before
editing, and keep changes limited to the current phase.

### Phase 1: Database migration

-   Add the `gate_impressions` table.
-   Add the composite `(campaign_id, started_at)` index to
    `unlock_sessions`.
-   Follow the project's existing migration/schema update conventions.
-   Verify compatibility with both MySQL 8 and MariaDB.
-   Add or update schema tests if the project has them.

**Done when:** a clean database can be initialized with the new schema,
and existing schema setup remains compatible with supported database
engines.

### Phase 2: Impression recording endpoint and persistence

-   Inspect existing routing, request validation, repository, visitor
    identity, and error-response conventions.
-   Add the smallest endpoint and persistence implementation consistent
    with the current architecture.
-   Validate campaign existence/state and the page-view UUID according
    to established endpoint patterns.
-   Get visitor identity through the existing server-side `VisitorId`
    source.
-   Snapshot the presentation type and unlock method used for the
    impression.
-   Treat only the expected unique-key duplicate as successful
    deduplication.
-   Keep other failures distinguishable in server-side diagnostics
    without exposing database details to the public response.

**Done when:** valid impressions are stored, repeated submissions for
the same key do not create duplicate rows, and invalid input or
unrelated persistence errors are handled correctly.

### Phase 3: Frontend impression registration

-   Generate one UUID per document and retain it on `window`.
-   Register an impression only after the gate UI has rendered
    successfully.
-   Ensure repeated initialization reuses the same page-view ID.
-   Make the request non-blocking and ensure failures do not interfere
    with the existing unlock flow.
-   Do not record an impression when no gate is shown.

**Done when:** Popup + Timer, Popup + Click, and Content + Click record
impressions under the intended conditions, while analytics endpoint
failure does not prevent normal gate behavior.

### Phase 4: Reporting queries and metric calculations

-   Implement campaign/date-range counts using half-open UTC intervals.
-   Filter Sessions by `unlock_sessions.started_at`.
-   Filter Completed through the associated sessions' `started_at`, not
    completion timestamps.
-   Implement the rate display rules and Timer Finished wait rate.
-   Keep queries parameterized and consistent with existing repository
    conventions.

**Done when:** the report uses consistent cohorts, handles empty
periods, and never displays a rate when its defined display conditions
are not met.

### Phase 5: Admin UI

-   Add the report to the existing admin navigation and campaign UI
    using current project patterns.
-   Show the 7-day and 30-day counts and rates.
-   Show Finished wait rate for Timer campaigns only.
-   Include concise metric definitions and avoid implying that event
    counts equal unique people.

**Done when:** an administrator can view understandable analytics for a
campaign without needing to interpret raw database records.

### Phase 6: Automated tests and regression checks

Test at minimum:

-   Valid impression creates one row.
-   Repeating the same campaign/visitor/page-view key creates no
    additional row and is treated as success.
-   A new page-view ID creates a new impression.
-   Missing or malformed page-view IDs are rejected.
-   Client-supplied visitor IDs and timestamps are not trusted.
-   A visitor already unlocked who sees no gate does not generate an
    impression.
-   An impression request failure does not block Timer start or Click
    interaction.
-   Sessions in the range include active, expired, and completed
    statuses.
-   A completion is counted according to its session's `started_at`,
    including when `completed_at` falls outside the selected range.
-   Empty periods and zero denominators display `—`.
-   Rates are hidden when Shown is below 20 or Completed exceeds Shown.
-   Timer Finished wait rate uses the same session cohort.
-   Existing unlock, frequency-limit, security, and campaign behavior
    continues to pass.

**Done when:** all relevant new tests pass and the full existing test
suite passes.

## 7. Known limitations

-   Counts are events, not unique visitors.
-   Browser blocking, network failures, or an analytics endpoint outage
    can cause impressions to be undercounted.
-   The page-view ID is a deduplication mechanism, not a security
    boundary.
-   Historical presentation and unlock method are preserved on
    impressions, but not on existing session/completion records. Do not
    present method-specific historical session/completion rates.
-   Changing campaign configuration can make comparisons across periods
    harder to interpret. Duplicating a campaign for a materially
    different setup is a possible operational workaround, not an
    enforced requirement.
-   No revenue attribution or advanced analytics is provided in this
    release.

## 8. Release acceptance checklist

-   [ ] Database migration/schema update works on MySQL 8 and MariaDB.
-   [ ] Impression rows are created only after successful gate
    rendering.
-   [ ] Same-document duplicate initialization is deduplicated.
-   [ ] Analytics failures never block gate functionality.
-   [ ] Reporting date boundaries use UTC and half-open intervals.
-   [ ] Completed is calculated through sessions filtered by
    `started_at`.
-   [ ] Rates follow the documented display rules.
-   [ ] Timer Finished wait rate is shown only where applicable.
-   [ ] Admin UI does not claim unique visitors or click-through rate.
-   [ ] New tests and the full regression suite pass.
-   [ ] `ANALYTICS.md` status and any implementation deviations are
    updated before release.
