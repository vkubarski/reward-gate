# Reward Gate Analytics

## Purpose

Define the scope, behavior, implementation sequence, and acceptance criteria for the first analytics release.

This document is the implementation plan for analytics. `SPECIFICATION.md` remains the source of truth for overall product requirements. Update this document as implementation decisions change; do not duplicate unrelated project documentation.

## Status

- Planning: complete.
- Implementation: implemented.
- Automated tests: passing (218 tests, 1,009 assertions).
- Final acceptance review: in progress.
- Target release: v0.4.0.
- Release scope: basic campaign-level analytics only.

## 1. Scope

### Included

- Record a gate impression after the gate UI has rendered successfully.
- Deduplicate impressions for the same campaign, visitor, and page view.
- Display campaign-level counts for the last 7 and 30 UTC calendar days.
- Display Shown, Sessions, Completed, and Unlock rate.
- Show a Timer-specific Finished wait rate when existing session data supports reliable identification of Timer sessions.
- Keep analytics collection non-blocking so failures do not interfere with unlocking.
- Add the database index needed for campaign/date-range session queries.
- Add automated tests for recording, deduplication, reporting, and failure behavior.
- Display the actual UTC date range used for each report.

### Explicitly excluded

- Unique-visitor metrics or claims that event counts represent people.
- Click-through rate in this release.
- Revenue metrics and external revenue integrations.
- Verification-failure logging.
- Event logs, rollup tables, scheduled aggregation, or analytics infrastructure.
- A/B testing, recommendations, attribution, geographic/device segmentation, or advanced dashboards.
- Method-specific historical comparisons of sessions and completions, except for a Finished wait rate when historical Timer sessions can be identified reliably from stored session data.
- A `page_view_id` column on `unlock_sessions`. Revisit only if a genuine click-through metric is later required.
- A new general-purpose rate-limiting subsystem solely for analytics.

## 2. Metric definitions

All metrics are event counts, not unique people.

### Reporting periods

Use UTC calendar days, consistent with the application's existing timezone configuration.

- **Last 7 days:** from 00:00 UTC six calendar days before today, inclusive, through 00:00 UTC tomorrow, exclusive.
- **Last 30 days:** from 00:00 UTC 29 calendar days before today, inclusive, through 00:00 UTC tomorrow, exclusive.

Use half-open intervals:

`timestamp >= start_utc AND timestamp < end_utc`

Display the actual inclusive calendar dates covered by the report, explicitly labeled UTC. For example: `October 7–13, UTC`.

The report service must use an explicit reference time rather than reading the system clock internally. Pass a `DateTimeImmutable` reference time into the reporting calculation so date boundaries can be tested deterministically.

### Timezone handling

Reuse the existing timezone configuration:

- `bootstrap.php` calls `date_default_timezone_set('UTC')`.
- `Connection::create()` executes `SET time_zone = '+00:00'` for each database connection.
- Analytics reporting boundaries must be calculated in UTC.
- Database timestamps must be interpreted consistently as UTC.
- When displaying timestamps in the browser, convert UTC values to local time using the existing frontend conventions. Reporting-period labels must remain explicitly labeled UTC.

Do not introduce another timezone configuration mechanism.

MySQL `DATETIME` does not store timezone metadata. The UTC convention is established by the application and database session configuration, so tests must verify that the existing setup is used correctly.

### Shown

Count `gate_impressions` rows whose `created_at` falls within the selected period.

An impression means the gate UI rendered successfully. It does not prove that a person noticed or read it.

### Sessions

Count accepted `unlock_sessions` rows whose session-start timestamp falls within the selected period, regardless of session status.

A session is counted once it has been accepted and inserted. Repeated attempts can create multiple sessions.

Before implementing reporting, verify that the existing session-start timestamp is non-null for accepted sessions, is assigned by the server, and is not subsequently rewritten.

Use `unlock_sessions.started_at` if these conditions hold. If they do not, establish which existing immutable server-generated timestamp reliably represents session acceptance before proceeding. Use that timestamp consistently for both Sessions and Completed.

