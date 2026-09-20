# Reward Gate — Development TODO

**Status:** MVP Development
**Related Documents:** `docs/specification.md`, `docs/architecture.md`, `docs/database.md`

This document is the active implementation checklist for Reward Gate.

The specification defines what the product should become.

The architecture defines how the application should be structured.

The database document defines how persistent data should be modeled.

This document tracks implementation progress and the remaining work required to reach the MVP.

---

## 1. Project Foundation

### 1.1 Repository

* [x] Initialize Git repository
* [x] Rename default branch to `main`
* [x] Create private GitHub repository
* [x] Connect local repository to GitHub
* [x] Verify push/pull workflow

### 1.2 Git Configuration

* [x] Create `.gitignore`
* [x] Ignore `vendor/`
* [x] Ignore `private/`
* [x] Ignore `.php-cs-fixer.cache`
* [x] Ignore environment files
* [x] Ignore operating-system files
* [x] Ignore local editor/development files
* [ ] Review `.gitignore` before first commercial release

### 1.3 Project Structure

* [x] Create `src/`
* [x] Create `public/`
* [x] Create `config/`
* [x] Create `database/`
* [x] Create `tests/`
* [x] Create `docs/`
* [x] Create `private/`
* [x] Create `storage/` if required

### 1.4 Documentation

* [x] Create `README.md`
* [x] Create `docs/specification.md`
* [x] Create `docs/architecture.md`
* [x] Create `docs/database.md`
* [x] Create `docs/todo.md`

---

## 2. Development Tooling

### 2.1 PHP

* [x] PHP development environment
* [x] Composer
* [x] Composer autoloading
* [x] Establish minimum supported PHP version
* [ ] Confirm final supported PHP version range
* [ ] Test against supported PHP versions

### 2.2 Static Analysis

* [x] PHPStan
* [x] PHPStan configuration
* [ ] Establish initial PHPStan baseline
* [ ] Increase PHPStan strictness as codebase grows
* [ ] Ensure production code passes PHPStan

### 2.3 Formatting

* [x] PHP-CS-Fixer
* [x] PHP-CS-Fixer configuration
* [ ] Establish final project coding standard
* [ ] Ensure production PHP code passes PHP-CS-Fixer

### 2.4 Editor / IDE

* [x] Intelephense
* [x] Treesitter
* [ ] Configure Xdebug if required
* [ ] Configure `nvim-dap` if required

### 2.5 Testing

* [x] Install/configure PHPUnit
* [x] Establish test directory structure
* [x] Create first automated test
* [x] Define unit/integration testing approach
* [x] Configure test database
* [x] Configure Composer test commands
* [x] Unit-test services
* [x] Integration-test repositories
* [x] Integration-test service/database interactions
* [x] Unit-test authentication/security components
* [x] Unit-test controllers
* [x] Review important untested application areas
* [x] Test campaign presentation-settings normalization
* [x] Test campaign frequency-limit handling
* [x] Test popup-related controller behavior
* [x] Test Content Gate-related controller behavior
* [x] Test visitor binding and wrong-visitor rejection
* [x] Test click unlock completion without timer delay
* [x] Test frequency-limit transaction rollback behavior
* [x] Test concurrent completion protection

---

## 3. Application Foundation

### 3.1 Bootstrap

* [x] Create application bootstrap
* [x] Configure Composer autoloading
* [x] Establish application entry point
* [x] Establish configuration loading
* [ ] Establish environment configuration where required
* [ ] Establish production error handling
* [ ] Establish application logging if required

### 3.2 HTTP Layer

* [x] Define routing approach
* [x] Implement HTTP method handling
* [x] Implement route registration through `config/routes.php`
* [x] Implement controller dispatch
* [x] Implement JSON responses
* [x] Implement basic request handling
* [ ] Review and improve input validation as features are added
* [x] Support trailing-slash routes
* [x] Unit-test router behavior
* [x] Add visitor/demo route used for local gate testing

### 3.3 Database

