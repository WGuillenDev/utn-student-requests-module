# UTN Student Requests Module

Portal, rules engine, and centralized inbox for student requests at Universidad Técnica Nacional (UTN), San Carlos Campus.

Adapted from FR-ES-01 through FR-ES-04 of SRS v1.2 — *Sistema Integrado de Gestión Académica y Docente*.

---

## Overview

When a student needs a course requirement waived, or a course passed at another institution recognized as equivalent, the process traditionally runs on email: messages that get lost, go unanswered for weeks, or get resolved inconsistently depending on who reads them.

This module replaces that informal process with a formal record:

- a **portal** where students submit and track their own requests,
- a **rules engine** that resolves the cases with clear, objective criteria,
- a **centralized inbox** where Teaching Coordination (*Docencia*) and the Registrar's Office (*Registro*) handle the cases that require human judgment.

The rules engine is the core design constraint: approval criteria are never hard-coded per course. The Coordinator configures rules from the interface, and the engine evaluates them in the configured order — adding a rule requires no code change.

---

## Functional Scope

| ID | Requirement | Summary |
|----|-------------|---------|
| **ES-01** | Requirement waiver requests | Students submit a waiver request with supporting documentation. A per-course, coordinator-configured rule set (minimum grade, accumulated credits, terminal-plan membership, or always-manual-review) is evaluated in order and returns an immediate result. Already-recognized waivers are rejected as duplicates. |
| **ES-02** | External course validation | Students request recognition of a course passed at another institution, attaching supporting documents. The system checks a catalog of historical precedents and flags the case when an approved precedent exists. |
| **ES-03** | Request status tracking | Students follow their request through a five-stage progress tracker. The estimated resolution date is entered by the reviewer or derived automatically as *received + 24 h*. An email confirms every submission. |
| **ES-04** | Centralized staff inbox | A single inbox aggregates every request, sortable and searchable, split into per-type worklists plus an archive of resolved cases. |

---

## Review Workflow

A request travels through two independent review stages before it closes:

```
Student submits
      │
      ▼
Received by Docencia ──► Docencia decides ──► Sent to / Received by Registro
                          (approve · deny)                  │
                                                            ▼
                                              Registro issues the resolution
                                                (approve · deny — its own
                                                 decision, not Docencia's)
                                                            │
                                                            ▼
                                                 Published to the student
```

- **Docencia** resolves the substance of the request. For validations this happens through *Recognize / Do not recognize*, which requires the external course's code, credits and grade, plus a written reason when declining.
- **Registro** issues the formal resolution (number, session, act, date — all mandatory) and publishes it. Only then is the request closed and the academic credit registered.
- Students see a simplified status — *Pending review*, *Approved*, *Denied* — because the two internal stages are not theirs to reason about. The progress tracker communicates the stage narratively instead.

Both stages generate the corresponding official document: the real institutional **SLR-002** AcroForm is filled for waivers, and an **RSREC-001**-styled PDF is rendered for validations. The document is archived on the request and available to the student from their own detail view.

---

## Architecture

Hexagonal architecture (Ports & Adapters) with Domain-Driven Design. Business rules live in framework-free PHP:

```
src/<BoundedContext>/<Aggregate>/
├── Domain/          Entities, value objects, domain services, contracts (ports)
├── Application/     Use cases, DTOs — orchestration only
├── Infrastructure/  Eloquent repositories, external services (adapters)
└── Presentation/    Livewire components, form objects, policies
```

The domain layer imports no Laravel, Livewire or Alpine class. Removing the framework must leave the domain compiling — that is the test the layering is held to.

Bounded contexts: `Requests` (Request, WaiverRule, ValidationPrecedent), `IdentityAccess` (Role, Permission), `Shared` (export ports).

See [`ARCHITECTURE.md`](ARCHITECTURE.md) for the full convention and how to add a module.

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8.3, Laravel 13 |
| Frontend | TALL stack — Tailwind CSS 4, Alpine.js, Laravel, Livewire 4 |
| Auth | Laravel Fortify (sessions, 2FA, passkeys) |
| Database | MySQL 8.x |
| Documents | spatie/laravel-pdf + Browsershot (PDF), spatie/simple-excel (XLSX), pypdf (AcroForm filling) |
| Queue | Database driver, for outbound email |
| Testing | PHPUnit 12 |
| Static analysis | Larastan, Laravel Pint |

---

## Requirements

