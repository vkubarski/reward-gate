```markdown
# Reward Gate — Architecture

**Status:** Development
**Architecture Version:** 1.0
**Related Specification:** `docs/specification.md`

---

## 1. Architecture Overview

Reward Gate is initially implemented as a simple, self-hosted PHP application.

The architecture intentionally separates:

- public web-accessible files
- application code
- third-party dependencies
- configuration
- database migrations
- writable runtime data

The preferred architectural rule is:

> Only the `public/` directory should be directly accessible from the web.

The application must also support hosting environments where the customer is required to place the complete application inside the web root. In that deployment model, the same security boundary must be enforced through appropriate web-server configuration so that private application files cannot be accessed through HTTP.

The preferred deployment keeps private application files outside the web root whenever the hosting environment allows it.

The initial architecture is designed for ordinary PHP hosting and should not require a framework, Node.js runtime, or server-side build system.

---

## 2. Deployment Architecture

Reward Gate supports two primary deployment models.

The preferred deployment keeps the private application outside the web root.

A compatibility deployment allows the complete application to be installed inside the web root when hosting restrictions make the preferred deployment impossible.

### 2.1 Preferred Deployment

The preferred deployment structure is:

```text
/home/customer/
├── reward-gate-app/
│   ├── src/
│   ├── vendor/
│   ├── config/
│   ├── database/
│   └── storage/
│
└── public_html/
    └── reward-gate/
        ├── index.php
        └── assets/
```

In this arrangement:

- `reward-gate-app/` contains the private application.
- `public_html/reward-gate/` contains only publicly accessible files.
- `vendor/` is outside the web root.
- configuration and secrets are outside the web root.
- runtime storage is outside the web root.
- database migrations and other application files are outside the web root.

The exact physical location may differ between hosting environments, but the security boundary should remain the same.

This is the recommended deployment model.

### 2.2 Web-Root Deployment

Some hosting environments may require the customer to install the complete application inside `public_html/`.

Reward Gate must support this deployment model as a compatibility option.

Example:

```text
/home/customer/
└── public_html/
    └── reward-gate/
        ├── src/
        ├── vendor/
        ├── config/
        ├── database/
        ├── storage/
        └── public/
            ├── index.php
            └── assets/
```

In this arrangement, the private application files physically exist inside the web root.

The application must still treat `public/` as the intended HTTP-facing directory.

The following directories must not be directly accessible through HTTP:

- `src/`
- `vendor/`
- `config/`
- `database/`
- `storage/`

Appropriate web-server access restrictions must be used to prevent direct access to these directories and their contents.

This deployment model is supported for compatibility with restrictive hosting environments but is less secure than the preferred deployment because the private application files physically reside inside the web root.

The application must not require the customer to manually modify application source code simply because this deployment model is being used.

---

## 3. Public Directory

The `public/` directory contains only files that need to be accessible through HTTP.

Typical contents:

```text
public/
├── index.php
└── assets/
    ├── css/
    ├── js/
    └── images/
