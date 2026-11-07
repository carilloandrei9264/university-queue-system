# Changelog

All notable changes to this project are recorded here.
Format: [Keep a Changelog](https://keepachangelog.com/en/1.1.0/). Versioning: [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added
- Laravel 13 application foundation, MVP database migrations, and fake demo accounts for students, admin, and office staff.
- Local-only demo login/logout, role-specific dashboards, and server-side role guards.
- Terminal-only first administrator command and admin pages for departments, services, and staff.
- Validation and feature tests for admin authorization, record management, and first-admin provisioning.
- Student ticket creation and cancellation, plus staff call-next, complete, skip, and recall actions with transaction-locked daily numbering.
- Public office queue displays with automatic refresh, showing the serving and next waiting ticket codes without student details.

## [0.0.0] - 2026-11-06

### Added
- Project documentation pack (requirements, architecture, database schema, UI/UX design, test plan, deployment, roadmap).
- Audit of the original NU Queue-It system.
- Review of the original UI (palette, screens, responsiveness, contrast) with screenshots, and a design system built from the NU blue and gold identity.

### Changed
- Planning documents now reflect the agreed direction: Laravel, a relational SQL database (MySQL by default, MariaDB if the university's host provides it), Laravel migrations, university-hosted deployment, and a one-department pilot as the MVP. ADR-0001 is accepted.
- Open questions answered: Admission, Registrar and Accounting (the enrollment process) are the offices in scope, students sign in with the university account with seeded records for now (ADR-0002), cancel and skip join the MVP, and front-end assets are build-free.