* [x] Configure PDO
* [x] Establish database connection
* [x] Configure database credentials
* [x] Use prepared SQL statements
* [x] Use explicit transactions where required by unlock completion
* [x] Test database connection locally
* [x] Test database connection on demo server

### 3.4 Presentation

* [x] Establish Controller → View architecture
* [x] Establish shared `layout.php`
* [x] Move document structure into layout
* [x] Remove duplicated `<html>`, `<head>` and `<body>` structures from views
* [x] Establish campaign views
* [x] Establish shared rendering approach
* [x] Establish dedicated admin layout
* [x] Establish dedicated authentication layout
* [x] Establish responsive admin navigation
* [x] Establish admin theme selection with system/light/dark modes

---

## 4. Database

### 4.1 Migration System

* [x] Select migration approach
* [x] Create migration directory
* [x] Create migration tracking mechanism
* [x] Create initial migration
* [x] Test migrations on a clean database
* [x] Test migration upgrades

### 4.2 Core Tables

* [x] Create `campaigns`
* [x] Create `unlock_sessions`
* [x] Create `unlock_completions`
* [x] Create `admin_users`

### 4.3 Constraints and Indexes

* [x] Add primary keys
* [x] Add foreign keys
* [x] Add unique constraints
* [x] Add required indexes
* [x] Review cascade/delete behavior
* [x] Review timestamp fields
* [x] Review replay-prevention constraints

### 4.4 Database Verification

* [x] Verify complete schema locally
* [x] Verify relationships
* [x] Verify replay-prevention constraints
* [x] Export schema/data for demo deployment
* [x] Deploy database to demo server
* [x] Verify session/completion lifecycle locally
* [ ] Verify session/completion lifecycle remotely
* [ ] Verify migration rollback behavior where supported

---

## 5. Application Core

### 5.1 Campaign

* [x] Define campaign persistence model
* [x] Implement campaign repository
* [x] Implement campaign controller
* [x] Implement campaign list
* [x] Implement campaign detail view
* [x] Implement campaign creation
* [x] Implement campaign editing
* [x] Implement campaign activation/deactivation
* [x] Implement campaign archive behavior
* [x] Define campaign lifecycle rules
* [x] Implement presentation-settings normalization
* [x] Implement popup presentation settings
* [x] Implement Content Gate campaign configuration
* [x] Implement campaign frequency-limit configuration
* [x] Preserve unlimited frequency behavior with `NULL`
* [ ] Decide whether hard-delete is required for MVP
* [ ] Review campaign lifecycle edge cases

### 5.2 Unlock Session

* [x] Define unlock-session domain rules
* [x] Implement unlock-session repository
* [x] Implement secure session creation
* [x] Generate secure unlock token
* [x] Store server-side start timestamp
* [x] Define session expiration
* [x] Implement session lookup
* [x] Implement session validation
* [x] Bind sessions to the visitor when a visitor ID is available

### 5.3 Unlock Completion

* [x] Define completion rules
* [x] Implement completion repository
* [x] Implement server-side completion verification
* [x] Implement one-time completion
* [x] Prevent replay
* [x] Record completion timestamp
* [x] Handle expired sessions
* [x] Handle invalid sessions
* [x] Reject completion from the wrong visitor
* [x] Support anonymous/unbound sessions
* [x] Protect frequency-limit checks against concurrent completions
* [x] Lock the campaign row during completion verification

### 5.4 Timer Unlock

* [x] Define timer unlock interface/boundary
* [x] Define minimum completion time
* [x] Create unlock-session flow
* [x] Start timer on client
* [x] Verify elapsed time on server
* [x] Reject premature completion
* [x] Complete unlock after server verification

### 5.5 Click Unlock

* [x] Define click unlock interface/boundary
* [x] Create click unlock session flow
* [x] Allow click completion without timer delay
* [x] Verify click completion server-side
* [x] Use the same completion/replay/frequency rules as timer unlock
* [x] Support Content Gate click unlock

### 5.6 Frequency Limit

