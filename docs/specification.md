# Reward Gate

**Status:** Development
**Specification Version:** 1.0

## 1. Product Overview

Reward Gate is a self-hosted PHP application for creating and managing
reward-based content and action gates.

A website owner can place a gate in front of content, downloads, features,
or other actions. A visitor performs a required action, the action is
verified by the server, and the configured reward is granted.

The fundamental product flow is:

**Presentation → Unlock Method → Verification → Reward**

The initial product is intentionally focused on a small number of
well-defined use cases, while the underlying architecture is designed to
support additional presentation types, unlock methods, and reward types
without requiring a fundamental redesign.

The first implementation is a standalone PHP application using:

- PHP
- MariaDB / MySQL
- HTML
- CSS
- Vanilla JavaScript

The initial product does not depend on a large application framework.

Reward Gate is intended to evolve through several stages:

**MVP → First Commercial Release → Reusable Engine → Multi-Platform Product
→ Reward Optimization Platform**

The long-term goal is to provide a general-purpose reward and unlock engine
that can be integrated with:

- Standalone PHP websites
- WordPress
- Laravel
- Other PHP applications
- Custom websites through JavaScript

A future hosted SaaS version may also be developed if the self-hosted
product demonstrates sufficient market demand.

## 2. Product Philosophy

Reward Gate should be developed according to the following principles.

### 2.1 Build a Product, Not an Architecture Exercise

The primary goal is to build something that provides real value to website
owners and can eventually be sold.

Technical complexity should be introduced when it provides a concrete
benefit to the product, not merely because a feature might be useful
someday.

### 2.2 The Reward / Unlock Engine Is the Product

Popup Gate and Content / Read-more Gate are presentation types.

They are not the fundamental product.

The underlying system is responsible for the common workflow:

**Presentation → Unlock Method → Verification → Reward**

Different presentation types should use the same underlying business logic
where practical.

### 2.3 Avoid Premature Abstraction

The MVP should remain simple and understandable.

The codebase should have sensible boundaries between presentation logic,
business logic, verification, rewards, and persistence.

More formal abstractions should be introduced when actual requirements
justify them.

The project should not create generic frameworks, interfaces, factories,
or extension systems solely to support hypothetical future features.

### 2.4 Design for Extension Without Over-Engineering

The product should be able to evolve beyond the initial MVP.

Future functionality may introduce:

- Additional presentation types
- Additional unlock methods
- Additional reward types
- Additional campaign configuration
- Additional integrations

The initial implementation should therefore avoid unnecessary hard-coding
that would make these additions unnecessarily difficult.

However, the MVP should not contain implementations or database structures
for features that do not yet exist.

### 2.5 User Experience Is a Product Feature

Reward Gate should provide a clear and predictable visitor experience.

The product should avoid:

- Deceptive buttons
- Misleading instructions
- Fake download controls
- Forced redirects
- Unnecessarily difficult interfaces
- Confusing unlock flows

The visitor should understand:

1. What is currently locked.
2. What action is required.
3. How much progress has been made.
4. What will happen after successful completion.

A good visitor experience is considered part of the product's competitive
advantage.

### 2.6 Validate Before Expanding

Development priorities should be influenced by real usage and commercial
feedback whenever possible.

The team should regularly ask:

> Does this feature help prove the product, sell the product, improve the
> visitor experience, or materially improve the system?

If not, it should generally be deferred.

### 2.7 MVP vs. Commercial Product

The MVP exists to prove the core workflow.

It is not necessarily the final product offered to customers.

The first commercial release is expected to contain a substantially more
capable feature set, including selected strategic "killer features" that
provide meaningful differentiation.

Features should therefore be classified as:

- **MVP**
- **First Commercial Release**
- **Post-Release**
- **Long-Term**

## 3. Core Concept

Reward Gate is built around a common reward and unlock workflow.

The core conceptual model is:

**Campaign → Presentation → Unlock Method → Verification → Reward**

Additional concerns operate around this workflow:

**Limits / Anti-Abuse → Analytics**

### 3.1 Campaign

A campaign defines what the visitor is being asked to do and what happens
after successful completion.

A campaign may eventually configure:

- Presentation
- Unlock method
- Reward
- Timing
- Visitor limits
- Frequency rules
- Appearance
- Analytics
- Optimization settings

The MVP should use only the configuration required by the supported features.

### 3.2 Presentation

A presentation determines **how the unlock experience is shown to the
visitor**.

MVP presentations:

- Popup Gate
- Content / Read-more Gate

Future presentations may include:

- Full Page Gate
- Inline Gate
- Blurred Content Gate
- Download Gate
- Button Gate
- Embedded Widget
- Feature Gate
- Premium Content Gate

Presentation-specific code should control the user interface, while the
underlying unlock logic should remain shared.

### 3.3 Unlock Method

An unlock method determines **what action the visitor must complete**.

The MVP supports:

- Timer

Potential future methods include:

- Link Visit
- Rewarded Video
- Offerwall
- Survey
- External Task

Each method should ultimately provide a way for the server to determine
whether the required action was successfully completed.

### 3.4 Verification

Verification determines whether the reported visitor action is valid.

The browser is not trusted to make this decision.

The client may report events such as:

- Unlock started
- Timer displayed
- Timer completed
- Completion requested

The server determines whether the unlock requirements have actually been
satisfied.

### 3.5 Reward

A reward is the result granted after successful verification.

The MVP reward is:

**Unlock / reveal protected content**

Future rewards may include:

- File downloads
- Coupons
- Discount codes
- Credits
- Premium features
- External links
- Other digital rewards

The reward system should not be permanently coupled to content unlocking.

### 3.6 Limits and Anti-Abuse

Reward Gate must prevent trivial repeated abuse of rewards.

Examples include:

- One-time completion
- Replay protection
- Session expiration
- Minimum completion time
- Visitor/session frequency limits

The MVP provides basic protection.

More advanced fraud detection and optimization may be introduced later.

### 3.7 Analytics

Campaign activity should eventually provide meaningful information about
the performance of the unlock flow.

Important events and metrics may include:

- Gates shown
- Unlocks started
- Unlocks completed
- Completion rate
- Abandonment
- Reward grants
- Revenue, where applicable

Analytics should focus on information that can support product decisions
rather than vanity metrics.

### 3.8 Separation of Responsibilities

The system should maintain a clear separation between:

**Presentation**

How the visitor sees and interacts with the gate.

**Unlock Method**

What action the visitor must perform.

**Verification**

Whether that action is valid.

**Reward**

What the visitor receives after successful verification.

This separation is central to the product's ability to support additional
presentation types and unlock methods without duplicating the core business
logic.

## 4. MVP

### 4.1 Presentation Types

The MVP supports two presentation types.

#### 4.1.1 Popup Gate

The Popup Gate presents the unlock flow inside a modal interface.

Typical workflow:

1. The gate appears.
2. The visitor is shown the required action.
3. The timer begins.
4. The visitor waits for the required duration.
5. The client reports completion.
6. The server verifies the unlock session.
7. The protected content is unlocked.

The Popup Gate must:

- Work on desktop and mobile.
- Clearly communicate the required action.
- Display progress and remaining time.
- Prevent interaction with protected content while locked.
- Unlock automatically after successful verification.
- Avoid deceptive or misleading UI.

#### 4.1.2 Content / Read-more Gate

The Content / Read-more Gate leaves part of the content visible while
protecting the remainder.

Typical workflow:

1. The visitor reads the visible portion of the content.
2. The remaining content is hidden or otherwise protected.
3. A clear "Continue Reading" or "Unlock the Rest" control is displayed.
4. The visitor starts the unlock flow.
5. The timer begins.
6. The visitor completes the required duration.
7. The server verifies completion.
8. The protected content becomes visible.

The amount of content visible before the gate should eventually be
configurable.

The MVP may use a simple configuration value or sensible default rather
than requiring a full visual campaign builder.

#### 4.1.3 Shared Presentation Principle

Both presentation types must use the same underlying unlock/session and
verification logic wherever practical.

Presentation-specific code should be responsible for:

- Rendering the interface.
- Handling presentation-specific user interaction.
- Displaying unlock progress.
- Revealing the reward after successful verification.

Presentation-specific code should not independently implement:

- Unlock session creation.
- Completion validation.
- Replay protection.
- Reward authorization.
- Anti-abuse rules.

This separation is an important architectural test of the MVP.

### 4.2 Unlock Methods

The MVP supports one unlock method:

**Timer**

The Timer method requires the visitor to remain in the unlock flow for a
configured minimum duration before the reward can be granted.

#### 4.2.1 Timer