```

The public directory may contain:

- the front controller
- CSS
- JavaScript
- publicly required images
- other intentionally public assets

It should not contain:

- application classes
- Composer dependencies
- configuration secrets
- database credentials
- private logs
- database migration files
- development tools
- test files

The `public/` directory is the intended HTTP-facing boundary of the application.

In the preferred deployment, private application files are physically located outside the web root.

In a web-root deployment, the complete application may physically reside below the web root, but the web server must prevent direct HTTP access to private application directories and files.

The `public/` directory itself should contain only resources that are intentionally exposed to the browser.

---

## 4. Application Directory

The application directory contains the private application code and supporting application resources.

In the preferred deployment, it is located outside the web root:

```text
reward-gate-app/
├── src/
├── vendor/
├── config/
├── database/
└── storage/
```

In a web-root deployment, the same directories may physically exist below `public_html/`:

```text
reward-gate/
├── src/
├── vendor/
├── config/
├── database/
├── storage/
└── public/
```

Regardless of the physical deployment location, these directories are considered private application resources and must not be directly accessible through HTTP.

### 4.1 `src/`

Contains application source code.

Potential future organization:

```text
src/
├── Campaign/
├── Unlock/
├── Reward/
├── Security/
├── Database/
├── Http/
└── Support/
```

The exact namespace and directory structure should evolve from actual requirements rather than being created entirely in advance.

The MVP should avoid creating large numbers of empty abstractions.

### 4.2 `vendor/`

Contains Composer-managed third-party dependencies.

This directory is never intended to be directly accessible through the web.

The distributed commercial package should normally contain the production `vendor/` directory so customers do not need Composer or shell access merely to install the application.

### 4.3 `config/`

Contains application configuration.

Environment-specific secrets should not be committed to Git or distributed publicly.

Examples include:

- database credentials
- encryption secrets
- application secrets
- environment-specific configuration

Configuration files that contain secrets must remain outside the public HTTP boundary and must be protected from direct web access in web-root deployments.

### 4.4 `database/`

Contains database-related application files.

Potential contents include:

```text
database/
├── migrations/
└── seeds/
```

The exact migration system will be determined during implementation.

Database files must not be directly accessible through HTTP.

### 4.5 `storage/`

Contains writable runtime data.

Potential contents include:

```text
storage/
├── logs/
├── cache/
└── temporary/
```

The application should not require the public directory to be writable for normal operation.

Runtime storage must not be directly accessible through HTTP.

---

## 5. Front Controller

The application uses a front controller as the HTTP entry point.

In the preferred deployment, the front controller is located at:

```text
public/index.php
```

When the contents of `public/` are deployed directly into a customer-facing directory, the entry point may instead appear at a path such as:

```text
public_html/reward-gate/index.php
```

The physical location may therefore differ between deployment models, but the front controller always represents the public entry point of the application.

The front controller is responsible for:

- determining the application root
- loading configuration
- loading the Composer autoloader
- initializing the application
- dispatching the incoming request

Conceptually:

```text
HTTP Request
     │
     ▼
Front Controller
     │
     ├── Determine application root
     ├── Load configuration
     ├── Load Composer autoloader
     ├── Initialize application
     └── Dispatch request
              │
              ▼
        Application logic
```

The front controller should remain thin.

Business logic should not gradually accumulate inside `index.php`.

The application should resolve the location of the private application using predictable filesystem paths or installation configuration rather than requiring customers to manually edit `index.php` after installation.

---

## 6. Separation of Presentation and Business Logic

Reward Gate's architecture must distinguish between the presentation of a gate and the underlying unlock logic.

For example:

```text
Popup Gate
    │
    └──┐
       │
Content / Read-more Gate
    │
    └──┐
       ▼
   Unlock Engine
       │
       ├── Unlock Session
       ├── Timer
       ├── Verification
       ├── Completion
       ├── Anti-abuse
       └── Reward
```

The Popup Gate and Content / Read-more Gate should not contain separate implementations of:

- session creation
- timer validation
- completion verification
- replay protection
- reward granting
- anti-abuse rules

Presentation-specific code should control how the gate is displayed.

The underlying engine should control whether the visitor has legitimately completed the required action.

---

## 7. Core Domain Concepts

The initial architecture is based around several independent concepts.

### 7.1 Campaign

Defines what a site owner wants to achieve.

A campaign may eventually contain:

- presentation configuration
- unlock method
- reward configuration
- visitor limits
- appearance settings
- targeting rules

### 7.2 Presentation

Defines how the gate is displayed.

MVP presentations:

- Popup
- Content / Read-more

Future presentations may include:

- Full Page
- Inline
- Download Gate
- Button Gate
- Embedded Widget
- Feature Gate

### 7.3 Unlock Method

Defines the action a visitor must perform.

MVP:

- Timer

Future possibilities:

- Link Visit
- Rewarded Video
- Offerwall
- Survey
- External Task

### 7.4 Unlock Session

Represents an individual visitor attempt to complete an unlock action.

The session should contain enough server-side state to determine whether completion is legitimate.

### 7.5 Completion

Represents a successfully verified unlock attempt.

A completion should be treated as a state transition rather than simply another client-side event.

### 7.6 Reward

Defines what the visitor receives after successful completion.

MVP:

- Protected content unlock

Future possibilities include:

- Downloads
- Coupons
- Credits
- Premium features
- External links

---

## 8. Unlock Flow

The common unlock flow is:

```text
Visitor
   │
   ▼
Presentation
   │
   ▼
Start Unlock Session
   │
   ▼
Perform Unlock Action
   │
   ▼
Client Reports Completion
   │
   ▼
Server Verification
   │
   ├── Invalid ──► Reject
   │
   └── Valid
        │
        ▼
   Create Completion
        │
        ▼
   Grant Reward
        │
        ▼
   Unlock / Reveal