* [x] Define campaign-specific frequency-limit behavior
* [x] Enforce frequency limits when starting unlock sessions
* [x] Enforce frequency limits during completion
* [x] Preserve `NULL` as unlimited/permanent unlock behavior
* [x] Define exact cutoff behavior
* [x] Verify completions exactly at the cutoff are no longer considered recent
* [x] Protect concurrent completions with campaign row locking

### 5.7 Reward / Unlock Result

MVP does not require a separate reward subsystem.

The MVP reward is simply:

> **Unlock/reveal the protected content.**

* [x] Define unlock result
* [x] Return successful unlock state
* [x] Ensure presentation code does not contain unlock business logic

---

## 6. Administration

### 6.1 Authentication

* [x] Define administrator authentication
* [x] Implement login
* [x] Implement logout
* [x] Implement password hashing
* [x] Implement secure admin sessions
* [x] Implement authentication guard
* [x] Protect admin routes

### 6.2 Campaign Management

* [x] Campaign list
* [x] Campaign detail
* [x] Create campaign
* [x] Edit campaign
* [x] Enable/disable campaign
* [x] Archive campaign
* [x] Configure timer duration
* [x] Configure presentation type
* [x] Configure popup title
* [x] Configure popup message
* [x] Configure popup message visibility
* [x] Configure popup content/ad code
* [x] Configure frequency limit
* [x] Allow unlimited frequency by leaving frequency limit empty

### 6.3 Admin UI

* [x] Establish basic admin layout
* [x] Create navigation
* [x] Create reusable campaign form structure
* [x] Create validation/error display
* [x] Ensure basic mobile usability
* [x] Add responsive sidebar/offcanvas navigation
* [x] Add appearance/theme controls
* [ ] Refine final visual theme
* [ ] Add final Reward Gate logo/branding
* [ ] Add favicon

A dashboard is not required for the MVP.

---

## 7. Popup Gate

### 7.1 Presentation

* [x] Create initial popup gate HTML structure
* [x] Create popup gate CSS
* [x] Create popup gate JavaScript
* [x] Implement open/close behavior
* [x] Prevent protected interaction while locked
* [x] Ensure responsive behavior
* [x] Load popup configuration from campaign data
* [x] Keep visitor-facing campaign configuration separate from admin endpoints
* [x] Support configurable title
* [x] Support configurable message
* [x] Support hiding the message
* [x] Support arbitrary trusted HTML popup content
* [x] Support image content
* [x] Support iframe/embed content
* [x] Support trusted embedded JavaScript
* [x] Deliberately mount embedded `<script>` elements
* [x] Render title/message as text rather than trusted HTML
* [x] Handle campaign-loading errors
* [x] Handle unlock-start errors
* [x] Prevent secondary JavaScript errors when the popup has not yet been created

### 7.2 Unlock Flow

* [x] Create unlock session from popup
* [x] Display timer
* [x] Prevent client-side timer manipulation from granting unlock
* [x] Report completion to server
* [x] Handle verification response
* [x] Unlock protected content automatically
* [x] Enforce campaign frequency limit

### 7.3 UX

* [ ] Explain required action clearly
* [x] Display clear countdown/progress
* [ ] Display completion state
* [x] Display failure state
* [ ] Display dedicated expired state
* [x] Avoid deceptive UI
* [x] Test desktop
* [x] Test mobile
* [ ] Perform final UX review

---

## 8. Content / Read-more Gate

The MVP Content Gate is a client-side visibility gate. Reward Gate does not store,
copy, parse, or serve the customer’s article content.

The customer page contains the article and an empty split-point element. The gate
CSS hides following siblings before JavaScript runs, and the gate JavaScript
renders the unlock UI inside the split point. After server-verified completion,
the original DOM is revealed in place.

### 8.1 Presentation Contract