The timer must be enforced by the server rather than relying solely on the
client-side countdown.

The general workflow is:

1. The visitor starts an unlock session.
2. The server records the session start time.
3. The server determines the minimum completion time.
4. The client displays the countdown to the visitor.
5. The client reports that the timer has completed.
6. The server verifies that the required time has actually elapsed.
7. The server marks the session as completed if all requirements are valid.
8. The configured reward is granted.

The client-side timer exists primarily for user feedback.

It must not be treated as the authority for determining whether the visitor
has satisfied the timer requirement.

#### 4.2.2 Timer Configuration

The exact timer configuration will be determined during implementation.

The system should support at least:

- Configurable duration.
- Server-side minimum completion time.
- Session expiration.
- One-time completion.

The MVP does not require multiple simultaneous unlock methods within a
single campaign.

#### 4.2.3 Future Unlock Methods

Additional unlock methods are outside MVP scope.

Potential future methods include:

- Link Visit
- Rewarded Video
- Offerwall
- Survey
- External Task

Future methods should integrate with the same general unlock workflow:

**Unlock Method → Verification → Reward**

Adding a new unlock method should not require duplicating the core
campaign, session, completion, or reward logic.

### 4.3 Rewards

The MVP supports one reward type:

**Unlock / Reveal Protected Content**

A successful unlock allows the visitor to access content that was
previously protected by the Reward Gate.

The same reward concept must work with both MVP presentation types.

#### 4.3.1 Popup Gate Reward

For a Popup Gate, successful verification causes the protected content
associated with the gate to become accessible.

#### 4.3.2 Content / Read-more Gate Reward

For a Content / Read-more Gate, successful verification reveals the
previously protected portion of the content.

#### 4.3.3 Reward Authorization

A reward must only be granted after successful server-side verification.

The client must not be able to directly grant or authorize its own reward.

The server should determine:

- Whether the unlock session is valid.
- Whether the required action has been completed.
- Whether the completion has already been used.
- Whether applicable limits have been exceeded.
- Whether the reward can be granted.

#### 4.3.4 Future Reward Types

Additional reward types are outside MVP scope.

Potential future rewards include:

- File downloads
- Coupons
- Discount codes
- Credits
- Premium features
- External links
- Digital products
- Other site-defined rewards

Future reward types should use the same general verification and
authorization workflow rather than introducing separate unlock systems.

### 4.4 Core Workflow

The MVP uses a common unlock workflow for both presentation types.

The fundamental flow is:

**Visitor → Presentation → Unlock Session → Timer → Server Verification → Reward**

#### 4.4.1 Popup Gate Flow

1. The visitor loads a page containing a Popup Gate.
2. The Popup Gate is displayed.
3. The visitor starts the unlock process.
4. The server creates an unlock session.
5. The server records the session start time and required completion time.
6. The client displays the countdown.
7. The visitor waits until the required duration has elapsed.
8. The client requests completion.
9. The server validates the unlock session.
10. The server verifies that the minimum completion time has elapsed.
11. The server verifies that the session has not already been completed or
    invalidated.
12. The server records the successful completion.
13. The reward is granted.
14. The protected content becomes accessible.
15. The unlock interface is closed or otherwise removed from the active
    content flow.

#### 4.4.2 Content / Read-more Gate Flow

1. The visitor loads a page containing a Content / Read-more Gate.
2. The configured visible portion of the content is displayed.
3. The protected portion remains inaccessible.
4. The visitor selects the unlock control.
5. The server creates an unlock session.
6. The server records the session start time and required completion time.
7. The client displays the countdown.
8. The visitor waits until the required duration has elapsed.
9. The client requests completion.
10. The server validates the unlock session.
11. The server verifies that the minimum completion time has elapsed.
12. The server verifies that the session has not already been completed or
    invalidated.
13. The server records the successful completion.
14. The reward is granted.
15. The protected portion of the content becomes accessible.

#### 4.4.3 Shared Workflow

The two presentation types should share the same underlying:

- Unlock session creation.
- Timer requirements.
- Completion verification.
- Replay protection.
- Reward authorization.
- Anti-abuse rules.

Only the presentation and visitor interaction should differ.

#### 4.4.4 Invalid Completion

If completion verification fails, the reward must not be granted.

Possible failure conditions include:

- Invalid session.
- Invalid or expired token.
- Insufficient elapsed time.
- Already completed session.
- Expired session.
- Exceeded usage limit.
- Other server-side validation failure.

The client should receive an appropriate failure response and must not
assume that the reward was granted.

#### 4.4.5 Completion Must Be Idempotent

A successful completion must not grant the same reward multiple times.

If the client submits the same completion request more than once, the
server must ensure that the reward cannot be granted repeatedly.

The exact implementation will be determined during the technical design
phase.

### 4.5 Security

The server is authoritative for all security-sensitive unlock decisions.

The browser and client-side JavaScript must be treated as untrusted.

Client-side code may:

- Display the unlock interface.
- Display the countdown.
- Track UI state.
- Report visitor actions.
- Request completion.

Client-side code must not be responsible for deciding whether an unlock
is valid or whether a reward may be granted.

#### 4.5.1 Server-Side Verification

Before granting a reward, the server must verify at least:

- The unlock session exists.
- The unlock session belongs to the applicable campaign/context.
- The security token is valid.
- The session has not expired.
- The required minimum completion time has elapsed.
- The session has not already been completed.
- Applicable usage or anti-abuse limits have not been exceeded.
- The requested reward is authorized for the session.

#### 4.5.2 Client-Side Manipulation

The system must not rely on client-controlled values for security decisions.

Examples of values that must not be trusted include:

- Countdown duration.
- Completion timestamp supplied by the client.
- Completion status supplied by the client.
- Reward identifiers supplied without server-side validation.
- Unlock state stored only in JavaScript.
- Unlock state stored only in browser storage.

A visitor modifying JavaScript, browser storage, network requests, or form
data must not be able to trivially bypass the unlock requirements.

#### 4.5.3 Unlock Sessions

Each unlock attempt should use a server-side unlock session.

The session should have:

- A unique identifier.
- A secure token or equivalent authentication mechanism.
- A server-side creation timestamp.
- A required completion time or equivalent validation data.
- An expiration time.
- A completion state.

The exact session and token implementation will be defined during the
technical architecture phase.

#### 4.5.4 Replay Protection

A successfully completed unlock session must not be reusable to grant the
same reward repeatedly.

Completion requests must be protected against replay.

A previously completed, expired, or otherwise invalid session must not be
accepted as a new valid completion.

#### 4.5.5 Protected Content

Where the reward provides access to protected content, the implementation
must ensure that the protection mechanism cannot be trivially bypassed by
simply changing a client-side flag.

For content that is intended to remain inaccessible before successful
completion, the protected state should be enforced in a way appropriate to
the content type.

The exact protection mechanism will be determined during implementation.

#### 4.5.6 Security Scope

The MVP is intended to prevent straightforward client-side bypasses and
replay attacks.

It is not intended to provide complete protection against sophisticated
fraud, automation, distributed abuse, browser instrumentation, or
determined attackers.

Additional anti-fraud and abuse controls may be introduced as the product
develops.

## 5. First Commercial Release

The first commercial release should be substantially more capable than the bare MVP.

The MVP exists to prove the core Reward Gate workflow:

**Presentation → Unlock Method → Verification → Reward**

The first commercial release should turn that working foundation into a product that website owners can realistically install, configure, operate, and pay for.

The commercial release should prioritize features that:

- make Reward Gate useful to real website owners
- improve visitor experience
- provide meaningful campaign control
- provide actionable analytics
- demonstrate the long-term product direction
- differentiate Reward Gate from simple popup/gate scripts

The commercial release should still avoid unnecessary infrastructure such as SaaS, billing, multi-tenancy, or framework-specific integrations unless those features are independently justified.

### 5.1 Campaign Management

The commercial release should allow website owners to create and manage multiple campaigns.

A campaign should define the behavior of a Reward Gate without requiring the owner to modify application code.

Potential campaign configuration should include:

- campaign name
- enabled / disabled state
- presentation type
- unlock method
- reward type
- timer duration
- protected content behavior
- visitor frequency limits
- basic anti-abuse settings
- appearance settings
- campaign objective
- fallback behavior where applicable

Campaigns should be independently configurable.

The system should allow multiple campaigns to coexist on the same installation.

The campaign management interface should remain understandable and avoid exposing unnecessary technical details.

### 5.2 Analytics

The commercial release should provide useful campaign analytics rather than a dashboard dominated by vanity metrics.

Core metrics should include:

- visitors
- gates shown
- unlocks started
- unlocks completed
- completion rate
- abandonment rate
- average completion time
- successful unlocks
- verification failures
- revenue where applicable
- revenue per 1,000 visitors where applicable

Analytics should allow the owner to understand the basic funnel:

**Visitors → Gates Shown → Unlocks Started → Unlocks Completed**

Where sufficient data exists, analytics should support segmentation such as:

- campaign
- presentation type
- unlock method
- device
- geography
- visitor type
- time period

Analytics should eventually move beyond displaying numbers and help answer:

- What is happening?
- Where are visitors dropping out?
- Which configuration performs best?
- What should the owner change?

Advanced predictive analytics and automated optimization are described under the Smart Features section.

### 5.3 Customization

The commercial release should provide enough customization for Reward Gate to integrate naturally into different websites.

Customization should include, where appropriate:

- colors
- typography
- buttons
- borders
- spacing
- overlay appearance
- popup dimensions
- content-gate appearance
- messages
- countdown presentation
- unlock button text
- close behavior
- basic branding

The system should provide sensible defaults so that a campaign can be created without extensive configuration.

Customization should improve integration without turning the product into a full website builder.

The backend administration interface should use **Bootstrap 5** for its UI.

The visitor-facing gate UI should remain lightweight and should not require a frontend framework.

### 5.4 Killer Features

The first commercial release should implement a meaningful selection of Reward Gate's strategic killer features.

The exact implementation order may change based on MVP results and product validation, but the following capabilities represent the intended commercial direction.

#### 5.4.1 Smart Reward Routing

Reward Gate should eventually be able to select the most appropriate unlock method for a visitor based on available information and campaign objectives.

Potential factors include:

- new vs. returning visitor
- device
- geography
- available inventory
- previous campaign performance
- visitor history

The system should be designed so that routing logic can be introduced without changing the fundamental unlock-session architecture.

#### 5.4.2 Campaign Objectives

Campaign owners should be able to define an objective rather than manually optimizing every individual setting.

Potential objectives include:

- maximize revenue
- maximize unlock rate
- maximize engagement
- minimize visitor friction
- balanced performance

Objectives should influence recommendations and, where implemented, automatic campaign optimization.

#### 5.4.3 Reward Gate Recipes

The product should provide predefined campaign configurations for common use cases.

Examples:

- Content Unlock
- Download Gate
- Monetize Content
- Giveaway Gate
- Coupon Gate

Recipes should provide a fast starting point while still allowing customization.

#### 5.4.4 Useful Revenue Analytics

Where revenue-generating unlock methods are supported, analytics should focus on commercially meaningful measurements.

Examples include:

- revenue
- revenue per visitor
- revenue per 1,000 visitors
- completion rate
- revenue by campaign
- revenue by unlock method
- revenue by visitor segment

The goal is to help owners understand whether a campaign is actually producing value.

#### 5.4.5 Automatic Recommendations

Reward Gate should eventually provide actionable recommendations based on campaign performance.

Examples:

> "Your current timer has a low completion rate. Consider reducing the wait time."

> "This unlock method is performing better for mobile visitors."

Recommendations should be based on available campaign data rather than generic advice.

#### 5.4.6 Campaign Health Warnings

The system should detect potentially abnormal campaign behavior.

Examples include:

- falling completion rate
- increasing verification failures
- unusual traffic patterns
- inventory problems
- unexpected revenue drops
- unusually high abandonment

Warnings should help the owner identify problems before they become significant.

#### 5.4.7 One-Click A/B Testing

The system should eventually allow campaign configurations to be tested against each other without requiring manual duplication and analysis.

Potential test variables include:

- timer duration
- presentation
- message
- unlock method
- visual design
- content-gate position

The system should measure meaningful outcomes such as:

- completion rate
- abandonment
- revenue
- engagement

A/B testing should be introduced only where the available traffic makes the results useful.

#### 5.4.8 Smart Fallbacks

Reward Gate should support alternative unlock methods when a preferred method is unavailable.

Example:

**Video → Offer → Link → Timer**

The visitor should experience one continuous unlock flow rather than being presented with a broken or unavailable reward.

Fallback behavior should remain controlled by campaign configuration and applicable provider requirements.

#### 5.4.9 Zero-Configuration Starter Campaign

A new owner should be able to create a working campaign with minimal configuration.

Potential flow:

**Create Campaign → Choose Recipe → Configure Basic Settings → Install / Embed → Done**

The product should provide sensible defaults for common use cases.

#### 5.4.10 Explain My Campaign

Reward Gate should be able to translate technical campaign configuration into plain language.

Example:

> "Visitors see a content gate after approximately 10% of the article. They must wait 10 seconds. Successful completion reveals the remaining content."

This feature should make campaign configuration easier to understand and troubleshoot.

### 5.5 Smart Features

Smart features should move Reward Gate beyond a static gate system while remaining understandable and controllable by the owner.

Potential smart functionality includes:

#### 5.5.1 Goal-Based Optimization

The owner should be able to define an objective and constraints.

Example:

> "Maximize revenue while never making visitors wait more than 20 seconds."

Reward Gate should use these constraints when evaluating campaign configurations.

#### 5.5.2 Revenue vs. UX Balancing

Optimization should consider multiple competing outcomes:

- revenue
- completion rate
- abandonment
- wait time
- engagement
- retention

The system should not optimize a single metric at the expense of visitor experience.

#### 5.5.3 Automatic Method Optimization

Reward Gate should learn which unlock methods perform best for different visitor segments.

Potential segments include:

- device
- geography
- visitor type
- traffic source
- time
- visitor history

The system may then adjust campaign strategy accordingly.

#### 5.5.4 Predictive Recommendations

Where sufficient historical data exists, Reward Gate may estimate:

- expected completion rate
- expected revenue
- expected abandonment
- likely best unlock method
- likely best timer duration

Predictions should be presented as estimates rather than guarantees.

#### 5.5.5 Campaign Performance Intelligence

The system should move beyond displaying statistics.

It should help answer:

- What is happening?
- Why might it be happening?
- What should the owner change?

The purpose is to turn analytics into actionable information.

#### 5.5.6 Automatic Campaign Optimization

The owner should eventually be able to define:

- objective
- maximum wait time
- priorities
- constraints

Reward Gate can then test configurations and automatically move toward better-performing configurations.

Automatic optimization should remain bounded by owner-defined constraints.

#### 5.5.7 Adaptive Campaigns

Campaign behavior may adapt to:

- visitor type
- device
- geography
- traffic source
- time
- visitor history

The objective is to avoid forcing every visitor through identical behavior when evidence shows that different strategies perform better.

#### 5.5.8 Self-Improving Campaigns

Over time, the system should be able to learn from campaign results and improve its decisions.

Potential optimization targets include:

- completion
- revenue
- engagement
- retention
- abandonment reduction

This represents the transition from a configurable gate system toward a continuously improving optimization engine.

### 5.6 Visitor Experience

Visitor experience should be treated as a core product feature rather than an afterthought.

Reward Gate should deliberately avoid:

- fake buttons
- fake download buttons
- misleading redirects
- deceptive instructions
- unnecessarily unclosable interfaces
- confusing interaction flows
- hidden requirements
- unexpected navigation
- excessive waiting

The visitor should always understand:

1. What is being requested.
2. Why the action is required.
3. How long the action should take.
4. What will happen after successful completion.

The interface should work well on:

- desktop
- tablet
- mobile devices

The gate should communicate progress clearly.

Successful completion should result in an obvious and immediate unlock.

Failed verification should provide a clear explanation where possible rather than silently failing.

The product should prioritize a clean and predictable visitor experience because visitor trust is itself a competitive advantage.

The system should also respect applicable advertising, privacy, accessibility, and platform policies.

The commercial release should therefore pursue the following principle:

**Monetization and visitor experience should improve together rather than treating the visitor as an obstacle between the website owner and revenue.**

## 6. Supported Integrations

Reward Gate should eventually support multiple environments while keeping the underlying Reward / Unlock Engine independent from any specific platform.

The integration strategy is:

**Core Engine → Platform Integration → Presentation / Frontend**

The core engine should contain the business logic required for campaigns, unlock sessions, verification, rewards, limits, and anti-abuse.

Platform-specific integrations should provide the environment-specific functionality required to run that engine.

### 6.1 Standalone PHP

The standalone PHP implementation is the initial product environment.

It should provide:

- campaign management
- database integration
- server-side unlock processing
- presentation rendering
- visitor/session handling
- administration interface
- configuration
- analytics
- security and anti-abuse mechanisms

The initial implementation should remain framework-free.

The standalone version is also the primary environment used to validate the product before extracting reusable functionality.

### 6.2 WordPress

