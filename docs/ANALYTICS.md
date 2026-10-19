# Reward Gate Analytics

## Purpose

Define the behavior, metric definitions, implementation decisions, and acceptance criteria for Reward Gate analytics.

This document is the source of truth for analytics-specific requirements. `SPECIFICATION.md` remains the source of truth for overall product requirements.

The initial campaign analytics implementation is complete. The next iteration expands reporting with additional date-range presets and a daily breakdown of campaign activity.

Keep the analytics implementation focused on useful product functionality. Avoid unnecessary infrastructure, abstractions, or restrictions that do not provide meaningful value.

## Status

- Initial analytics implementation: complete.
- Initial automated tests: passing (218 tests, 1,009 assertions).
- Initial release: v0.4.0.
- Next iteration: planned.
- Next iteration scope:
  - Add Last 90 Days and This Month reporting presets.
  - Add a daily breakdown of Shown, Sessions, and Completed.
  - Keep summary metrics and daily reporting synchronized to the selected reporting period.
  - Ensure daily totals reconcile with the summary counts.
  - Refine metric explanations and reporting behavior where necessary.

## 1. Scope

### Implemented

- Record a gate impression after the gate UI has rendered successfully.
- Deduplicate impressions for the same campaign, visitor, and page view.
- Display campaign-level summary metrics for a selected reporting period.
- Display Shown, Sessions, Completed, and Unlock rate.
- Show a Timer-specific Finished wait rate when stored session data supports reliable identification of historical Timer sessions.
- Keep analytics collection non-blocking so failures do not interfere with unlocking.
- Use UTC reporting boundaries and half-open intervals.
- Use the existing application timezone configuration.
- Add the database index needed for campaign/date-range session queries.
- Include automated tests for impression recording, deduplication, reporting, and failure behavior.

### Next iteration

- Provide four reporting-period presets: Last 7 Days, Last 30 Days, Last 90 Days, and This Month.
- Apply the selected period consistently to the summary metrics and daily report.
- Add a daily breakdown of Shown, Sessions, and Completed.
- Include every UTC calendar date in the selected period, including dates with zero activity.
- Ensure daily totals reconcile exactly with the corresponding summary counts.
- Display the actual UTC date range covered by the report.
- Explain the meaning and limitations of the metrics in the admin UI.
- Keep reporting queries efficient and compatible with MySQL 8 and MariaDB.

### Not currently included

- Unique-visitor metrics or claims that event counts represent people.
- Click-through rate, because the current data model does not reliably associate impressions with sessions.
- Revenue metrics and external revenue integrations.
- Verification-failure logging.
- Event-level analytics views, rollup tables, or scheduled aggregation.
- A/B testing, recommendations, attribution, geographic/device segmentation, or advanced analytics infrastructure.
- Method-specific historical session/completion comparisons, except for Finished wait rate when stored session data reliably identifies historical Timer sessions.
- A `page_view_id` column on `unlock_sessions`. Revisit if a genuine click-through metric becomes a product requirement.
- A new general-purpose rate-limiting subsystem solely for analytics.

These exclusions describe the current product scope, not permanent restrictions. Revisit them when a concrete product requirement justifies the additional functionality.

## 2. Metric definitions

All metrics are event counts, not unique people.

### Reporting periods

All reporting periods use UTC calendar dates.

The selected period applies to every summary metric and every row in the daily report.

#### Available presets

- **Last 7 Days:** Today and the previous six UTC calendar days.
- **Last 30 Days:** Today and the previous 29 UTC calendar days.
- **Last 90 Days:** Today and the previous 89 UTC calendar days.
- **This Month:** The first day of the current UTC calendar month through today.

All presets include today's partial data.

#### Interval semantics

All reporting queries use half-open intervals:

`timestamp >= start_utc AND timestamp < end_utc`

The start boundary is inclusive. The end boundary is exclusive.

The end boundary is 00:00 UTC tomorrow.

For Last 7 Days, Last 30 Days, and Last 90 Days, the start boundary is 00:00 UTC six, 29, or 89 calendar days before today, respectively.

For This Month, the start boundary is 00:00 UTC on the first day of the current month.

#### Displaying the selected period

Display the actual inclusive calendar dates covered by the report, explicitly labeled UTC.

Examples:

- `October 11–17, 2026, UTC`
- `July 20–October 17, 2026, UTC`
- `October 1–17, 2026, UTC`

The displayed dates must match the actual query boundaries.

The reporting-period label must not be converted to the browser's local timezone.

#### Reference time

Reporting-period calculations must accept an explicit `DateTimeImmutable` reference time.