* [x] Define the Content Gate split-point HTML contract
* [x] Use one empty `<div data-reward-gate data-campaign-id="...">` at the cut point
* [x] Define following-sibling visibility behavior
* [x] Create Content Gate CSS
* [x] Ensure Content Gate CSS loads before protected content can render
* [x] Keep protected article HTML in the customer page rather than Reward Gate DB
* [x] Create Content Gate JavaScript
* [x] Render gate UI inline at the split point
* [x] Keep the original customer DOM intact
* [x] Set an explicit unlocked state on successful completion
* [x] Support one Content Gate per page for MVP

### 8.2 Unlock Flow

* [x] Load and validate the campaign
* [x] Verify the campaign is configured for Content Gate presentation
* [x] Verify the supported unlock method
* [x] Start unlock session using the shared unlock protocol
* [x] Open the configured destination through the visitor CTA
* [x] Request click completion from the server
* [x] Verify completion server-side
* [x] Reveal protected content after successful completion
* [x] Keep protected content hidden when initialization fails
* [x] Display a safe inline error when the gate cannot initialize
* [x] Handle invalid/missing campaign configuration without exposing content
* [x] Handle non-JSON/error responses safely
* [x] Handle unlock-start failures without leaving the gate permanently disabled
* [x] Remove the gate UI after successful unlock
* [x] Restore the CTA after unlock failure
* [x] Use server-side visitor status to preserve unlock state across reloads
* [x] Enforce campaign frequency-limit behavior
* [ ] Verify incorrect gate placement safely

> Content Gate uses the **click** unlock method for MVP. It does not display a timer.

### 8.3 JavaScript / Shared Unlock

* [x] Inspect the existing Popup unlock flow and identify the genuinely shared protocol
* [ ] Extract a shared client-side unlock helper
* [x] Keep presentation-specific UI logic outside the shared unlock flow
* [x] Ensure Content Gate and Popup Gate use the same server verification rules
* [x] Avoid creating a generic frontend framework or speculative abstraction

The current implementation intentionally does not introduce a generic frontend
unlock helper. The shared protocol is provided by the existing unlock API and
server-side service.

### 8.4 Configuration

* [x] Define that Content Gate configuration belongs to the campaign
* [x] Define that `presentation_settings` does not store customer article HTML
* [x] Define required Content Gate presentation settings for MVP
* [x] Ensure no percentage-based or selector-based article splitting is required for MVP
* [x] Ensure Content Gate uses the shared unlock protocol
* [x] Define destination URL as Content Gate campaign configuration
* [x] Define CTA label as Content Gate campaign configuration
* [x] Define one Content Gate per page as an MVP limitation

### 8.5 Browser / Compatibility Tests

* [ ] Verify protected content stays hidden with JavaScript disabled
* [ ] Verify protected content stays hidden while the gate is initializing
* [ ] Verify successful unlock reveals the original DOM without rebuilding it
* [ ] Verify desktop behavior
* [ ] Verify mobile behavior
* [ ] Verify click completion cannot directly grant unlock without server verification
* [ ] Verify replay/frequency rules match Popup Gate behavior

---

## 9. Security & Anti-Abuse

### 9.1 Server Authority

* [x] Server determines session validity
* [x] Server determines minimum completion time for timer unlocks
* [x] Server determines completion eligibility
* [x] Client-side state cannot directly grant unlock
* [x] Click unlock completion is verified server-side

### 9.2 Session Security

* [x] Generate cryptographically secure session identifiers
* [x] Use secure token handling
* [x] Store only necessary sensitive token material
* [x] Implement session expiration
* [x] Implement one-time completion
* [x] Prevent replay
* [x] Bind completion to the visitor where applicable
* [x] Reject completion from a different visitor

### 9.3 Request Security

* [x] Validate all user input
* [x] Prevent unauthorized campaign access
* [x] Implement CSRF protection for state-changing admin requests
* [x] Use prepared SQL statements
* [x] Escape output appropriately
* [x] Protect internal application files from direct HTTP access
* [x] Handle malformed JSON requests safely
* [x] Handle non-JSON backend errors safely in visitor-facing Content Gate JavaScript

### 9.4 Anti-Abuse

