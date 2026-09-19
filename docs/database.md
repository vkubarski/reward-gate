# Reward Gate — Database Design

**Status:** Development
**Database Version:** 1.0
**Related Documents:** `docs/specification.md`, `docs/architecture.md`

## 1. Database Overview

Reward Gate uses a relational database to store the persistent state required by campaigns, presentations, unlock methods, rewards, unlock sessions, and unlock completions.

The database should remain small and understandable during the MVP while preserving clear boundaries between the major product concepts.

The database is responsible for persistent application state.

It is not responsible for presentation logic or application business logic.

The application layer determines how database records are created, validated, updated, and interpreted.

The MVP database should support the complete workflow:

**Campaign → Presentation → Unlock Method → Unlock Session → Verification → Completion → Reward**

The database should be extensible enough to support additional presentation types, unlock methods, and reward types without requiring a fundamental redesign.

---

## 2. Database Principles

### 2.1 Keep the MVP Schema Small

The MVP should contain only tables and fields that have a real purpose.

Future concepts should not receive database tables merely because they may exist someday.

### 2.2 Model Important Concepts Independently

The following concepts should remain distinguishable:

- Campaign
- Presentation
- Unlock Method
- Reward
- Unlock Session
- Unlock Completion

This prevents presentation logic, unlock logic, and reward logic from becoming tightly coupled.

### 2.3 Prefer Explicit Relationships

Relationships between important entities should normally be represented using foreign keys.

The database should not rely on naming conventions alone when a real relational constraint is appropriate.

### 2.4 Avoid Premature Generic Design

The schema should not attempt to support every possible future feature through a large collection of generic tables or key/value structures.

For example, the MVP does not need a universal "plugin", "action", or "entity metadata" system.

Real requirements should drive future schema extensions.

### 2.5 Preserve Data Integrity in the Database

Important invariants should be enforced at the database level where practical.

Examples include:

- primary-key uniqueness
- foreign-key relationships
- required fields
- unique identifiers
- valid status relationships where enforceable

Application-level validation remains necessary, but the database should provide a second layer of protection.

---

## 3. Core Entities

The MVP database should distinguish between persistent business entities and configuration concepts.

The primary persistent entities are:

- Campaign
- Unlock Session
- Unlock Completion

Presentation, Unlock Method, and Reward are important product concepts, but in the MVP they are treated as campaign configuration rather than independent reusable database entities.

This keeps the schema small while preserving clear separation between the different parts of the unlock workflow.

---

### 3.1 Campaigns

A campaign represents a configured Reward Gate experience.

A campaign defines:

* how the gate is presented;
* what unlock method is used;
* what reward is granted after successful completion;
* campaign status and limits;
* presentation-specific configuration where required.

The campaign does **not** own the customer's website content.

For Content / Read-more Gate, the article remains on the customer's website.
Reward Gate stores only the configuration required to operate the gate.

A campaign should have a stable identifier so that unlock sessions,
completions, analytics, and other records can reference it.

---

### 3.2 Presentation Configuration

A presentation defines **how the Reward Gate is displayed to the visitor**.

MVP presentation types:

- `popup`
- `content_gate`

Future presentation types may include:

- `full_page`
- `inline`
- `blurred_content`
- `download_gate`
- `button_gate`
- `embedded_widget`
- `feature_gate`
- `premium_content_gate`

In the MVP, the presentation is campaign configuration rather than a separate reusable database entity.

Presentation configuration should determine the visitor-facing behavior without determining the underlying unlock or verification logic.

For example:

```text
Campaign
    presentation_type = popup
```

and:

```text
Campaign
    presentation_type = content_gate
```

may both use exactly the same unlock engine.

The presentation layer is responsible for the visitor-facing experience.

The underlying unlock engine remains shared.

---

### 3.3 Unlock Configuration

An unlock method defines **what action the visitor must complete**.

The MVP supports:

- `timer`

Future methods may include:

- `link`
- `video`
- `offerwall`
- `survey`
- `external_task`

In the MVP, the unlock method is campaign configuration rather than a separate reusable database entity.

For example:

```text
Campaign
    unlock_method = timer
    unlock_configuration = ...
```

The unlock method should remain independent from the presentation.

For example, both a Popup Gate and a Content Gate may use the same timer implementation.

The unlock engine is responsible for determining whether the required action has been legitimately completed.

---

### 3.4 Reward Configuration

A reward defines **what the visitor receives after successful verification**.

The MVP supports:

- `content`

Future reward types may include:

