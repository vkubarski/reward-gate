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

- [x] Initialize Git repository
- [x] Rename default branch to `main`
- [x] Create private GitHub repository
- [x] Connect local repository to GitHub
- [x] Verify push/pull workflow

### 1.2 Git Configuration

- [x] Create `.gitignore`
- [x] Ignore `vendor/`
- [x] Ignore `private/`
- [x] Ignore `.php-cs-fixer.cache`
- [x] Ignore environment files
- [x] Ignore operating-system files
- [ ] Review `.gitignore` before first commercial release

### 1.3 Project Structure

- [x] Create `src/`
- [x] Create `public/`
- [x] Create `config/`
- [x] Create `database/`
- [x] Create `tests/`
- [x] Create `docs/`
- [x] Create `private/`
- [x] Create `storage/` if required

### 1.4 Documentation

- [x] Create `README.md`
- [x] Create `docs/specification.md`
- [x] Create `docs/architecture.md`
- [x] Create `docs/database.md`
- [x] Create `docs/todo.md`

---

## 2. Development Tooling

### 2.1 PHP

- [x] PHP development environment
- [x] Composer
- [x] Composer autoloading
- [x] Establish minimum supported PHP version
- [ ] Confirm final supported PHP version range
- [ ] Test against supported PHP versions

### 2.2 Static Analysis

- [x] PHPStan
- [x] PHPStan configuration
- [ ] Establish initial PHPStan baseline
- [ ] Increase PHPStan strictness as codebase grows
- [ ] Ensure production code passes PHPStan

### 2.3 Formatting

- [x] PHP-CS-Fixer
- [x] PHP-CS-Fixer configuration
- [ ] Establish final project coding standard
- [ ] Ensure production PHP code passes PHP-CS-Fixer

### 2.4 Editor / IDE

- [x] Intelephense
- [x] Treesitter
- [ ] Configure Xdebug if required
- [ ] Configure `nvim-dap` if required

### 2.5 Testing

- [x] Install/configure PHPUnit
- [x] Establish test directory structure
- [x] Create first automated test
- [x] Define unit/integration testing approach
- [x] Configure test database
- [x] Configure Composer test commands
- [x] Unit-test services
- [x] Integration-test repositories
- [x] Integration-test service/database interactions
- [x] Unit-test authentication/security components
- [x] Unit-test controllers
- [x] Review important untested application areas

---

## 3. Application Foundation

### 3.1 Bootstrap

- [x] Create application bootstrap
- [x] Configure Composer autoloading
- [x] Establish application entry point
- [x] Establish configuration loading
- [ ] Establish environment configuration where required
- [ ] Establish production error handling
- [ ] Establish application logging if required

### 3.2 HTTP Layer

- [x] Define routing approach
- [x] Implement HTTP method handling
- [x] Implement route registration through `config/routes.php`
- [x] Implement controller dispatch
- [x] Implement JSON responses
- [x] Implement basic request handling
- [ ] Review and improve input validation as features are added
- [x] Support trailing-slash routes
- [x] Unit-test router behavior

### 3.3 Database

- [x] Configure PDO
- [x] Establish database connection
- [x] Configure database credentials
- [x] Use prepared SQL statements
- [ ] Establish reusable transaction handling where required
- [x] Test database connection locally
- [x] Test database connection on demo server

### 3.4 Presentation

- [x] Establish Controller → View architecture
- [x] Establish shared `layout.php`
- [x] Move document structure into layout
- [x] Remove duplicated `<html>`, `<head>` and `<body>` structures from views
- [x] Establish campaign views
- [x] Establish shared rendering approach

---

## 4. Database

### 4.1 Migration System

- [x] Select migration approach
- [x] Create migration directory
- [x] Create migration tracking mechanism
- [x] Create initial migration
- [x] Test migrations on a clean database
- [x] Test migration upgrades

### 4.2 Core Tables

- [x] Create `campaigns`
- [x] Create `unlock_sessions`
- [x] Create `unlock_completions`
- [x] Create `admin_users`

### 4.3 Constraints and Indexes

- [x] Add primary keys
- [x] Add foreign keys
- [x] Add unique constraints
- [x] Add required indexes
- [x] Review cascade/delete behavior
- [x] Review timestamp fields
- [x] Review replay-prevention constraints

### 4.4 Database Verification

- [x] Verify complete schema locally
- [x] Verify relationships
- [x] Verify replay-prevention constraints
- [x] Export schema/data for demo deployment
- [x] Deploy database to demo server
- [x] Verify session/completion lifecycle locally
- [ ] Verify session/completion lifecycle remotely
- [ ] Verify migration rollback behavior where supported

