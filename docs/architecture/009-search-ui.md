# ADR 009: Starterkit theme, components and core HTMX for the search UI

- Date: 2026-11-24
- Status: Implemented; pending review
- Approved scope: Phase 09, issue #27. No backend redesign or new search behavior, external providers or third-party front-end frameworks.

## Context

Phases 03–08 delivered the search, enrichment, activation and analytics layers on core Olivero with little styling. PLAN Phase 09 asks for branding, responsive and accessible components, and loading, error and empty states. It requires progressive enhancement, minimal JavaScript and Drupal-native rendering, and says Single Directory Components (SDC) should be evaluated. The owner asked for HTMX from Drupal core and a custom theme rather than an Olivero subtheme. Search is the priority.

## Decision

### Theme

- **Generation.** `meddiscover` was generated with core Starterkit (`php core/scripts/drupal generate-theme meddiscover`). Olivero is not a Starterkit base and is not meant to be subthemed, so the theme starts from core's `starterkit_theme` markup. Its files are owned by this project and are not coupled to a base theme that can change.
- **Default and admin themes.** It is the default theme; Claro remains the admin theme. The front page is the conversational search (*Ask*), matching the POC's purpose.
- **Design tokens.** CSS custom properties in `css/base/tokens.css` define color, spacing, radius and type. Every color pair meets WCAG 2.1 AA; computed contrast is at least 6.1:1, body text 14.9:1.
- **Layout.** A mobile-first header with branding, a main menu for the four search modes and a footer notice, plus `prefers-reduced-motion` support.
- **Components (SDC).** Each has a schema, a template and scoped CSS:
  - `meddiscover:result-card`, with slots for title, description, score, source, next steps and details.
  - `meddiscover:notice`, with `info` and `warning` variants and an accessible label.

  Components are used where the theme owns the markup: the result template and the page footer. They were not pushed into module code, so `med_search` stays theme-agnostic.

### Module changes, presentation only

- **Result theme hook.** `med_search` defines a `med_search_result` theme hook (object-oriented `#[Hook('theme')]` and a preprocess hook) with a neutral default template.
  - The preprocess hook exposes named parts (title, description, score, source, next steps) and passes every other child, such as enrichment or parts other modules add, as `details`.
  - The theme maps those parts to the result-card component.
  - The `#med_topic` and `#med_enrichment` data used by activation and analytics is unchanged.
- **Semantic classes** (`med-results`, `med-state--empty|error|notice`, `med-answer--{status}`) give each state a distinct, styleable class while the text and ARIA roles stay the same.
- **Bug fix: numbered sources.** Numbered citation sources now render. Before, a sibling `#plain_text` render element was dropped inside list items, so the Sources list showed no `[n]`.

### Core HTMX, progressive enhancement

The search form uses `Drupal\Core\Htmx\Htmx`:

```php
(new Htmx())->post(Url::fromRoute('<current>'))->onlyMainContent()
  ->select('form[data-med-search]')->target('this')->swap('outerHTML')
  ->indicator('#med-search-loading')->disabledElt('find [type="submit"]')
  ->applyTo($form);
```

- **Swap scope.** Only the form region is swapped. The response is main content only (`_wrapper_format=drupal_htmx`), and core HTMX re-attaches Drupal behaviors, so analytics beacons keep working.
- **Loading indicator.** It has `role="status"` and announces the expected wait: “10 to 20 seconds” for conversational answers, measured in Phase 08. The submit button is disabled during the request, so the search cannot be sent twice.
- **Errors and focus.** `med_search/search` (1.2 KB) shows a hidden `role="alert"` message on network, server or timeout errors. After a swap it moves focus to the results heading.
- **No JavaScript.** Without JavaScript, the full-page POST and rebuild work exactly as before; this was tested over HTTP.

### Analytics visualization

The metrics report shows each rate as a native `<meter>` with the number next to it. This needs no chart library, works for assistive technology and doesn't rely on color alone.

## Accessibility checklist

| Check | How |
|---|---|
| Skip link and landmarks | Starterkit `html.html.twig` skip link; `header`, `main` and `footer`; the notice is an `aside` with a label |
| Keyboard and focus | Visible 3 px focus ring on all interactive elements; focus moves to the results after an HTMX swap |
| Live regions | Loading `role="status"`, results `aria-live="polite"`, request errors and urgent messages `role="alert"` |
| Color | All pairs AA or better (computed); states also differ by border, text and role, not only color |
| External links | The “(external site: host)” text is inside the link (Phase 07) |
| Motion | `prefers-reduced-motion` disables the spinner animation |
| Responsive | Mobile-first and fluid; checked with headless Chrome at 500 px (its minimum window width) and 1280 px |

No automated axe or Lighthouse run is included. Neither is available in the DDEV or CI setup without adding a browser stack.

## Performance

- **Backend unchanged.** The real-stack benchmark repeated after the change shows the same latency as Phase 08 within noise: lexical, semantic and hybrid medians of 10–38 ms end-to-end, conversational about 11.7 s, no errors.
- **Added assets.** About 11 KB of theme CSS and module JS, plus core's HTMX (51 KB minified, loaded only on search pages). HTMX responses carry the main content only.

## Alternatives considered

- **Olivero subtheme.** Rejected: core does not support Olivero as a base theme, and its markup can change in minor releases.
- **Contrib base themes (Radix, Bootstrap).** Rejected: they would add a framework and build tooling for a small POC; core Starterkit and SDC cover the need.
- **Calling SDC from module code.** Rejected: it would couple `med_search` to one theme. A theme hook with a neutral template keeps the module reusable.
- **A JavaScript single-page app or a fetch-based search widget.** Rejected: more JavaScript, duplicated rendering and lost progressive enhancement. HTMX reuses Drupal's own form rendering.
- **Streaming LLM tokens.** Deferred: it needs a streaming endpoint and changes to the safety-verification flow. A clear loading state addresses the wait now.

## Limitations

- **No automated audit.** There is no automated accessibility audit; the checklist was verified manually and with rendered-HTML tests.
- **Starterkit defaults elsewhere.** Content pages (topic nodes) use Starterkit defaults with the global tokens; only search pages are fully designed.
- **No answer streaming.** The conversational wait is announced, not shortened.
- **No live progress.** The HTMX indicator shows activity, not progress.
