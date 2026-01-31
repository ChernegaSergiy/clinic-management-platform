# Contributing Guidelines

This document describes the key rules and recommendations for developers working on the project.

## Git Workflow

We use a simplified GitFlow.

-   `main` - the stable branch that contains production-ready code.
-   `develop` - the integration branch for new features. All developers merge their changes here.
-   `feature/<scope>-<short-desc>` - branches for developing new functionality.
-   `bugfix/<scope>-<issue-id>` - branches for fixing bugs.

### Branch Naming

-   **Scope (context):** `patients`, `auth`, `infra`, `docs`, etc.
-   **Short description:** A short description in `kebab-case`.

Example: `feature/patients-add-search-form`

## Issues

We use issues primarily for project work planning (not only for bug reports).

### Issue Naming

The issue title follows this pattern:

`PREFIX-ID Short Title`

-   **PREFIX** maps to a domain/module (e.g., `CM`, `PAT`, `APT`, `EMR`, `LAB`, `INV`, `BIL`, `REP`, `ADM`, `SEC`, `QA`, `LOC`).
-   **ID** is a numeric identifier within the domain (e.g., `001`, `120`, `260`).
-   **Short Title** is the high-level scope shown in the issue list.

Examples:

-   `CM-005 Public Landing Page`
-   `APT-120 Unified Scheduling & Waitlist`
-   `SEC-260 Security Guardrails Package`

### Issue Body Structure

The issue body uses a more detailed heading that is not the same as the issue title.

Recommended template:

```
# Detailed Execution Heading

## User Story
As a <role>, I want <capability> so <benefit>.

## Acceptance Criteria
1. ...
2. ...
3. ...
```

### Labels

Labels are assigned based on the type and intent of work:

-   `documentation` for docs, content, or guidelines work.
-   `enhancement` for new features or improvements.
-   `help wanted` for work that benefits from extra contributors.
-   `good first issue` only for starter-friendly tasks.

## Commit Style

We use a style inspired by [Conventional Commits](https://www.conventionalcommits.org/) with project-specific rules.

**Format:** `type: Message` or `type(scope): Message`

-   **scope:** Optional context (`patients`, `auth`, `infra`, etc.).
-   **type:** The type of change:
    -   `feat`: A new feature.
    -   `fix`: A bug fix.
    -   `refactor`: Code refactoring without changing behavior.
    -   `test`: Adding or fixing tests.
    -   `docs`: Documentation changes.
    -   `chore`: Routine tasks (dependency updates, CI setup).
-   **Message** starts with a capital letter and is a short sentence that may include an action verb.
-   **One line only.** We do not use commit bodies.
-   **Atomic commits.** Each commit should represent a single logical change.

**Examples:**

```
feat: Add patient intake form
fix(auth): Resolve session regeneration bug
docs: Add instructions for local setup
```

## Pull Requests (PR)

1.  **Create a PR** to merge your `feature` or `bugfix` branch into `develop`.
2.  The **PR description** should include a link to the related issue and a short summary of changes.
3.  **Code Review:** Every PR must be reviewed by at least one other developer.
4.  **Merging:** After a successful review and all checks pass, the PR can be merged into `develop`.