WordPress is expected to be the first major commercial integration after the standalone product has been validated.

The WordPress integration should provide native WordPress functionality rather than requiring users to manually integrate the standalone PHP application.

Potential functionality includes:

- WordPress plugin installation
- native admin configuration
- campaign management
- shortcode support
- block support
- content-gate integration
- WordPress user/session integration where appropriate
- WordPress database integration
- WordPress-specific settings

The WordPress integration should reuse the shared Reward Gate Engine wherever practical.

WordPress-specific code should remain outside the core engine.

### 6.3 Laravel

A Laravel integration may be developed after the WordPress implementation and extraction of genuinely reusable core functionality.

Potential functionality includes:

- Laravel package installation
- service provider integration
- Laravel routing
- middleware
- configuration
- database integration
- authentication integration where appropriate
- Blade integration
- campaign management

Laravel-specific functionality should remain outside the framework-independent core.

The Laravel integration is intended to demonstrate that the Reward Gate Engine can operate independently from both standalone PHP and WordPress.

### 6.4 Generic PHP / JavaScript

A lightweight framework-independent integration should eventually allow Reward Gate to be used by custom websites and other PHP-based systems.

The integration may provide:

- PHP integration methods
- JavaScript SDK
- embeddable gate components
- API endpoints
- campaign configuration
- unlock-session handling
- content unlocking
- download unlocking
- event reporting

The goal is to allow developers to integrate Reward Gate without adopting a specific PHP framework or CMS.

This integration may also become the foundation for supporting other platforms that can communicate with Reward Gate through HTTP APIs or JavaScript.

The generic integration should remain lightweight and should not require a large client-side framework.

## 7. Product Architecture Direction

Reward Gate should be developed as a simple application first, while maintaining clear boundaries between presentation, business logic, persistence, and platform-specific integration.

The architecture should support future extraction and reuse without requiring the MVP to implement a complete framework-independent engine from day one.

The guiding principle is:

**Simple implementation now → clear boundaries → evidence-based extraction later**

### 7.1 Core Architectural Concepts

The product should conceptually separate:

- Campaigns
- Presentations
- Unlock Methods
- Unlock Sessions
- Verification
- Rewards
- Limits / Anti-Abuse
- Analytics
- Platform Integration

These concepts should have clear responsibilities.

A presentation should determine **how the gate is displayed**.

An unlock method should determine **what the visitor must do**.

Verification should determine **whether the required action was legitimately completed**.

A reward should determine **what the visitor receives after successful completion**.

Campaign configuration should determine **how these components are combined**.

### 7.2 Shared Business Logic

Popup Gate and Content / Read-more Gate should use the same underlying business logic wherever practical.

The following functionality should not be duplicated merely because the presentation differs:

- unlock-session creation
- session validation
- timer verification
- completion processing
- reward granting
- replay protection
- visitor limits
- anti-abuse checks

Presentation-specific code should primarily control:

- rendering
- user interaction
- presentation state
- frontend behavior

This separation is an important architectural test during the MVP.

### 7.3 Application Structure

The initial standalone application should use a straightforward PHP structure.

The implementation should separate, as appropriate:

- HTTP request handling
- application/business logic
- database access
- presentation/rendering
- configuration
- reusable utilities
- frontend assets

The exact directory structure should be determined during implementation based on actual requirements.

The project should not introduce layers solely because they are common in large frameworks.

### 7.4 Database Boundary

Database access should be kept separate from business logic.

Application code should not spread raw SQL and database-specific behavior throughout presentation code.

Database access should use a small and consistent abstraction appropriate for the project's size.

PDO is the preferred database access mechanism for the initial implementation.

The database layer should support both MySQL and MariaDB where practical.

### 7.5 Configuration

Configuration should be separated from application code.

Environment-specific values should not be committed to the repository.

Examples include:

- database credentials
- application secrets
- API keys
- server-specific configuration
- debugging configuration

Local development and production environments should be able to use different configuration values without modifying tracked application code.

### 7.6 Security Boundary

The server is authoritative.

Client-side JavaScript must never be treated as a trusted source for security decisions.

The browser may report events, but the server must independently determine whether those events are valid.

Security-sensitive operations should therefore remain server-side, including:

- unlock-session creation
- token generation and validation
- completion verification
- reward granting
- access checks
- replay protection
- visitor limits

### 7.7 Presentation Independence

The core unlock workflow should not depend on a specific presentation.

The same unlock session and verification mechanisms should be usable by:

- Popup Gate
- Content / Read-more Gate
- future Download Gate
- future Inline Gate
- future Full Page Gate
- other future presentation types

Adding a new presentation should primarily require implementing the presentation-specific behavior rather than rewriting the unlock engine.

### 7.8 Unlock Method Independence

The unlock engine should not be designed specifically around the timer.

Timer is the first unlock method, but future methods may include:

- link visits
- rewarded video
- offerwalls
- surveys
- external tasks

The system should therefore distinguish between:

**"What action is required?"**

and:

**"How is completion verified?"**

The timer implementation should be the first concrete implementation of this concept.

### 7.9 Reward Independence

The unlock engine should not assume that every successful completion reveals content.

The MVP reward is protected-content unlocking, but future rewards may include:

- downloads
- coupons
- credits
- premium features
- external links
- digital products

Reward handling should therefore remain conceptually separate from unlock verification.

### 7.10 Platform Independence

Platform-specific functionality should remain outside the shared business logic.

Examples include:

**Standalone PHP**

- HTTP handling
- database configuration
- application installation
- administration

**WordPress**

- hooks
- `$wpdb`
- shortcodes
- blocks
- WordPress authentication
- WordPress admin UI

**Laravel**

- routing
- middleware
- Eloquent
- service providers
- Blade
- Laravel configuration

The shared Reward Gate Engine should not contain platform-specific dependencies.

### 7.11 Future Reward Gate Core

After the standalone product and additional integrations provide enough evidence about what is genuinely reusable, shared functionality may be extracted into a dedicated Reward Gate Core.

Potential responsibilities include:

- campaign logic
- presentation configuration
- unlock methods
- unlock sessions
- completion verification
- rewards
- limits
- anti-abuse
- visitor/session handling
- common analytics

The core should be extracted from proven requirements rather than designed entirely from hypothetical future use cases.

### 7.12 Dependency Philosophy

Dependencies should be kept intentionally small.

A dependency should be introduced when it provides meaningful value such as:

- security
- reliability
- maintainability
- significant reduction in implementation complexity

Large frameworks or libraries should not be introduced merely to provide functionality that can be implemented clearly with the existing stack.

The initial application should remain based primarily on:

- PHP
- MariaDB / MySQL
- HTML
- CSS
- JavaScript
- Bootstrap 5 for the backend administration interface

### 7.13 Extensibility Without Over-Engineering

The architecture should make common future changes reasonably straightforward without attempting to predict every possible feature.

Examples of changes that should be possible without fundamental redesign:

- adding a presentation type
- adding an unlock method
- adding a reward type
- adding campaign settings
- adding analytics
- adding a visitor limit
- adding another platform integration

This does not justify creating generic interfaces, factories, registries, event systems, or abstraction layers before they are needed.

The architecture should earn its complexity.

### 7.14 Architecture Evolution

The architecture is expected to evolve as the product grows.

The intended progression is:

**Standalone Application → Proven Shared Components → Reward Gate Core → Platform Integrations**

Architecture decisions should therefore be revisited when real requirements appear rather than being treated as permanently fixed during the MVP.

The primary architectural goal is not maximum abstraction.

It is maintaining enough separation that the product can evolve without repeatedly rewriting its fundamental business logic.

## 8. Database Principles

The Reward Gate database should remain relatively small, explicit, and understandable.

The database should support the current product requirements while allowing future presentation types, unlock methods, rewards, campaigns, and integrations to be introduced without requiring a fundamental redesign.

The guiding principle is:

**Explicit schema over premature generic abstraction.**

### 8.1 Core Entities

The initial data model should be built around the concepts that actually exist in the product.

Core entities are expected to include:

- campaigns
- presentations
- unlock methods
- rewards
- unlock sessions
- unlock completions

Additional entities may be introduced when real requirements justify them.

The exact physical schema should be determined during technical implementation rather than being unnecessarily fixed in this product specification.

### 8.2 Campaigns

A campaign represents a configured Reward Gate experience.

A campaign should be able to define or reference:

- presentation type
- unlock method
- reward
- configuration
- status
- limits
- relevant analytics settings

Campaigns should be independently manageable.

The schema should allow multiple campaigns to exist simultaneously.

### 8.3 Presentations

Presentation configuration should be conceptually separate from the campaign itself.