```

The client is responsible for presenting the interaction.

The server is authoritative regarding completion.

---

## 9. Server Authority

The browser must never be treated as authoritative for security-sensitive state.

Client-side JavaScript may:

- display the countdown
- update progress indicators
- respond to user interaction
- report events to the server
- update the interface after successful verification

The server must determine:

- whether the unlock session exists
- whether the session is valid
- whether enough time has elapsed
- whether the security token is valid
- whether completion has already occurred
- whether replay is being attempted
- whether applicable visitor limits have been exceeded
- whether the reward may be granted

Core principle:

> The client can report that an event happened. The server decides whether the event is valid.

---

## 10. Database Architecture

The database should remain relatively small during the MVP.

Core concepts include:

- campaigns
- presentations
- unlock methods
- rewards
- unlock sessions
- unlock completions

The physical schema should be normalized where useful without turning the MVP into a generic enterprise metadata system.

The database should support adding new presentation types, unlock methods, and reward types without requiring a fundamental redesign.

At the same time, hypothetical future functionality should not result in dozens of unused tables.

---

## 11. Extensibility Strategy

Extensibility should happen at meaningful boundaries.

The architecture should make it possible to add:

```text
Presentation
    ├── Popup
    ├── Content Gate
    └── Future Presentation

Unlock Method
    ├── Timer
    ├── Future Method
    └── Future Method

Reward
    ├── Content
    ├── Future Reward
    └── Future Reward
```

Adding a new implementation should ideally involve adding the new behavior rather than rewriting the existing unlock/session/completion system.

However, abstractions should only be introduced when the second real implementation demonstrates that the abstraction is useful.

The architecture should not create interfaces and factories merely because a third implementation might exist someday.

---

## 12. Composer and Dependencies

Reward Gate uses Composer for PHP dependency management.

During development:

```text
composer.json
composer.lock
vendor/
```

The `vendor/` directory is generated from the locked dependency set.

`vendor/` is excluded from Git because it is generated dependency output.

For commercial distribution, the release package should normally include the production `vendor/` directory.

Customers should not be required to execute:

```text
composer install
```

as part of the normal installation process.

This is particularly important for shared hosting environments where customers may have only FTP or file-manager access.

The production `vendor/` directory must be treated as private application content and must not be directly accessible through HTTP.

In the preferred deployment, it is located outside the web root:

```text
/home/customer/
├── reward-gate-app/
│   ├── src/
│   ├── vendor/
│   ├── config/
│   ├── database/
│   └── storage/
│
└── public_html/
    └── reward-gate/
        ├── index.php
        └── assets/
```

In a web-root deployment, it may physically exist below `public_html/`:

```text
public_html/
└── reward-gate/
    ├── src/
    ├── vendor/
    ├── config/
    ├── database/
    ├── storage/
    └── public/
```

In the latter case, the web server must prevent direct HTTP access to `vendor/` and the other private application directories.

The development repository and commercial distribution package therefore have different responsibilities:

```text
Git repository
    └── Source + dependency definitions

Commercial release
    └── Source + production vendor/ + installation files
```

The commercial package should contain all dependencies required for normal operation so that a customer can install and run Reward Gate without requiring Composer or shell access.

---

## 13. Supported Hosting Environments

The application should primarily target ordinary PHP hosting environments.

Important deployment scenarios include:

### 13.1 Custom Document Root

The hosting environment allows the customer to configure the site's document root.

Preferred structure:

```text
/home/customer/
└── reward-gate-app/
    ├── src/
    ├── vendor/
    ├── config/
    ├── database/
    ├── storage/
    └── public/
```

The web server document root points directly to:

```text
/home/customer/reward-gate-app/public/
```

This is the cleanest deployment model because private application files are physically outside the web root.

### 13.2 Fixed `public_html`

Some shared hosting environments require the document root to remain:

```text
/home/customer/public_html/
```

In this case:

```text
/home/customer/
├── reward-gate-app/
│   ├── src/
│   ├── vendor/
│   ├── config/
│   ├── database/
│   └── storage/
│
└── public_html/
    └── reward-gate/
        ├── index.php
        └── assets/