- `download`
- `coupon`
- `credit`
- `feature`
- `external_link`
- other site-defined rewards

In the MVP, the reward is campaign configuration rather than a separate reusable database entity.

For example:

```text
Campaign
    reward_type = content
    reward_configuration = ...
```

The reward should remain conceptually independent from the unlock method.

For example:

```text
Timer → Content Unlock
```

is one combination, while future combinations may include:

```text
Video → Download
```

or:

```text
Offer → Coupon
```

The reward system should therefore consume the result of successful unlock verification rather than implementing the verification logic itself.

---

### 3.5 Unlock Sessions

An unlock session represents an individual visitor attempt to complete an unlock action.

The session is created server-side and belongs to a specific campaign.

It should contain the information required for the server to determine whether a completion request is valid.

Important concepts include:

- session identifier
- campaign identifier
- unlock method used for the session
- unlock configuration required for verification
- creation/start time
- expiration time
- current state
- security token information
- visitor/session association where applicable

The session should preserve the relevant unlock parameters that were in effect when the session was created.

For example, if a timer campaign is configured for 30 seconds and an administrator later changes the campaign to 60 seconds, an existing session should not silently change its required completion time.

The client must never be able to create a valid unlock session merely by modifying browser-side state.

---

### 3.6 Unlock Completions

An unlock completion represents a successfully verified completion of an unlock session.

A completion should be recorded only after the server has validated the session and confirmed that the required unlock conditions have been satisfied.

The completion record may be used for:

- preventing replay
- determining whether a session has already completed
- analytics
- usage limits
- future revenue calculations
- auditing

A completion should be associated with exactly one unlock session.

For the MVP, a successful unlock session should have at most one completion.

The completion represents the verified state transition:

```text
Unlock Session
      │
      ▼
Server Verification
      │
      ▼
Unlock Completion
```

The completion record should not be treated as proof merely because a client-side completion event was received. Server-side verification must occur before the completion is created.

---

## 4. Relationships

The MVP database relationships are centered around campaigns, unlock sessions, and unlock completions.

The high-level relationship is:

```text
Campaign
    │
    │ 1
    │
    └────────── N Unlock Sessions
                       │
                       │ 1
                       │
                       └────────── 0..1 Unlock Completion
```

A campaign contains the configuration that determines:

```text
Campaign
├── Presentation configuration
├── Unlock configuration
└── Reward configuration
```

These configurations do not require independent reusable database records in the MVP.

Presentation configuration is stored with the campaign.

For Content / Read-more Gate, this configuration describes the gate behavior
only. It does not contain the article or the protected portion of the article.

The resulting model is:

```text
                    ┌─────────────────────────┐
                    │        Campaign         │
                    │                         │
                    │ Presentation config     │
                    │ Unlock config           │
                    │ Reward config           │
                    └────────────┬────────────┘
                                 │
                                 │ 1:N
                                 ▼
                    ┌─────────────────────────┐
                    │    Unlock Session       │
                    │                         │
                    │ Campaign                │
                    │ Unlock parameters       │
                    │ Start / expiration      │
                    │ Security state          │
                    └────────────┬────────────┘
                                 │
                                 │ 1:0..1
                                 ▼
                    ┌─────────────────────────┐
                    │   Unlock Completion     │
                    │                         │
                    │ Session                 │
                    │ Completion time         │
                    │ Verification result     │
                    └─────────────────────────┘
```

### 4.1 Campaign → Unlock Sessions

One campaign may have many unlock sessions.

Each unlock session belongs to exactly one campaign.

Conceptually:

```text
campaigns
    1
    │
    └────── N unlock_sessions
```

The foreign-key relationship should be enforced by the database.

Deleting a campaign must not leave orphaned unlock sessions.

The exact deletion behavior will be determined together with the retention policy.

### 4.2 Unlock Session → Unlock Completion

An unlock session may have zero or one successful completion.

Conceptually:

```text
unlock_sessions
    1
    │
    └────── 0..1 unlock_completions
```

The database should enforce the one-completion-per-session rule through an appropriate uniqueness constraint.

An incomplete, expired, or rejected session therefore does not require a completion record.

### 4.3 Configuration Relationships

Presentation, unlock, and reward configuration belong conceptually to the campaign.

They are not independent reusable entities in the MVP.

For example:

```text
Campaign A
    presentation = popup
    unlock_method = timer
    reward = content
```

Another campaign may independently use:

```text
Campaign B
    presentation = content_gate
    unlock_method = timer
    reward = content
```

