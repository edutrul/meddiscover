# medDiscover theme

The public front-end theme for medDiscover. It was generated from core's [Starterkit](https://www.drupal.org/docs/core-modules-and-themes/core-themes/starterkit-theme) and has no base theme at runtime. Administration uses core Claro.

For the design, see [search UI](../../../../docs/architecture/009-search-ui.md).

## What it contains

- **Single Directory Components** (`components/`):
  - `meddiscover:result-card` renders a search result. `med_search` maps its parts (title, description, score, source, next steps, details) onto the card through `templates/med-search-result.html.twig`.
  - `meddiscover:notice` is a styled status or warning box.
- **Design tokens** (`css/base/tokens.css`): colours, spacing and radii as CSS custom properties (`--md-*`), used by every component stylesheet.
- **Search styles** (`css/components/med-search.css`): the search panel, result cards, answers with numbered sources, the Compare page (verdict, homepage ribbon, technical breakdown, flow diagram) and the Developer mode panel.
- **Progressive enhancement:** the search forms post in place with core HTMX. Without JavaScript, the regular POST and rebuild still work.
- **Accessibility:** a visible focus outline everywhere; a focusable results heading after each swap; external links marked with an icon and hidden "(opens in a new tab)" text; layouts that work at phone width without sideways scrolling.

## Development

After changing templates, libraries or component metadata:

```sh
ddev drush cache:rebuild
```

The `Powered by Drupal` block stays disabled; the UI contract test checks this.

## Maintainers

- Eduardo Telaya — https://github.com/edutrul