* [x] Implement basic visitor/session frequency limiting
* [x] Define applicable campaign limits
* [x] Prevent repeated completion
* [x] Handle expired sessions
* [x] Implement first-party visitor identification
* [x] Use a cryptographically random visitor ID
* [x] Store visitor ID in a first-party cookie
* [x] Avoid IP address as visitor identity
* [x] Avoid browser fingerprinting
* [x] Define cookie-clearing/incognito limitations
* [x] Define campaign-specific frequency-limit behavior
* [x] Protect concurrent completion/frequency-limit checks with a campaign row lock
* [ ] Record verification failures where useful
* [ ] Perform production privacy/configuration review of visitor identification

### 9.5 Security Review

* [ ] Perform manual security review
* [x] Review authentication
* [x] Review authorization
* [x] Review session handling
* [x] Review database access
* [x] Review public/private directory handling
* [ ] Review production configuration
* [ ] Review trusted executable popup content security implications
* [x] Define Content Gate client-side visibility as a presentation/UX boundary rather than a strong security boundary
* [ ] Document the Content Gate security boundary clearly

The following are intentionally **not MVP requirements**:

* IP-based visitor identity
* Browser fingerprinting
* JWT-based unlock tokens
* CAPTCHA
* Redis/distributed anti-abuse infrastructure
* ML/fraud scoring
* Elaborate anti-bot systems

Basic endpoint rate limiting may be added later if real-world usage demonstrates
that it is necessary.

---

## 10. Deployment

### 10.1 Apache

* [x] Configure local Apache virtual host
* [x] Configure Apache front-controller routing
* [x] Verify `mod_rewrite`
* [x] Create root `.htaccess`
* [x] Create protection rules for sensitive files
* [x] Create directory protection rules
* [x] Test campaign routes locally
* [x] Test visitor/demo route locally
* [x] Test local visitor access from a mobile device

### 10.2 Shared Hosting

* [x] Deploy application to demo server
* [x] Resolve `open_basedir` restrictions
* [x] Deploy application inside `public_html`
* [x] Configure database connection
* [x] Deploy database
* [x] Verify PHP execution
* [x] Verify front-controller routing
* [x] Verify campaign routes
* [x] Verify HTTPS
* [x] Protect internal application files
* [ ] Test complete visitor workflow remotely

### 10.3 Nginx

* [ ] Install Nginx locally
* [ ] Configure PHP-FPM
* [ ] Create Nginx server configuration
* [ ] Configure front-controller routing
* [ ] Configure direct-access protection
* [ ] Test static assets
* [ ] Test existing files
* [ ] Test trailing-slash routes
* [ ] Test 404 handling
* [ ] Document Nginx deployment requirements

---

## 11. Testing

### 11.1 Automated Tests

* [x] Install/configure PHPUnit
* [x] Test Router
* [x] Test campaign repository
* [x] Test campaign logic
* [x] Test timer logic
* [x] Test click unlock logic
* [x] Test unlock-session creation
* [x] Test session validation
* [x] Test completion validation
* [x] Test replay prevention
* [x] Test visitor binding
* [x] Test wrong-visitor rejection
* [x] Test authentication
* [x] Test security components
* [x] Test controllers
* [x] Test presentation-settings normalization
* [x] Test frequency-limit input handling
* [x] Test frequency-limit cutoff behavior
* [x] Test frequency-limit transaction rollback
* [x] Test concurrent completion protection

Current automated test suite:

* [x] Unit tests
* [x] Integration tests
* [x] Controller tests
* [x] Authentication/security tests
* [x] Composer test commands
* [x] Full test suite passing

Current verified baseline:

> **171 tests, 773 assertions**

### 11.2 Integration Tests

* [x] Test database operations
* [x] Test session creation
* [x] Test completion flow
* [x] Test authentication repository
* [x] Test campaign management repository
* [x] Test complete timer unlock service flow
* [x] Test click unlock completion
* [x] Test visitor-bound completion
* [x] Test frequency-limit enforcement during completion
* [x] Test campaign-row locking during completion