Both campaigns may use the same underlying timer implementation without requiring a shared `unlock_methods` database record.

This keeps the database focused on persistent business state rather than turning implementation types into unnecessary database entities.

### 4.4 Session Configuration Snapshot

An unlock session should preserve the relevant unlock configuration that was active when the session was created.

This prevents later campaign changes from altering the validity of an already-started session.

For example:

```text
Campaign
    Timer = 30 seconds
          │
          ▼
Unlock Session created
    Required duration = 30 seconds
          │
          ▼
Campaign changed to 60 seconds
          │
          ▼
Existing session remains valid according to
the configuration that existed when it started.
```

The exact fields used to preserve this configuration will be determined during schema implementation.

The important rule is:

> A session's security-sensitive requirements must not change unexpectedly because the parent campaign was edited after the session was created.

---

## 5. Campaign Configuration

A campaign contains the configuration required to define a Reward Gate experience.

The MVP should keep this configuration explicit and understandable.

Conceptually, a campaign contains:

```text
Campaign
├── Presentation configuration
├── Unlock configuration
└── Reward configuration
```

The exact physical columns will be determined during schema implementation.

### 5.1 Presentation Configuration

The campaign should identify which presentation is used.

Presentation-specific settings are stored in the campaign's
`presentation_settings` JSON field.

The structure of this JSON depends on the presentation type.

For example, Popup Gate settings may contain:

```json
{
  "title": "Unlock Content",
  "message": "Please wait while your content is being unlocked.",
  "show_message": true,
  "content": "<div>...</div>"
}
```

For Content / Read-more Gate, the settings contain only configuration for
the gate itself.

Examples may include:

```json
{
  "continue_text": "Continue reading"
}
```

The Content Gate does **not** store article HTML, protected content,
public content, or copies of customer website content in
`presentation_settings`.

The Content Gate split-point and article content remain entirely on the
customer's website.

Presentation-specific settings should only be stored where they are
actually required. The schema should not create a large collection of
nullable columns for hypothetical presentation types.

#### 5.1.1 Content Ownership Boundary

Reward Gate and customer website content have separate responsibilities.

Reward Gate database:

* stores campaign configuration;
* stores presentation configuration;
* stores unlock and reward configuration;
* stores unlock sessions and completions.

Customer website:

* stores the article/content;
* determines the article structure;
* places the Content Gate split-point;
* serves the article HTML.

The MVP does not copy, synchronize, parse, or manage the customer's article
content in the Reward Gate database.

The database therefore has no Content Gate article-content field or separate
content table.

### 5.2 Unlock Configuration

The campaign should identify the unlock method and its required configuration.

For the MVP:

```text
timer
```

A timer campaign may require configuration such as:

- required duration
- expiration behavior

Security-sensitive values should be validated by the application and enforced using server-side session state.

### 5.3 Reward Configuration

The campaign should identify the reward type and its required configuration.

For the MVP:

```text
content
```

The reward configuration should describe what becomes available after successful verification.

The database should not attempt to implement the reward itself.

### 5.4 Configuration Strategy

The MVP should prefer explicit columns for configuration that is:

- simple
- stable
- required by the current product

For example:

```text
campaigns
    presentation_type
    unlock_method
    timer_duration
    reward_type
```

may be preferable to a generic configuration table when the requirements are this small.

If configuration becomes genuinely dynamic as new product features are introduced, the schema can be extended based on the actual requirement.

The MVP should not introduce a universal key/value or metadata system merely to avoid adding explicit columns later.

Presentation-specific JSON should contain configuration rather than customer
content.

For example, `presentation_settings` may contain Popup Gate title/message
settings or other presentation behavior, but Content / Read-more Gate does
not use this field to store or transport article HTML.

The application and frontend integration determine how Content Gate operates
on content already present on the customer's page.

### 5.5 Campaign Changes

Campaign configuration may change over time.

Changes to a campaign must not invalidate or silently modify security-sensitive requirements of existing unlock sessions.

An unlock session therefore preserves the relevant configuration required to validate that session.

The campaign represents the current configuration for newly created sessions.

Existing sessions are validated according to the configuration captured when those sessions were created.

### 5.6 Configuration Validation

The application layer is responsible for validating that campaign configuration is internally consistent.

Examples include:

```text
presentation_type = popup
unlock_method = timer
timer_duration > 0
reward_type = content
```

The database should enforce structural constraints where practical, while the application remains responsible for higher-level configuration rules.

The configuration model should remain simple until real product requirements demonstrate a need for greater flexibility.