```

Only the contents of `public/` are placed into:

```text
public_html/reward-gate/
```

The private application remains outside the web root.

This deployment model is particularly relevant to Apache/cPanel-style hosting.

### 13.3 Complete Application Below `public_html`

Some customers may install the complete application directly below `public_html/`.

Example:

```text
/home/customer/
└── public_html/
    └── reward-gate/
        ├── src/
        ├── vendor/
        ├── config/
        ├── database/
        ├── storage/
        └── public/
            ├── index.php
            └── assets/
```

This deployment is supported for compatibility purposes.

The `public/` directory remains the intended HTTP-facing directory.

Private directories must not be directly accessible through HTTP, including:

- `src/`
- `vendor/`
- `config/`
- `database/`
- `storage/`

Appropriate web-server access restrictions must be applied to enforce this boundary.

This deployment model is less desirable than keeping the private application outside the web root because the private files physically reside within the web root.

However, the application must remain functional in this configuration because customers may have hosting environments that make the preferred deployment impractical.

### 13.4 Deployment Recommendation

The installation documentation should recommend deployment models in the following order:

1. Custom document root pointing to `public/`.
2. Fixed `public_html` with the private application outside the web root.
3. Complete application below `public_html` with explicit access restrictions.

The application should not require customers to manually edit application source code merely because one of these deployment models is being used.

Where possible, the application root should be determined through predictable filesystem paths or installation configuration.

---

## 14. Apache and Nginx

The application should avoid depending on server-specific behavior wherever practical.

Apache and Nginx can both support the preferred architecture when the document root can be configured appropriately.

### 14.1 Apache

Apache shared hosting may provide `.htaccess` support.

When the complete application is installed below the web root, `.htaccess` rules may be used to prevent direct HTTP access to private application directories and sensitive files.

Private directories that must be protected include:

- `src/`
- `vendor/`
- `config/`
- `database/`
- `storage/`

`.htaccess` protection is defense-in-depth and a compatibility mechanism. It should not be treated as the primary security boundary when the private application can instead be placed outside the web root.

The preferred Apache deployment is therefore to configure the document root to point to `public/` whenever the hosting environment permits it.

### 14.2 Nginx

Nginx does not use `.htaccess`.

The preferred Nginx deployment is to configure the server's document root directly to the application's `public/` directory.

When the complete application is located below the web root, equivalent server-level access restrictions must be configured to prevent HTTP access to private directories and sensitive files.

On ordinary shared hosting, customers may not have permission to modify the Nginx server configuration.

In such environments, the hosting provider may need to configure the document root or access restrictions.

### 14.3 Server Independence

The application should not assume that Apache-specific mechanisms are available.

The installation and documentation should clearly distinguish between:

- Apache deployments
- Nginx deployments
- custom document-root hosting
- fixed `public_html` hosting

The application itself should keep server-specific configuration outside the core business logic wherever possible.

`.htaccess` files may be included as an additional Apache protection mechanism, but the application must not rely on them as the only protection for sensitive data when a safer deployment arrangement is available.

---

## 15. Public Assets

Public assets should be kept under:

```text
public/assets/
```

Potential structure:

```text
public/assets/
├── css/
├── js/
└── images/
```

Only assets required by the browser should be placed here.

In the preferred deployment, these files are served directly from the application's `public/` directory.

In a fixed `public_html` deployment, the contents of `public/assets/` are placed in the corresponding publicly accessible directory, for example:

```text
public_html/reward-gate/assets/
```

In a complete web-root deployment, the assets remain under:

```text
public_html/reward-gate/public/assets/
```

The installation and web-server configuration must ensure that only the intended public assets are accessible through HTTP.

Private application resources should never be exposed simply because they are convenient to reference from a public URL.

---

## 16. Runtime Storage

Runtime-generated files belong under:

```text
storage/
```

Potential examples:

- logs
- cache
- temporary files
- generated files

The storage directory should not be publicly accessible through HTTP.

In the preferred deployment, `storage/` is physically located outside the web root:

```text
reward-gate-app/
└── storage/
```

In a complete web-root deployment, `storage/` may physically exist below `public_html/`, but the web server must prevent direct HTTP access to it.

The application should define which directories require write access.

Write permissions should be kept as narrow as practical.

The application should not require the `public/` directory or its assets to be writable for normal operation.

---

## 17. Configuration and Secrets

Secrets must never be directly accessible through HTTP.

Examples include:

- database passwords
- API keys
- encryption secrets
- mail credentials
- external service credentials

In the preferred deployment, configuration and secrets are physically located outside the web root:

```text
/home/customer/
└── reward-gate-app/
    └── config/