---

## 5. Application Core

### 5.1 Campaign

- [x] Define campaign persistence model
- [x] Implement campaign repository
- [x] Implement campaign controller
- [x] Implement campaign list
- [x] Implement campaign detail view
- [x] Implement campaign creation
- [ ] Implement campaign editing
- [ ] Implement campaign activation/deactivation
- [ ] Implement campaign archive/delete behavior

### 5.2 Unlock Session

- [x] Define unlock-session domain rules
- [x] Implement unlock-session repository
- [x] Implement secure session creation
- [x] Generate secure unlock token
- [x] Store server-side start timestamp
- [x] Define session expiration
- [x] Implement session lookup
- [x] Implement session validation

### 5.3 Unlock Completion

- [x] Define completion rules
- [x] Implement completion repository
- [x] Implement server-side completion verification
- [x] Implement one-time completion
- [x] Prevent replay
- [x] Record completion timestamp
- [x] Handle expired sessions
- [x] Handle invalid sessions

### 5.4 Timer Unlock

- [x] Define timer unlock interface/boundary
- [x] Define minimum completion time
- [x] Create unlock-session flow
- [x] Start timer on client
- [x] Verify elapsed time on server
- [x] Reject premature completion
- [x] Complete unlock after server verification

### 5.5 Reward / Unlock Result

MVP does not require a separate reward subsystem.

The MVP reward is simply:

> **Unlock/reveal the protected content.**

- [x] Define unlock result
- [x] Return successful unlock state
- [x] Ensure presentation code does not contain unlock business logic

---

## 6. Administration

### 6.1 Authentication

- [x] Define administrator authentication
- [x] Implement login
- [x] Implement logout
- [x] Implement password hashing
- [x] Implement secure admin sessions
- [x] Implement authentication guard
- [x] Protect admin routes

### 6.2 Campaign Management

- [x] Campaign list
- [x] Campaign detail
- [x] Create campaign
- [ ] Edit campaign
- [ ] Enable/disable campaign
- [ ] Archive/delete campaign
- [x] Configure timer duration
- [x] Configure presentation type

### 6.3 Admin UI

- [x] Establish basic admin layout
- [ ] Create navigation
- [ ] Create reusable form structure
- [ ] Create validation/error display
- [ ] Ensure basic mobile usability

A dashboard is not required for the MVP.

---

## 7. Popup Gate

### 7.1 Presentation

- [x] Create initial popup gate HTML structure
- [x] Create popup gate CSS
- [x] Create popup gate JavaScript
- [x] Implement open/close behavior
- [x] Prevent protected interaction while locked
- [x] Ensure responsive behavior
- [x] Load popup configuration from campaign data
- [x] Keep visitor-facing campaign configuration separate from admin endpoints

### 7.2 Unlock Flow

- [x] Create unlock session from popup
- [x] Display timer
- [x] Prevent client-side timer manipulation from granting unlock
- [x] Report completion to server
- [x] Handle verification response
- [x] Unlock protected content automatically

### 7.3 UX

- [ ] Explain required action clearly
- [ ] Display clear countdown/progress
- [ ] Display completion state
- [ ] Display failure/expired state
- [ ] Avoid deceptive UI
- [ ] Test desktop
- [ ] Test mobile

---

## 8. Content / Read-more Gate

### 8.1 Presentation

- [ ] Define content-gate HTML structure
- [ ] Define visible-content area
- [ ] Define protected-content area
- [ ] Create gate control
- [ ] Create CSS
- [ ] Create JavaScript

### 8.2 Unlock Flow

- [ ] Display initial visible content
- [ ] Hide protected content
- [ ] Display Continue Reading / Unlock control
- [ ] Start unlock session
- [ ] Display timer
- [ ] Verify completion server-side
- [ ] Reveal protected content

### 8.3 Configuration

- [ ] Define visible-content configuration
- [ ] Determine default visible-content behavior
- [ ] Determine whether configurable percentage is required for MVP
- [ ] Ensure Content Gate uses the shared unlock engine

---

## 9. Security & Anti-Abuse

### 9.1 Server Authority

- [x] Server determines session validity
- [x] Server determines minimum completion time
- [x] Server determines completion eligibility
- [x] Client-side state cannot directly grant unlock

### 9.2 Session Security

- [x] Generate cryptographically secure session identifiers
- [x] Use secure token handling
- [x] Store only necessary sensitive token material
- [x] Implement session expiration
- [x] Implement one-time completion
- [x] Prevent replay

### 9.3 Request Security

