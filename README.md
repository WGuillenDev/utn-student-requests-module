# UTN Student Requests Module

Portal, rules engine, and centralized inbox for student requests at UTN, San Carlos Campus.

## Overview

Today, when a student needs a course requirement waived, or a course passed at another institution recognized as equivalent, the process runs on email: messages that get lost, go unanswered for weeks, or get resolved inconsistently depending on who reads them.

This project replaces that informal process with a formal record: a portal where students submit requests, a rules engine that automatically resolves the cases with clear, objective criteria, and a centralized inbox where the Teaching Coordination office (Docencia) handles the cases that genuinely require human judgment.

The rules engine is the core design challenge: approval criteria are not hard-coded per course. Instead, the Coordinator configures rules per course from the interface, and the engine evaluates them in the configured order — adding a new rule should never require a code change.

## Functional Scope

| ID | Requirement | Summary |
|----|-------------|---------|
| ES-01 | Requirement waiver requests | Student submits a waiver request with supporting documentation; a per-course, coordinator-configured rule set (minimum grade, accumulated credits, terminal plan membership, or manual review) is evaluated in order and returns an immediate result, deduplicating already-recognized waivers. |
| ES-02 | External course validation | Student requests recognition of a course passed at another institution, with supporting documents; the system checks a catalog of historical precedents before routing the case to the Technical Validation Committee. |
| ES-03 | Request status tracking | Students track their request status (Pending, In Review, Approved, Denied); an estimated resolution date is set by the reviewer or auto-assigned after 24 hours, with email notifications on every status change. |
| ES-04 | Centralized inbox for Docencia | A single inbox aggregates every request (waivers and validations), filterable and sortable by type, program, status, and received date. |

Adapted from FR-ES-01 through FR-ES-04 of SRS v1.2 (Integrated Academic and Faculty Management System, UTN San Carlos Campus).

## Architecture

Hexagonal architecture (Ports & Adapters) with Domain-Driven Design: business rules live in `app/Domain`, framework-free, with `app/Application` orchestrating use cases and `app/Infrastructure` holding every framework-specific adapter (Eloquent, HTTP, Livewire). See [ARCHITECTURE.md](ARCHITECTURE.md) for the full layering convention and how to add a new module.

## Tech Stack

- **Backend:** Laravel 13, Livewire 4, Laravel Fortify (authentication, 2FA, passkeys)
- **Frontend:** TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire) + TypeScript
- **Database:** MySQL 8.x
- **Auth (API):** JWT for external-facing endpoints
- **Testing:** PHPUnit

## Setup

```bash
composer install
cp .env.example .env
php artisan key:generate
```

Create a MySQL 8.x database and set the connection in `.env` (`DB_CONNECTION=mysql`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`), then:

```bash
php artisan migrate
npm install
composer dev
```

## Academic Context

Developed for **Programación en Ambiente Web I (ISW-521)**, Universidad Técnica Nacional (UTN), San Carlos Campus — 2026, second four-month term.

## Project Status

🚧 **In progress.** Done so far: the hexagonal architecture scaffold, the authentication baseline (Fortify, 2FA, passkeys), all database migrations (auth, RBAC, academic catalog, and the ES-01–ES-04 core tables), seeders, Eloquent models, and model factories. Still pending: Domain entities/value objects, Application use cases, `DomainServiceProvider` bindings, TypeScript, JWT-secured endpoints, the external REST API integration, and automated tests.

## Author

**WGuillenDev**
**maykel0**
