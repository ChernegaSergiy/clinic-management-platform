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

-   **Вага шрифтів:**
    -   `Light`: 300
    -   `Regular`: 400
    -   `Semi-bold`: 600
    -   `Bold`: 700

## 3. Відступи та інтервали (Spacing)

Ми використовуємо систему відступів, кратну 4px або 8px, для забезпечення візуальної гармонії.

-   `Extra Small`: 4px
-   `Small`: 8px
-   `Medium`: 16px
-   `Large`: 24px
-   `Extra Large`: 32px

## 4. Іконографія

Для іконок використовується бібліотека `Font Awesome` (через Semantic UI) або власні SVG-іконки для унікальних елементів.

-   **Приклади використання:**
    -   `user icon`: для профілів користувачів.
    -   `calendar icon`: для подій та розкладів.
    -   `file medical alternate icon`: для медичних записів.
    -   `pills icon`: для медикаментів.

## 5. Компоненти UI (Twig Macros)

Детальний опис та приклади використання компонентів (кнопки, картки, поля форм) будуть надані в окремих Twig-макросах.

-   **Кнопки:** `button.html.twig`
-   **Картки:** `card.html.twig`
-   **Поля форм:** `form_field.html.twig`

## 6. Доступність (WCAG 2.1 AA)

Усі компоненти розробляються з урахуванням вимог доступності:
-   **Контрастність:** Забезпечення достатньої контрастності тексту та фону.
-   **Семантична розмітка:** Використання правильних HTML-тегів та ARIA-атрибутів.
-   **Навігація з клавіатури:** Можливість взаємодії з усіма елементами за допомогою клавіатури.