---

## 6. Unlock Session Lifecycle

An unlock session represents the server-side lifecycle of one visitor's attempt to complete an unlock action.

A session is created by the server for a specific campaign.

The basic lifecycle is:

```text
Created
   │
   ▼
Active
   │
   ├──────────────► Expired
   │
   ▼
Completed
```

A session may also become invalid or rejected as a result of server-side validation.

The exact state model will be finalized during implementation.

### 6.1 Session Creation

When a visitor begins an unlock flow, the server creates an unlock session.

The session should record:

- campaign identifier
- unlock method
- relevant unlock configuration
- creation/start time
- expiration time where applicable
- security token information
- visitor/session association where applicable
- current state

The client must not be able to choose or modify security-sensitive session values directly.

### 6.2 Active Session

An active session represents an unlock attempt that may still be completed.

For a timer unlock, the server records the start time and determines when the required duration has elapsed.

The browser may display a countdown based on this information, but the browser clock is never authoritative.

### 6.3 Session Expiration

A session becomes expired when its allowed completion window has passed without a valid completion.

Expired sessions cannot subsequently be completed.

Expired sessions may eventually be removed by a cleanup process according to the retention policy.

### 6.4 Session Completion

A session becomes completed only after successful server-side verification.

The completion process should:

1. Load the session.
2. Verify that the session exists.
3. Verify that the session is still valid.
4. Verify the security token.
5. Verify the required unlock conditions.
6. Verify that the session has not already completed.
7. Create the unlock completion.
8. Mark the session as completed.

These operations should be performed in a way that prevents two simultaneous requests from successfully completing the same session.

### 6.5 Campaign Changes

The validity of an active session must not unexpectedly change because the campaign configuration was edited after the session was created.

For example:

```text
Campaign
    Timer = 30 seconds
          │
          ▼
Session created
    Required duration = 30 seconds
          │
          ▼
Administrator changes campaign
    Timer = 60 seconds
          │
          ▼
Existing session
    Still requires 30 seconds
```

The session therefore preserves the security-sensitive configuration required to validate itself.

The campaign's current configuration applies to newly created sessions.

### 6.6 Server-Side Time

Security-sensitive timing decisions must use server-side timestamps.

The application must not trust:

- browser clocks
- client-provided elapsed time
- client-provided completion timestamps

The client may report that the visitor believes the required action has finished, but the server determines whether enough time has actually elapsed.

### 6.7 Concurrency

Session completion must be safe when multiple completion requests arrive close together.

The database and application should ensure that:

```text
One Unlock Session
        │
        └──────► Maximum one successful Completion
```

The implementation should use appropriate database constraints and transaction handling rather than relying solely on application-level checks.

---

## 7. Completion Lifecycle

An unlock completion represents a successfully verified completion of an unlock session.

A completion is created only after the server has validated the session and confirmed that all required unlock conditions have been satisfied.

The conceptual flow is:

```text
Client reports completion
          │
          ▼
Server loads session
          │
          ▼
Validate security token
          │
          ▼
Validate session state
          │
          ▼
Validate elapsed time / unlock conditions
          │
          ▼
Validate applicable limits
          │
          ▼
Atomically create completion
          │
          ▼
Mark session completed
          │
          ▼
Grant reward / unlock
```

### 7.1 Server Verification

The client-side completion event is only a request for verification.

The server must independently determine whether:

- the session exists
- the session belongs to the expected campaign
- the session is still valid
- the security token is valid
- the required unlock conditions have been satisfied
- the session has not already completed
- applicable limits have not been exceeded

A client request must never be sufficient by itself to create a successful completion.

### 7.2 One-Time Completion

An unlock session may have at most one successful completion.

The database should enforce this rule through an appropriate uniqueness constraint on the unlock session reference.

Conceptually:

```text
unlock_session_id
        │
        └────── 0..1 unlock_completion
```

If a completion request is received after the session has already completed, the application must not create another completion record.

### 7.3 Atomic Completion

Completion creation and session state transition should be handled atomically.

Conceptually:

```text
BEGIN TRANSACTION
        │
        ├── Validate session state
        ├── Validate unlock conditions
        ├── Create completion
        └── Mark session completed
        │
        ▼
COMMIT
```

If the operation fails, the transaction should not leave the database in a partially completed state.

The exact transaction and locking strategy will be determined during implementation.

### 7.4 Completion Timestamp

The completion timestamp must be generated by the server.

The application must not rely on a completion time supplied by the browser.

This timestamp represents when the server accepted the unlock as successfully verified.