### 11.3 End-to-End Tests

* [x] Visitor encounters Popup Gate locally
* [x] Visitor starts Popup unlock locally
* [x] Popup timer completes locally
* [x] Server verifies Popup completion locally
* [x] Protected content unlocks locally
* [x] Popup replay attempt fails
* [x] Expired/invalid session paths are implemented
* [x] Manipulated client timer cannot directly grant unlock
* [x] Multiple completion attempts fail
* [x] Frequency-limit rejection tested
* [x] Popup content HTML tested
* [x] Popup image content tested
* [x] Popup iframe content tested
* [x] Popup embedded JavaScript tested
* [x] Popup error handling tested
* [ ] Complete Content Gate browser/E2E happy path
* [ ] Formal repeatable browser/E2E test procedure
* [ ] Verify complete workflow remotely

---

## 12. MVP Demo

### 12.1 Local

* [x] Create local database
* [x] Create development/test data
* [x] Create demo campaign
* [x] Create demo protected content
* [x] Create local demo route
* [x] Verify Popup Gate visitor workflow locally
* [x] Verify Popup Gate on desktop
* [x] Verify Popup Gate on phone
* [x] Create dedicated Content Gate demo page
* [ ] Verify Content Gate with JavaScript disabled
* [ ] Verify Content Gate visitor workflow locally
* [ ] Verify Content Gate on desktop
* [ ] Verify Content Gate on phone
* [ ] Finalize demo data/setup procedure

### 12.2 Remote

* [x] Deploy application
* [x] Deploy database
* [x] Verify HTTPS
* [ ] Test Popup Gate remotely
* [ ] Test Content Gate remotely
* [ ] Test expired sessions
* [ ] Test replay attempts
* [ ] Test manipulated timer
* [ ] Test multiple completion attempts
* [ ] Test mobile devices

---

## 13. First Commercial Release

These features remain intentionally deferred until the MVP is working.

### 13.1 Campaign Features

* [ ] Multiple campaigns
* [ ] Campaign activation/deactivation improvements
* [ ] Campaign duplication
* [ ] Campaign scheduling
* [ ] Campaign targeting
* [ ] Campaign usage limits
* [ ] Campaign status management improvements

### 13.2 Analytics

* [ ] Visitors
* [ ] Gates shown
* [ ] Unlocks started
* [ ] Unlocks completed
* [ ] Completion rate
* [ ] Abandonment rate
* [ ] Revenue metrics where applicable
* [ ] Performance by unlock method

### 13.3 Customization

* [ ] Popup customization
* [ ] Content gate customization
* [ ] Colors
* [ ] Typography
* [ ] Button styles
* [ ] Messages
* [ ] Timer configuration
* [ ] Visible-content configuration

### 13.4 Advanced / Killer Features

Deferred until the basic product has been validated.

* [ ] Smart Reward Routing
* [ ] Campaign Objectives
* [ ] Reward Gate Recipes
* [ ] Revenue Analytics
* [ ] Automatic Recommendations
* [ ] Campaign Health Warnings
* [ ] A/B Testing
* [ ] Smart Fallbacks
* [ ] Zero-Configuration Starter Campaign
* [ ] Explain My Campaign
* [ ] Goal-Based Optimization
* [ ] Revenue vs. UX Balancing
* [ ] Automatic Method Optimization
* [ ] Predictive Recommendations
* [ ] Campaign Performance Intelligence
* [ ] Automatic Campaign Optimization
* [ ] Adaptive Campaigns

---

## 14. Commercial Packaging

### 14.1 Self-Hosted Distribution

* [ ] Define release package structure
* [ ] Include production Composer dependencies
* [ ] Exclude development dependencies
* [ ] Define Apache deployment package
* [ ] Define Nginx deployment package
* [ ] Ensure application files are protected
* [ ] Create installation instructions
* [ ] Create upgrade instructions
* [ ] Create server requirements documentation
* [ ] Test installation on clean hosting environment

