# Clinic Design System

This document describes the visual language and components used in the project to ensure interface consistency and coherence.

## 1. Color Palette

We use a palette based on the clinic's corporate colors, with an emphasis on cleanliness, trust, and professionalism.

-   **Primary colors:**
    -   `Primary Blue`: #2185d0 (Semantic UI Blue) - for primary actions, buttons, links.
    -   `Secondary Grey`: #767676 - for secondary elements, text.
    -   `Success Green`: #21ba45 - for success messages.
    -   `Warning Yellow`: #fbbd08 - for warnings.
    -   `Danger Red`: #db2828 - for errors and critical actions.

-   **Neutral colors:**
    -   `White`: #FFFFFF
    -   `Light Grey`: #F9FAFB
    -   `Dark Grey`: #333333
    -   `Black`: #000000

## 2. Typography

We use fonts that ensure readability and a modern appearance.

-   **Primary font:** `Lato` (or system `sans-serif` as fallback).
-   **Font sizes:**
    -   `H1`: 2.5rem (40px)
    -   `H2`: 2rem (32px)
    -   `H3`: 1.75rem (28px)
    -   `H4`: 1.5rem (24px)
    -   `H5`: 1.25rem (20px)
    -   `Body Text`: 1rem (16px)
    -   `Small Text`: 0.875rem (14px)

-   **Font weights:**
    -   `Light`: 300
    -   `Regular`: 400
    -   `Semi-bold`: 600
    -   `Bold`: 700

## 3. Spacing and Intervals

We use a spacing system that is a multiple of 4px or 8px to ensure visual harmony.

-   `Extra Small`: 4px
-   `Small`: 8px
-   `Medium`: 16px
-   `Large`: 24px
-   `Extra Large`: 32px

## 4. Iconography

For icons, we use the `Font Awesome` library (via Semantic UI) or custom SVG icons for unique elements.

-   **Usage examples:**
    -   `user icon`: for user profiles.
    -   `calendar icon`: for events and schedules.
    -   `file medical alternate icon`: for medical records.
    -   `pills icon`: for medications.

## 5. UI Components (Twig Macros)

Detailed descriptions and usage examples of components (buttons, cards, form fields) will be provided in separate Twig macros.

-   **Buttons:** `button.html.twig`
-   **Cards:** `card.html.twig`
-   **Form fields:** `form_field.html.twig`

## 6. Accessibility (WCAG 2.1 AA)

All components are developed taking into account accessibility requirements:
-   **Contrast:** Ensuring sufficient contrast between text and background.
-   **Semantic markup:** Using correct HTML tags and ARIA attributes.
-   **Keyboard navigation:** Ability to interact with all elements using the keyboard.