### 7.5 Reward / Unlock

Successful completion may trigger the configured reward or unlock behavior.

The completion record represents the verified state transition.

The reward system should consume this verified result rather than independently deciding whether the visitor has completed the unlock.

This maintains the separation:

```text
Unlock Verification
        │
        ▼
Completion
        │
        ▼
Reward / Unlock
```

### 7.6 Failed Completion Requests

Invalid completion requests should not create completion records.

Examples include:

- invalid session
- expired session
- invalid security token
- insufficient elapsed time
- already completed session
- exceeded visitor or campaign limits

Failed attempts may be logged or counted for anti-abuse purposes where required, but the MVP should not create persistent records for every failed request without a concrete reason.

---

## 8. Anti-Abuse Data

The MVP requires enough persistent information to prevent trivial abuse of the unlock workflow.

Relevant information may include:

- unlock session identifier
- campaign identifier
- security token hash
- session creation/start time
- session expiration time
- completion time
- session state
- visitor/session identifier where appropriate

The database should support the following basic protections:

- one-time completion
- replay prevention
- session expiration
- server-side timing validation
- basic visitor/session frequency limits
- campaign-level usage limits where required

### 8.1 Token Storage

Security tokens should not be stored in plaintext when the application does not need to recover the original token.

Where practical, the database should store a secure hash of the token and compare the hash of the presented token during verification.

The exact token-generation and hashing mechanism will be determined during security and implementation design.

### 8.2 Visitor Identification

Visitor identification should use the minimum information required for the relevant anti-abuse rule.

The MVP should prefer pseudonymous or short-lived identifiers rather than storing unnecessary personal information.

The database should not become a general-purpose visitor-tracking system.

### 8.3 Failed Attempts

The MVP does not require a permanent database record for every failed completion request.

Failed requests may be handled through:

- application logs
- rate limiting
- temporary counters
- other short-lived mechanisms

Persistent abuse records should only be introduced when a concrete product or security requirement justifies them.

### 8.4 Limits

Campaign or visitor limits may eventually require persistent counters or event records.

The MVP should implement only the limits that are actually required by the first release.

If a limit can be safely enforced using existing session or completion data, a separate counter table should not be introduced merely for convenience.

### 8.5 Privacy

Anti-abuse data should be limited to what is necessary for security and product functionality.

The application should avoid storing:

- unnecessary personally identifiable information
- complete browsing histories
- unrelated visitor activity
- permanent identifiers when short-lived identifiers are sufficient

Retention periods for visitor-related data should be defined before the corresponding production feature is implemented.

---

## 9. Visitor / Session Identification

Reward Gate may need to associate unlock activity with a visitor or browser session for basic anti-abuse and frequency limiting.

The MVP should minimize the amount of visitor information stored.

The system should distinguish between:

- the Reward Gate unlock session
- the visitor/browser identifier used for anti-abuse
- personally identifiable information

These concepts should not be treated as interchangeable.

### 9.1 Reward Gate Unlock Session

The unlock session represents one specific attempt to complete an unlock.

It is a server-side application record and should have its own unique identifier.

The unlock session should not depend on personally identifying the visitor.

### 9.2 Visitor / Browser Identifier

Where visitor-level limits are required, the application may use a pseudonymous identifier associated with the visitor or browser.

The identifier should be:

- sufficient for the required anti-abuse rule
- difficult to guess or forge
- limited in scope where practical
- retained only as long as necessary

The exact implementation may use a signed or securely generated identifier rather than relying directly on raw IP addresses.

### 9.3 IP Addresses

IP addresses may be useful for security controls such as rate limiting, but they should not automatically become the primary visitor identity.

IP addresses can be shared by many legitimate visitors and can change for the same visitor.

If IP addresses are stored, the application should have a concrete reason for doing so and should apply an appropriate retention policy.

### 9.4 Personal Information

The MVP should not require names, email addresses, accounts, or other personally identifiable information merely to complete an unlock.

Personally identifiable information should only be introduced when a specific product feature requires it.

The database should therefore avoid creating a general-purpose `visitors` table unless a real requirement emerges.

### 9.5 Privacy and Retention

Visitor-related identifiers should have a defined retention period.

Data should not be retained indefinitely merely because storage is cheap.

The eventual retention policy should consider:

- the purpose of the identifier
- anti-abuse requirements
- analytics requirements
- applicable privacy requirements
- whether the identifier can be deleted or anonymized after its useful lifetime

The MVP should favor minimal data collection and short retention periods.