Do not read the system clock from inside the date-range calculation.

This allows deterministic testing of midnight UTC boundaries, month and year transitions, leap years, month lengths, and server environments configured for timezones other than UTC.

Reuse the existing date-range calculation and extend it to support the additional presets rather than introducing a second implementation.

Convert the supplied reference time to UTC before determining the current calendar date or calculating reporting boundaries. All calendar arithmetic must use UTC dates, regardless of the timezone associated with the supplied reference time.

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

 Because Shown and Completed use different event cohorts and are not directly associated, Completed can occasionally exceed Shown. Lost impression requests can also contribute to this difference. In that case, suppress the rate rather than display a misleading percentage.

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

- **Unlock rate:** Completed divided by Shown for the same reporting window. This is an aggregate ratio, not a matched conversion rate, because the application does not directly associate individual impressions with individual sessions.
- **Finished wait rate:** The proportion of eligible accepted Timer sessions that completed.

Unlock rate can be hidden when Shown is below 20 or Completed exceeds Shown. Finished wait rate can be hidden when fewer than 20 eligible Timer sessions exist.

These are deliberate display rules, not errors.

Browser blocking, network failures, and direct requests can cause event counts to diverge. The report must not imply that the counts are guaranteed to represent every real interaction.

## 3. Daily report

Add a daily breakdown of campaign activity for the selected reporting period.

The report must use the same metric definitions and reporting boundaries as the summary.

### Columns

The daily report contains:

- Date.
- Shown.
- Sessions.
- Completed.

Dates are UTC calendar dates.

The report may be presented as a table, a chart, or a combination of both. Prioritize readability and accurate interpretation over visual complexity.

Do not introduce a charting dependency solely for this report unless a concrete requirement justifies it. A clear table or lightweight native HTML/CSS visualization is sufficient.

Do not add a daily Unlock rate chart. Daily conversion rates could encourage misleading interpretations of low-volume data and would frequently be hidden by the minimum sample-size rule.

Keep Unlock rate and Finished wait rate in the summary metrics.

### Daily Shown

Count impressions grouped by the UTC calendar date of `gate_impressions.created_at`.

Use the same start and end boundaries as the summary.

### Daily Sessions

Count sessions grouped by the UTC calendar date of `unlock_sessions.started_at`.

Include all session statuses, consistent with the summary.

### Daily Completed

Count completions through their associated sessions and group them by the UTC calendar date of the associated session's `started_at`.

Do not group this metric by `unlock_completions.completed_at`.

The daily Completed series must use the same session-start cohort as the summary.

### Reconciliation with summary counts

For every selected reporting period:

- The sum of daily Shown must equal summary Shown.
- The sum of daily Sessions must equal summary Sessions.
- The sum of daily Completed must equal summary Completed.

The summary and daily report should be derived from the same underlying metric definitions and equivalent database filters. Avoid maintaining separate implementations of the same counting rules where doing so could cause the results to diverge.

Treat the summary and daily breakdown as one reporting result. If the complete result cannot be obtained, show an appropriate reporting error rather than presenting a mixture of new and stale values or treating query failures as zero activity.

### Missing dates

Return one daily row for every UTC calendar date in the selected period.

Dates without activity must contain zero values.

Generate missing dates in PHP rather than introducing a calendar table or additional database infrastructure.

Include today, even if no events have occurred yet.

### Partial current day

Today's values represent activity recorded from 00:00 UTC through the time the report is generated.

They are not final daily totals.

The UI should make the reporting timezone clear and may note that today's data is partial.

Do not estimate or extrapolate the remainder of the day.

### Sessions crossing midnight

A session belongs to the UTC calendar date on which it started.

A completion belongs to the date of its associated session's start, even if the completion occurred on another date.

For example, if a session starts shortly before midnight and completes after midnight:

- Sessions increases on the start date.
- Completed increases on the same start date.
- The completion date does not determine the daily Completed bucket.

### Interpretation

The daily report shows recorded event counts, not unique people.

Sessions may exceed Shown because of retries, repeated attempts, missing impression requests, or multiple accepted sessions associated with a browsing context.

Do not present Sessions divided by Shown as a conversion rate.

Do not imply that every session corresponds to a distinct visitor or impression.

## 4. Data model

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

## 5. Collection behavior

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
- Validate the page-view ID as a hyphenated UUID. Normalize it to lowercase before persistence so that case variations cannot bypass string-based deduplication.

Use existing request-throttling protections if the application already has a suitable mechanism. Do not introduce a new general-purpose rate-limiting subsystem solely for analytics in this release.

