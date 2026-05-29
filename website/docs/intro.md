---
slug: /intro
title: Ocean Documentation Overview
---

# Ocean Documentation Overview

Ocean is an internal business platform for **marine ecological sample management and equipment inspection workflows**. This documentation site replaces the old root-level `docs/` and `openspec/` directories with a bilingual Docusaurus structure that is easier to maintain and publish.

## Current decisions

- **Laravel must remain the backend runtime.**
- The recommended long-term solution line is:
  - `Laravel API`
  - `React + TypeScript` SPA with `Vite`
  - `analysis-worker` implemented in Python
  - `MariaDB`
  - `Redis`
  - `Nginx`
  - `Docker Compose`
- The documentation site itself lives in `website/` and is built with `Docusaurus`.

## Why the documentation was restructured

The previous project documentation was split across many Markdown files and OpenSpec archives, including:

- role definitions and requirements
- P0 API scope and field drafts
- MariaDB data model and state transitions
- Laravel migration and runtime notes
- historical OpenSpec proposals and decision trails

That structure was useful during discovery, but it was not ideal for ongoing maintenance, bilingual publication, or onboarding. The new site provides a cleaner information architecture and a single long-term documentation entry.

## Documentation map

- [Technology Stack Reassessment](./architecture/tech-stack)
- [System Architecture](./architecture/system-architecture)
- [Roles and Requirements](./product/roles-and-requirements)
- [Versioned v1 Roadmap](./product/v1-roadmap)
- [P0 API](./api/p0-api)
- [Data Model and State Flows](./data/data-model-and-states)
- [Deployment and Operations](./operations/deployment)
- [OpenSpec Decision Summary](./history/openspec-decisions)

## Maintenance rules

1. New project documentation should be added under `website/docs/`.
2. Simplified Chinese translations should be added under `website/i18n/zh-Hans/docusaurus-plugin-content-docs/current/`.
3. The deleted root-level `docs/` and `openspec/` directories should not be restored as the primary documentation entry.