---

## 10. Timestamps

Important state changes should have explicit timestamps.

Examples include:

- campaign creation
- campaign update
- unlock session creation
- unlock session expiration
- unlock completion
- record deletion where retention requirements justify it

Timestamps should be generated and stored consistently by the application and database.

Security-sensitive timestamps must use server-side time.

The application must not depend on timestamps supplied by the visitor's browser for:

- session creation
- timer validation
- session expiration
- completion validation
- reward eligibility

### 10.1 Campaign Timestamps

Campaigns should normally have timestamps for:

- creation
- last update

These timestamps describe changes to the campaign configuration.

### 10.2 Unlock Session Timestamps

Unlock sessions should record the timestamps required to determine their lifecycle.

At minimum, this may include:

- creation/start time
- expiration time where applicable
- completion time where applicable

The session start time must be established by the server when the session is created.

### 10.3 Completion Timestamp

An unlock completion should have a server-generated completion timestamp.

This timestamp represents when the server successfully verified the unlock.

It must not be copied from a client-provided timestamp.

### 10.4 Timezone and Storage

Database timestamps should use a consistent representation independent of the customer's local timezone.

The application should use a single canonical timezone for stored timestamps and convert timestamps for display when necessary.

The exact timezone convention will be finalized during implementation.

The important rule is:

> Stored timestamps must be unambiguous and consistently interpreted across servers, customers, and visitors.

---

## 11. Indexing Strategy

Indexes should be created for columns that are frequently used for:

- foreign-key lookups
- session retrieval
- token lookup
- campaign filtering
- status filtering
- time-based queries
- analytics queries

Indexes should be added based on actual query patterns.

The MVP should avoid excessive indexing simply because a column might theoretically be queried in the future.

Particular attention should be given to unlock sessions because they are expected to be accessed frequently during the visitor unlock workflow.

---

## 12. Foreign Keys and Constraints

Foreign keys should be used where they provide meaningful data-integrity guarantees.

The core MVP relationships are:

- unlock sessions reference campaigns
- unlock completions reference unlock sessions

These relationships should be enforced at the database level.

### 12.1 Campaign → Unlock Session

Each unlock session belongs to exactly one campaign.

Conceptually:

```text
campaigns
    1
    │
    └────── N unlock_sessions
```

The `unlock_sessions` table should therefore contain a foreign key referencing the campaign primary key.

The foreign-key behavior for campaign deletion should be selected according to the retention requirements.

The application must not leave orphaned unlock sessions.

### 12.2 Unlock Session → Unlock Completion

Each unlock completion belongs to exactly one unlock session.

Conceptually:

```text
unlock_sessions
    1
    │
    └────── 0..1 unlock_completions
```

The `unlock_completions` table should contain a foreign key referencing the unlock session primary key.

A uniqueness constraint on the session reference should enforce the rule that one unlock session can have at most one successful completion.

### 12.3 Primary Keys

Every persistent entity should have a stable primary key.

At minimum:

```text
campaigns
    primary key

unlock_sessions
    primary key

unlock_completions
    primary key
```

The exact primary-key type will be selected during implementation.

### 12.4 Uniqueness Constraints

Important business uniqueness rules should be enforced at the database level.

Examples include:

- unique campaign identifiers where required
- unique unlock session identifiers
- one completion per unlock session
- other unique values where a real business rule requires them

A uniqueness constraint should represent an actual business invariant rather than being added simply because a column "looks like it should be unique."

### 12.5 Required Fields

Fields required for a record to be meaningful should be declared non-nullable where practical.

Application-level validation should still be used for rules that cannot be expressed safely through standard database constraints.

### 12.6 Avoid Mechanical Constraints

Constraints should represent real relationships and business rules.

The database should not introduce additional foreign keys merely to make the schema appear more relational.

Likewise, unnecessary lookup tables should not be created solely to support foreign keys for values that are actually simple configuration types.

The schema should favor clear domain relationships over artificial normalization.

---

## 13. Deletion and Retention

Deletion behavior should be explicitly considered for each major entity.

The MVP should distinguish between:

- active application state
- temporary unlock-session state
- historical completion data
- visitor-related security data

Data should not be retained indefinitely without a clear purpose.

### 13.1 Campaign Deletion

Deleting a campaign must not leave orphaned unlock sessions.

The final deletion behavior should be selected according to the product's retention and analytics requirements.

Possible approaches include:

- preventing deletion while dependent historical data exists
- soft-deleting the campaign
- retaining historical sessions and completions while making the campaign inactive
- explicitly deleting dependent data when historical retention is not required