### 14.2 Installation

Deferred until the MVP deployment process is stable.

* [ ] Installation wizard
* [ ] Environment checks
* [ ] Database configuration
* [ ] Database migrations
* [ ] Administrator account creation
* [ ] Initial configuration
* [ ] Demo data option

### 14.3 Licensing

* [ ] Determine licensing model
* [ ] Determine license-key requirements
* [ ] Determine update mechanism
* [ ] Determine domain activation requirements

Licensing should not be implemented until the commercial model has been validated.

---

## 15. Future Integrations

### 15.1 WordPress

* [ ] Design WordPress integration
* [ ] Implement WordPress plugin
* [ ] Native campaign configuration
* [ ] Shortcode/block support
* [ ] Test shared engine integration

### 15.2 Laravel

* [ ] Design Laravel integration
* [ ] Implement Laravel package
* [ ] Laravel-specific integration layer
* [ ] Test shared engine integration

### 15.3 Generic PHP / JavaScript

* [ ] Define generic PHP integration
* [ ] Define JavaScript integration
* [ ] Create lightweight integration API
* [ ] Document embedding process

---

## 16. Long-Term Product

* [ ] Additional unlock methods
* [ ] Additional presentation types
* [ ] Additional reward types
* [ ] Download Gate
* [ ] Rewarded Video
* [ ] Offerwall integration
* [ ] Survey integration
* [ ] External Task integrations
* [ ] Hosted SaaS
* [ ] Multi-tenancy
* [ ] Billing
* [ ] Subscriptions
* [ ] Advanced analytics
* [ ] Predictive analytics
* [ ] Automatic campaign optimization
* [ ] Revenue optimization engine

---

## 17. Release Checklist

Before commercial release:

* [ ] All MVP functionality complete
* [ ] Security review complete
* [x] Automated tests passing
* [ ] PHPStan passing
* [ ] PHP-CS-Fixer passing
* [ ] Supported PHP versions tested
* [ ] Clean installation tested
* [ ] Upgrade/migration tested
* [x] Apache deployment tested
* [ ] Nginx deployment tested
* [x] Shared-hosting deployment tested
* [x] Mobile testing complete
* [ ] Documentation complete
* [ ] Production configuration reviewed
* [ ] Secrets excluded from distribution
* [ ] Development dependencies excluded from production package
* [ ] Release archive tested on a clean server

---

## 18. Current Priority

The core backend, Campaign Management, Popup Gate, unlock engine, visitor identification,
frequency limiting, and Content Gate implementation are substantially complete.

The remaining work is now primarily **verification, UX polish, production review, and deployment**.

### Current order

1. **Complete Content Gate local browser/E2E verification**

   Verify the actual visitor flow:

   * initial locked state
   * protected content visibility
   * CTA click
   * destination opening
   * server-side completion
   * content reveal
   * reload/persistent unlock behavior
   * frequency-limit behavior
   * failure states

2. **Complete Popup Gate UX**

   * final wording
   * completion state
   * dedicated expired state
   * final visual polish

3. **Complete local end-to-end MVP visitor workflow**

   Exercise both Popup Gate and Content Gate as a coherent local visitor workflow.

4. **Complete production security and anti-abuse review**

   Focus on actual production risks rather than adding speculative anti-abuse infrastructure.

5. **Verify complete visitor workflow remotely**

   Test the real deployment environment on the demo server.

6. **PHPStan / PHP-CS-Fixer finalization**

7. **Nginx local deployment**

8. **Final documentation / release documentation**

9. **Commercial packaging**

Do not start advanced analytics, optimization, integrations, licensing, or SaaS work
until the MVP visitor workflow is stable.

---

## 19. Development Rule

At every stage, prioritize work that:

1. Proves the product works.
2. Improves security or reliability.
3. Improves the visitor experience.
4. Creates clear commercial value.

Avoid implementing infrastructure solely because it may become useful someday.

Reward Gate should evolve from a working product into a larger platform based on evidence rather than speculation.