- [x] Validate all user input
- [x] Prevent unauthorized campaign access
- [x] Implement CSRF protection for state-changing admin requests
- [x] Use prepared SQL statements
- [x] Escape output appropriately
- [x] Protect internal application files from direct HTTP access

### 9.4 Anti-Abuse

- [x] Implement basic visitor/session frequency limiting
- [x] Define applicable campaign limits
- [x] Prevent repeated completion
- [x] Handle expired sessions
- [ ] Record verification failures where useful
- [ ] Review visitor identification strategy for privacy and production use
- [ ] Define visitor identification and frequency-limiting strategy for production, including privacy, proxy/IP handling, rotation, and abuse resistance

### 9.5 Security Review

- [ ] Perform manual security review
- [x] Review authentication
- [x] Review authorization
- [x] Review session handling
- [x] Review database access
- [x] Review public/private directory handling
- [ ] Review production configuration

---

## 10. Deployment

### 10.1 Apache

- [x] Configure local Apache virtual host
- [x] Configure Apache front-controller routing
- [x] Verify `mod_rewrite`
- [x] Create root `.htaccess`
- [x] Create protection rules for sensitive files
- [x] Create directory protection rules
- [x] Test campaign routes locally

### 10.2 Shared Hosting

- [x] Deploy application to demo server
- [x] Resolve `open_basedir` restrictions
- [x] Deploy application inside `public_html`
- [x] Configure database connection
- [x] Deploy database
- [x] Verify PHP execution
- [x] Verify front-controller routing
- [x] Verify campaign routes
- [x] Verify HTTPS
- [x] Protect internal application files
- [ ] Test complete visitor workflow remotely

### 10.3 Nginx

- [ ] Install Nginx locally
- [ ] Configure PHP-FPM
- [ ] Create Nginx server configuration
- [ ] Configure front-controller routing
- [ ] Configure direct-access protection
- [ ] Test static assets
- [ ] Test existing files
- [ ] Test trailing-slash routes
- [ ] Test 404 handling
- [ ] Document Nginx deployment requirements

---

## 11. Testing

### 11.1 Automated Tests

- [x] Install/configure PHPUnit
- [x] Test Router
- [x] Test campaign repository
- [x] Test campaign logic
- [x] Test timer logic
- [x] Test unlock-session creation
- [x] Test session validation
- [x] Test completion validation
- [x] Test replay prevention
- [x] Test authentication
- [x] Test security components
- [x] Test controllers

Current automated test suite:

- [x] Unit tests
- [x] Integration tests
- [x] Controller tests
- [x] Authentication/security tests
- [x] Composer test commands
- [x] Full test suite passing

Current verified baseline:

> **137 tests, 542 assertions**

### 11.2 Integration Tests

- [x] Test database operations
- [x] Test session creation
- [x] Test completion flow
- [x] Test authentication repository
- [x] Test campaign management repository
- [x] Test complete timer unlock service flow

### 11.3 End-to-End Tests

- [ ] Visitor encounters gate
- [ ] Visitor starts unlock
- [ ] Timer completes
- [ ] Server verifies completion
- [ ] Protected content unlocks
- [ ] Replay attempt fails
- [ ] Expired session fails
- [ ] Manipulated client timer fails
- [ ] Multiple completion attempts fail

---

## 12. MVP Demo

### 12.1 Local

- [x] Create local database
- [x] Create development/test data
- [x] Create demo campaign
- [ ] Create demo protected content
- [ ] Verify complete visitor workflow

### 12.2 Remote

- [x] Deploy application
- [x] Deploy database
- [x] Verify HTTPS
- [ ] Test Popup Gate remotely
- [ ] Test Content Gate remotely
- [ ] Test expired sessions
- [ ] Test replay attempts
- [ ] Test manipulated timer
- [ ] Test multiple completion attempts
- [ ] Test mobile devices

---

## 13. First Commercial Release

These features remain intentionally deferred until the MVP is working.

### 13.1 Campaign Features

- [ ] Multiple campaigns
- [ ] Campaign activation/deactivation improvements
- [ ] Campaign duplication
- [ ] Campaign scheduling
- [ ] Campaign targeting
- [ ] Campaign usage limits
- [ ] Campaign status management

### 13.2 Analytics

- [ ] Visitors
- [ ] Gates shown
- [ ] Unlocks started
- [ ] Unlocks completed
- [ ] Completion rate
- [ ] Abandonment rate
- [ ] Revenue metrics where applicable
- [ ] Performance by unlock method

### 13.3 Customization

- [ ] Popup customization
- [ ] Content gate customization
- [ ] Colors
- [ ] Typography
- [ ] Button styles
- [ ] Messages
- [ ] Timer configuration
- [ ] Visible-content configuration