```

Development environments may use local environment files such as:

```text
.env
```

These files are ignored by Git.

A safe example configuration may be distributed as:

```text
.env.example
```

without real credentials.

When the complete application is installed below `public_html`, configuration files may physically exist within the web-root hierarchy for compatibility reasons. In that deployment, the web server must explicitly prevent direct HTTP access to configuration files and directories.

The application should never assume that hiding a configuration file's URL is sufficient protection.

Where the hosting environment permits it, sensitive configuration should remain physically outside the web root even when the rest of the application is installed below `public_html`.

The application must also ensure that:

- real credentials are never committed to Git
- real credentials are never included in the commercial distribution package
- configuration files containing secrets are not exposed as downloadable files
- installation diagnostics do not display secrets
- error messages do not disclose credentials or other sensitive configuration values

---

## 18. Git Repository

Git is the source-control system for Reward Gate development.

The repository should contain:

- application source code
- configuration templates
- Composer definitions
- database migrations
- tests
- project documentation
- deployment-related source files
- development configuration that does not contain secrets

The repository should not contain:

- secrets
- `.env`
- `vendor/`
- runtime logs
- caches
- private server credentials
- temporary private development files
- customer-specific deployment configuration

The `vendor/` directory is generated from `composer.lock` and is therefore excluded from Git.

The private development directory:

```text
private/
```

is also excluded from Git and may contain local information that should never become part of the project repository.

The repository may be hosted as a private GitHub repository during development.

The private repository can be shared with project collaborators, including the product partner, by granting them appropriate repository access.

The Git repository represents the development source of the product. It is not required to have exactly the same physical structure as the final customer installation.

In particular:

```text
Git repository
    ├── Source code
    ├── Composer definitions
    ├── Documentation
    └── Development files

Commercial release
    ├── Source code
    ├── Production vendor/
    ├── Installation files
    └── Customer documentation
```

The commercial release process is responsible for producing a clean, distributable package from the development repository.

---

## 19. Development vs Commercial Release

The Git repository is the development source of Reward Gate.

The commercial release package is a prepared distribution intended for customers who may have limited technical access to their hosting environment.

The two are therefore not necessarily identical.

### 19.1 Development Repository

The development repository contains the source and project files required to develop, test, and maintain Reward Gate.

Typical structure:

```text
reward-gate/
├── src/
├── public/
├── config/
├── database/
├── tests/
├── docs/
├── composer.json
├── composer.lock
└── phpstan.neon
```

The repository does not contain the generated production `vendor/` directory.

### 19.2 Commercial Release Package

The commercial release should normally contain all files required for a customer to install and run the application without Composer or shell access.

Typical structure:

```text
reward-gate/
├── src/
├── public/
├── config/
├── database/
├── storage/
├── vendor/
├── install/
└── documentation/
```

The exact release contents may evolve as installation and distribution requirements become clearer.

### 19.3 Deployment Compatibility

The commercial package should support the deployment models described in Section 13.

A customer may use:

1. A custom document root pointing to `public/`.
2. A fixed `public_html` installation with the private application outside the web root.
3. A complete application installation below `public_html`, with appropriate server restrictions protecting private directories.

The release package should contain the same application regardless of deployment model.

The difference is primarily where the customer places the files and how the web server exposes the `public/` directory.

### 19.4 Generated Files

Generated production dependencies and other release-specific files may be added during the packaging process.

For example:

```text
composer install --no-dev --optimize-autoloader
```

may be used to prepare the production `vendor/` directory before creating the customer package.

The exact release-building procedure will be documented separately when the commercial packaging process is implemented.

### 19.5 Customer Installation

Customers should not be expected to understand:

- Composer
- Git
- PHP dependency management
- application source structure
- development tooling

The commercial package should therefore be self-contained and suitable for installation using ordinary hosting tools such as:

- FTP
- SFTP
- hosting control panels
- file managers

An installation wizard may later automate environment checks, database setup, configuration, and administrator creation.

---

## 20. Installation Philosophy

The commercial installation should be designed for users who may have:

- FTP access
- SFTP access
- a hosting control panel
- a file manager
- a MySQL/MariaDB database
- no SSH access
- no Composer installation
- limited technical knowledge

Therefore the product should not make developer tooling a prerequisite for normal installation.

The customer should receive a self-contained release package containing the production dependencies required to run Reward Gate.

The installation process should support the deployment models described in Section 13:

1. Custom document root pointing to `public/`.
2. Fixed `public_html` with the private application outside the web root.
3. Complete application installation below `public_html`, with appropriate protection of private directories.

The application should determine its application root reliably rather than requiring customers to manually modify application source code for ordinary installation layouts.

Where configuration is required, it should be handled through installation configuration rather than by asking customers to edit internal PHP source files.

The eventual installation process should aim to be:

```text
Upload
   │
   ▼