The MVP should favor predictable behavior over automatic cascading deletion of potentially useful historical data.

### 13.2 Unlock Session Retention

Unlock sessions are operational records and may have limited long-term value.

Expired or otherwise inactive sessions may eventually be cleaned up.

Cleanup should not remove information that is still required for:

- active verification
- replay prevention
- usage limits
- required analytics
- auditing

The retention period should be determined during implementation based on the actual product requirements.

### 13.3 Completion Retention

Successful unlock completions may have longer-term value than individual sessions.

They may be required for:

- analytics
- campaign statistics
- usage limits
- auditing
- future revenue calculations

Completion records should therefore not automatically be deleted when an unlock session expires or is cleaned up.

The exact retention policy will be determined before production analytics or reporting features are implemented.

### 13.4 Visitor-Related Data

Visitor or browser identifiers should have the shortest practical retention period consistent with the security feature that requires them.

If a visitor identifier is no longer required for:

- anti-abuse
- rate limiting
- campaign limits
- analytics

it should not be retained indefinitely.

### 13.5 Security-Sensitive Data

Security-sensitive information should not be retained longer than necessary.

Examples include:

- token hashes
- temporary verification state
- short-lived session information
- temporary anti-abuse counters

The application should prefer cleanup of expired temporary data rather than allowing such data to accumulate indefinitely.

### 13.6 Soft Deletion

Soft deletion should not be introduced automatically for every table.

It should be used only where preserving historical records while making an entity inactive provides a real product benefit.

The MVP should avoid adding deletion flags merely because they are common in generic application architectures.

### 13.7 Retention Policy

The final retention policy should be documented before the corresponding production features are implemented.

Retention decisions should consider:

- security requirements
- analytics requirements
- storage costs
- customer expectations
- applicable privacy requirements
- whether the data can be safely anonymized or deleted

The guiding principle is:

> Keep data for as long as it has a defined purpose, and remove it when that purpose no longer exists.

---

## 14. Future Extensibility

The schema should allow additional functionality to be introduced without redesigning the entire database.

Future functionality may include:

### Additional Presentation Types

```text
popup
content_gate
full_page
inline
download_gate
embedded_widget
```

### Additional Unlock Methods

```text
timer
link
video
offerwall
survey
external_task
```

### Additional Reward Types

```text
content
download
coupon
credit
feature
external_link
```

Adding a new presentation, unlock method, or reward type should not automatically require a new database table.

If the new type can be represented by the existing campaign configuration model, the schema should remain unchanged.

For example:

```text
Campaign
    presentation_type = new_type
```

may be sufficient if the new presentation requires no additional persistent structure.

### 14.1 When a New Table Is Justified

A new database entity should be introduced when the feature represents persistent information that:

- exists independently of a campaign configuration
- has its own lifecycle
- requires relationships to other persistent entities
- is queried independently
- cannot be represented clearly by existing campaign/session/completion data

A new table should not be created merely because a new implementation class or feature type exists.

### 14.2 Configuration Growth

If a new presentation, unlock method, or reward requires additional configuration, the schema should first consider whether that configuration can be represented with explicit campaign fields.

If configuration becomes genuinely dynamic or significantly more complex, the schema may introduce a more appropriate structure based on the actual requirement.

The MVP should not introduce a universal configuration framework in anticipation of hypothetical future features.

### 14.3 Session and Completion Stability

Future presentation types and unlock methods should continue to use the existing session and completion architecture where their behavior fits the same verification model.

The goal is:

```text
Presentation
      │
      ▼
Unlock Engine
      │
      ▼
Unlock Session
      │
      ▼
Verification
      │
      ▼
Unlock Completion
      │
      ▼
Reward
```

Adding a new presentation should therefore not require a new session or completion system.

Adding a new unlock method should reuse the common session and verification lifecycle where possible.

Adding a new reward should consume the verified completion rather than implementing its own independent verification mechanism.

### 14.4 Avoid Premature Generalization

Extensibility should not become an excuse for generic schema design.

A real second or third implementation should be used to identify useful abstractions.

The architecture should evolve from demonstrated requirements rather than attempting to predict every future product feature.

---

## 15. MVP Schema

The MVP is expected to require the following core database tables:

```text
campaigns
unlock_sessions
unlock_completions
```

Presentation, unlock method, and reward are represented as campaign configuration in the MVP rather than as separate reusable tables.

Conceptually:

