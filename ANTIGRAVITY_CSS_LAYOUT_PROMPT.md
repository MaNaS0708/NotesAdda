# Notes Adda — CSS layout-only redesign brief

You are working on a WordPress notes-sharing plugin. Fix **only the placement, spacing, sizing, and responsive layout** of the authenticated application views.

## Non-negotiable constraints

- Edit **only** `ui/css/notes-adda-app.css`.
- Do not edit PHP, HTML, JavaScript, AJAX handlers, database classes, copy, colors, fonts, icons, button styles, border radii, form behavior, or responsive navigation behavior.
- Do not add, remove, rename, or reorder any markup or files.
- Do not change any AJAX, jQuery, WordPress, authentication, upload, search, filter, or modal functionality.
- Keep the existing warm clay color palette and all existing visual styling. This is a layout pass only.
- Add overrides at the end of the CSS file where possible, instead of rewriting unrelated existing rules.

## Current problem to solve

At a 1920px desktop viewport, the logged-in Library view is visually unbalanced:

1. The sidebar is about 300px wide.
2. The content has been constrained to a narrow column at the left, leaving a very large blank area on the right.
3. Within that narrow column, the title, search field, filters, search button, result count, and empty state still feel crowded and stuck together.
4. The empty-state card is too wide relative to its content and creates a large, awkward empty rectangle.
5. The view must feel like a calm, modern notes workspace—not a stretched dashboard and not a compressed form.

## Required desktop layout (minimum viewport: 1100px)

Use the full remaining width after the sidebar, but establish a **balanced content frame**. Do not cap the whole application at a small fixed width that leaves dead space on the right.

- Keep the left sidebar fixed/sticky as it is.
- Give `.na-main-container` a fluid content area with generous but proportional horizontal padding. It should fill the space next to the sidebar; it must not appear as a narrow left-aligned strip.
- Inside `.na-view`, use a readable max-width only for text blocks, not for the entire page.
- `.na-view-header` should be a two-column grid/flex layout: title/copy stays left, primary action stays top-right. Use a generous horizontal gap and a 32–48px vertical separation below the header.
- Title and subtitle must align with the search/filter card and result area below.
- The Library toolbar must be a clean two-row layout:
  - Row 1: search field only, spanning the available width.
  - Row 2: subject filter, sort filter, and Search button aligned left with comfortable 12–16px gaps.
  - Never force the search field, both selects, and the button into one cramped row.
- The result metadata line must sit below the toolbar with at least 20px breathing space; its helper text should align on the opposite side only when there is room, otherwise wrap cleanly.
- Cards should use a responsive grid with `minmax(300px, 1fr)` and 24–32px gaps. Use 2–3 columns depending on available width, never extremely wide cards.
- Empty/loading states should be a centered card with a sensible max-width (roughly 720–840px), min-height around 300px, and vertical breathing space. It should not stretch across the entire library region.

## Required tablet/mobile behavior

- Preserve the existing bottom navigation at mobile sizes.
- Under 900px, stack the header action beneath the heading only when necessary.
- Under 900px, stack the toolbar naturally: search full width, then filter controls in a wrapping row.
- Under 700px, keep controls full width or a simple two-column filter grid; no horizontal overflow.
- Do not reduce text contrast or make tap targets smaller.

## Acceptance checks

Before finishing, inspect these states at desktop (~1920px), laptop (~1440px), tablet (~900px), and mobile (~390px):

1. Library empty state.
2. Library with several cards.
3. My Notes empty state.
4. My Notes with several cards.
5. Upload/edit modal.

The result should have intentional whitespace, aligned left edges, no unused right-side dead zone, no cramped toolbar, no overlapping controls, and no horizontal scrolling.