The public endpoint's exposure to deliberate abuse must be documented and considered before release.

## 6. Admin report

Add a simple campaign analytics view using the existing admin UI patterns.

For each selected campaign and reporting period, show:

- Shown
- Sessions
- Completed
- Unlock rate

Provide the Last 7 Days, Last 30 Days, Last 90 Days, and This Month presets.

Use a single shared reporting-period selection for the summary metrics and daily report. Include the actual UTC date range covered by the selected period.

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

### Daily report

Display daily Shown, Sessions, and Completed counts for every UTC date in the selected period.

Use zero-filled rows for dates without activity.

Ensure the daily totals reconcile with the summary counts.

The report may use a table, a lightweight chart, or both.

If a chart is used:

- Keep the three event-count series clearly distinguishable.
- Do not add a daily Unlock rate series.
- Do not introduce a secondary axis merely to force unrelated metrics into a visualization.
- Do not imply that event counts represent unique people.
- Keep UTC date labels unchanged by browser-local timezone conversion.

## 7. Implementation plan

The initial impression-collection and summary-reporting implementation is complete. The next iteration extends the reporting functionality.

Inspect the existing implementation and tests before editing. Reuse working code, queries, date calculations, and UI components where appropriate.

### Phase 1: Reporting-period presets

- Extend the existing period selector with Last 90 Days and This Month.
- Reuse the existing UTC date-range calculation.
- Apply the selected period to summary metrics.
- Display the actual inclusive UTC calendar dates.
- Preserve today's partial data.
- Keep half-open query boundaries.

**Tests:**

- Last 7 Days includes exactly seven UTC calendar dates.
- Last 30 Days includes exactly 30 UTC calendar dates.
- Last 90 Days includes exactly 90 UTC calendar dates.
- This Month starts on the first day of the current UTC month.
- All presets include today.
- The exclusive end boundary is midnight UTC tomorrow.
- Date calculations behave correctly across month and year boundaries.
- Leap years and month lengths are handled correctly.
- Tests use an explicit reference time and do not depend on the real system clock.

**Done when:** all four presets produce the correct UTC boundaries and the summary displays the corresponding period.

### Phase 2: Daily aggregate reporting

- Add daily Shown counts grouped by impression creation date.
- Add daily Sessions counts grouped by session-start date.
- Add daily Completed counts grouped by the associated session's start date.
- Apply the same selected period and campaign filters to all metrics.
- Fill missing dates with zero-valued rows in PHP.
- Include today's partial data.
- Keep SQL aggregation efficient and compatible with MySQL 8 and MariaDB.

**Tests:**

- Daily Shown counts use `gate_impressions.created_at`.
- Daily Sessions include active, expired, and completed sessions.
- Daily Completed counts use the associated session's `started_at`.
- A session crossing midnight remains grouped by its start date.
- A completion recorded outside the reporting period counts if its session started within the period.
- A completion recorded inside the period does not count if its session started outside the period.
- Missing dates are returned with zero counts.
- Every selected UTC calendar date appears exactly once.
- The first and last dates follow the same boundaries as the summary.

**Done when:** the daily report returns correct counts for every date in the selected period, including dates without activity.

### Phase 3: Reconciliation and consistency

- Ensure the daily report and summary use identical metric definitions.
- Ensure daily Completed and summary Completed use the same session-start cohort.
- Ensure all reporting components use the same selected period.
- Verify that no metric silently applies different session statuses or timestamp definitions.

**Tests:**

For each reporting period:

- Sum of daily Shown equals summary Shown.
- Sum of daily Sessions equals summary Sessions.
- Sum of daily Completed equals summary Completed.

Include tests with sessions crossing midnight and completions recorded on a different date from their associated session's start.

**Done when:** all daily totals reconcile exactly with the summary counts for the same campaign and reporting period.

### Phase 4: Admin UI

- Add Last 90 Days and This Month to the existing reporting-period selector.
- Add the daily report to the campaign analytics view.
- Apply the selected period to both the summary and daily report.
- Display the actual UTC date range.
- Display zero-activity dates.
- Explain that today's data is partial.
- Explain the meaning and limitations of the event counts.
- Keep the existing Unlock rate and Finished wait rate rules.
- Do not add daily conversion rates.
- Keep the UI consistent with the existing admin interface.

**Done when:** administrators can switch between all four presets and view consistent summary metrics and daily counts for the selected period.

### Phase 5: Regression testing and quality checks