```text
campaigns
├── presentation configuration
├── unlock configuration
└── reward configuration
        │
        ▼
unlock_sessions
        │
        ▼
unlock_completions
```

The exact columns, data types, indexes, and constraints will be finalized before implementation migrations are created.

The initial schema should support:

- Popup Gate
- Content / Read-more Gate
- Timer unlock
- Content unlock
- Server-side verification
- Basic anti-abuse
- Basic campaign configuration
- Unlock completion tracking
- Session expiration
- One-time completion

The MVP should not create tables for future features that are not required by the first release.

### 15.1 Campaigns

The `campaigns` table should contain the persistent configuration required to
define a campaign.

This may include:

* campaign identity;
* name;
* status;
* presentation type;
* presentation settings JSON;
* unlock method;
* unlock configuration where required;
* timer duration;
* frequency limits where required;
* reward type;
* reward configuration where required;
* creation timestamp;
* update timestamp.

The `presentation_settings` JSON field stores presentation-specific
configuration.

For Content / Read-more Gate, it stores gate configuration only and does
not contain customer article HTML.

The exact fields should be based on the requirements in the specification
and finalized during implementation.

### 15.2 Unlock Sessions

The `unlock_sessions` table should contain the server-side state required for an individual visitor unlock attempt.

This may include:

- session identity
- campaign reference
- unlock method
- relevant unlock configuration or security-sensitive parameters
- start time
- expiration time
- current state
- security token hash
- visitor/session identifier where required

The session should preserve the security-sensitive values required to validate the attempt independently of later campaign configuration changes.

### 15.3 Unlock Completions

The `unlock_completions` table should contain successfully verified unlock events.

This may include:

- completion identity
- unlock session reference
- completion timestamp
- relevant verification information
- information required for analytics or auditing where justified

The unlock session reference should be unique so that a session can have at most one successful completion.

### 15.4 Schema Scope

The MVP schema should remain deliberately small.

Tables for the following should not be introduced until a concrete requirement exists:

- visitors
- analytics events
- fraud detection
- scheduling
- targeting
- reusable presentation definitions
- reusable unlock method definitions
- reusable reward definitions
- generic metadata systems
- plugin systems

These may become valid future additions, but they are not required to establish the initial unlock workflow.

---

## 16. Database Migration Strategy

Database changes should be managed through versioned migrations rather than manual production changes.

Each migration should:

- have a clear purpose
- be applied in a predictable order
- be safe to run only once
- be tested before deployment
- avoid destructive changes without explicit consideration
- be reversible where practical
- preserve existing customer data unless a destructive change is explicitly required

The migration system should eventually support:

```text
Development database
        │
        ▼
Migration
        │
        ▼
Test database
        │
        ▼
Commercial release
        │
        ▼
Customer installation / upgrade
```

### 16.1 New Installations

A new Reward Gate installation should be able to initialize its database by applying the complete migration history in order.

The installation process should not require the customer to manually create tables or modify SQL files.

### 16.2 Customer Upgrades

Existing customer installations must be upgradeable through migrations.

An application upgrade should be able to determine which migrations have already been applied and execute only the migrations that are still pending.

Conceptually:

```text
Customer Database
       │
       ▼
Check Migration Version
       │
       ▼
Apply Pending Migrations
       │
       ▼
Updated Database
```

Database upgrades should not require customers to manually edit production tables.

### 16.3 Migration Tracking

The migration system should maintain a small amount of metadata identifying which migrations have already been applied.

The exact table and migration format will be determined when the migration technology is selected.

### 16.4 Destructive Changes

Destructive migrations require explicit consideration.

Examples include:

- dropping a column
- dropping a table
- changing a column in a way that can lose data
- deleting historical records
- changing a constraint that existing data violates

Where practical, schema changes should be introduced through multiple safe steps rather than combining data loss with structural changes in a single migration.

### 16.5 Migration Testing

Migrations should be tested against both:

- a fresh database
- a database containing data from previous application versions

A migration that works on a fresh installation is not automatically safe for an existing customer installation.

### 16.6 Migration Technology

The migration technology will be selected during implementation.

The MVP should favor a small, reliable migration system that:

- works with both MySQL and MariaDB
- is compatible with the project's supported PHP versions
- can run without requiring the customer to install development tooling
- can be integrated into the commercial installation and upgrade process

The migration system should not introduce a large framework dependency unless the actual product requirements justify it.

The final commercial installation process should be able to execute required migrations through the application's installation or upgrade mechanism rather than requiring customers to have SSH access, Composer, or database administration tools.