- PHP 8.3+
- Composer 2
- Node.js 20+ and npm
- MySQL 8.x
- Python 3 with `pypdf` — used to fill the official SLR-002 form
- Chromium, installed automatically by Puppeteer, for PDF rendering

---

## Setup

```bash
git clone <repository-url>
cd utn-student-requests-module

composer install
npm install
pip install pypdf

cp .env.example .env
php artisan key:generate
```

Create a MySQL database and set the connection in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_DATABASE=gestion_academica_utn
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Configure outbound mail and the queue driver:

```dotenv
QUEUE_CONNECTION=database

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_address
MAIL_PASSWORD=your_app_password
```

Then build the schema and start the application:

```bash
php artisan migrate --seed
composer dev
```

`composer dev` runs the PHP server, a queue worker and the Vite dev server together — use it rather than `php artisan serve` alone.

**Email is queued, so it is only delivered while a worker is running.** If you start the server on its own, start a worker too:

```bash
php artisan queue:work
```

Without one, notifications accumulate in the `jobs` table and are never sent. In production this belongs under a process supervisor.

---

## Seeded Accounts

`php artisan migrate --seed` creates one account per role. All use the password `12345678`.

| Role | Email | Sees |
|------|-------|------|
| Superadmin | `prueba@gmail.com` | Everything; bypasses all authorization checks |
| Admin | `admin@gmail.com` | Everything, through explicit permissions |
| Docencia | `docencia@gmail.com` | Pending requests, waiver rules, validation precedents |
| Registro | `registro@gmail.com` | Requests already resolved by Docencia, pending publication |
| Student | `estudiante@gmail.com` | Their own requests only |

The seeder also creates fixtures covering all three ES-01 engine outcomes: one course auto-approves, one auto-denies, and one has no rules so it falls through to manual review.

---

## Testing

```bash
php artisan test
./vendor/bin/pint --test      # code style
./vendor/bin/phpstan analyse  # static analysis
```

---

## Accessibility

The interface is operable by keyboard end to end: every control is a real focusable element, focus is visible under `:focus-visible`, a skip link jumps past the navigation, and modals move focus in, trap it, close on `Escape` and return focus to whatever opened them. Interactive controls carry the corresponding ARIA state.

---

## Scope and Integration

This is a **minimum viable product**. It runs standalone: every piece of academic data it reads — student records, course catalog, study plans, grades — lives in its own database and is populated by seeders, following the *simulated academic record* the SRS defines for this module. There is no live connection to the university's academic systems yet.

That boundary is deliberate rather than incidental, and the architecture is built for it. Everything the module needs from the outside world is expressed as a port in the domain layer, with a local adapter behind it:

| Port | Current adapter | Reads / writes |
|------|-----------------|----------------|
| `StudentAcademicProfileRepositoryInterface` | `EloquentStudentAcademicProfileRepository` | The academic record the rules engine evaluates |
| `AcademicRecordRegistrarInterface` | `EloquentAcademicRecordRegistrar` | The credit registered when a request is approved |
| `RequestNotifierInterface` | `EloquentRequestNotifier` | Outbound notification to the student |
| `ResolutionDocumentGeneratorInterface` | `BrowsershotResolutionDocumentGenerator` | The official resolution document |

Connecting the module to a real institutional system is a matter of writing a new adapter for the relevant port and rebinding it in `DomainServiceProvider` — an HTTP client against the university API instead of an Eloquent query. No domain rule, use case or Livewire component changes, because none of them know where the data comes from.

---

## Project Status

All four requirements — **ES-01 through ES-04** — are implemented and verified end to end in the browser against a real database, as a standalone MVP (see [Scope and Integration](#scope-and-integration) above).

Implemented: the rules engine and its per-course configuration, waiver deduplication, the validation precedent catalog, the two-stage Docencia → Registro review, official document generation (SLR-002 and RSREC-001), submission email, academic credit registration on approval, the staff inbox with search and PDF/Excel export, the student portal with progress tracking, RBAC across five roles, and keyboard accessibility.

---

## Academic Context

Developed for **Programación en Ambiente Web I (ISW-521)**, Universidad Técnica Nacional, San Carlos Campus — 2026, second four-month term.

The technical and AI decision diary required by the course is kept at [`Diario_de_Decisiones_Tecnicas_e_IA.docx`](Diario_de_Decisiones_Tecnicas_e_IA.docx).

## Authors

- [WGuillenDev](https://github.com/WGuillenDev)
- [maykel0](https://github.com/maykel0)