Open installation URL
   │
   ▼
Environment checks
   │
   ▼
Database configuration
   │
   ▼
Database initialization
   │
   ▼
Administrator setup
   │
   ▼
Complete
```

An installation wizard is a future commercial-release feature, not an MVP requirement.

Until the installer exists, installation documentation should provide clear instructions for each supported deployment model.

---

## 21. Security Boundary

The architecture follows this rule:

> Only files intended to be publicly accessible should be reachable through HTTP.

The `public/` directory is the logical public boundary of the application.

Preferred arrangement:

```text
                 INTERNET
                    │
                    ▼
             ┌─────────────┐
             │   public/   │
             │             │
             │ index.php   │
             │ assets/     │
             └──────┬──────┘
                    │
                    ▼
             ┌─────────────┐
             │ Application │
             │    src/     │
             └──────┬──────┘
                    │
          ┌─────────┼─────────┐
          ▼         ▼         ▼
       config/   database/  storage/
          │
          ▼
       Secrets
```

In the preferred deployment, the physical security boundary and the logical application boundary are the same because everything except `public/` is located outside the web root.

In a fixed `public_html` deployment, the public files are placed directly into the publicly accessible directory while the private application remains outside the web root.

In a complete application deployment below `public_html`, the private directories may physically exist within the web-root hierarchy. In that case, the web server must explicitly prevent HTTP access to those directories and files.

Private application resources that must not be directly accessible include:

- `src/`
- `vendor/`
- `config/`
- `database/`
- `storage/`
- private configuration files
- environment files
- logs
- temporary files

The security boundary must therefore be enforced by the deployment arrangement and, where necessary, by web-server access rules.

`.htaccess` or equivalent server configuration is a defense mechanism, not a substitute for keeping sensitive files outside the web root when that is possible.

The application must never assume that a file is safe merely because users are not supposed to know its URL.

---

## 22. Architecture Evolution

The architecture is expected to evolve as the product gains real implementations and customers.

The MVP should first prove:

1. The unlock workflow works.
2. Server-side verification works.
3. Popup and Content / Read-more presentations can share the same engine.
4. The data model can support the required state transitions.
5. The deployment model works on ordinary PHP hosting.
6. The commercial package can be installed without Composer or shell access.
7. The application can operate under different supported document-root configurations without requiring source-code modifications.

Only after these requirements are demonstrated should larger abstractions be introduced.

Future architecture may eventually extract a reusable Reward Gate Core for:

- standalone PHP
- WordPress
- Laravel
- generic PHP/JavaScript integrations

The extraction should be based on real duplicated requirements rather than theoretical reuse.

Deployment compatibility should also evolve from real customer environments rather than assuming that every customer has control over their web-server configuration.

The architecture should preserve a clean distinction between:

```text
Application
    │
    ├── Public interface
    ├── Private application code
    ├── Dependencies
    ├── Configuration
    ├── Database
    └── Runtime storage