Initial presentation types include:

- Popup Gate
- Content / Read-more Gate

Future types may include:

- Full Page Gate
- Inline Gate
- Blurred Content Gate
- Download Gate
- Button Gate
- Embedded Widget
- Feature Gate

The database should avoid hard-coding presentation-specific fields into unrelated tables where practical.

### 8.4 Unlock Methods

Unlock methods should be represented as an independent concept.

The MVP supports:

- Timer

Future methods may include:

- Link Visit
- Rewarded Video
- Offerwall
- Survey
- External Task

Adding a new unlock method should not require rebuilding the fundamental unlock-session or completion model.

Method-specific configuration should be stored in a way that remains understandable and maintainable.

The MVP should not create database structures for methods that do not yet exist merely for theoretical extensibility.

### 8.5 Rewards

Rewards should be modeled independently from unlock methods.

The MVP reward is:

- protected-content unlock

Future rewards may include:

- downloads
- coupons
- credits
- premium features
- external links
- digital products

A successful unlock should result in a reward being granted through a clearly defined application flow.

The database should not assume that every reward is simply "content becomes visible."

### 8.6 Unlock Sessions

An unlock session represents a specific visitor attempt to complete an unlock action.

A session should contain the information required to determine whether completion is valid.

Potential data includes:

- session identifier
- campaign
- unlock method
- creation timestamp
- expiration timestamp where applicable
- required completion time
- completion state
- security token or token reference
- relevant visitor/session information

The server must remain authoritative over session state.

Client-side state must not be sufficient to create or complete an unlock.

### 8.7 Unlock Completions

A completion represents a successfully verified unlock action.

Completion records may be used for:

- reward granting
- replay prevention
- analytics
- auditing
- anti-abuse
- campaign statistics

A completion should be associated with the relevant:

- unlock session
- campaign
- visitor/session context
- reward

The database should prevent the same unlock session from being successfully completed multiple times.

### 8.8 Visitor and Frequency Limits

The database should support basic visitor/session frequency limitations.

The implementation may use combinations of:

- session identifiers
- signed tokens
- hashed identifiers
- timestamps
- campaign-specific limits

The exact implementation should be determined during the security and technical design phase.

Visitor identification should be minimized and should consider applicable privacy requirements.

### 8.9 IDs and Relationships

Database entities should use stable identifiers and explicit relationships.

Foreign keys should be used where they provide meaningful referential integrity.

Relationships should be straightforward to understand.

The schema should avoid excessive polymorphic relationships when ordinary relational relationships are sufficient.

Generic structures such as `entity_type` and `entity_id` should not be introduced merely to avoid creating an additional foreign key.

### 8.10 Timestamps

Important state changes should have explicit timestamps.

Examples include:

- campaign creation
- campaign modification
- session creation
- session expiration
- completion
- reward granting

Timestamps should use a consistent strategy throughout the application.

The application should avoid mixing incompatible timezone assumptions between PHP, the database, and stored records.

### 8.11 Database Integrity

The database should enforce important invariants wherever practical.

Examples include:

- unique identifiers
- required fields
- foreign-key relationships
- unique completion constraints
- valid status values
- appropriate indexes

Security-critical rules should still be enforced by application logic.

Database constraints are a second line of defense, not a replacement for server-side validation.

### 8.12 Indexing

Indexes should be added based on actual query patterns.

Likely indexed fields include:

- campaign identifiers
- session identifiers
- completion identifiers
- status fields used for filtering
- timestamps used for reporting
- relationships used for joins

The project should avoid speculative indexing of every column.

Indexes should be reviewed as analytics and campaign-management queries become more complex.

### 8.13 Analytics Data

Analytics should initially use the simplest data model that provides the required information.

Where possible, analytics should be derived from existing campaign, session, and completion records rather than immediately introducing a separate analytics warehouse or event-streaming system.

Dedicated aggregation tables may be introduced later if real performance requirements justify them.

The MVP does not require:

- a data warehouse
- event streaming
- distributed analytics
- real-time analytics infrastructure

### 8.14 Database Portability

The initial application should support:

- MySQL
- MariaDB

Database features that are unnecessarily specific to one vendor should be avoided where practical.

At the same time, portability should not justify awkward abstractions or prevent the use of reliable database features that materially improve the product.

### 8.15 Schema Evolution

Database changes should be tracked through versioned migrations or an equivalent controlled mechanism.

Schema changes should not rely on manually editing production databases.

Each schema change should be reproducible in a new installation.

The migration process should eventually support:

- fresh installation
- upgrade from previous versions
- rollback where practical

A simple migration mechanism is preferred initially.

A full database migration framework should not be introduced unless the project's requirements justify it.

### 8.16 Data Retention

The application should avoid retaining visitor-related data indefinitely.

Retention periods should eventually be configurable or clearly defined according to:

- analytics requirements
- anti-abuse requirements
- legal requirements
- privacy requirements
- product requirements

Data that is no longer required should be eligible for deletion or anonymization.

### 8.17 Database Security

Database credentials must never be committed to the repository.

Production credentials should be supplied through environment-specific configuration.

The application should use parameterized queries.

User-controlled values must never be concatenated directly into SQL statements.

Database users should operate with the minimum privileges required by the application.

Administrative database credentials should not be used by the application runtime.

### 8.18 Avoiding Premature Complexity

The MVP database should remain small.

The following should not be introduced without a demonstrated requirement:

- generic entity systems
- database-level plugin architectures
- event-sourcing
- CQRS
- distributed databases
- database sharding
- data warehouses
- complex caching layers
- dozens of tables representing hypothetical future features

The database should model the product that exists, while leaving reasonable room for the product that is likely to exist.

### 8.19 Database Evolution Principle

The database should follow the same principle as the application architecture:

**Build for today's requirements, preserve reasonable paths to tomorrow's requirements.**

A new feature should normally extend the existing model rather than forcing a redesign.

However, if real product requirements demonstrate that the existing model is wrong, the schema should be changed rather than protected merely for the sake of backwards compatibility.

Correctness and maintainability take priority over preserving an abstraction that no longer fits.

## 9. Security & Privacy

Security is a core product requirement rather than a feature that can be added after the main functionality is complete.

Reward Gate controls access to content and potentially valuable rewards. The system must therefore treat the browser, visitor-controlled input, and external requests as untrusted.

The guiding principle is:

**The client reports events. The server decides whether they are valid.**

### 9.1 Server Authority

The server must remain authoritative for all security-sensitive operations.

Client-side JavaScript may:

- display the gate
- run the visible countdown
- update the user interface
- report events to the server

Client-side JavaScript must not be trusted to determine:

- whether the required time has elapsed
- whether an unlock is complete
- whether a reward should be granted
- whether a session is valid
- whether a visitor has exceeded a limit
- whether protected content should be permanently accessible

Any value received from the browser must be treated as untrusted input.

### 9.2 Unlock Session Security

Every unlock attempt should be represented by a server-side unlock session.

The server should determine:

- when the session was created
- which campaign it belongs to
- which unlock method is being used
- what completion requirements apply
- whether the session is still valid
- whether it has already been completed
- whether the reward has already been granted

A visitor should not be able to create an arbitrary valid unlock state simply by manipulating client-side data.

### 9.3 Timer Verification

The MVP timer must be verified server-side.

The server should record the start time of the unlock session and independently calculate whether the minimum required duration has elapsed.

The client-side countdown exists primarily for user experience.

It must not be treated as proof of completion.

For example, changing a JavaScript variable from:

`remainingTime = 10`

to:

`remainingTime = 0`

must not allow the visitor to complete the unlock.

### 9.4 Tokens

Security-sensitive operations should use appropriately generated, unpredictable tokens.

Tokens should:

- be generated using a cryptographically secure random source
- have sufficient entropy
- be associated with the relevant server-side state
- expire where appropriate
- not expose sensitive information
- not be reused unnecessarily

Tokens should not contain sensitive data merely because encoding it is convenient.

Encoding is not encryption.

### 9.5 Replay Protection

A successful unlock must not be reusable indefinitely.

The system should prevent:

- submitting the same completion request multiple times
- reusing a previously completed unlock token
- completing the same unlock session repeatedly
- replaying an old valid completion request
- obtaining the same reward repeatedly when campaign limits prohibit it

Completion processing should be idempotent where appropriate.

Database constraints should reinforce application-level replay protection.

### 9.6 Request Validation

All incoming requests must be validated server-side.

Validation should cover:

- required parameters
- parameter types
- allowed values
- string lengths
- identifiers
- timestamps
- campaign references
- session references
- authorization state

Unexpected parameters should not automatically be trusted or interpreted.

