# HorariosWFM — Repo Guide for OpenCode Agents

## Stack

PHP 8.3+ | Laravel 13 | Livewire 4 | FluxUI 2 | PostgreSQL 16 | Pest/PHPUnit | Vite + TailwindCSS 4

# AGENTS.md

# HorariosWFM — Engineering Guide for AI Coding Agents

> **This document is the architectural constitution of the project.**
>
> Every implementation, refactor, optimization or feature must comply with the rules defined here.
>
> When this document conflicts with framework conventions, **this document takes precedence**.

---

# Mission

HorariosWFM is a long-lived enterprise application built as a **Domain-Driven Modular Monolith**.

The objective is **not** to maximize Laravel usage.

The objective is to build software that:

* models the business correctly
* evolves safely
* minimizes coupling
* remains understandable after years of development
* can eventually extract bounded contexts into independent services if needed

Laravel is an implementation detail.

The Domain is the product.

---

# Core Engineering Principles

## The Domain owns the business

Business rules belong exclusively to the Domain.

Business rules must never be implemented in:

* Controllers
* Livewire Components
* Requests
* Jobs
* Commands
* Eloquent Models
* Policies
* Observers
* Services without clear domain responsibility

---

## Simplicity over cleverness

Prefer:

* explicit code
* small objects
* readable names
* composition

Avoid:

* magic
* hidden behavior
* unnecessary abstractions
* speculative flexibility

---

## Delete before abstracting

Before introducing:

* interfaces
* traits
* services
* factories
* helpers
* inheritance

Ask:

> Is duplication actually causing maintenance problems?

If not:

Do not abstract.

---

## The Monolith is the Architecture

The project intentionally remains a Modular Monolith.

Do not introduce:

* microservices
* distributed messaging
* service discovery
* network boundaries

unless there is a demonstrated business need.

---

# Architectural Style

The project follows:

* Domain Driven Design (DDD)
* Modular Monolith
* Tactical DDD where business complexity exists
* CQRS (logical separation only)
* Clean Architecture dependency rules
* Rich Domain Model

It does **not** follow textbook Clean Architecture blindly.

Laravel conventions should be preserved whenever they do not violate domain integrity.

---

# Bounded Contexts

Every module is an independent bounded context.

Each bounded context owns:

* language
* business rules
* persistence
* use cases
* events

Current bounded contexts include:

## Core

Authentication

Authorization

Roles

Permissions

Settings

System configuration

---

## Personnel

Employees

Departments

Directorates

Teams

Positions

Employment

---

## WFM

Scheduling

Planning

Absences

Intraday

Activities

Weekly planning

---

## Operations

KPIs

Productivity

Attendance

Performance

Monitoring

---

## Connect

Cisco

UCCX

CUIC

Realtime states

Call records

Integrations

---

## Communications

News

Polls

Comments

Mentions

Notifications

---

## Knowledge

Knowledge Base

Articles

Categories

Versions

---

## Filesystem

Storage

Folders

Sharing

Quotas

---

## Workflows

Leave Requests

Shift Swaps

Approvals

---

## Audit

Audit Trail

History

Exports

---

## Documentation

Public documentation

Internal documentation

---

## Helpdesk

Tickets

Support

Categories

---

## Support

Shared operational support

---

# Module Architecture

Every module MUST follow exactly this structure.

```
Module/

├── Domain/
│
├── Application/
│
├── Infrastructure/
│
└── Presentation/
```

Nothing outside these layers should contain business logic.

---

# Domain Layer

The Domain contains the business model.

The Domain must not know Laravel exists.

Allowed contents:

```
Domain/

Aggregates/

Entities/

ValueObjects/

Enums/

Events/

Factories/

Specifications/

Policies/

Repositories/

Services/

Exceptions/
```

---

## Domain Rules

The Domain:

* owns all business rules
* protects invariants
* contains behavior
* is framework independent

Forbidden dependencies:

* Laravel
* Livewire
* HTTP
* Eloquent
* Queue
* Cache
* Mail
* Notification
* Filesystem
* Database Facades

---

## Entities

Entities contain behavior.

Entities are not data containers.

Good:

```
Schedule

publish()

assignEmployee()

validateCoverage()

cancel()

approve()
```

Bad:

```
Schedule

getters

setters

no behavior
```

---

## Aggregate Roots

Every modification must happen through an Aggregate Root.

Never modify child entities directly.

