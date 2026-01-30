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

## Commit Style

We follow the Conventional Commits format.

**Format:** `scope(type): message`

-   **scope:** The context of the change (`patients`, `auth`, `infra`, etc.).
-   **type:** The type of change:
    -   `feat`: A new feature.
    -   `fix`: A bug fix.
    -   `refactor`: Code refactoring without changing behavior.
    -   `test`: Adding or fixing tests.
    -   `docs`: Documentation changes.
    -   `chore`: Routine tasks (dependency updates, CI setup).

**Examples:**

```
patients(feat): add patient intake form
auth(fix): resolve session regeneration bug
docs(update): add instructions for local setup
```

## Pull Requests (PR)

1.  **Create a PR** to merge your `feature` or `bugfix` branch into `develop`.
2.  The **PR description** should include a link to the related issue and a short summary of changes.
3.  **Code Review:** Every PR must be reviewed by at least one other developer.
4.  **Merging:** After a successful review and all checks pass, the PR can be merged into `develop`.