Validation should occur before business logic is executed.

### 9.7 SQL Security

All database queries involving user-controlled or externally supplied values must use parameterized queries.

The application must not construct SQL by directly concatenating untrusted input.

PDO prepared statements should be used consistently.

Database permissions should follow the principle of least privilege.

### 9.8 Cross-Site Scripting

User-controlled data must not be rendered into HTML, JavaScript, CSS, or other executable contexts without appropriate escaping or encoding.

The application should use context-appropriate output escaping.

This applies particularly to:

- campaign names
- custom text
- URLs
- analytics data
- administrative input
- content configuration
- visitor-supplied values

The application should not assume that data is safe merely because it originated from an authenticated administrator.

### 9.9 Cross-Site Request Forgery

State-changing administrative and application requests should be protected against CSRF where applicable.

The implementation should use appropriate CSRF tokens or equivalent protections.

CSRF protection should be applied to operations such as:

- creating campaigns
- modifying campaigns
- deleting campaigns
- changing settings
- modifying users
- changing integrations
- other privileged state changes

### 9.10 Authentication and Authorization

Administrative functionality must require authentication.

Authentication and authorization should be treated as separate concerns.

Authentication answers:

**Who is this user?**

Authorization answers:

**What is this user allowed to do?**

The application should verify authorization on the server for every privileged operation.

The UI must not be treated as an authorization boundary.

Hiding an administrative button does not prevent a user from manually requesting the underlying endpoint.

### 9.11 Password Security

User passwords must never be stored in plaintext.

Passwords should be stored using PHP's password hashing facilities with an appropriate modern algorithm.

The application should rely on:

- `password_hash()`
- `password_verify()`
- `password_needs_rehash()`

rather than implementing custom password hashing.

Password reset functionality should use short-lived, unpredictable, single-use tokens.

### 9.12 Session Security

Application sessions should use secure cookie and session configuration appropriate to the deployment environment.

Where applicable, cookies should use:

- `Secure`
- `HttpOnly`
- `SameSite`

Session identifiers should be regenerated after authentication and other security-sensitive transitions where appropriate.

The application should avoid exposing session identifiers through URLs.

### 9.13 Content Protection

If Reward Gate claims to protect content, the protected content must not remain trivially accessible through an alternative public endpoint.

For content that is intended to be protected, the application should determine whether the visitor has a valid unlock before serving the protected resource.

Client-side hiding alone is not a sufficient security boundary.

For example, placing the complete protected content in the HTML and merely hiding it with CSS does not provide meaningful access control.

### 9.14 Download Protection

Future Download Gate functionality should not expose protected files through permanent publicly accessible URLs when the download itself is intended to be protected.

Potential mechanisms include:

- protected storage
- authorization checks
- temporary download URLs
- expiration
- one-time or limited-use access
- server-side streaming

The exact mechanism should be selected during implementation based on the deployment environment and threat model.

### 9.15 Input and File Handling

Any future functionality involving file uploads must treat uploaded files as untrusted.

The application should validate:

- file type
- file size
- file name
- storage location
- file contents where appropriate

Uploaded files should not automatically be stored in publicly executable locations.

File paths must never be constructed directly from untrusted input without validation.

### 9.16 External Integrations

Future integrations with advertising networks, offerwalls, surveys, payment providers, or other external services must not automatically be trusted.

External callbacks and webhooks should be independently verified.

Where supported, verification should include:

- signatures
- shared secrets
- provider-specific authentication
- timestamp validation
- replay protection
- request validation

The application should grant rewards only after the external event has been successfully verified.

### 9.17 Secrets and Configuration

Secrets must never be committed to the Git repository.

This includes:

- database passwords
- application secrets
- API keys
- webhook secrets
- encryption keys
- external service credentials
- production credentials

Environment-specific configuration should be kept outside tracked source code.

The repository may provide a safe example configuration file containing placeholder values.

### 9.18 Error Handling

Production error messages must not expose sensitive internal information.

Errors should not reveal:

- database credentials
- SQL queries containing sensitive values
- filesystem paths where unnecessary
- internal secrets
- stack traces to visitors
- authentication details
- implementation-specific information that assists attackers

Detailed errors should remain available through controlled development or server logs.

### 9.19 Logging and Auditing

Security-relevant events should be logged where appropriate.

Potential events include:

- authentication failures
- successful authentication
- authorization failures
- suspicious unlock attempts
- repeated completion attempts
- token validation failures
- external verification failures
- administrative changes

Logs should avoid storing unnecessary personal or sensitive information.

Logging should support investigation without becoming an uncontrolled collection of visitor data.

### 9.20 Rate Limiting and Abuse Prevention

The application should use appropriate rate limiting for security-sensitive endpoints.

Potential targets include:

- authentication
- password reset
- unlock-session creation
- completion requests
- external verification endpoints
- administrative endpoints

Rate limits should be designed according to the purpose of the endpoint.

Visitor-facing unlock functionality should not be made unusable merely because anti-abuse controls are overly aggressive.

### 9.21 Security Headers

The application should use appropriate HTTP security headers where practical.

Depending on the deployment and application requirements, these may include:

- Content-Security-Policy
- X-Content-Type-Options
- Referrer-Policy
- Permissions-Policy
- Strict-Transport-Security

Headers should be configured based on actual application behavior rather than copied blindly from generic security checklists.

### 9.22 HTTPS

Production deployments should use HTTPS.

Sensitive communication, authentication, administrative operations, unlock sessions, and external integrations should not rely on unencrypted HTTP.

The application should not attempt to implement its own transport encryption.

TLS should be provided by the web server, reverse proxy, hosting platform, or equivalent infrastructure.

### 9.23 Dependency Security

Third-party dependencies should be kept to a minimum and maintained.

Dependencies should be evaluated for:

- security history
- maintenance status
- compatibility
- necessity
- transitive dependencies

Composer should be used to manage PHP dependencies.

The project should periodically review dependency updates and known security advisories.

### 9.24 Privacy by Design

Reward Gate should collect only the visitor information necessary for its functionality.

Potentially sensitive or identifying information should not be collected simply because it might be useful for future analytics.

Before introducing visitor tracking, determine:

- why the data is required
- how long it must be retained
- who can access it
- whether it can be anonymized
- whether it requires user consent
- whether the same functionality can be achieved with less data

### 9.25 Visitor Identification

Anti-abuse functionality may require some form of visitor identification.

Possible mechanisms include:

- server-side sessions
- short-lived tokens
- signed identifiers
- hashed values
- limited cookie-based state

The least invasive mechanism that satisfies the product requirement should be preferred.

The system should avoid collecting precise or persistent identifiers unless there is a demonstrated need.

### 9.26 GDPR and Data Protection

Because Reward Gate may be deployed on websites serving visitors in the European Union and other jurisdictions with privacy regulations, privacy requirements must be considered from the beginning.

Depending on the deployment and functionality, this may include:

- privacy notices
- cookie disclosure
- consent mechanisms
- data minimization
- retention policies
- deletion mechanisms
- data export
- access controls
- processor/controller responsibilities

The exact legal requirements depend on how the product is deployed and what data it processes.

The application should not claim legal compliance merely because technical privacy features exist.

### 9.27 Analytics and Privacy

Analytics should be designed around useful product information rather than collecting as much visitor data as possible.

Preferred metrics include:

- gates shown
- unlocks started
- unlocks completed
- completion rate
- abandonment
- reward grants
- campaign performance

Analytics should avoid unnecessary personally identifiable information.

Where aggregate statistics are sufficient, raw visitor-level data should not be retained indefinitely.

### 9.28 Security Testing

Security should be tested as part of normal development.

Testing should include, where applicable:

- invalid input
- expired sessions
- replayed completions
- modified timers
- invalid tokens
- unauthorized requests
- CSRF attempts
- SQL injection attempts
- XSS attempts
- authentication failures
- rate-limit behavior
- direct access to protected resources

Security testing should focus particularly on the unlock and reward workflow because that is the core security boundary of the product.

### 9.29 Security Updates

Security fixes should be treated as high-priority maintenance work.

The project should maintain a process for:

- dependency updates
- vulnerability review
- security fixes
- deployment of security patches
- communicating important security issues to customers

For the future commercial product, security updates should be considered part of the product lifecycle rather than optional polish.

### 9.30 Security Principle

The overall security model can be summarized as:

**Never trust the browser.**

The visitor controls the browser.

The visitor can modify JavaScript, HTTP requests, cookies, local storage, timers, and other client-side state.

Reward Gate must therefore assume that any client-side behavior can be manipulated.

The server must independently verify every condition that matters for granting access to a reward.