### 13.4 Advanced / Killer Features

Deferred until the basic product has been validated.

- [ ] Smart Reward Routing
- [ ] Campaign Objectives
- [ ] Reward Gate Recipes
- [ ] Revenue Analytics
- [ ] Automatic Recommendations
- [ ] Campaign Health Warnings
- [ ] A/B Testing
- [ ] Smart Fallbacks
- [ ] Zero-Configuration Starter Campaign
- [ ] Explain My Campaign
- [ ] Goal-Based Optimization
- [ ] Revenue vs. UX Balancing
- [ ] Automatic Method Optimization
- [ ] Predictive Recommendations
- [ ] Campaign Performance Intelligence
- [ ] Automatic Campaign Optimization
- [ ] Adaptive Campaigns

---

## 14. Commercial Packaging

### 14.1 Self-Hosted Distribution

- [ ] Define release package structure
- [ ] Include production Composer dependencies
- [ ] Exclude development dependencies
- [ ] Define Apache deployment package
- [ ] Define Nginx deployment package
- [ ] Ensure application files are protected
- [ ] Create installation instructions
- [ ] Create upgrade instructions
- [ ] Create server requirements documentation
- [ ] Test installation on clean hosting environment

### 14.2 Installation

Deferred until the MVP deployment process is stable.

- [ ] Installation wizard
- [ ] Environment checks
- [ ] Database configuration
- [ ] Database migrations
- [ ] Administrator account creation
- [ ] Initial configuration
- [ ] Demo data option

### 14.3 Licensing

- [ ] Determine licensing model
- [ ] Determine license-key requirements
- [ ] Determine update mechanism
- [ ] Determine domain activation requirements

Licensing should not be implemented until the commercial model has been validated.

---

## 15. Future Integrations

### 15.1 WordPress

- [ ] Design WordPress integration
- [ ] Implement WordPress plugin
- [ ] Native campaign configuration
- [ ] Shortcode/block support
- [ ] Test shared engine integration

### 15.2 Laravel

- [ ] Design Laravel integration
- [ ] Implement Laravel package
- [ ] Laravel-specific integration layer
- [ ] Test shared engine integration

### 15.3 Generic PHP / JavaScript

- [ ] Define generic PHP integration
- [ ] Define JavaScript integration
- [ ] Create lightweight integration API
- [ ] Document embedding process

---

## 16. Long-Term Product

- [ ] Additional unlock methods
- [ ] Additional presentation types
- [ ] Additional reward types
- [ ] Download Gate
- [ ] Rewarded Video
- [ ] Offerwall integration
- [ ] Survey integration
- [ ] External Task integrations
- [ ] Hosted SaaS
- [ ] Multi-tenancy
- [ ] Billing
- [ ] Subscriptions
- [ ] Advanced analytics
- [ ] Predictive analytics
- [ ] Automatic campaign optimization
- [ ] Revenue optimization engine

---

## 17. Release Checklist

Before commercial release:

- [ ] All MVP functionality complete
- [ ] Security review complete
- [x] Automated tests passing
- [ ] PHPStan passing
- [ ] PHP-CS-Fixer passing
- [ ] Supported PHP versions tested
- [ ] Clean installation tested
- [ ] Upgrade/migration tested
- [x] Apache deployment tested
- [ ] Nginx deployment tested
- [x] Shared-hosting deployment tested
- [ ] Mobile testing complete
- [ ] Documentation complete
- [ ] Production configuration reviewed
- [ ] Secrets excluded from distribution
- [ ] Development dependencies excluded from production package
- [ ] Release archive tested on a clean server

---

## 18. Current Priority

The original priority list is now outdated because several of its early items are already implemented.

### Current order

1. **Complete Popup Gate UX and real visitor workflow**
2. **Complete Campaign Management CRUD**
3. **Implement Content / Read-more Gate**
4. **Complete production security and anti-abuse review**
5. **Verify complete visitor workflow locally**
6. **Verify complete visitor workflow remotely**
7. **Test mobile behavior**
8. **PHPStan / PHP-CS-Fixer finalization**
9. **Nginx local deployment**
10. **Final documentation and packaging**

Do not start advanced analytics, optimization, integrations, licensing, or SaaS work until the MVP visitor workflow is stable.

---

## 19. Development Rule

At every stage, prioritize work that:

1. Proves the product works.
2. Improves security or reliability.
3. Improves the visitor experience.
4. Creates clear commercial value.

Avoid implementing infrastructure solely because it may become useful someday.

Reward Gate should evolve from a working product into a larger platform based on evidence rather than speculation.