- Run reporting-specific tests.
- Run existing analytics tests.
- Run the full relevant test suite.
- Check empty periods, low event counts, and populated periods.
- Check that the summary and daily report update together when the selected period changes.
- Check that reporting errors do not display misleading zero counts or stale data.
- Verify compatibility with supported MySQL and MariaDB environments where practical.

Existing impression collection, deduplication, unlocking, frequency-limit, security, and campaign behavior must remain unchanged.

**Done when:** all relevant tests pass, daily counts reconcile with the summary, and existing application behavior remains intact.

## 8. Known limitations

- Counts are events, not unique visitors.
- Browser blocking, network failures, or an analytics endpoint outage can cause impressions to be undercounted.
- The page-view ID is a deduplication mechanism, not a security boundary.
- Anyone able to call the public impression endpoint may attempt to inflate counts with fabricated page-view IDs. The endpoint must not be treated as proof of genuine human interaction.
- Use existing request-throttling protections where available. A dedicated analytics rate-limiting subsystem is not currently required.
- One row is stored per recorded page view. The table can grow with traffic. Retention and pruning should be considered as the product scales.
- Historical presentation and unlock methods are preserved on impressions but not necessarily on existing session/completion records. Do not present method-specific historical session/completion rates unless the stored session data reliably supports them.
- If historical Timer sessions cannot be distinguished from Click sessions, omit Finished wait rate rather than infer historical methods from the current campaign configuration.
- Changing campaign configuration can make comparisons across periods harder to interpret. Duplicating a campaign for a materially different setup is a possible operational workaround, not an enforced requirement.
- Unlock rate may be hidden when Shown is below 20 or Completed exceeds Shown. This is an intentional display rule.
- Finished wait rate may be hidden when fewer than 20 eligible Timer sessions exist.
- Daily counts represent recorded events grouped by defined timestamps. They do not establish unique visitors, direct attribution, or guaranteed one-to-one relationships between impressions and sessions.
- Today's daily counts are partial and will change as more events are recorded.
- Daily Completed is grouped by the associated session's start date, not the completion date.
- Revenue attribution and advanced analytics are not currently implemented.

## 9. Next iteration acceptance checklist

### Reporting periods

- [x] Last 7 Days is available and uses the correct UTC boundaries.
- [x] Last 30 Days is available and uses the correct UTC boundaries.
- [x] Last 90 Days is available and uses the correct UTC boundaries.
- [x] This Month is available and starts on the first day of the current UTC month.
- [x] All presets include today's partial data.
- [x] All periods use half-open UTC intervals.
- [x] The displayed date range matches the actual query boundaries.
- [x] Date-boundary tests use an explicit reference time.

### Daily report

- [x] Daily Shown is grouped by impression creation date.
- [x] Daily Sessions is grouped by session-start date.
- [x] Daily Completed is grouped by the associated session's start date.
- [x] All session statuses are included in Sessions.
- [x] Every UTC calendar date in the selected period appears in the report.
- [x] Dates without activity have zero counts.
- [x] Today's partial data is included.
- [x] Daily Shown totals equal summary Shown.
- [x] Daily Sessions totals equal summary Sessions.
- [x] Daily Completed totals equal summary Completed.
- [x] Sessions crossing midnight are grouped by their start date.
- [x] Daily date labels remain in UTC.

### Admin UI

- [x] The selected reporting period applies to the summary and daily report.
- [x] The actual UTC date range is displayed.
- [x] Metric explanations describe event counts and their limitations.
- [x] The UI explains that today's data is partial.
- [x] Existing Unlock rate display rules remain unchanged.
- [x] Existing Finished wait rate display rules remain unchanged.
- [x] No daily Unlock rate chart is introduced.
- [x] Reporting failures do not present misleading zero counts or stale results.
- [x] Summary metrics, timeline chart, and daily report update together when the period changes.
- [x] Chart labels use readable dates.
- [x] The daily report uses readable date labels.
- [x] The analytics layout works on mobile and desktop.
- [x] Chart resizing and daily-table horizontal scrolling work correctly.

### Compatibility and regression

- [x] Queries remain compatible with MySQL 8 and MariaDB.
- [x] Existing indexes are reused where appropriate.
- [x] No unnecessary schema changes are introduced.
- [x] No unnecessary analytics infrastructure or dependencies are added.
- [x] Existing impression collection and deduplication continue to work.
- [x] Analytics failures remain non-blocking for gate functionality.
- [x] Existing unlocking, frequency-limit, security, and campaign behavior continues to pass.
- [x] New tests and the existing regression suite pass.

### Documentation

- [x] `ANALYTICS.md` reflects the implemented behavior.
- [x] Any implementation deviations are documented.
- [x] Acceptance status is updated after verification.