### 9.31 Privacy Principle

The overall privacy model can be summarized as:

**Collect only what is necessary, retain it only as long as necessary, and protect what is collected.**

Privacy should be considered part of the product architecture rather than a documentation exercise performed immediately before commercial release.

## 10. Commercial Model

Reward Gate is intended to become a commercially distributed software product.

The initial commercial strategy should focus on a self-hosted product that customers can purchase, install, configure, and operate on their own infrastructure.

A hosted SaaS offering may be introduced later if market demand justifies the additional infrastructure and operational complexity.

The guiding principle is:

**Sell a useful product first. Build the larger business model after demand is proven.**

### 10.1 Initial Commercial Product

The first commercial release should be a significantly more capable version of the MVP.

The MVP exists primarily to validate:

- the core unlock workflow
- the product architecture
- security
- usability
- technical feasibility

The first commercial release should add selected features that provide meaningful customer value.

These may include:

- multiple campaigns
- campaign management
- customization
- analytics
- additional presentation options
- improved visitor controls
- selected killer features
- selected smart features
- improved installation and configuration
- documentation

The commercial release should not simply be the MVP with a payment button attached.

### 10.2 Self-Hosted Distribution

The primary initial commercial model is self-hosted software.

Customers should be able to install Reward Gate on their own hosting environment.

Potential distribution channels include:

- CodeCanyon
- the Reward Gate website
- developer marketplaces
- direct sales
- other software marketplaces

The exact distribution strategy may evolve based on where customers are actually acquired.

### 10.3 Licensing

The initial licensing model should remain simple.

Potential models include:

- one-time license
- extended license
- optional support
- optional updates
- premium modules

The final licensing structure should be determined before commercial launch.

Licensing mechanisms such as domain activation or license keys should only be implemented when they serve an actual commercial requirement.

The product should not introduce complicated licensing infrastructure merely to imitate larger commercial software products.

### 10.4 Pricing

Pricing should be based on perceived customer value rather than development cost.

Potential pricing factors include:

- target customer
- number of websites
- available features
- support period
- update period
- commercial usage rights
- advanced integrations
- premium modules

Pricing should remain simple enough that a prospective customer can understand what they are purchasing.

The exact prices remain TBD until market validation provides useful evidence.

### 10.5 Updates and Support

A commercial product should define a clear distinction between:

- software license
- software updates
- technical support

Potential models include:

- license includes a defined update period
- optional renewal for continued updates
- optional paid support
- extended support packages

The exact model should be determined based on the distribution platform and customer expectations.

### 10.6 WordPress Commercial Product

WordPress is expected to become a major commercial platform after the standalone product has been validated.

The WordPress product may eventually be distributed separately or as part of a broader Reward Gate product family.

Potential commercial approaches include:

- standalone WordPress license
- bundle with the self-hosted product
- premium WordPress edition
- free WordPress integration with paid advanced features

The final approach should be based on actual customer demand.

### 10.7 Premium Modules

Some advanced functionality may eventually be distributed as optional premium modules.

Potential modules include:

- advanced analytics
- additional unlock methods
- advanced integrations
- optimization features
- A/B testing
- smart routing
- revenue optimization
- advanced anti-abuse
- additional presentation types

Premium modules should provide meaningful value rather than artificially removing basic functionality from the core product.

### 10.8 Free and Paid Editions

A future product strategy may include a free edition and one or more paid editions.

Possible differences may involve:

- campaign limits
- presentation types
- analytics
- integrations
- advanced optimization
- support
- customization

The free edition should still provide enough functionality to demonstrate the product's value.

The exact feature split remains TBD.

### 10.9 SaaS

A hosted SaaS version is a long-term commercial possibility.

A SaaS version could provide:

- hosted campaigns
- accounts
- billing
- subscriptions
- multi-tenancy
- centralized analytics
- hosted integrations
- usage limits
- automatic updates
- managed infrastructure

The SaaS version should not be built before the self-hosted product demonstrates sufficient demand.

Building SaaS introduces significant additional complexity, including:

- infrastructure
- uptime
- billing
- authentication
- multi-tenancy
- customer support
- data protection
- monitoring
- backups
- operational costs

These costs must be justified by actual market demand.

### 10.10 SaaS Pricing

A future SaaS model may use recurring subscriptions.

Potential plans could include:

- Free / Starter
- Professional
- Business
- Enterprise

Potential limits may include:

- campaigns
- visitors
- unlocks
- websites
- analytics retention
- advanced methods
- optimization features
- integrations

The exact plans, limits, and pricing remain TBD.

### 10.11 Revenue Sharing

Future integrations with monetization providers may introduce revenue-sharing opportunities.

Potential models include:

- customer keeps the generated revenue
- Reward Gate takes a percentage
- subscription plus revenue share
- hybrid commercial model

The appropriate model depends on the economics of the specific integration.

Reward Gate should not assume that every unlock method generates revenue.

### 10.12 Advertising and Rewarded Monetization

Future monetization integrations may include:

- rewarded video
- offerwalls
- sponsored links
- affiliate offers
- other externally verified actions

These integrations must comply with the policies of the relevant providers.

Rewarded or incentivized interactions should only be implemented where the provider explicitly permits the intended use case.

### 10.13 Commercial Distribution Features

Once the product is commercially validated, additional distribution functionality may include:

- installation wizard
- server requirement checks
- database setup
- admin account creation
- demo data
- documentation
- update mechanism
- license activation
- domain activation
- white-labeling
- branding customization

These features should be introduced when they materially improve commercial distribution.

### 10.14 Installation Experience

The installation process should eventually be simple enough for a non-expert customer to complete.

A future installation flow may include:

1. Upload or install Reward Gate.
2. Check server requirements.
3. Configure the database.
4. Create the administrator account.
5. Complete initial configuration.
6. Optionally load a demo campaign.
7. Begin creating campaigns.

The installation process should clearly identify missing requirements rather than failing with obscure PHP or database errors.

### 10.15 Product Packaging

The commercial product should be packaged as a complete solution rather than a collection of development components.

A commercial release should include:

- application source code
- required dependencies
- installation instructions
- configuration documentation
- user documentation
- upgrade instructions
- troubleshooting information
- licensing information

The product should not require the customer to understand the internal development environment.

### 10.16 Documentation

Commercial distribution should include documentation covering at least:

- installation
- requirements
- configuration
- campaign creation
- presentation configuration
- unlock methods
- protected content
- analytics
- troubleshooting
- upgrades
- security considerations

Documentation should be written for the customer rather than for the developer.

### 10.17 Commercial Validation

Before investing heavily in additional commercial infrastructure, the product should be validated against real customers.

Important questions include:

- Will website owners install it?
- Will they pay for it?
- What do they use it for?
- Which presentation types are most valuable?
- Which unlock methods are most requested?
- Do customers care more about gating or monetization?
- Which integrations matter most?
- What pricing feels reasonable?
- What causes customers to choose Reward Gate over alternatives?

Development priorities should be adjusted based on real evidence.

### 10.18 Commercial Release Strategy

The intended progression is:

**MVP → Commercial Product → Market Validation → Expansion**

The first commercial release should include the core product plus selected high-value features.

Not every long-term feature needs to be complete before launch.

The commercial release should prioritize features that:

- improve customer value
- differentiate the product
- improve usability
- improve monetization potential
- demonstrate the long-term product direction

### 10.19 Commercial Principle

Reward Gate should avoid building commercial infrastructure before it is needed.

The product should first prove that:

**People want the product → people use the product → people pay for the product**

Only then should the business model become significantly more complex.

The long-term commercial vision may include self-hosted software, premium modules, WordPress products, revenue-sharing integrations, and SaaS.

The initial objective is much simpler:

**Create a product that customers genuinely want to buy.**

## 11. Long-Term Vision

Reward Gate should evolve through several deliberate stages:

**MVP → Real Product → Reusable Engine → Multi-Platform Product → Intelligent Optimization Platform → Reward Optimization Engine**

The long-term goal is to build a general-purpose system for converting visitor actions into verified rewards.

### 11.1 From Gate to Reward Engine

The fundamental product should not remain tied to the concept of a popup.

The underlying engine should eventually support:

- different presentation types
- different unlock methods
- different verification mechanisms
- different reward types
- visitor and campaign limits
- anti-abuse controls
- analytics
- optimization

The core conceptual workflow remains:

**Presentation → Unlock Method → Verification → Reward**

The presentation should determine how the experience is delivered.

The engine should determine whether the required action was successfully completed and what reward should be granted.

### 11.2 Multi-Platform Product

After the standalone PHP implementation has been validated, the product should expand to additional environments based on demonstrated demand.