```

and the physical location where those components happen to be installed.

The physical installation layout may vary between hosting environments, but the logical security and application boundaries should remain consistent.

---

## 23. Architectural Principles

The project follows these principles:

1. **Public code is separate from private application code.**
2. **The server is authoritative for unlock verification.**
3. **Presentation is separate from business logic.**
4. **The MVP database remains small and understandable.**
5. **Composer manages PHP dependencies.**
6. **Production dependencies are packaged for customers.**
7. **Secrets never belong in the public web root or Git repository.**
8. **Shared hosting must be considered a first-class deployment environment.**
9. **The application should support different document-root configurations without requiring customers to modify application source code.**
10. **The logical public boundary remains consistent even when the physical installation layout differs.**
11. **Security boundaries should not depend solely on `.htaccess`.**
12. **The development repository and commercial release package may differ.**
13. **The commercial package should work without requiring Composer or shell access.**
14. **Abstractions are introduced when justified by real requirements.**
15. **Server-specific behavior should remain outside the core application logic wherever practical.**
16. **Writable directories should be limited to locations that actually require write access.**
17. **Architecture should serve the product rather than become the product.**

The preferred deployment should always be the simplest and safest option available in the customer's hosting environment.

Compatibility with less ideal hosting configurations is important, but compatibility should not weaken the security requirements of the application.

---

## 24. Initial Target Structure

The development repository is expected to begin with the following structure:

```text
reward-gate/
├── .gitignore
├── .php-cs-fixer.php
├── README.md
├── composer.json
├── composer.lock
├── phpstan.neon
│
├── docs/
│   ├── specification.md
│   ├── architecture.md
│   ├── database.md
│   └── todo.md
│
├── src/
│
├── public/
│   ├── index.php
│   └── assets/
│
├── database/
│   └── migrations/
│
├── tests/
│
└── private/
```

`private/` is a local development area and is ignored by Git.

It may contain:

- temporary notes
- server credentials
- deployment information
- private testing material
- other information that should not enter the repository

The `private/` directory is not part of the commercial application.

### 24.1 Repository Structure vs Installation Structure

The development repository is not required to match the physical structure used by customers.

The repository represents the complete source project:

```text
reward-gate/
├── src/
├── public/
├── config/
├── database/
├── tests/
├── docs/
└── vendor/              # generated locally, not committed
```

A customer installation may instead use one of the supported deployment arrangements.

#### Preferred: Custom Document Root

```text
/home/customer/
└── reward-gate-app/
    ├── src/
    ├── vendor/
    ├── config/
    ├── database/
    ├── storage/
    └── public/
```

The web server document root points to:

```text
/home/customer/reward-gate-app/public/
```

#### Fixed `public_html`

```text
/home/customer/
├── reward-gate-app/
│   ├── src/
│   ├── vendor/
│   ├── config/
│   ├── database/
│   └── storage/
│
└── public_html/
    └── reward-gate/
        ├── index.php
        └── assets/
```

Only the contents of `public/` are placed into the public directory.

#### Complete Application Below `public_html`

Some customers may choose or be required to install the entire application below the web root:

```text
/home/customer/
└── public_html/
    └── reward-gate/
        ├── src/
        ├── vendor/
        ├── config/
        ├── database/
        ├── storage/
        └── public/
            ├── index.php
            └── assets/
```

This deployment is supported only when the web server is configured so that private directories cannot be accessed directly through HTTP.

The application should determine its paths from its installation location and should not require customers to edit internal PHP source files merely because they selected a different supported deployment model.

As implementation progresses, additional directories may be introduced when they have a concrete purpose.

---

## 25. Final Architecture Goal

The initial architecture should remain simple enough that a developer can understand the entire application without navigating a maze of framework conventions.

At the same time, it must establish the boundaries required for the product to grow.

The application should maintain a clear separation between:

```text
Public Interface
       │
       ▼
Application
       │
       ├── Unlock Engine
       ├── Campaigns
       ├── Presentations
       ├── Unlock Methods
       └── Rewards
       │
       ├── Database
       ├── Configuration
       └── Runtime Storage
```

The physical deployment of these components may differ between hosting environments.

The preferred deployment keeps all private components outside the web root. Less capable hosting environments may require the complete application to be installed below `public_html`, in which case private directories must be protected from direct HTTP access.

The application should therefore separate:

- logical application boundaries
- security boundaries
- physical filesystem layout

without coupling the business logic to a specific hosting configuration.

The intended progression is:

```text
Simple PHP Application
        │
        ▼
Shared Unlock Engine
        │
        ▼
Multiple Presentations / Methods / Rewards
        │
        ▼
Reusable Reward Gate Core
        │
        ├── Standalone PHP
        ├── WordPress
        ├── Laravel
        └── Generic PHP / JavaScript
        │
        ▼
Reward Optimization Platform
```

The architecture exists to support that progression without forcing the complexity of the final system into the MVP.

The application should be:

- simple to understand
- secure by default
- compatible with ordinary PHP hosting
- installable without developer tooling
- independent of a specific document-root layout
- extensible where real requirements justify it
- suitable for packaging as self-hosted commercial software

**The rule is simple: build today's product cleanly, while keeping tomorrow's product possible.**