Do not silently mix timestamp definitions across metrics.

### Completed

Count `unlock_completions` rows whose associated session has its session-start timestamp within the selected period.

Join completions to `unlock_sessions` through `unlock_session_id`, and apply the date filter to the session's start timestamp.

Do not independently filter completion rows by `completed_at` for this metric.

This keeps Completed and Sessions in the same cohort, so Completed cannot exceed Sessions under the existing one-completion-per-session constraint.

A completion can be recorded outside the selected period and still count if its associated session started within the period. Conversely, a completion recorded inside the period does not count if its associated session started outside the period.

### Unlock rate

`Completed / Shown * 100`

Display a percentage only when:

- Shown is at least 20; and
- Completed is not greater than Shown.

Otherwise, display `—`.

Continue to show the underlying counts.

A valid rate of zero completions with at least 20 impressions is **0%**, not a dash.

The minimum of 20 impressions is a conservative product display rule, not a claim of statistical significance.

Because an impression request can be lost while a session is still recorded, Completed can occasionally exceed Shown. In that case, suppress the rate rather than display a misleading percentage.

The report must explain that a dash can mean the sample is too small or that the rate is not meaningful under the defined rules. It must not look like a calculation error.

### Timer detail: Finished wait rate

For Timer campaigns, the intended metric is:

`Completed Timer sessions / Accepted Timer sessions * 100`

Use the same reporting cohort: Timer sessions started during the selected period and completions associated with those sessions.

Display the rate only when:

- At least 20 eligible Timer sessions started during the selected period; and
- Completed Timer sessions are not greater than eligible Timer sessions.

Otherwise, display `—`.

A valid rate of zero completions with at least 20 eligible Timer sessions is **0%**.

The sample-size threshold avoids displaying a potentially misleading 100% based on a single session.

**Historical method identification is a prerequisite.** Do not determine whether a historical session was a Timer session solely from the campaign's current `unlock_method`. Campaign configuration can change after a session was created.

Before implementing this metric, inspect the existing session schema and session-creation code. If an immutable session field reliably distinguishes Timer sessions from Click sessions, use that field to select both the numerator and denominator.

For example, `required_duration_seconds` may be suitable if the actual implementation guarantees distinct values for Timer and Click sessions. Verify this rather than assuming it.

If the existing session data cannot reliably distinguish historical Timer sessions, do not label a generic session completion rate as Finished wait rate. Defer the metric until reliable identification is possible, or revise the product requirement explicitly before implementation.

Finished wait rate measures the share of accepted Timer sessions that completed. It is not an engagement measure because Timer sessions start automatically.

### Click campaigns

Do not show a click-through percentage in this release.

`Sessions / Shown` is not a valid click-through rate because one displayed gate can generate multiple sessions through retries or repeated attempts.

Show the counts without relabeling that ratio as click-through.

### Interpretation and limitations of rates

The two rates answer different questions:

- **Unlock rate:** what proportion of recorded gate impressions corresponded to completed sessions in the same session-start cohort?
- **Finished wait rate:** what proportion of eligible accepted Timer sessions completed?

Unlock rate can be hidden when Shown is below 20 or Completed exceeds Shown. Finished wait rate can be hidden when fewer than 20 eligible Timer sessions exist.

These are deliberate display rules, not errors.

Browser blocking, network failures, and direct requests can cause event counts to diverge. The report must not imply that the counts are guaranteed to represent every real interaction.

## 3. Data model

### New table: `gate_impressions`

Use the following schema, adjusting naming only if required to match established project conventions:

```sql
CREATE TABLE gate_impressions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    campaign_id BIGINT UNSIGNED NOT NULL,
    visitor_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
    page_view_id CHAR(36) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
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

The ASCII character set is appropriate for the hexadecimal visitor identifier and UUID. It avoids using a wider Unicode representation for identifiers and gives them predictable, case-sensitive comparisons.

Requirements:

- `visitor_id` must come from the existing server-side `VisitorId` implementation. Do not trust a visitor ID supplied by JavaScript.
- `page_view_id` is a hyphenated UUID generated once per document and stored on `window`.
- Reuse the same page-view ID for repeated initialization within a document, including a restored document. A fresh document load gets a fresh ID.
- Reject a missing or malformed page-view ID. Do not silently generate a replacement on the server for invalid input, because a retry would bypass deduplication.
- Set `created_at` on the server. Ignore client timestamps.
- Derive `presentation_type` and `unlock_method` from the campaign configuration loaded by the server when processing the request. Do not accept these values from the client.
- Snapshot the presentation type and unlock method used for the impression. Do not derive historical impression values from the campaign's current configuration when reporting.
- Use `ON DELETE RESTRICT`, consistent with existing foreign-key behavior. Do not cascade-delete impressions.
- Retain `visitor_id` for the defined visitor-scoped deduplication key. This does not authorize unique-visitor reporting.

### Existing table index

Add the following index to `unlock_sessions`:

```sql
INDEX idx_unlock_sessions_campaign_started (campaign_id, started_at)
```

Do not remove the existing single-column `(campaign_id)` index in this change. Consider index cleanup separately after reviewing actual query plans and usage.

Do not add a `(campaign_id, completed_at)` index for this release. The Completed metric filters the associated session cohort and finds completion rows through the existing unique index on `unlock_session_id`.

Before finalizing the migration, verify that `started_at` has the properties required by the reporting definitions. If it is not reliable, resolve the timestamp choice before building the reporting queries.

## 4. Collection behavior

### Recording conditions

1. Render the gate UI successfully.
2. Trigger impression registration asynchronously.
3. Continue the existing unlock flow without waiting for impression registration to finish.
4. Ignore impression-request failures for gate functionality. They must not prevent Timer `/start`, arm/disarm click handling, or otherwise block unlocking.
5. Do not record an impression merely because campaign configuration was loaded, `/status` was checked, a timer started, a CTA was clicked, or a retry occurred.
6. If `/status` indicates the visitor is already unlocked and no gate UI is shown, do not record an impression.
7. A new page load that renders the gate is a new impression. A second initialization in the same document is deduplicated.
8. Use the existing server-side visitor cookie behavior. Do not add fingerprinting or attempt to make the client-generated page-view ID tamper-proof.

### Definition of successful rendering

Rendering success must be defined by the actual presentation type rather than by generic initialization.

- **Popup Gate:** record after `createGate()` has successfully returned with the popup element attached to the document and visible, before the automatic Timer start or subsequent unlock interaction.
- **Content Gate:** record after `/status` has established that the visitor is still locked and the CTA has been inserted into the document successfully. Do not wait for a CTA click.
- **Already unlocked:** return without rendering the gate and without recording an impression.
- **Render failure:** do not record an impression if the required UI was not successfully inserted and displayed.

These conditions must be implemented at the render sites, not in a generic initialization function that might run before the UI exists or run repeatedly.

For Popup Gate, do not register impressions inside `handleClickUnlock()`, `retryUnlock()`, or `unlockContent()`.

For Content Gate, do not register impressions in the click handler or after the unlock action.

### Page-view ID generation

Generate one UUID per document and retain it on `window`.

Use `crypto.randomUUID()` where available. Provide a fallback based on `crypto.getRandomValues()` for environments where `crypto.randomUUID()` is unavailable.

The fallback must generate a valid UUID, not a timestamp or `Math.random()` identifier.

Do not regenerate the identifier during repeated initialization in the same document.

### Request transport and credentials

Use the same-origin request transport and credential behavior established by the existing `/start` flow so that the existing visitor cookie is sent consistently.

The impression request must be fired without awaiting its completion. A failed request must not interrupt or delay the unlock flow.

Use a neutral, project-consistent public route name. Avoid obvious analytics/tracking terminology in the endpoint path where practical, because some browser blockers filter requests by URL patterns.

The route name is an implementation detail. Do not introduce a new generic analytics API abstraction.

### Endpoint validation and error handling

- Validate the page-view UUID before persistence.
- Obtain visitor identity through the existing server-side `VisitorId` source.
- Load the campaign and derive `presentation_type` and `unlock_method` server-side.
- Reject a nonexistent or inactive campaign according to the existing endpoint and campaign-state conventions.
- Do not trust client-supplied visitor IDs, timestamps, presentation types, or unlock methods.
- Return an appropriate response without exposing database details to the public.
- Keep unrelated persistence failures distinguishable in server-side diagnostics, following existing project conventions.
- Treat only the expected duplicate-key condition as successful deduplication.

Use existing request-throttling protections if the application already has a suitable mechanism. Do not introduce a new general-purpose rate-limiting subsystem solely for analytics in this release.

The public endpoint's exposure to deliberate abuse must be documented and considered before release.

## 5. Admin report

Add a simple campaign analytics view using the existing admin UI patterns.

For each selected campaign and reporting period, show:

- Shown
- Sessions
- Completed
- Unlock rate

Provide the last 7 days and last 30 days views. Include the actual UTC date range in each view.

Include the Timer Finished wait rate only when the stored session data reliably identifies eligible historical Timer sessions. If that prerequisite is not met, omit the metric rather than display a misleading rate.

Use existing admin authentication and authorization patterns. Escape output as required by established conventions.

Do not introduce a new frontend framework, reporting framework, or generic analytics abstraction.

### Metric explanations

The report must explain, in concise UI copy or a tooltip, that:

- Values are event counts, not unique people.
- Shown counts successfully rendered gate UI, not confirmed human attention.
- Sessions counts accepted unlock sessions, including active, expired, and completed sessions.
- Completed is grouped by the session-start date, not the completion date.
- Unlock rate can be hidden when the sample is too small or when the recorded counts do not support a meaningful percentage.
- Finished wait rate, when shown, measures the completion share of eligible accepted Timer sessions. It is not an engagement measure.

Do not show click-through rate, unique visitors, revenue, or method-specific historical session/completion comparisons.

## 6. Implementation plan

Complete each phase in order. Inspect existing code and tests before editing, and keep changes limited to the current phase.

Tests that prove a phase's behavior should be added in that phase rather than postponed until the end.

### Phase 1: Database migration

- Add the `gate_impressions` table.
- Add the composite `(campaign_id, started_at)` index to `unlock_sessions`.
- Follow the project's existing migration/schema update conventions.
- Verify compatibility with both MySQL 8 and MariaDB.
- Reuse the existing PHP and MySQL UTC configuration. Do not introduce another timezone mechanism.
- Verify the properties of `unlock_sessions.started_at`: server-assigned, non-null for accepted sessions, and not rewritten after creation.
- Add or update schema tests if the project has them.
- Review the size and collation of the new identifier columns.

**Done when:** a clean database can be initialized with the new schema, existing schema setup remains compatible with supported database engines, and the timestamp assumptions required by reporting have been verified.

### Phase 2: Impression recording endpoint and persistence

- Inspect existing routing, request validation, repository, visitor identity, campaign-state, and error-response conventions.
- Add the smallest endpoint and persistence implementation consistent with the current architecture.
- Validate campaign existence/state and the page-view UUID according to established endpoint patterns.
- Get visitor identity through the existing server-side `VisitorId` source.
- Derive presentation type and unlock method from the server-loaded campaign configuration.
- Snapshot these values at impression-recording time.
- Treat only the expected unique-key duplicate as successful deduplication.
- Ensure unrelated database errors remain distinguishable in server-side diagnostics without exposing database details to the public response.
- Use the existing database connection and timezone conventions.

**Tests in this phase:**

- A valid impression creates exactly one row.
- Repeating the same campaign/visitor/page-view key creates no additional row and is treated as success.
- A new page-view ID creates a new impression.
- Missing or malformed page-view IDs are rejected.
- Client-supplied visitor IDs and timestamps are not trusted.
- Client-supplied presentation types and unlock methods are ignored.
- The stored snapshot values come from the server-loaded campaign configuration.
- Nonexistent or inactive campaigns are rejected.
- An unrelated database error is not mistaken for a duplicate.

**Done when:** valid impressions are stored, repeated submissions for the same key do not create duplicate rows, and invalid input or unrelated persistence errors are handled correctly.

### Phase 3: Frontend impression registration

- Generate one UUID per document and retain it on `window`.
- Use `crypto.randomUUID()` where available and a `crypto.getRandomValues()` fallback otherwise.
- Register an impression only after the gate UI has rendered successfully.
- Ensure repeated initialization reuses the same page-view ID.
- Make the request non-blocking and ensure failures do not interfere with the existing unlock flow.
- Do not record an impression when no gate is shown.
- Reuse the existing request transport and credential behavior.

**Tests in this phase:**

- Popup + Timer records an impression after the popup is rendered.
- Popup + Click records an impression after the popup is rendered.
- Content + Click records an impression after the locked state and CTA have been rendered.
- Repeated initialization within one document reuses the same page-view ID.
- A fresh document load gets a fresh page-view ID.
- A missing `crypto.randomUUID()` does not prevent UUID generation when `crypto.getRandomValues()` is available.
- A visitor already unlocked who sees no gate does not generate an impression.
- A render failure does not generate an impression.
- Retry and repeated Click interactions do not generate additional impressions for the same page view.
- Click handling does not trigger impression registration.
- Impression-request failure does not block Timer start or Click interaction.

**Done when:** Popup + Timer, Popup + Click, and Content + Click record impressions under the intended conditions, while analytics endpoint failure does not prevent normal gate behavior.

### Phase 4: Reporting queries and metric calculations

- Implement campaign/date-range counts using half-open UTC intervals.
- Filter Sessions by the verified session-start timestamp.
- Filter Completed through the associated sessions' session-start timestamp, not completion timestamps.
- Implement the rate display rules and Timer Finished wait rate where historical Timer sessions can be identified reliably.
- Keep queries parameterized and consistent with existing repository conventions.
- Keep the implementation simple. A shared query may be used where it improves consistency and readability, but do not add a reporting abstraction solely to combine queries.
- Pass an explicit reference time into the reporting calculation rather than reading the current time internally.
- Return or retain the UTC start and end dates needed for the report labels.

**Tests in this phase:**

- Sessions in the range include active, expired, and completed statuses.
- A session starting exactly at the inclusive start boundary is included.
- A session starting exactly at the exclusive end boundary is excluded.
- A completion is counted according to its session's start timestamp, including when `completed_at` falls outside the selected range.
- A completion inside the reporting period is excluded when its associated session started outside the period.
- Empty periods return zero counts.
- Zero denominators display `—`.
- Unlock rate is hidden when Shown is below 20.
- Unlock rate is hidden when Completed exceeds Shown.
- Unlock rate displays `0%` when Shown is at least 20 and Completed is zero.
- Finished wait rate uses the same session cohort and includes only reliably identified Timer sessions.
- Finished wait rate is hidden below 20 eligible Timer sessions.
- Finished wait rate displays `0%` when there are at least 20 eligible Timer sessions and none completed.
- UTC boundaries remain deterministic when the supplied reference time crosses a month, year, or daylight-saving transition in the server's local timezone.

**Done when:** the report uses consistent cohorts, handles empty periods and date boundaries, and never displays a rate when its defined display conditions are not met.

### Phase 5: Admin UI

- Add the report to the existing admin navigation and campaign UI using current project patterns.
- Show the 7-day and 30-day counts and rates.
- Show the actual UTC date range for each reporting period.
- Show Finished wait rate for Timer campaigns only when historical Timer sessions can be identified reliably.
- Include concise metric definitions and avoid implying that event counts equal unique people.
- Explain why a rate may display as `—`.
- Escape output and use existing admin authorization patterns.

**Done when:** an administrator can view understandable analytics for a campaign without needing to interpret raw database records.

### Phase 6: Automated tests and regression checks

Ensure the phase-specific tests are in place, then run the complete relevant test suite.

Test at minimum:

- Valid impression creates one row.
- Repeating the same campaign/visitor/page-view key creates no additional row and is treated as success.
- A new page-view ID creates a new impression.
- Missing or malformed page-view IDs are rejected.
- Client-supplied visitor IDs, timestamps, presentation types, and unlock methods are not trusted.
- A visitor already unlocked who sees no gate does not generate an impression.
- A render failure does not generate an impression.
- Impression-request failure does not block Timer start or Click interaction.
- Retry and repeated Click interactions do not write additional impressions for the same page view.
- Nonexistent and inactive campaigns are rejected.
- An unrelated database error is not treated as a duplicate.
- Sessions in the range include active, expired, and completed statuses.
- A session at the inclusive start boundary is included and a session at the exclusive end boundary is excluded.
- A completion is counted according to its session's start timestamp, including when `completed_at` falls outside the selected range.
- A completion inside the range is excluded when its associated session started outside the range.
- Empty periods and zero denominators display `—`.
- Rates are hidden when Shown is below 20 or Completed exceeds Shown.
- Zero completions with a sufficient denominator display `0%`.
- Finished wait rate uses the same session cohort and only reliably identified Timer sessions.
- Finished wait rate is hidden below 20 eligible Timer sessions.
- UTC handling is correct and date-boundary tests do not depend on the real system clock.
- Existing unlock, frequency-limit, security, and campaign behavior continues to pass.

**Done when:** all relevant new tests pass and the full existing test suite passes.

## 7. Known limitations

- Counts are events, not unique visitors.
- Browser blocking, network failures, or an analytics endpoint outage can cause impressions to be undercounted.
- The page-view ID is a deduplication mechanism, not a security boundary.
- Anyone able to call the public impression endpoint may attempt to inflate counts with fabricated page-view IDs. The analytics endpoint must not be treated as proof of genuine human interaction.
- Use existing request-throttling protections where available. A dedicated analytics rate-limiting subsystem is outside this release's scope.
- One row is stored per recorded page view. The table can grow with traffic; retention and pruning should be considered before broad distribution, but scheduled aggregation and retention infrastructure are not part of this release.
- Historical presentation and unlock method are preserved on impressions, but not necessarily on existing session/completion records. Do not present method-specific historical session/completion rates unless the stored session data reliably supports them.
- If historical Timer sessions cannot be distinguished from Click sessions, omit Finished wait rate rather than infer historical methods from the current campaign configuration.
- Changing campaign configuration can make comparisons across periods harder to interpret. Duplicating a campaign for a materially different setup is a possible operational workaround, not an enforced requirement.
- Unlock rate may be hidden when Shown is below 20 or Completed exceeds Shown. This is an intentional safeguard, not a reporting failure.
- No revenue attribution or advanced analytics is provided in this release.

## 8. Release acceptance checklist

- [x] Database migration/schema update works on MySQL 8 and MariaDB.
- [x] Existing PHP and MySQL UTC configuration is reused for analytics timestamps.
- [x] Session-start timestamp assumptions are verified against the existing schema and service.
- [x] Impression rows are created only after successful gate rendering.
- [x] Same-document duplicate initialization is deduplicated.
- [x] Impression requests reuse the existing visitor cookie and credential behavior.
- [x] Client-supplied visitor IDs, timestamps, presentation types, and unlock methods are ignored.
- [x] Expected duplicate submissions are handled without masking unrelated database errors.
- [x] Analytics failures never block gate functionality.
- [x] Reporting date boundaries use UTC and half-open intervals.
- [x] Date-boundary tests use an explicit reference time.
- [x] Completed is calculated through sessions filtered by the session-start timestamp.
- [x] Rates follow the documented display rules, including the minimum sample sizes.
- [x] Timer Finished wait rate is shown only when historical Timer sessions can be identified reliably.
- [x] Admin UI displays the actual UTC date ranges and explains the metrics.
- [x] Admin UI does not claim unique visitors or click-through rate.
- [x] Public-endpoint abuse and impression-table growth limitations are documented.
- [x] New tests and the existing regression suite pass.
- [ ] `ANALYTICS.md` status and any implementation deviations are updated before release.