Potential integrations include:

- Standalone PHP
- WordPress
- Laravel
- Generic PHP / JavaScript
- Other CMS or website platforms where commercially justified

Platform-specific functionality should remain separate from the underlying Reward Gate Core.

The goal is to allow the same product concepts to operate across different environments without forcing the core engine to depend on any particular framework.

### 11.3 Reward Gate Core

Once multiple real implementations exist, genuinely reusable functionality should be extracted into a shared Reward Gate Core.

Potential responsibilities include:

- campaign logic
- presentation configuration
- unlock sessions
- unlock methods
- verification
- reward handling
- usage limits
- anti-abuse
- visitor/session handling
- analytics
- optimization logic

The core should be extracted from proven requirements rather than designed as a theoretical universal framework before those requirements exist.

### 11.4 Intelligent Reward Optimization

The long-term product vision extends beyond configuring static campaigns.

The system should eventually help determine the most effective unlock strategy for a given visitor and campaign.

The owner may define objectives and constraints such as:

- maximize revenue
- maximize completion rate
- maximize engagement
- minimize visitor friction
- limit visitor wait time
- prioritize a particular reward
- maintain a maximum abandonment rate

The system could then determine or recommend:

- presentation type
- unlock method
- fallback method
- timer duration
- visitor segmentation
- frequency
- reward strategy

### 11.5 Adaptive Campaigns

Campaign behavior may eventually adapt according to factors such as:

- new vs. returning visitor
- device
- geography
- traffic source
- time
- visitor history
- historical campaign performance
- available inventory

The objective is not to expose more configuration to the site owner.

The objective is to reduce unnecessary configuration by allowing the system to make evidence-based decisions automatically.

### 11.6 Continuous Optimization

A mature Reward Gate installation should be capable of learning from campaign results.

Potential capabilities include:

- automatic A/B testing
- performance comparison
- method selection
- smart fallbacks
- automatic recommendations
- campaign health detection
- predictive performance estimates
- automatic campaign optimization

The system should answer not only:

> "What happened?"

but eventually:

> "Why did it happen?"

and:

> "What should we change?"

### 11.7 Reward Optimization Engine

The ultimate product concept is a **Reward Optimization Engine**.

Instead of requiring the owner to manually determine every aspect of an unlock campaign, the owner could define an objective and constraints.

For example:

> **Maximize revenue while never making visitors wait more than 20 seconds.**

The system could use campaign data and visitor context to determine an appropriate strategy.

This may include selecting:

- the presentation
- the unlock method
- the fallback sequence
- the duration
- the frequency
- the reward strategy
- the appropriate visitor segment

The system would continuously evaluate the results and improve its decisions.

### 11.8 Product Principles

The long-term evolution should remain guided by the following principles:

- Working product over clever architecture
- Real users over theoretical flexibility
- Simple UX over configuration complexity
- Validated features over speculative features
- Extensible architecture without premature abstraction
- Evidence-based optimization over arbitrary automation
- Visitor experience as a product advantage
- Commercial value over technical novelty

The product should become more capable over time without becoming unnecessarily complicated for the people who use it.

### 11.9 Long-Term Outcome

The desired progression is:

**Simple Product**
→ **Useful Product**
→ **Reusable Engine**
→ **Multi-Platform Product**
→ **Optimization Platform**
→ **Reward Optimization Engine**

Reward Gate should ultimately allow website owners to exchange valuable visitor actions for meaningful rewards while minimizing friction, preventing trivial abuse, and continuously improving campaign performance.

The long-term objective is therefore not to build a better popup.

It is to build a general-purpose engine for **verified actions, rewards, and optimization**.

## 12. Explicitly Deferred Features

The following features are intentionally outside the scope of the current implementation unless a concrete requirement causes them to be brought forward.

Deferring a feature does not mean that the feature is rejected. It means that implementation should wait until there is sufficient product, technical, or commercial justification.

### 12.1 SaaS Infrastructure

Deferred:

- hosted SaaS platform
- multi-tenancy
- tenant isolation
- hosted campaign management
- centralized account management
- subscription management
- SaaS usage limits
- hosted analytics infrastructure
- cloud-based optimization services

The initial product is self-hosted.

### 12.2 Commercial Licensing Infrastructure

Deferred:

- license keys
- domain activation
- license servers
- automatic license validation
- subscription enforcement
- update licensing
- installation activation
- white-label licensing

These features should only be implemented when they support an actual commercial distribution model.

### 12.3 Payment Infrastructure

Deferred:

- subscription billing
- payment processing
- premium plans
- visitor payments
- payment-provider integrations
- automated invoices
- recurring billing
- currency management

Potential providers and pricing models remain commercial decisions.

### 12.4 Platform Integrations

Deferred from the initial implementation:

- WordPress plugin
- Laravel package
- generic reusable SDK
- JavaScript SDK
- integrations with additional CMS platforms

The initial implementation should remain a standalone PHP application.

These integrations should be developed after the core product has demonstrated sufficient value to justify them.

### 12.5 External Unlock Providers

Deferred:

- rewarded video providers
- offerwalls
- survey providers
- affiliate networks
- sponsored-link providers
- external task providers
- advertising network integrations

The MVP uses a timer so that the complete unlock and verification workflow can be validated without external provider dependencies.

### 12.6 Advanced Analytics

Deferred:

- advanced funnel analysis
- revenue attribution
- cohort analysis
- visitor segmentation analytics
- long-term retention analytics
- advanced reporting
- predictive analytics
- cross-campaign intelligence

The first commercial release may include useful analytics, but analytics infrastructure should not become a separate product before there is sufficient data to justify it.

### 12.7 Advanced Optimization

Deferred until sufficient real-world data exists:

- automatic method optimization
- automatic campaign optimization
- predictive recommendations
- machine-learning-based visitor segmentation
- dynamic reward selection
- automatic duration optimization
- advanced reinforcement or bandit-style optimization

Optimization should be based on real campaign data rather than assumptions made before the product has users.

### 12.8 Advanced Anti-Fraud

Deferred:

- sophisticated bot detection
- device fingerprinting
- advanced fraud scoring
- behavioral fingerprinting
- distributed fraud intelligence
- external fraud-detection services
- advanced reputation systems

The MVP should prevent trivial abuse without attempting to solve the entire internet's fraud problem.

### 12.9 Advanced Campaign Builder

Deferred:

- complex visual campaign builder
- drag-and-drop interfaces
- highly granular conditional rules
- complex campaign workflows
- visual automation builders
- arbitrary campaign scripting

Campaign configuration should initially remain simple and understandable.

### 12.10 Large-Scale Administration

Deferred:

- complex administrator hierarchies
- enterprise organizations
- teams and permissions
- multi-level roles
- centralized user administration
- enterprise audit systems
- organization-wide configuration

These capabilities may become relevant for larger commercial deployments.

### 12.11 Advanced Content Management

Deferred:

- full CMS functionality
- page builder
- theme marketplace
- complete content editor
- media library
- website builder
- general-purpose publishing system

Reward Gate is not intended to become a general-purpose CMS.

### 12.12 Advanced Customization

Deferred:

- complete visual theme builder
- arbitrary CSS editors
- advanced layout builders
- custom component systems
- marketplace themes
- extensive white-labeling

The product should provide useful customization without becoming a design platform.

### 12.13 Hosted Infrastructure & Global Scaling

Deferred:

- distributed application architecture
- global edge infrastructure
- CDN-based execution
- distributed analytics
- global event pipelines
- large-scale caching architecture
- horizontally scaled processing systems

The initial architecture should target a normal self-hosted PHP deployment.

Scaling decisions should be based on actual requirements.

### 12.14 Mobile Applications

Deferred:

- native Android application
- native iOS application
- mobile administration applications
- mobile SDKs

The initial product is web-based.

### 12.15 AI Features Beyond Practical Product Value

Deferred:

- AI-generated campaigns
- AI-generated configuration
- conversational campaign management
- AI-generated analytics reports
- autonomous campaign management
- predictive AI systems without sufficient supporting data

AI should only be introduced where it provides measurable product value.

### 12.16 General Rule for Deferred Features

A deferred feature may be brought forward when at least one of the following becomes true:

1. A real user requirement makes it necessary.
2. A commercial opportunity clearly justifies it.
3. A technical limitation blocks an important product capability.
4. Evidence from testing or real usage demonstrates that the feature would materially improve the product.
5. The feature is required to support an already-validated product direction.

Features should not be implemented merely because they appear in the long-term roadmap.

The project should continuously distinguish between:

**"We may eventually need this."**

and:

**"We need this now to make the product better."**

The second category takes priority.