Aggregate Roots protect consistency.

---

## Value Objects

Anything with business meaning becomes a Value Object.

Examples:

* EmployeeId
* TeamId
* ScheduleId
* Email
* DateRange
* ShiftCode
* Percentage
* PhoneNumber

Value Objects must be immutable.

---

## Domain Events

Domain Events describe business facts.

Examples:

* EmployeeCreated
* WeeklySchedulePublished
* ShiftSwapApproved
* LeaveRequestRejected

They do not send emails.

They do not write logs.

They do not notify users.

Infrastructure reacts to them.

---

## Repositories

Repositories are interfaces.

Interfaces belong to Domain.

Implementations belong to Infrastructure.

---

## Domain Services

Use Domain Services only when behavior:

* belongs to the domain
* does not naturally fit an Entity
* does not belong to Infrastructure

Avoid turning every operation into a Service.

---

# Application Layer

Application coordinates use cases.

It does not make business decisions.

Allowed contents:

```
Application/

Commands/

Queries/

Handlers/

DTO/

Contracts/

Mappers/

Results/
```

---

## Commands

Commands mutate state.

Example:

```
CreateEmployee

UpdateSchedule

ApproveLeave

PublishPlanning
```

---

## Queries

Queries never modify state.

Queries may optimize reads.

---

## Handlers

Handlers orchestrate:

* repositories
* aggregates
* domain services
* events

Handlers do not implement business rules.

---

## DTOs

DTOs transport data.

They do not validate.

They do not contain business logic.

---

# Infrastructure Layer

Infrastructure contains technical implementations.

Examples:

```
Persistence

Eloquent

Repositories

Notifications

Jobs

Mail

External APIs

Cisco

Filesystem

Providers

Console

Cache
```

---

## Persistence

Eloquent Models are persistence models.

They are NOT domain entities.

Example:

```
Employee

Domain Entity

↓

EmployeeModel

Persistence Model
```

---

## External Systems

Every external system must have an Anti-Corruption Layer.

Examples:

Cisco

LDAP

CUIC

Finesse

WhatsApp

Never expose vendor models to the Domain.

---

# Presentation Layer

Presentation translates user interaction into use cases.

Allowed contents:

```
Controllers

Requests

Livewire

Views

Routes
```

---

## Controllers

Controllers:

* receive HTTP
* validate request
* call Application
* return response

Nothing more.

---

## Livewire

Livewire components:

* orchestrate UI
* call use cases
* present results

Never:

* query business logic
* manipulate Eloquent directly
* contain business rules

---

## Requests

Requests validate input only.

Never execute business logic.

---

# Dependency Rules

Dependencies always flow inward.

```
Presentation

↓

Application

↓

Domain

↑

Infrastructure
```

Infrastructure implements Domain contracts.

---

## Forbidden Dependencies

Forbidden:

```
Domain → Laravel

Domain → Eloquent

Domain → Livewire

Domain → Controllers

Application → Eloquent

Application → Livewire

Presentation → Domain Repositories

Module A → Module B Models
```

---

# Cross Module Communication

Modules never share Models.

Modules communicate through:

* Domain Events
* Public Application Contracts
* Shared Contracts
* Anti-Corruption Layers

Never through:

* direct Eloquent access
* foreign repositories
* shared database assumptions

---

# Shared Kernel

Shared code belongs in:

```
app/Shared
```

Only include:

* Uuid
* DateRange
* Email
* Percentage
* Money
* Clock
* Result
* Shared Contracts

Never place business logic here.

---

# CQRS

Logical separation only.

Commands

↓

Write

Queries

↓

Read

Do not create separate databases.

---

# Coding Standards

Prefer:

* final classes
* readonly objects
* enums
* explicit constructors
* dependency injection

Avoid:

* facades inside Domain
* static helpers
* utility classes
* God Services
* God Controllers
* God Livewire Components

---

# Database Rules

Database:

PostgreSQL only.

Rules:

* ULID primary keys
* jsonb instead of json
* foreign keys required
* indexes for lookup columns
* eager loading
* prevent lazy loading
* transactions for writes

Never use MySQL-specific syntax.

---

# Performance Rules

Mandatory:

* eager loading
* chunk()
* cursor()
* queue expensive work
* pagination

Avoid:

* N+1
* SELECT *
* loading entire aggregates unnecessarily

---

# Error Handling

Business errors:

Domain Exceptions

Infrastructure failures:

Infrastructure Exceptions

Presentation converts exceptions into responses.

---

# Authorization

Authorization belongs to Policies.

Business decisions never depend on Policies.

Policies answer:

Can the user execute this use case?

They do not decide business rules.

---

# Events

Prefer Domain Events.

Laravel Events exist only as infrastructure adapters.

---

# Observers

Observers should only handle technical concerns.

Allowed:

* cache invalidation
* audit logging
* indexing

Forbidden:

* business rules
* workflow execution
* domain decisions

---

# Transactions

Every write use case must execute inside a transaction.

Never scatter transactions across multiple services.

The Application layer owns transaction boundaries.

---

# Testing Strategy

## Domain

Unit Tests

Fast

Pure PHP

---

## Application

Feature Tests

Use Case Tests

---

## Infrastructure

Integration Tests

---

## Presentation

HTTP

Livewire

Browser tests when appropriate

---

Every new feature must include tests.

---

# Git

Branch strategy:

develop

↓

feature/*

Commits:

Conventional Commits

Examples:

```
feat(personnel): agregar aprobación de empleados

fix(wfm): corregir cálculo de cobertura

refactor(connect): migrar dominio de llamadas
```

---

# Refactoring Workflow

Every refactor follows exactly this sequence.

## Phase 1

Understand

* business process
* use cases
* dependencies
* invariants

---

## Phase 2

Redesign

Identify:

* aggregates
* entities
* value objects
* events
* repositories

---

## Phase 3

Implement

Create:

* Domain
* Application
* Infrastructure
* Presentation

---

## Phase 4

Cleanup

Remove:

* dead code
* duplicated logic
* obsolete classes

---

## Phase 5

Validate

Run:

* tests
* lint
* static analysis

Verify architecture.

---

# Agent Behavior

Before writing code an AI Agent MUST:

1. Understand the business problem.

2. Identify the domain language.

3. Discover aggregates.

4. Discover invariants.

5. Detect duplicated business logic.

6. Propose a cleaner model.

7. Implement incrementally.

Never reorganize files without improving the model.

---

# Architectural Checklist

Every completed task must satisfy:

* [ ] Domain has zero Laravel dependencies
* [ ] Domain has zero Eloquent dependencies
* [ ] Business logic lives only in Domain
* [ ] Livewire contains no business rules
* [ ] Controllers contain no business rules
* [ ] Requests only validate
* [ ] Repositories are interfaces
* [ ] Infrastructure implements repositories
* [ ] Aggregate invariants are protected
* [ ] Value Objects are immutable
* [ ] No cyclic dependencies
* [ ] No duplicated business rules
* [ ] No dead code introduced

---

# Laravel Stack

* PHP 8.4+
* Laravel 13
* Livewire 4
* FluxUI 2
* PostgreSQL 16
* TailwindCSS 4
* Vite
* Pest
* PHPUnit

---

# Development Commands

| Command              | Purpose                        |
| -------------------- | ------------------------------ |
| composer setup       | Initial project installation   |
| composer dev         | Local development              |
| composer dev:uploads | Development with upload limits |
| composer lint        | Fix coding style               |
| composer lint:check  | Verify coding style            |
| composer test        | Run complete validation        |
| ./vendor/bin/pest    | Execute Pest                   |
| npm run dev          | Frontend development           |
| npm run build        | Production assets              |

---

# Final Rule

Whenever a decision is unclear:

Prefer the solution that:

* reduces coupling
* increases cohesion
* simplifies the domain
* removes code instead of adding abstractions
* keeps Laravel as an implementation detail
* makes the business model easier to understand

The Domain is always the source of truth.

<!-- CODEGRAPH_START -->
## CodeGraph

In repositories indexed by CodeGraph (a `.codegraph/` directory exists at the repo root), reach for it BEFORE grep/find or reading files when you need to understand or locate code:

- **MCP tool** (when available): `codegraph_explore` answers most code questions in one call — the relevant symbols' verbatim source plus the call paths between them, including dynamic-dispatch hops grep can't follow. Name a file or symbol in the query to read its current line-numbered source. If it's listed but deferred, load it by name via tool search.
- **Shell** (always works): `codegraph explore "<symbol names or question>"` prints the same output.

If there is no `.codegraph/` directory, skip CodeGraph entirely — indexing is the user's decision.
<!-- CODEGRAPH_END -->
