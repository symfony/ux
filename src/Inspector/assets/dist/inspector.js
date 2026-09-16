var inspector_default = "@layer base {\n  :host {\n    --panel: light-dark(oklch(95.5% .004 270), oklch(19% .006 270));\n    --surface: light-dark(#fff, oklch(14.5% .004 270));\n    --surface-hover: color-mix(in srgb, var(--text) 5%, var(--surface));\n    --surface-zebra: color-mix(in srgb, var(--text) 2.5%, var(--surface));\n    --control-hover: color-mix(in srgb, var(--text) 5%, transparent);\n    --control-active: light-dark(#ffffffeb, #0000005c);\n    --line-soft: light-dark(#14192312, #ffffff16);\n    --detail-edge: light-dark(#0000001f, #00000080);\n    --text: light-dark(oklch(19% .014 270), oklch(97% .004 270));\n    --muted: light-dark(oklch(42% .012 270), oklch(79% .01 270));\n    --faint: light-dark(oklch(54% .01 270), oklch(70% .01 270));\n    --focus: light-dark(#000000e0, #ffffffe6);\n    --scroll-thumb: light-dark(#0000002e, #fff3);\n    --scroll-thumb-hover: light-dark(#00000061, #fff6);\n    --accent-ui: light-dark(oklch(54% .2 255), oklch(70% .16 252));\n    --stimulus: light-dark(#12825c, #43d69b);\n    --livecomponent: light-dark(#a45c00, #ffb84d);\n    --turbo: light-dark(#6f35b5, #b985ff);\n    --panel-width: 21.25rem;\n    --font-ui: system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;\n    --mono: ui-monospace, \"SFMono-Regular\", Menlo, monospace;\n    --space-1: .25rem;\n    --space-2: .5rem;\n    --space-3: .75rem;\n    --space-4: 1rem;\n    --space-6: 1.5rem;\n    --text-xs: .6875rem;\n    --text-sm: .75rem;\n    --text-md: .8125rem;\n    --group-title-size: .5625rem;\n    --key-value-size: .6875rem;\n    --weight: 400;\n    --weight-strong: 500;\n    --target: 2.5rem;\n    --radius-sm: .25rem;\n    --radius-md: .375rem;\n    z-index: 2147483644;\n    font: var(--weight) var(--text-md)/1.5 var(--font-ui);\n    -webkit-font-smoothing: antialiased;\n    -moz-osx-font-smoothing: grayscale;\n    pointer-events: none;\n    position: fixed;\n    inset: 0;\n  }\n\n  :host(:not([open])) {\n    width: 0;\n    height: 100%;\n    inset: auto 0 0 auto;\n  }\n\n  :host *, :host :before, :host :after {\n    box-sizing: border-box;\n  }\n\n  [hidden] {\n    display: none !important;\n  }\n\n  button, input {\n    font: inherit;\n    border: 0;\n  }\n\n  button {\n    color: inherit;\n    cursor: pointer;\n    background: none;\n  }\n\n  button:focus-visible, input:focus-visible {\n    outline: 2px solid var(--focus);\n    outline-offset: -2px;\n  }\n\n  svg {\n    fill: none;\n    stroke: currentColor;\n    stroke-linecap: round;\n    stroke-linejoin: round;\n    stroke-width: 1.75px;\n    width: 1rem;\n    height: 1rem;\n  }\n\n  [data-framework=\"stimulus\"] {\n    --framework: var(--stimulus);\n  }\n\n  [data-framework=\"livecomponent\"] {\n    --framework: var(--livecomponent);\n  }\n\n  [data-framework=\"turbo\"] {\n    --framework: var(--turbo);\n  }\n\n  [data-framework=\"default\"] {\n    --framework: var(--muted);\n  }\n\n  .pane {\n    flex-direction: column;\n    min-height: 0;\n    display: flex;\n  }\n\n  :where(.stack-title, .stack-badge, .selector, .detail-head h2, .detail-selector, .expandable-text, .key-value > .value, .tree .key, .relation > .key, .relation > .value, .disclosure .name, .event-target, .raw-list strong) {\n    text-overflow: ellipsis;\n    white-space: nowrap;\n    overflow: hidden;\n  }\n}\n\n@layer components {\n  .inspector {\n    color: var(--text);\n    color-scheme: dark;\n    z-index: 2;\n    width: min(var(--panel-width), 100vw);\n    background: var(--panel);\n    border-left: 1px solid var(--line-soft);\n    visibility: hidden;\n    pointer-events: auto;\n    height: 100dvh;\n    position: absolute;\n    inset: 0 0 0 auto;\n    overflow: hidden;\n    translate: 100%;\n    container: inspector / inline-size;\n\n    :host([ready]) & {\n      transition: translate .18s cubic-bezier(.2, 0, 0, 1);\n    }\n\n    :host([open]) & {\n      visibility: visible;\n      translate: 0;\n    }\n  }\n\n  .pull-tab {\n    color: var(--text);\n    position: fixed;\n    inset: 0;\n    overflow: clip;\n\n    :host([open]) & {\n      display: none;\n    }\n\n    &:before {\n      content: \"\";\n      background: var(--muted);\n      opacity: 0;\n      width: 3px;\n      transition: opacity .12s;\n      position: absolute;\n      inset: 0 0 0 auto;\n    }\n\n    & > button {\n      border: 1px solid var(--line-soft);\n      border-radius: var(--radius-md) 0 0 var(--radius-md);\n      background: var(--panel);\n      width: 2.5rem;\n      height: 2.5rem;\n      font-weight: var(--weight-strong);\n      pointer-events: auto;\n      border-right: 0;\n      transition: translate .12s;\n      position: absolute;\n      top: 50%;\n      right: 0;\n      translate: 2rem -50%;\n    }\n\n    &:is([data-near], :focus-within) {\n      &:before {\n        opacity: 1;\n      }\n\n      & > button {\n        translate: 0 -50%;\n      }\n    }\n\n    @media (hover: none) {\n      & > button {\n        translate: 0 -50%;\n      }\n    }\n  }\n\n  .panel-resize {\n    z-index: 5;\n    cursor: ew-resize;\n    touch-action: none;\n    width: .5rem;\n    position: absolute;\n    inset: 0 auto 0 0;\n\n    &:after {\n      background: var(--faint);\n      content: \"\";\n      opacity: 0;\n      width: 1px;\n      transition: opacity .12s;\n      position: absolute;\n      inset: 0 auto 0 0;\n    }\n  }\n\n  .panel-resize:is(:hover, :focus-visible):after, .inspector[data-resizing] .panel-resize:after {\n    opacity: .8;\n  }\n\n  .inspector > header {\n    min-height: var(--target);\n    padding: 0 var(--space-3);\n    background: var(--panel);\n    box-shadow: inset 0 -.5px var(--line-soft);\n    grid-template-columns: 1fr auto 1fr;\n    align-items: center;\n    display: grid;\n\n    & > strong {\n      color: var(--muted);\n      font-size: var(--text-xs);\n      font-weight: var(--weight);\n      letter-spacing: .12em;\n      text-transform: uppercase;\n      grid-column: 2;\n    }\n  }\n\n  .header-actions {\n    gap: var(--space-1);\n    display: flex;\n  }\n\n  .header-end {\n    grid-column: 3;\n    justify-self: end;\n  }\n\n  .icon-button {\n    width: var(--target);\n    min-height: var(--target);\n    border-radius: var(--radius-sm);\n    color: var(--muted);\n    background: none;\n    flex: none;\n    place-items: center;\n    padding: 0;\n    transition: background-color .12s, color .12s;\n    display: inline-grid;\n\n    &:hover, &.active {\n      color: var(--text);\n    }\n\n    &[data-action=\"target\"].active {\n      color: var(--stimulus);\n\n      & svg {\n        stroke-width: 2.25px;\n      }\n    }\n  }\n\n  [data-action=\"activity\"] {\n    position: relative;\n\n    & b {\n      background: var(--control-hover);\n      min-width: 1rem;\n      height: 1rem;\n      box-shadow: inset 0 0 0 .5px var(--line-soft);\n      color: var(--muted);\n      font: var(--weight) .5625rem/1rem var(--mono);\n      text-align: center;\n      border-radius: 99rem;\n      padding: 0 .25rem;\n      position: absolute;\n      top: 0;\n      right: -.125rem;\n    }\n  }\n\n  .activity-tools, .filters {\n    background: var(--surface);\n    box-shadow: inset 0 -1px var(--line-soft);\n    flex: none;\n    display: grid;\n\n    & > input {\n      border-radius: var(--radius-sm);\n      appearance: none;\n      background: var(--panel);\n      width: calc(100% - 1.25rem);\n      min-height: 2rem;\n      color: var(--text);\n      margin: .5rem .625rem;\n      padding: 0 .625rem;\n\n      &:hover {\n        background: var(--surface-hover);\n      }\n\n      &::placeholder {\n        color: var(--faint);\n      }\n    }\n  }\n\n  .filter-list {\n    background: var(--panel);\n    align-items: center;\n    gap: .125rem;\n    padding: .125rem;\n    display: flex;\n  }\n\n  .filter {\n    justify-content: center;\n    align-items: center;\n    gap: var(--space-2);\n    min-width: 0;\n    min-height: 2.25rem;\n    padding: 0 var(--space-2);\n    color: var(--faint);\n    background: none;\n    border-radius: 0;\n    flex: 1;\n    transition: background-color .12s, color .12s;\n    display: inline-flex;\n\n    &:hover {\n      background: var(--control-hover);\n      color: var(--text);\n    }\n\n    &.active, &[role=\"tab\"][aria-selected=\"true\"] {\n      border-radius: var(--radius-sm);\n      background: var(--surface);\n      color: var(--text);\n      box-shadow: 0 .5px 1px #0000002e;\n    }\n\n    &.unavailable {\n      opacity: .5;\n    }\n  }\n\n  .filter b, .activity, .event-count {\n    min-width: 1rem;\n    padding: .0625rem var(--space-1);\n    background: color-mix(in srgb, var(--text) 7%, transparent);\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.25 var(--mono);\n    text-align: center;\n    font-variant-numeric: tabular-nums;\n    border-radius: 99rem;\n  }\n\n  .inspector > main {\n    background: var(--surface);\n    flex: 1;\n    min-height: 0;\n    overflow: hidden;\n  }\n\n  :where(.stack-body, .timeline) {\n    scrollbar-color: var(--scroll-thumb) transparent;\n    scrollbar-width: thin;\n  }\n\n  :where(.stack-body, .timeline):is(:hover, :focus-within) {\n    scrollbar-color: var(--scroll-thumb-hover) transparent;\n  }\n\n  .stack {\n    height: 100%;\n    position: relative;\n  }\n\n  .stack-nav {\n    flex: none;\n    display: none;\n  }\n\n  .stack.is-drilled .stack-nav {\n    z-index: 2;\n    width: var(--target);\n    height: 2.75rem;\n    display: block;\n    position: absolute;\n    top: 0;\n    left: 0;\n  }\n\n  .stack-link {\n    align-items: center;\n    gap: var(--space-2);\n    width: 100%;\n    min-height: 2.75rem;\n    padding: var(--space-2) var(--space-3);\n    background: var(--panel);\n    color: var(--muted);\n    text-align: start;\n    display: flex;\n  }\n\n  .stack-link:hover {\n    background: var(--control-hover);\n    color: var(--text);\n  }\n\n  .stack.is-drilled .stack-link {\n    display: none;\n  }\n\n  .stack.is-drilled .stack-link.previous {\n    width: var(--target);\n    background: none;\n    justify-content: center;\n    min-height: 2.75rem;\n    padding: 0;\n    display: flex;\n  }\n\n  .stack.is-drilled .stack-link.previous :is(.stack-title, .stack-badge) {\n    clip-path: inset(50%);\n    white-space: nowrap;\n    width: 1px;\n    height: 1px;\n    position: absolute;\n    overflow: hidden;\n  }\n\n  .stack-link > svg {\n    flex: none;\n  }\n\n  .stack-title {\n    min-width: 0;\n    color: var(--text);\n    font-size: var(--text-md);\n    font-weight: var(--weight-strong);\n    flex: 1;\n  }\n\n  .stack-badge {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n  }\n\n  .stack-body {\n    flex: 1;\n    min-height: 0;\n    position: relative;\n    overflow: auto;\n  }\n\n  .stack-page {\n    height: 100%;\n  }\n\n  .drill-detail {\n    background: var(--surface);\n    flex: 1;\n    height: 100%;\n    overflow: hidden;\n  }\n\n  .component {\n    background: var(--surface);\n    transition: background-color .1s;\n\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    &:hover, &:focus-within {\n      background: var(--surface-hover);\n    }\n\n    &.selected {\n      background: var(--control-active);\n    }\n  }\n\n  .component-row {\n    align-items: center;\n    gap: var(--space-2);\n    width: 100%;\n    min-width: 0;\n    min-height: 2.75rem;\n    padding: 0 var(--space-3);\n    cursor: pointer;\n    text-align: start;\n    background: none;\n    display: flex;\n\n    &.activity-pulse .activity {\n      animation: .7s cubic-bezier(.2, 0, 0, 1) activity-count-pulse;\n    }\n  }\n\n  .identity {\n    flex: 1;\n    min-width: 0;\n\n    & strong {\n      color: var(--text);\n      font-size: var(--text-md);\n      font-weight: var(--weight-strong);\n      letter-spacing: -.01em;\n      text-overflow: ellipsis;\n      white-space: nowrap;\n      display: block;\n      overflow: hidden;\n    }\n  }\n\n  .selector {\n    min-width: 0;\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n    align-items: center;\n    gap: .375rem;\n    display: flex;\n\n    &:before {\n      background: var(--framework);\n      content: \"\";\n      border-radius: 50%;\n      flex: none;\n      width: .3125rem;\n      height: .3125rem;\n    }\n  }\n\n  .component-row > svg {\n    color: var(--faint);\n    flex: none;\n  }\n\n  .activity {\n    transition: background-color .6s cubic-bezier(.2, 0, 0, 1), color .6s cubic-bezier(.2, 0, 0, 1);\n  }\n\n  @keyframes activity-count-pulse {\n    0%, 20% {\n      background: color-mix(in srgb, var(--framework) 22%, transparent);\n      color: var(--framework);\n    }\n  }\n\n  .detail {\n    background: var(--surface);\n    flex: 1;\n  }\n\n  .detail-head {\n    align-items: center;\n    gap: 0 var(--space-2);\n    min-height: 2.5rem;\n    padding: var(--space-1) var(--space-3);\n    background: var(--panel);\n    box-shadow: inset 0 .5px var(--detail-edge),\n        inset 0 -.5px var(--detail-edge);\n    grid-template-columns: minmax(0, 1fr) auto;\n    display: grid;\n  }\n\n  .stack.is-drilled .detail-head {\n    padding-inline-start: calc(var(--target) + var(--space-2));\n  }\n\n  .detail-head h2 {\n    min-width: 0;\n    color: var(--text);\n    font-size: var(--text-sm);\n    font-weight: var(--weight-strong);\n    letter-spacing: -.01em;\n    margin: 0;\n  }\n\n  .framework {\n    color: var(--framework);\n    font: var(--weight-strong) .5625rem/1 var(--font-ui);\n    letter-spacing: .09em;\n    text-transform: uppercase;\n    grid-column: 2;\n  }\n\n  .detail-selector {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n    grid-column: 1 / -1;\n    margin: 0;\n  }\n\n  .expandable-text {\n    cursor: zoom-in;\n    min-width: 0;\n    max-width: 100%;\n  }\n\n  .expandable-text.expanded {\n    overflow-wrap: anywhere;\n    text-overflow: clip;\n    white-space: pre-wrap;\n    word-break: break-word;\n    cursor: zoom-out;\n    overflow: visible;\n  }\n\n  .expandable-text:focus-visible {\n    border-radius: var(--radius-sm);\n    outline: 2px solid var(--focus);\n    outline-offset: 1px;\n  }\n\n  .detail-body {\n    flex: 1;\n    min-width: 0;\n    min-height: 0;\n    overflow: auto;\n  }\n\n  .framework-detail {\n    background: var(--surface);\n  }\n\n  .framework-detail > h3 {\n    padding: var(--space-3);\n    background: var(--panel);\n    color: var(--muted);\n    font-size: var(--text-xs);\n    font-weight: var(--weight-strong);\n    letter-spacing: .035em;\n    margin: 0;\n  }\n\n  .detail-body[data-frameworks=\"1\"] .framework-detail > h3 {\n    display: none;\n  }\n\n  .empty {\n    place-content: center;\n    gap: var(--space-2);\n    min-height: 9.5rem;\n    padding: var(--space-6);\n    color: var(--muted);\n    text-align: center;\n    display: grid;\n\n    & strong {\n      color: var(--text);\n      font-size: var(--text-md);\n      font-weight: var(--weight-strong);\n    }\n\n    & small {\n      max-width: 14rem;\n      color: var(--faint);\n      font-size: var(--text-sm);\n      text-wrap: pretty;\n    }\n  }\n\n  .page-rule {\n    text-align: start;\n    background: none;\n    width: 100%;\n  }\n\n  .page-rule[aria-pressed=\"true\"] {\n    background: var(--surface-hover);\n    box-shadow: inset 3px 0;\n  }\n\n  .page-rule:hover {\n    background: var(--surface-hover);\n  }\n\n  .inspector > footer {\n    align-items: center;\n    gap: var(--space-2);\n    min-height: 2.125rem;\n    padding: 0 var(--space-2);\n    background: var(--panel);\n    color: var(--faint);\n    font-size: var(--text-xs);\n    letter-spacing: .08em;\n    text-transform: uppercase;\n    box-shadow: inset 0 1px var(--line-soft);\n    flex: none;\n    grid-template-columns: 1fr auto 1fr;\n    display: grid;\n    position: relative;\n  }\n\n  .monitor-status {\n    font-size: var(--group-title-size);\n    letter-spacing: .1em;\n    grid-area: 1 / 1;\n    justify-self: start;\n    align-items: center;\n    font-stretch: condensed;\n    display: flex;\n  }\n\n  .live-dot {\n    width: .3125rem;\n    height: .3125rem;\n    margin-right: var(--space-1);\n    background: var(--stimulus);\n    border-radius: 50%;\n  }\n\n  .groups {\n    flex-direction: column;\n    gap: 0;\n    min-width: 0;\n    display: flex;\n  }\n\n  .group {\n    background: var(--surface);\n    overflow: hidden;\n\n    &[data-static] {\n      overflow: visible;\n    }\n\n    & > .title {\n      align-items: center;\n      gap: var(--space-1);\n      min-height: 1.6rem;\n      padding: 0 var(--space-3);\n      background: var(--panel);\n      box-shadow: inset 0 .5px var(--line-soft),\n            inset 0 -.5px var(--line-soft);\n      color: var(--muted);\n      font-size: var(--group-title-size);\n      font-stretch: condensed;\n      font-weight: var(--weight-strong);\n      letter-spacing: .1em;\n      text-transform: uppercase;\n      margin: 0;\n      display: flex;\n    }\n\n    & > summary.title {\n      cursor: pointer;\n      list-style: none;\n    }\n\n    & > .title > .icon {\n      width: .875rem;\n      color: var(--framework);\n      flex: 0 0 .875rem;\n      place-items: center;\n      display: grid;\n    }\n\n    & > .title > .icon svg {\n      stroke-width: 1.5px;\n      width: .6875rem;\n      height: .6875rem;\n    }\n\n    & > .title > .name {\n      flex: 1;\n      min-width: 0;\n    }\n\n    & > summary.title::-webkit-details-marker {\n      display: none;\n    }\n\n    & > summary.title:after {\n      color: var(--faint);\n      content: \"›\";\n      opacity: 0;\n      flex: none;\n      font-size: .75rem;\n      line-height: 1;\n      transition: rotate .12s, opacity .12s;\n      rotate: 0deg;\n    }\n\n    & > summary.title:is(:hover, :focus-visible):after {\n      opacity: .65;\n    }\n\n    &[open] > summary.title:after {\n      rotate: 90deg;\n    }\n\n    & > .content {\n      grid-template-columns: var(--space-4) minmax(7.25rem, .72fr) minmax(0, 1fr) var(--space-4);\n      padding: 0;\n      display: grid;\n    }\n\n    & > .content > .key-values, & > .content .relations {\n      display: contents;\n    }\n\n    &[data-static] > .content {\n      box-shadow: inset 0 -.5px var(--line-soft);\n    }\n  }\n\n  .key-values {\n    margin: 0;\n    padding: 0;\n  }\n\n  .key-value {\n    min-height: 2rem;\n    padding: 0 var(--space-3);\n    background: none;\n    grid-template-columns: minmax(7.25rem, .72fr) minmax(0, 1fr);\n    align-items: center;\n    gap: 0;\n    transition: background-color .1s;\n    display: grid;\n    position: relative;\n  }\n\n  .group > .content .key-value {\n    grid-column: 1 / -1;\n    grid-template-columns: subgrid;\n    padding-inline: 0;\n\n    & > .key {\n      grid-column: 2;\n    }\n\n    & > .value, &[data-structured] > :is(.value-meta, .value) {\n      grid-column: 3;\n    }\n\n    &[data-vertical] {\n      grid-template-rows: 1.6rem auto;\n\n      & > .key {\n        grid-column: 2 / 4;\n        align-self: center;\n      }\n\n      & > .value {\n        padding-block: var(--space-2);\n        padding-inline: 3rem var(--target);\n        background: var(--surface-zebra);\n        white-space: normal;\n        grid-column: 1 / -1;\n      }\n    }\n  }\n\n  .compound-field {\n    grid-column: 1 / -1;\n    grid-template-columns: subgrid;\n    min-width: 0;\n    margin: 0;\n    padding: 0;\n    display: grid;\n  }\n\n  .compound-field + .compound-field {\n    box-shadow: inset 0 .5px var(--line-soft);\n  }\n\n  .compound-field > .key-value {\n    grid-column: 1 / -1;\n  }\n\n  .compound-field > .action-parameter {\n    background: var(--surface-zebra);\n    min-height: 1.625rem;\n  }\n\n  .compound-field > .action-parameter > .key {\n    color: var(--faint);\n    padding-inline-start: var(--space-4);\n  }\n\n  .key-value {\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    & :is(dt, dd) {\n      margin: 0;\n    }\n\n    & > .key {\n      color: var(--muted);\n      font-size: var(--key-value-size);\n      font-weight: var(--weight);\n      padding-inline-end: var(--space-2);\n      line-height: 1.45;\n    }\n\n    &:is([data-element], .relation) > .key {\n      color: var(--text);\n    }\n\n    & > .value {\n      overflow-wrap: anywhere;\n      min-width: 0;\n      color: var(--text);\n      font: var(--weight) var(--key-value-size)/1.45 var(--mono);\n    }\n\n    &:has(.tree) > .value {\n      white-space: normal;\n      overflow: visible;\n    }\n\n    & > .value[data-long], &[data-field-key$=\"status\"] > .value {\n      color: var(--muted);\n    }\n  }\n\n  .value-list {\n    min-width: 0;\n    padding-block: var(--space-1);\n    gap: .125rem;\n    display: grid;\n  }\n\n  .value-list__item {\n    overflow-wrap: anywhere;\n    min-width: 0;\n  }\n\n  .value-status {\n    color: var(--faint);\n    font-size: var(--key-value-size);\n    font-family: var(--font-ui);\n    margin-inline-end: var(--space-1);\n  }\n\n  .value-status[data-status=\"connected\"] {\n    display: none;\n  }\n\n  .key-value {\n    &[data-structured] {\n      padding-block: var(--space-2);\n      grid-template-columns: minmax(7.25rem, .72fr) minmax(0, 1fr);\n      align-items: center;\n    }\n\n    &[data-structured] > .key {\n      grid-column: 1;\n    }\n\n    &[data-structured] > .value-meta {\n      color: var(--faint);\n      font: var(--weight) var(--key-value-size)/1 var(--mono);\n      white-space: nowrap;\n      grid-column: 2;\n      justify-self: start;\n      padding-inline-end: var(--target);\n    }\n\n    &[data-structured] > .value {\n      width: 100%;\n      padding-top: var(--space-1);\n      grid-column: 2;\n    }\n\n    &[data-element] > .value {\n      align-items: center;\n      gap: var(--space-2);\n      min-width: 0;\n      display: flex;\n    }\n  }\n\n  .value-note {\n    color: var(--faint);\n    font-size: var(--key-value-size);\n    font-family: var(--font-ui);\n    flex: none;\n  }\n\n  .bool, .num, .string {\n    color: var(--text);\n  }\n\n  .v-str {\n    color: color-mix(in srgb, var(--accent-ui) 68%, var(--text));\n  }\n\n  .v-num {\n    color: color-mix(in srgb, var(--accent-ui) 44%, var(--text));\n  }\n\n  .v-bool {\n    color: color-mix(in srgb, var(--accent-ui) 82%, var(--text));\n  }\n\n  .num, .v-num {\n    font-variant-numeric: tabular-nums;\n  }\n\n  .nul, .v-nul {\n    color: var(--faint);\n    font-style: italic;\n  }\n\n  .tree {\n    min-width: 0;\n    font: var(--weight) var(--text-xs)/1.55 var(--mono);\n\n    & details {\n      min-width: 0;\n    }\n\n    & summary, & .row {\n      align-items: baseline;\n      column-gap: var(--space-1);\n      grid-template-columns: max-content .35rem minmax(0, 1fr);\n      min-width: 0;\n      display: grid;\n    }\n\n    & summary {\n      cursor: pointer;\n    }\n\n    & .row {\n      padding-left: 0;\n    }\n\n    & .row:has( > :only-child) {\n      grid-template-columns: minmax(0, 1fr);\n    }\n\n    & .nest {\n      min-width: 0;\n      padding-left: var(--space-2);\n    }\n\n    & .key {\n      color: var(--muted);\n    }\n\n    & .colon {\n      color: var(--faint);\n    }\n\n    &.array, & .array {\n      gap: .125rem var(--space-2);\n      flex-wrap: wrap;\n      display: flex;\n    }\n\n    & .array > .array-item {\n      flex: 0 auto;\n    }\n\n    & .array > details.array-item {\n      flex-basis: 100%;\n    }\n\n    & :is(.v-str, .v-num, .v-bool, .v-nul, .type) {\n      overflow-wrap: break-word;\n      word-break: normal;\n      min-width: 0;\n    }\n\n    & .type {\n      color: var(--faint);\n    }\n  }\n\n  .target-pill {\n    min-height: var(--target);\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.45 var(--mono);\n    text-align: start;\n    text-overflow: ellipsis;\n    white-space: nowrap;\n    background: none;\n    border-radius: 0;\n    padding: 0;\n  }\n\n  .target-pill.expanded {\n    white-space: normal;\n  }\n\n  .target-pill:is(:hover, :focus-visible) {\n    color: var(--muted);\n  }\n\n  .target-pill .tag, .target-pill .cls {\n    color: inherit;\n    background: none;\n    padding: 0;\n  }\n\n  .key-value[data-changed] {\n    background: color-mix(in srgb, var(--framework) 8%, var(--surface));\n  }\n\n  .relations {\n    display: grid;\n  }\n\n  .relation {\n    background: var(--surface);\n    width: 100%;\n    color: var(--text);\n    text-align: start;\n    border-radius: 0;\n    transition: background-color .12s;\n\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    & > .key {\n      font-size: var(--key-value-size);\n      font-weight: var(--weight);\n      line-height: 1.45;\n      display: block;\n    }\n\n    & > .value {\n      color: var(--faint);\n      font: var(--weight) var(--key-value-size)/1.45 var(--mono);\n      padding-inline-end: var(--target);\n      display: block;\n    }\n\n    & > svg {\n      width: .875rem;\n      height: .875rem;\n      color: var(--faint);\n      justify-self: center;\n    }\n  }\n\n  .group > .content .relation > svg {\n    top: 50%;\n    right: calc((var(--target) - .875rem) / 2);\n    position: absolute;\n    translate: 0 -50%;\n  }\n\n  .activity-label {\n    z-index: 1;\n    background: var(--panel);\n    width: 100%;\n    box-shadow: inset 0 .5px var(--line-soft);\n    color: var(--muted);\n    border-radius: 0;\n    flex: none;\n    padding: 0;\n    position: sticky;\n    bottom: 0;\n  }\n\n  .activity-label > .title {\n    width: 100%;\n    box-shadow: none;\n  }\n\n  .drawer {\n    background: var(--surface);\n    height: auto;\n    min-height: 2.5rem;\n    max-height: min(10rem, 40%);\n    box-shadow: 0 -.5px var(--line-soft);\n    flex: none;\n    position: relative;\n  }\n\n  .drawer[data-sized] {\n    max-height: calc(100% - 6rem);\n  }\n\n  .drawer .empty {\n    min-height: 2.5rem;\n    padding: var(--space-2);\n  }\n\n  .drawer-resize {\n    z-index: 2;\n    cursor: ns-resize;\n    touch-action: none;\n    height: .625rem;\n    position: absolute;\n    top: -.625rem;\n    left: 0;\n    right: 0;\n  }\n\n  .drawer-resize:after {\n    background: var(--faint);\n    content: \"\";\n    opacity: .35;\n    border-radius: 99rem;\n    width: 2.5rem;\n    height: 3px;\n    transition: opacity .12s;\n    position: absolute;\n    top: .125rem;\n    left: 50%;\n    translate: -50%;\n  }\n\n  .drawer-resize:is(:hover, :focus-visible):after, .drawer[data-resizing] .drawer-resize:after {\n    opacity: 1;\n  }\n\n  .drawer {\n    & > .timeline {\n      overscroll-behavior: contain;\n      flex: 1;\n      height: auto;\n      min-height: 0;\n    }\n\n    & :is(.event-target, .event-go, .event-actions) {\n      display: none;\n    }\n\n    & button.disclosure:after {\n      opacity: 0;\n      font-size: 1rem;\n    }\n\n    & .event-row:is(:hover, :focus-within) button.disclosure:after {\n      opacity: .65;\n    }\n\n    & .disclosure .name {\n      overflow-wrap: anywhere;\n      white-space: normal;\n    }\n\n    & .activity-detail .key-value:first-child > .value {\n      padding-inline-end: 0;\n    }\n  }\n\n  .events, .event {\n    margin: 0;\n    padding: 0;\n    list-style: none;\n  }\n\n  .timeline {\n    background: var(--surface);\n    height: 100%;\n    overflow: auto;\n  }\n\n  .event {\n    background: var(--surface);\n  }\n\n  .event:nth-child(2n) {\n    background: var(--surface-zebra);\n  }\n\n  .event-row {\n    min-height: var(--target);\n    box-shadow: inset 0 -.5px var(--line-soft);\n    align-items: center;\n    display: flex;\n  }\n\n  .event-row.selected {\n    background: var(--control-active);\n  }\n\n  .event-row.compact, .event-row.compact .disclosure {\n    min-height: 1.6rem;\n  }\n\n  .disclosure {\n    min-width: 0;\n    min-height: var(--target);\n    align-items: center;\n    gap: var(--space-2);\n    padding: 0 var(--space-3);\n    color: var(--text);\n    text-align: start;\n    background: none;\n    flex: 1;\n    grid-template-columns: .75rem minmax(0, 1fr) auto .5rem;\n    display: grid;\n  }\n\n  button.disclosure:after {\n    color: var(--faint);\n    content: \"›\";\n    opacity: .45;\n    grid-column: 4;\n    justify-self: end;\n    font-size: .875rem;\n    line-height: 1;\n    transition: rotate .12s;\n  }\n\n  button.disclosure[aria-expanded=\"true\"]:after {\n    rotate: 90deg;\n  }\n\n  .event-dot {\n    background: var(--framework);\n    border-radius: 50%;\n    width: .375rem;\n    height: .375rem;\n  }\n\n  .disclosure .name {\n    font-size: var(--key-value-size);\n    font-weight: var(--weight);\n    line-height: 1rem;\n  }\n\n  .event-target {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1rem var(--mono);\n  }\n\n  .event-go svg {\n    width: .75rem;\n    height: .75rem;\n  }\n\n  .event-go:hover {\n    background: var(--control-hover);\n  }\n\n  .event-count {\n    flex: none;\n  }\n\n  .event-detail {\n    background: color-mix(in srgb, var(--text) 2.5%, var(--surface));\n  }\n\n  .activity-detail {\n    background: none;\n    padding: 0;\n    position: relative;\n  }\n\n  .event-actions {\n    z-index: 1;\n    position: absolute;\n    top: 0;\n    right: 0;\n  }\n\n  .event-copy:hover {\n    background: none;\n  }\n\n  .event-copy.copied {\n    background: var(--control-active);\n    color: var(--text);\n  }\n\n  .activity-detail .key-values {\n    background: none;\n  }\n\n  .activity-detail .key-value {\n    min-height: var(--target);\n  }\n\n  .activity-detail .key-value:first-child > .value {\n    padding-inline-end: var(--target);\n  }\n\n  .raw-events {\n    box-shadow: inset 0 .5px var(--line-soft);\n  }\n\n  .raw-events > summary {\n    min-height: var(--target);\n    align-items: center;\n    gap: var(--space-2);\n    padding-inline: var(--space-3);\n    color: var(--muted);\n    cursor: pointer;\n    font-size: var(--text-xs);\n    font-weight: var(--weight-strong);\n    display: flex;\n  }\n\n  .raw-count {\n    color: var(--faint);\n    font-family: var(--mono);\n    font-weight: var(--weight);\n  }\n\n  .raw-list {\n    margin: 0;\n    padding: 0;\n    list-style: none;\n\n    & li {\n      justify-content: space-between;\n      align-items: center;\n      gap: var(--space-2);\n      min-height: 2rem;\n      padding-inline: var(--space-3);\n      color: var(--faint);\n      font: var(--weight) var(--text-xs)/1.3 var(--mono);\n      display: flex;\n    }\n\n    & li + li {\n      box-shadow: inset 0 .5px var(--line-soft);\n    }\n\n    & strong {\n      color: var(--muted);\n    }\n  }\n}\n\n@layer overlay {\n  @scope (.overlay) {\n    :scope {\n      z-index: 1;\n      pointer-events: none;\n      position: fixed;\n      inset: 0;\n    }\n\n    .box {\n      border: 2px solid var(--framework);\n      border-radius: var(--radius-sm);\n      background: color-mix(in srgb, var(--framework), transparent 92%);\n      pointer-events: none;\n      position: fixed;\n      top: 0;\n      left: 0;\n    }\n\n    .box[data-mode=\"hover\"] {\n      background: none;\n      border-style: dashed;\n    }\n\n    .box[data-mode=\"all\"], .box[data-mode=\"event\"] {\n      background: none;\n    }\n\n    .box[data-mode=\"selected\"] {\n      box-shadow: 0 0 0 2px color-mix(in srgb, var(--framework), transparent 55%);\n    }\n\n    .box > span {\n      border-radius: var(--radius-sm) var(--radius-sm) 0 0;\n      background: var(--framework);\n      color: #101114;\n      max-width: 12.5rem;\n      font: var(--weight-strong) .625rem/1.25 var(--font-ui);\n      text-overflow: ellipsis;\n      white-space: nowrap;\n      padding: .125rem .25rem;\n      font-stretch: semi-condensed;\n      position: absolute;\n      top: 0;\n      left: -1px;\n      overflow: hidden;\n      translate: 0 -100%;\n    }\n\n    .box[data-mode=\"event\"] > span {\n      border-radius: 0 0 var(--radius-sm);\n      font-variant-numeric: tabular-nums;\n      translate: 0;\n    }\n\n    .box[data-mode=\"event\"].pulse {\n      animation: .65s ease-out overlay-pulse;\n    }\n\n    @keyframes overlay-pulse {\n      30% {\n        box-shadow: 0 0 0 var(--space-2) color-mix(in srgb, var(--framework), transparent 65%);\n      }\n    }\n  }\n}\n\n@layer states {\n  @media (width <= 32.5rem) {\n    .inspector {\n      width: 100vw;\n    }\n\n    .panel-resize {\n      display: none;\n    }\n  }\n\n  @container inspector (width <= 18rem) {\n    .inspector > header {\n      padding-inline: var(--space-1);\n      grid-template-columns: 1fr auto;\n    }\n\n    .inspector > header > strong {\n      display: none;\n    }\n\n    .header-end {\n      grid-column: 2;\n    }\n  }\n\n  @container inspector (width <= 11.5rem) {\n    .group > .content {\n      grid-template-columns: var(--space-4) minmax(0, 1fr) var(--space-4);\n    }\n\n    .key-value {\n      min-height: calc(var(--target) + var(--space-2));\n      padding-block: var(--space-1);\n      grid-template-columns: minmax(0, 1fr);\n      align-content: center;\n    }\n\n    .key-value > .key {\n      color: var(--faint);\n      font-size: var(--key-value-size);\n      padding-inline-end: var(--target);\n    }\n\n    .key-value > .value {\n      grid-column: 1;\n    }\n\n    .group > .content .key-value > :is(.key, .value) {\n      grid-column: 2;\n    }\n\n    .group > .content .key-value[data-vertical] > .value {\n      grid-column: 1 / -1;\n    }\n\n    .key-value[data-element] > .value {\n      gap: 0 var(--space-2);\n      flex-wrap: wrap;\n    }\n  }\n\n  @media (prefers-reduced-motion: reduce) {\n    :host *, :host :before, :host :after {\n      transition: none !important;\n      animation: none !important;\n    }\n  }\n}\n";
const queryElements = (selector) => {
	try {
		return [...document.querySelectorAll(selector)];
	} catch {
		return [];
	}
};
function createQueryCache() {
	const matches = /* @__PURE__ */ new Map();
	return (selector) => {
		let elements = matches.get(selector);
		if (!elements) matches.set(selector, elements = queryElements(selector));
		return elements;
	};
}
function sameElements(first, second) {
	return first.length === second.length && first.every((element, index) => element === second[index]);
}
function ancestors(element, selector) {
	const matches = [];
	for (let parent = element.parentElement?.closest(selector); parent; parent = parent.parentElement?.closest(selector)) matches.push(parent);
	return matches;
}
function scopedChildren(element, selector) {
	return [...element.querySelectorAll(selector)].filter((child) => child.parentElement?.closest(selector) === element);
}
function _checkPrivateRedeclaration(e, t) {
	if (t.has(e)) throw new TypeError("Cannot initialize the same private elements twice on an object");
}
function _classPrivateMethodInitSpec(e, a) {
	_checkPrivateRedeclaration(e, a), a.add(e);
}
function _classPrivateFieldInitSpec(e, t, a) {
	_checkPrivateRedeclaration(e, t), t.set(e, a);
}
function _assertClassBrand(e, t, n) {
	if ("function" == typeof e ? e === t : e.has(t)) return arguments.length < 3 ? t : n;
	throw new TypeError("Private element is not present on this object");
}
function _classPrivateFieldSet2(s, a, r) {
	return s.set(_assertClassBrand(s, a), r), r;
}
function _classPrivateFieldGet2(s, a) {
	return s.get(_assertClassBrand(s, a));
}
const BASE_ATTRIBUTES = new Set([
	"data-controller",
	"data-action",
	"data-model",
	"data-loading",
	"data-poll",
	"data-live-props-value",
	"data-live-name-value",
	"data-live-url-value",
	"data-live-fingerprint-value",
	"data-live-listeners-value",
	"data-live-props-from-parent-value",
	"src",
	"loading",
	"disabled",
	"busy",
	"complete"
]);
var _registry$8 = /* @__PURE__ */ new WeakMap();
var _state$7 = /* @__PURE__ */ new WeakMap();
var _observer$1 = /* @__PURE__ */ new WeakMap();
var _inspectorElement = /* @__PURE__ */ new WeakMap();
var _ignoreSelectors = /* @__PURE__ */ new WeakMap();
var _pending = /* @__PURE__ */ new WeakMap();
var _flushQueued = /* @__PURE__ */ new WeakMap();
var _mutations = /* @__PURE__ */ new WeakMap();
var _reportedPlugins = /* @__PURE__ */ new WeakMap();
var _ComponentDetector_brand = /* @__PURE__ */ new WeakSet();
var ComponentDetector = class {
	constructor(registry, state, inspectorElement, ignoreSelectors = []) {
		_classPrivateMethodInitSpec(this, _ComponentDetector_brand);
		_classPrivateFieldInitSpec(this, _registry$8, void 0);
		_classPrivateFieldInitSpec(this, _state$7, void 0);
		_classPrivateFieldInitSpec(this, _observer$1, null);
		_classPrivateFieldInitSpec(this, _inspectorElement, void 0);
		_classPrivateFieldInitSpec(this, _ignoreSelectors, void 0);
		_classPrivateFieldInitSpec(this, _pending, /* @__PURE__ */ new Set());
		_classPrivateFieldInitSpec(this, _flushQueued, false);
		_classPrivateFieldInitSpec(this, _mutations, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _reportedPlugins, /* @__PURE__ */ new Set());
		_classPrivateFieldSet2(_registry$8, this, registry);
		_classPrivateFieldSet2(_state$7, this, state);
		_classPrivateFieldSet2(_inspectorElement, this, inspectorElement);
		_classPrivateFieldSet2(_ignoreSelectors, this, ignoreSelectors.filter((selector) => {
			try {
				document.createDocumentFragment().querySelector(selector);
				return true;
			} catch (e) {
				console.warn(`[ux-inspector] Ignoring invalid "ignore_selectors" entry "${selector}":`, e);
				return false;
			}
		}));
	}
	scan() {
		const selector = _classPrivateFieldGet2(_registry$8, this).combinedSelector;
		if (!selector) return;
		const seen = new Set(_assertClassBrand(_ComponentDetector_brand, this, _components$2).call(this, document, selector));
		const query = createQueryCache();
		for (const element of seen) _assertClassBrand(_ComponentDetector_brand, this, _reconcile).call(this, element, query);
		for (const existing of _classPrivateFieldGet2(_state$7, this).elements) if (!seen.has(existing) || !existing.isConnected) _assertClassBrand(_ComponentDetector_brand, this, _remove).call(this, existing);
	}
	inspect(element) {
		_assertClassBrand(_ComponentDetector_brand, this, _reconcile).call(this, element, createQueryCache());
		return _classPrivateFieldGet2(_state$7, this).get(element);
	}
	observe() {
		if (_classPrivateFieldGet2(_observer$1, this)) return;
		_classPrivateFieldSet2(_observer$1, this, new MutationObserver((mutations) => {
			for (const mutation of mutations) {
				const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
				if (target && _assertClassBrand(_ComponentDetector_brand, this, _isToolingElement).call(this, target)) continue;
				const attributeName = mutation.attributeName;
				let changes = _classPrivateFieldGet2(_mutations, this).get(mutation.target);
				if (!changes) _classPrivateFieldGet2(_mutations, this).set(mutation.target, changes = /* @__PURE__ */ new Map());
				const key = mutation.type === "attributes" ? `attribute:${attributeName}` : mutation.type;
				let change = changes.get(key);
				if (!change) {
					change = {
						type: mutation.type,
						target: mutation.target,
						attributeName,
						addedNodes: [],
						removedNodes: []
					};
					changes.set(key, change);
				}
				change.addedNodes.push(...mutation.addedNodes);
				change.removedNodes.push(...mutation.removedNodes);
			}
			if (_classPrivateFieldGet2(_mutations, this).size) _assertClassBrand(_ComponentDetector_brand, this, _scheduleFlush).call(this);
		}));
		_classPrivateFieldGet2(_observer$1, this).observe(document.body || document.documentElement, {
			childList: true,
			subtree: true,
			attributes: true,
			characterData: true
		});
	}
	disconnect() {
		for (const element of _classPrivateFieldGet2(_state$7, this).elements) _classPrivateFieldGet2(_registry$8, this).notifyElementRemoved(element, _classPrivateFieldGet2(_state$7, this).get(element)?.keys() ?? []);
		_classPrivateFieldGet2(_observer$1, this)?.disconnect();
		_classPrivateFieldSet2(_observer$1, this, null);
		_classPrivateFieldGet2(_pending, this).clear();
		_classPrivateFieldGet2(_mutations, this).clear();
		_classPrivateFieldSet2(_flushQueued, this, false);
	}
	refresh(element) {
		_assertClassBrand(_ComponentDetector_brand, this, _queueOwners).call(this, element);
	}
	destroy() {
		this.disconnect();
	}
};
function _reconcile(element, query) {
	if (_assertClassBrand(_ComponentDetector_brand, this, _isExcluded).call(this, element)) {
		if (_classPrivateFieldGet2(_state$7, this).get(element)) _assertClassBrand(_ComponentDetector_brand, this, _remove).call(this, element);
		return;
	}
	const previous = _classPrivateFieldGet2(_state$7, this).get(element);
	const parsed = /* @__PURE__ */ new Map();
	for (const plugin of _classPrivateFieldGet2(_registry$8, this).getForElement(element)) try {
		plugin.observe?.(element);
		parsed.set(plugin.name, plugin.parse(element, query));
	} catch (e) {
		_classPrivateFieldGet2(_registry$8, this).notifyElementRemoved(element, [plugin.name]);
		if (!_classPrivateFieldGet2(_reportedPlugins, this).has(plugin.name)) {
			_classPrivateFieldGet2(_reportedPlugins, this).add(plugin.name);
			console.warn(`[ux-inspector] Plugin "${plugin.name}" parse() failed:`, e);
		}
	}
	for (const name of previous?.keys() ?? []) if (!parsed.has(name)) _classPrivateFieldGet2(_registry$8, this).notifyElementRemoved(element, [name]);
	_classPrivateFieldGet2(_state$7, this).replace(element, parsed);
}
function _queue(element) {
	if (!(element instanceof Element)) return;
	_classPrivateFieldGet2(_pending, this).add(element);
	_assertClassBrand(_ComponentDetector_brand, this, _scheduleFlush).call(this);
}
function _scheduleFlush() {
	if (_classPrivateFieldGet2(_flushQueued, this)) return;
	_classPrivateFieldSet2(_flushQueued, this, true);
	queueMicrotask(() => _assertClassBrand(_ComponentDetector_brand, this, _flush$1).call(this));
}
function _flush$1() {
	const mutations = [..._classPrivateFieldGet2(_mutations, this).values()].flatMap((changes) => [...changes.values()]);
	_classPrivateFieldGet2(_mutations, this).clear();
	let pageChanged = false;
	for (const mutation of mutations) {
		if (mutation.type === "attributes" && !BASE_ATTRIBUTES.has(mutation.attributeName ?? "") && !_classPrivateFieldGet2(_registry$8, this).isWatchedAttribute(mutation.attributeName ?? "")) continue;
		pageChanged = true;
		if (mutation.type === "childList") {
			for (const node of new Set(mutation.removedNodes)) if (node instanceof Element && !node.isConnected) _assertClassBrand(_ComponentDetector_brand, this, _removeSubtree).call(this, node);
			for (const node of new Set(mutation.addedNodes)) if (node instanceof Element) for (const element of _assertClassBrand(_ComponentDetector_brand, this, _components$2).call(this, node)) _assertClassBrand(_ComponentDetector_brand, this, _queue).call(this, element);
		} else if (mutation.target instanceof Element) {
			const selector = _classPrivateFieldGet2(_registry$8, this).combinedSelector;
			if (_classPrivateFieldGet2(_state$7, this).get(mutation.target) || selector && mutation.target.matches(selector)) _assertClassBrand(_ComponentDetector_brand, this, _queue).call(this, mutation.target);
		}
		_assertClassBrand(_ComponentDetector_brand, this, _queueOwners).call(this, mutation.target);
	}
	const query = createQueryCache();
	if (mutations.length) for (const owner of _classPrivateFieldGet2(_registry$8, this).collectExternalChanges(_classPrivateFieldGet2(_state$7, this), query, _classPrivateFieldGet2(_pending, this))) _classPrivateFieldGet2(_pending, this).add(owner);
	const pending = [..._classPrivateFieldGet2(_pending, this)];
	_classPrivateFieldGet2(_pending, this).clear();
	_classPrivateFieldSet2(_flushQueued, this, false);
	for (const target of pending) _assertClassBrand(_ComponentDetector_brand, this, _reconcile).call(this, target, query);
	if (pageChanged) _classPrivateFieldGet2(_state$7, this).notifyPageUpdated();
}
function _queueOwners(element) {
	let current = element instanceof Element ? element : element?.parentElement ?? null;
	while (current) {
		if (_classPrivateFieldGet2(_state$7, this).get(current)) _assertClassBrand(_ComponentDetector_brand, this, _queue).call(this, current);
		current = current.parentElement;
	}
}
function* _components$2(root, selector = _classPrivateFieldGet2(_registry$8, this).combinedSelector) {
	if (!selector || root instanceof Element && _assertClassBrand(_ComponentDetector_brand, this, _isExcluded).call(this, root)) return;
	if (root instanceof Element && root.matches(selector)) yield root;
	yield* root.querySelectorAll(selector);
}
function _removeSubtree(root) {
	for (const element of _classPrivateFieldGet2(_state$7, this).elements) if (element === root || root.contains(element)) _assertClassBrand(_ComponentDetector_brand, this, _remove).call(this, element);
}
function _remove(element) {
	_classPrivateFieldGet2(_pending, this).delete(element);
	const names = [..._classPrivateFieldGet2(_state$7, this).get(element)?.keys() ?? []];
	_classPrivateFieldGet2(_registry$8, this).notifyElementRemoved(element, names);
	_classPrivateFieldGet2(_state$7, this).remove(element);
}
function _isToolingElement(element) {
	return _classPrivateFieldGet2(_inspectorElement, this).contains(element) || !!element.closest("ux-inspector, .sf-toolbar");
}
function _isExcluded(element) {
	return !element.isConnected || _assertClassBrand(_ComponentDetector_brand, this, _isToolingElement).call(this, element) || _classPrivateFieldGet2(_ignoreSelectors, this).some((selector) => element.closest(selector));
}
const REQUEST = "turbo:before-fetch-request";
const RESPONSE = "turbo:before-fetch-response";
function projectActivity(entries, compact = true) {
	const open = [];
	const live = /* @__PURE__ */ new Map();
	const pendingChanges = /* @__PURE__ */ new Map();
	const rows = [];
	for (const entry of entries) {
		const key = targetOf(entry);
		if (entry.type === "livecomponent") {
			if (entry.event === "live:model:set") {
				const operation = live.get(key);
				if (operation) appendLiveEntry(operation, entry);
				else {
					let changes = pendingChanges.get(key);
					if (!changes) pendingChanges.set(key, changes = []);
					changes.push(entry);
				}
				continue;
			}
			if (entry.event === "live:request" || entry.event === "live:render:started") {
				let operation = live.get(key);
				if (!operation || entry.event === "live:request") {
					operation = liveOperation(entry, pendingChanges.get(key) || []);
					pendingChanges.delete(key);
					rows.push(operation);
					live.set(key, operation);
				} else appendLiveEntry(operation, entry);
				continue;
			}
			if (entry.event === "live:render:finished" || entry.event === "live:response:error") {
				const operation = live.get(key);
				if (operation) {
					appendLiveEntry(operation, entry);
					const info = operation.live;
					info.status = entry.event === "live:response:error" ? "error" : "complete";
					info.duration = Math.max(0, entry.time - info.startedAt);
					live.delete(key);
					continue;
				}
				if (entry.event === "live:render:finished") continue;
			}
		}
		if (isFetch(entry, REQUEST)) {
			const fetch = normalizeTurboFetchEntry(entry);
			const row = {
				id: entry.id,
				type: "turbo",
				event: "turbo:fetch",
				activityKind: "turbo-fetch",
				label: `${fetch.intent === "prefetch" ? "prefetch" : "fetch"} ${fetch.method} ${compactUrl(fetch.url)}`.trim(),
				time: entry.time,
				target: entry.target,
				owner: entry.owner,
				relatedElements: entry.relatedElements || [],
				fetch: {
					...fetch,
					pending: true,
					duration: null,
					status: null
				},
				rawEntries: [entry],
				rawCount: 1,
				occurrences: 1
			};
			rows.push(row);
			open.push(row);
			continue;
		}
		if (isFetch(entry, RESPONSE)) {
			const response = normalizeTurboFetchEntry(entry);
			const index = open.findIndex((row) => targetOf(row) === key && (!response.url || !row.fetch.url || response.url === row.fetch.url));
			if (index >= 0) {
				const row = open.splice(index, 1)[0];
				const info = row.fetch;
				Object.assign(info, {
					pending: false,
					status: response.status,
					duration: Math.max(0, entry.time - row.time),
					responseUrl: response.url || info.url
				});
				row.rawEntries.push(entry);
				row.rawCount++;
				continue;
			}
		}
		rows.push(entry);
	}
	for (const changes of pendingChanges.values()) rows.push(...changes);
	rows.sort((a, b) => a.time - b.time);
	if (!compact) return rows;
	const result = [];
	for (const row of rows) {
		const previous = result.at(-1);
		if (previous?.activityKind === "turbo-fetch" && row.activityKind === "turbo-fetch" && sameCompleteFetch(previous, row)) {
			previous.occurrences = (previous.occurrences ?? 1) + 1;
			previous.rawEntries.push(...row.rawEntries);
			previous.rawCount = previous.rawEntries.length;
			previous.time = row.time;
			previous.fetch.duration = row.fetch.duration;
		} else if (previous && sameRaw(previous, row)) {
			const group = previous.activityKind === "repeated" ? previous : {
				...previous,
				activityKind: "repeated",
				fetch: void 0,
				live: void 0,
				rawEntries: [previous],
				rawCount: 1,
				occurrences: 1
			};
			group.rawEntries.push(row);
			group.rawCount = (group.rawCount ?? 0) + 1;
			group.occurrences = (group.occurrences ?? 1) + 1;
			result[result.length - 1] = group;
		} else result.push(row);
	}
	return result;
}
function liveOperation(entry, changes) {
	const detail = record(entry.detail);
	const actions = Array.isArray(detail.actions) ? detail.actions : [];
	const models = Array.isArray(detail.models) ? detail.models : [];
	const trigger = actions.length ? `${actions.join(", ")}()` : models.length ? `model ${models.join(", ")}` : "render";
	const operation = {
		id: changes[0]?.id ?? entry.id,
		type: "livecomponent",
		event: "live:rerender",
		activityKind: "live-rerender",
		label: trigger === "render" ? "rerender" : `rerender · ${trigger}`,
		time: changes[0]?.time ?? entry.time,
		target: entry.target,
		owner: entry.owner,
		relatedElements: entry.relatedElements || [],
		rawEntries: [],
		rawCount: 0,
		occurrences: 1,
		live: {
			trigger,
			actions,
			models,
			changes: [],
			hooks: [],
			status: "pending",
			startedAt: entry.time,
			duration: null
		}
	};
	for (const change of changes) appendLiveEntry(operation, change);
	appendLiveEntry(operation, entry);
	return operation;
}
function appendLiveEntry(operation, entry) {
	operation.rawEntries.push(entry);
	operation.rawCount = (operation.rawCount ?? 0) + 1;
	const info = operation.live;
	if (entry.event === "live:model:set") {
		const detail = record(entry.detail);
		const previous = info.changes.find((change) => change.model === detail.model);
		if (previous) previous.value = detail.value;
		else info.changes.push({ ...detail });
	} else info.hooks.push(entry.event.replace(/^live:/, ""));
}
function normalizeTurboFetchEntry(entry) {
	const detail = record(entry?.detail);
	const request = record(detail.request || detail.fetchRequest);
	const options = record(detail.fetchOptions || request.fetchOptions || request.options);
	const fetchResponse = record(detail.fetchResponse || detail.response);
	const response = record(fetchResponse.response || fetchResponse);
	const headers = record(options.headers || request.headers);
	const purpose = Object.entries(headers).find(([key]) => key.toLowerCase() === "x-sec-purpose")?.[1];
	return {
		intent: String(purpose || "").toLowerCase() === "prefetch" ? "prefetch" : "fetch",
		method: String(options.method || request.method || (entry?.event === REQUEST ? "GET" : "")).toUpperCase(),
		url: url(detail.url || request.url || request.location || options.url || response.url || fetchResponse.location),
		priority: typeof (options.priority || request.priority) === "string" ? String(options.priority || request.priority) : "",
		status: number(response.status ?? fetchResponse.statusCode)
	};
}
function isFetch(entry, name) {
	return entry?.type === "turbo" && entry.event === name;
}
function sameCompleteFetch(a, b) {
	const left = a.fetch;
	const right = b.fetch;
	return !left.pending && !right.pending && targetOf(a) === targetOf(b) && left.intent === right.intent && left.method === right.method && left.url === right.url && left.status === right.status && left.priority === right.priority;
}
function sameRaw(a, b) {
	if (!a || a.activityKind && a.activityKind !== "repeated" || b.activityKind) return false;
	const source = a.rawEntries?.[0] || a;
	return source.type === b.type && source.event === b.event && (source.label || "") === (b.label || "") && source.target === b.target;
}
function record(value) {
	return value && typeof value === "object" && !Array.isArray(value) ? value : {};
}
function url(value) {
	if (typeof value === "string") return value;
	const object = record(value);
	return typeof (object.href || object.url) === "string" ? String(object.href || object.url) : "";
}
function number(value) {
	const result = Number(value);
	return Number.isFinite(result) ? result : null;
}
function compactUrl(value) {
	if (!value) return "";
	try {
		const parsed = new URL(value, document.baseURI);
		return parsed.pathname + parsed.search + parsed.hash;
	} catch {
		return value;
	}
}
function targetOf(entry) {
	return entry.target instanceof Element ? entry.target : null;
}
const SENSITIVE_KEY = /password|secret|token|authorization|cookie|csrf/i;
const MAX_STRING_LENGTH = 500;
const MAX_ITEMS = 20;
const VALUE_RULES = {
	overflow: null,
	redactAccessors: false
};
const EVENT_RULES = {
	overflow: "[truncated]",
	redactAccessors: true
};
function truncate(text, overflow = "") {
	return text.length > MAX_STRING_LENGTH ? `${text.slice(0, MAX_STRING_LENGTH)}${overflow}` : text;
}
function copyContainer(value, depth, descend, rules) {
	if (Array.isArray(value)) {
		const items = value.slice(0, MAX_ITEMS).map((item) => descend(item, "", depth + 1));
		if (rules.overflow !== null && value.length > MAX_ITEMS) items.push(rules.overflow);
		return items;
	}
	let descriptors;
	try {
		descriptors = Object.getOwnPropertyDescriptors(value);
	} catch {
		return "[unavailable]";
	}
	const keys = Object.keys(descriptors).filter((key) => descriptors[key].enumerable);
	const entries = [];
	for (const key of keys.slice(0, MAX_ITEMS)) {
		const descriptor = descriptors[key];
		if ("value" in descriptor) entries.push([key, descend(descriptor.value, key, depth + 1)]);
		else entries.push([key, rules.redactAccessors && SENSITIVE_KEY.test(key) ? "[redacted]" : "[accessor]"]);
	}
	const result = Object.fromEntries(entries);
	if (rules.overflow !== null && keys.length > MAX_ITEMS) result._truncated = true;
	return result;
}
function safeValue(value, key = "", depth = 0, seen = /* @__PURE__ */ new WeakSet()) {
	if (SENSITIVE_KEY.test(key)) return "[redacted]";
	if (depth > 3) return "[max depth]";
	if (typeof value === "string") return truncate(value);
	if (value === null || typeof value !== "object") return value;
	if (seen.has(value)) return "[circular]";
	seen.add(value);
	return copyContainer(value, depth, (item, name, next) => safeValue(item, name, next, seen), VALUE_RULES);
}
function safeUrl(value, absolute = false) {
	const input = truncate(String(value || ""));
	if (!input) return "";
	try {
		const url = new URL(input, document.baseURI);
		redactParams(url.searchParams);
		return absolute || /^[a-z][a-z\d+.-]*:/i.test(input) ? url.href : `${url.pathname}${url.search}${url.hash}`;
	} catch {
		return input;
	}
}
function snapshotEvent(value) {
	const seen = /* @__PURE__ */ new WeakSet();
	let budget = 100;
	const copy = (item, key, depth) => {
		try {
			return copyValue(item, key, depth);
		} catch {
			return "[unavailable]";
		}
	};
	const copyValue = (item, key, depth) => {
		if (SENSITIVE_KEY.test(key)) return "[redacted]";
		if (item === null || item === void 0 || typeof item === "boolean" || typeof item === "number") return item ?? null;
		if (typeof item === "string") return truncate(item, "[truncated]");
		if (typeof item === "bigint") return `${item}n`;
		if (typeof item !== "object") return `[${typeof item}]`;
		if (item instanceof Element) return describeElement(item);
		if (item instanceof Date) {
			const time = Date.prototype.getTime.call(item);
			return Number.isNaN(time) ? "[invalid date]" : new Date(time).toISOString();
		}
		if (item instanceof Error) return {
			name: item.name,
			message: item.message
		};
		if (globalThis.URL && item instanceof URL) return absoluteUrl(item.href);
		if (globalThis.URLSearchParams && item instanceof URLSearchParams) return truncate(redactParams(new URLSearchParams(item)).toString());
		if (globalThis.Headers && item instanceof Headers) return snapshotHeaders(item);
		if (globalThis.Request && item instanceof Request) return {
			url: absoluteUrl(item.url),
			method: item.method,
			headers: snapshotHeaders(item.headers),
			credentials: item.credentials,
			redirect: item.redirect,
			mode: item.mode,
			referrer: absoluteUrl(item.referrer),
			referrerPolicy: item.referrerPolicy,
			priority: item.priority || "",
			signal: copy(item.signal, "", depth + 1)
		};
		if (globalThis.Response && item instanceof Response) return {
			url: absoluteUrl(item.url),
			status: item.status,
			statusText: item.statusText,
			ok: item.ok,
			redirected: item.redirected,
			type: item.type,
			headers: snapshotHeaders(item.headers)
		};
		if (globalThis.AbortSignal && item instanceof AbortSignal) return { aborted: item.aborted };
		if (seen.has(item)) return "[circular]";
		if (depth >= 3 || budget-- <= 0) return "[truncated]";
		seen.add(item);
		return copyContainer(item, depth, copy, EVENT_RULES);
	};
	return copy(value, "", 0);
}
function redactParams(params) {
	for (const key of new Set(params.keys())) if (SENSITIVE_KEY.test(key)) params.set(key, "[redacted]");
	return params;
}
function snapshotHeaders(headers) {
	const result = {};
	let count = 0;
	try {
		for (const [name, value] of headers) {
			if (count++ === 30) {
				result._truncated = true;
				break;
			}
			result[name] = SENSITIVE_KEY.test(name) ? "[redacted]" : truncate(String(value));
		}
	} catch {
		return { _error: "[unavailable]" };
	}
	return result;
}
function absoluteUrl(value) {
	return safeUrl(value, true);
}
function describeElement(el) {
	return `${el.tagName.toLowerCase()}${el.id ? `#${el.id}` : ""}${el.className && typeof el.className === "string" ? "." + el.className.trim().split(/\s+/).slice(0, 2).join(".") : ""}`;
}
function freezeSnapshot(value, seen = /* @__PURE__ */ new WeakSet()) {
	if (!value || typeof value !== "object" || value instanceof Node || seen.has(value)) return value;
	seen.add(value);
	for (const descriptor of Object.values(Object.getOwnPropertyDescriptors(value))) if ("value" in descriptor) freezeSnapshot(descriptor.value, seen);
	return Object.freeze(value);
}
function sameSnapshot(left, right, seen = /* @__PURE__ */ new WeakMap()) {
	if (Object.is(left, right)) return true;
	if (!left || !right || typeof left !== "object" || typeof right !== "object") return false;
	if (left instanceof Node || right instanceof Node || Object.getPrototypeOf(left) !== Object.getPrototypeOf(right)) return false;
	const prototype = Object.getPrototypeOf(left);
	if (prototype !== Object.prototype && prototype !== Array.prototype && prototype !== null) return false;
	if (seen.has(left)) return seen.get(left) === right;
	seen.set(left, right);
	const a = Object.getOwnPropertyDescriptors(left);
	const b = Object.getOwnPropertyDescriptors(right);
	return Object.keys(a).length === Object.keys(b).length && Object.keys(a).every((key) => Object.hasOwn(b, key) && "value" in a[key] && "value" in b[key] && sameSnapshot(a[key].value, b[key].value, seen));
}
function activityElements(entry) {
	return new Set([
		entry.owner,
		entry.target,
		...entry.relatedElements ?? []
	].filter((element) => element instanceof Element));
}
var _entries$1 = /* @__PURE__ */ new WeakMap();
var _sequence = /* @__PURE__ */ new WeakMap();
var _byElement = /* @__PURE__ */ new WeakMap();
var _revision = /* @__PURE__ */ new WeakMap();
var _projections = /* @__PURE__ */ new WeakMap();
var _globalProjections = /* @__PURE__ */ new WeakMap();
var _frozen = /* @__PURE__ */ new WeakMap();
var _normalize = /* @__PURE__ */ new WeakMap();
var _EventMonitor_brand = /* @__PURE__ */ new WeakSet();
var _maxEntries = /* @__PURE__ */ new WeakMap();
var _active = /* @__PURE__ */ new WeakMap();
var _listeners = /* @__PURE__ */ new WeakMap();
var _categoryMap = /* @__PURE__ */ new WeakMap();
var _staticEvents = /* @__PURE__ */ new WeakMap();
var _boundHandler = /* @__PURE__ */ new WeakMap();
var EventMonitor = class {
	get revision() {
		return _classPrivateFieldGet2(_revision, this);
	}
	project(element = null, compact = true) {
		let scoped = element ? _classPrivateFieldGet2(_projections, this).get(element) : _classPrivateFieldGet2(_globalProjections, this);
		if (!scoped) _classPrivateFieldGet2(_projections, this).set(element, scoped = /* @__PURE__ */ new Map());
		let rows = scoped.get(compact);
		if (!rows) {
			rows = projectActivity(element ? _classPrivateFieldGet2(_byElement, this).get(element) ?? [] : _classPrivateFieldGet2(_entries$1, this), compact);
			freezeSnapshot(rows, _classPrivateFieldGet2(_frozen, this));
			scoped.set(compact, rows);
		}
		return rows;
	}
	constructor(maxEntries = 500, normalize = () => {}) {
		_classPrivateMethodInitSpec(this, _EventMonitor_brand);
		_classPrivateFieldInitSpec(this, _entries$1, []);
		_classPrivateFieldInitSpec(this, _sequence, 0);
		_classPrivateFieldInitSpec(this, _byElement, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldInitSpec(this, _revision, 0);
		_classPrivateFieldInitSpec(this, _projections, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldInitSpec(this, _globalProjections, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _frozen, /* @__PURE__ */ new WeakSet());
		_classPrivateFieldInitSpec(this, _normalize, void 0);
		_classPrivateFieldInitSpec(this, _maxEntries, void 0);
		_classPrivateFieldInitSpec(this, _active, false);
		_classPrivateFieldInitSpec(this, _listeners, []);
		_classPrivateFieldInitSpec(this, _categoryMap, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _staticEvents, /* @__PURE__ */ new Set());
		_classPrivateFieldInitSpec(this, _boundHandler, void 0);
		_classPrivateFieldSet2(_normalize, this, normalize);
		_classPrivateFieldSet2(_maxEntries, this, maxEntries);
		_classPrivateFieldSet2(_boundHandler, this, _assertClassBrand(_EventMonitor_brand, this, _handleEvent).bind(this));
	}
	get entries() {
		return [..._classPrivateFieldGet2(_entries$1, this)];
	}
	get active() {
		return _classPrivateFieldGet2(_active, this);
	}
	record({ type = "unknown", event, target = null, owner = null, detail = null, label = null, relatedElements = [] }) {
		var _this$sequence;
		const draft = {
			id: _classPrivateFieldSet2(_sequence, this, (_this$sequence = _classPrivateFieldGet2(_sequence, this), ++_this$sequence)),
			time: performance.now(),
			type,
			event,
			target: target instanceof Element ? target : null,
			owner: owner instanceof Element ? owner : null,
			detail,
			relatedElements: relatedElements.filter((element) => element instanceof Element).slice(0, 50)
		};
		if (label) draft.label = label;
		_classPrivateFieldGet2(_normalize, this).call(this, draft);
		const entry = Object.freeze({
			...draft,
			detail: freezeSnapshot(snapshotEvent(draft.detail), _classPrivateFieldGet2(_frozen, this)),
			relatedElements: Object.freeze([...draft.relatedElements])
		});
		_classPrivateFieldGet2(_frozen, this).add(entry);
		_classPrivateFieldGet2(_entries$1, this).push(entry);
		_assertClassBrand(_EventMonitor_brand, this, _index).call(this, entry);
		const removed = Object.freeze(_classPrivateFieldGet2(_entries$1, this).splice(0, Math.max(0, _classPrivateFieldGet2(_entries$1, this).length - _classPrivateFieldGet2(_maxEntries, this))));
		for (const expired of removed) _assertClassBrand(_EventMonitor_brand, this, _index).call(this, expired, true);
		_assertClassBrand(_EventMonitor_brand, this, _changed).call(this);
		for (const listener of _classPrivateFieldGet2(_listeners, this)) try {
			listener(entry, removed);
		} catch (error) {
			console.warn("[ux-inspector] Activity listener failed:", error);
		}
		return entry;
	}
	start(onEntry = null) {
		if (onEntry) this.addListener(onEntry);
		if (_classPrivateFieldGet2(_active, this)) return;
		_classPrivateFieldSet2(_active, this, true);
		for (const eventName of _classPrivateFieldGet2(_categoryMap, this).keys()) document.addEventListener(eventName, _classPrivateFieldGet2(_boundHandler, this), true);
	}
	stop() {
		if (!_classPrivateFieldGet2(_active, this)) return;
		_classPrivateFieldSet2(_active, this, false);
		for (const eventName of _classPrivateFieldGet2(_categoryMap, this).keys()) document.removeEventListener(eventName, _classPrivateFieldGet2(_boundHandler, this), true);
	}
	addListener(fn) {
		_classPrivateFieldSet2(_listeners, this, [..._classPrivateFieldGet2(_listeners, this), fn]);
	}
	removeListener(fn) {
		const idx = _classPrivateFieldGet2(_listeners, this).indexOf(fn);
		if (idx !== -1) _classPrivateFieldSet2(_listeners, this, _classPrivateFieldGet2(_listeners, this).toSpliced(idx, 1));
	}
	monitorEvents(eventNames, category = "unknown") {
		for (const name of eventNames) _classPrivateFieldGet2(_staticEvents, this).add(name);
		_assertClassBrand(_EventMonitor_brand, this, _registerEvents).call(this, eventNames, category);
	}
	setDynamicEvents(eventNames, category) {
		const newSet = new Set(eventNames);
		for (const [name, cat] of _classPrivateFieldGet2(_categoryMap, this)) {
			if (cat !== category || newSet.has(name) || _classPrivateFieldGet2(_staticEvents, this).has(name)) continue;
			_classPrivateFieldGet2(_categoryMap, this).delete(name);
			if (_classPrivateFieldGet2(_active, this)) document.removeEventListener(name, _classPrivateFieldGet2(_boundHandler, this), true);
		}
		_assertClassBrand(_EventMonitor_brand, this, _registerEvents).call(this, eventNames, category);
	}
	clear() {
		_classPrivateFieldSet2(_entries$1, this, []);
		_classPrivateFieldSet2(_byElement, this, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldSet2(_projections, this, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldSet2(_frozen, this, /* @__PURE__ */ new WeakSet());
		_assertClassBrand(_EventMonitor_brand, this, _changed).call(this);
	}
	destroy() {
		this.stop();
		this.clear();
		_classPrivateFieldSet2(_listeners, this, []);
		_classPrivateFieldGet2(_categoryMap, this).clear();
		_classPrivateFieldGet2(_staticEvents, this).clear();
	}
	getEntriesForElement(element, { includeDescendants = false } = {}) {
		if (!includeDescendants) return [..._classPrivateFieldGet2(_byElement, this).get(element) ?? []];
		return _classPrivateFieldGet2(_entries$1, this).filter((entry) => {
			if (entry.owner === element || entry.target === element) return true;
			if (entry.relatedElements?.includes(element)) return true;
			if (entry.owner && element.contains(entry.owner)) return true;
			if (entry.target && element.contains(entry.target)) return true;
			return entry.relatedElements?.some((related) => element.contains(related)) ?? false;
		});
	}
};
function _changed() {
	var _this$revision;
	_classPrivateFieldSet2(_revision, this, (_this$revision = _classPrivateFieldGet2(_revision, this), _this$revision++, _this$revision));
	_classPrivateFieldGet2(_globalProjections, this).clear();
}
function _index(entry, remove = false) {
	for (const element of activityElements(entry)) {
		const entries = _classPrivateFieldGet2(_byElement, this).get(element) ?? [];
		if (remove) {
			const index = entries.indexOf(entry);
			if (index !== -1) entries.splice(index, 1);
		} else entries.push(entry);
		if (entries.length) _classPrivateFieldGet2(_byElement, this).set(element, entries);
		else _classPrivateFieldGet2(_byElement, this).delete(element);
		_classPrivateFieldGet2(_projections, this).delete(element);
	}
}
function _registerEvents(eventNames, category) {
	for (const name of eventNames) {
		if (_classPrivateFieldGet2(_categoryMap, this).has(name)) continue;
		_classPrivateFieldGet2(_categoryMap, this).set(name, category);
		if (_classPrivateFieldGet2(_active, this)) document.addEventListener(name, _classPrivateFieldGet2(_boundHandler, this), true);
	}
}
function _handleEvent(event) {
	this.record({
		type: _classPrivateFieldGet2(_categoryMap, this).get(event.type) || "unknown",
		event: event.type,
		target: event.target instanceof Element ? event.target : null,
		detail: event.detail
	});
}
var _registry$7 = /* @__PURE__ */ new WeakMap();
var _state$6 = /* @__PURE__ */ new WeakMap();
var _edges = /* @__PURE__ */ new WeakMap();
var _dirty = /* @__PURE__ */ new WeakMap();
var _lifetime$8 = /* @__PURE__ */ new WeakMap();
var _RelationshipEngine_brand = /* @__PURE__ */ new WeakSet();
var RelationshipEngine = class {
	constructor(registry, state) {
		_classPrivateMethodInitSpec(this, _RelationshipEngine_brand);
		_classPrivateFieldInitSpec(this, _registry$7, void 0);
		_classPrivateFieldInitSpec(this, _state$6, void 0);
		_classPrivateFieldInitSpec(this, _edges, []);
		_classPrivateFieldInitSpec(this, _dirty, true);
		_classPrivateFieldInitSpec(this, _lifetime$8, new AbortController());
		_classPrivateFieldSet2(_registry$7, this, registry);
		_classPrivateFieldSet2(_state$6, this, state);
		const invalidate = () => {
			_classPrivateFieldSet2(_dirty, this, true);
		};
		for (const event of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) state.addEventListener(event, invalidate, { signal: _classPrivateFieldGet2(_lifetime$8, this).signal });
	}
	invalidate() {
		_classPrivateFieldSet2(_dirty, this, true);
	}
	getEdges() {
		if (_classPrivateFieldGet2(_dirty, this)) _assertClassBrand(_RelationshipEngine_brand, this, _rebuild).call(this);
		return [..._classPrivateFieldGet2(_edges, this)];
	}
	getRelatedTo(element) {
		if (_classPrivateFieldGet2(_dirty, this)) _assertClassBrand(_RelationshipEngine_brand, this, _rebuild).call(this);
		return _classPrivateFieldGet2(_edges, this).filter((e) => e.source === element || e.target === element);
	}
	getEdgesBetween(a, b) {
		if (_classPrivateFieldGet2(_dirty, this)) _assertClassBrand(_RelationshipEngine_brand, this, _rebuild).call(this);
		return _classPrivateFieldGet2(_edges, this).filter((e) => e.source === a && e.target === b || e.source === b && e.target === a);
	}
	getConnectedElements(element) {
		const related = this.getRelatedTo(element);
		const connected = /* @__PURE__ */ new Set();
		for (const edge of related) {
			if (edge.source !== element) connected.add(edge.source);
			if (edge.target !== element) connected.add(edge.target);
		}
		return [...connected];
	}
	getEdgeTypes() {
		if (_classPrivateFieldGet2(_dirty, this)) _assertClassBrand(_RelationshipEngine_brand, this, _rebuild).call(this);
		return [...new Set(_classPrivateFieldGet2(_edges, this).map((e) => e.type))];
	}
	destroy() {
		_classPrivateFieldGet2(_lifetime$8, this).abort();
		_classPrivateFieldSet2(_edges, this, []);
	}
};
function _rebuild() {
	_classPrivateFieldSet2(_edges, this, []);
	const elements = new Set(_classPrivateFieldGet2(_state$6, this).elements.filter((el) => el.isConnected));
	for (const element of elements) {
		const dataMap = _classPrivateFieldGet2(_state$6, this).get(element);
		if (!dataMap) continue;
		for (const [pluginName, data] of dataMap) {
			const pluginEdges = _classPrivateFieldGet2(_registry$7, this).collectRelationships(element, data, pluginName);
			for (const edge of pluginEdges) if (edge.source?.isConnected && edge.target?.isConnected) _classPrivateFieldGet2(_edges, this).push({
				...edge,
				evidence: edge.evidence || "declared",
				declared: true
			});
		}
		_assertClassBrand(_RelationshipEngine_brand, this, _addStructuralEdges).call(this, element, elements);
	}
	_assertClassBrand(_RelationshipEngine_brand, this, _deduplicateEdges).call(this);
	_classPrivateFieldSet2(_dirty, this, false);
}
function _addStructuralEdges(element, allElements) {
	let parent = element.parentElement;
	while (parent) {
		if (allElements.has(parent)) {
			_classPrivateFieldGet2(_edges, this).push({
				source: parent,
				target: element,
				type: "dom-parent",
				label: "contains",
				evidence: "structural",
				declared: false
			});
			break;
		}
		parent = parent.parentElement;
	}
}
function _deduplicateEdges() {
	const seen = /* @__PURE__ */ new Set();
	const ids = /* @__PURE__ */ new WeakMap();
	let nextId = 0;
	const elementId = (element) => {
		if (!ids.has(element)) ids.set(element, ++nextId);
		return ids.get(element);
	};
	_classPrivateFieldSet2(_edges, this, _classPrivateFieldGet2(_edges, this).filter((edge) => {
		const key = `${edge.type}:${elementId(edge.source)}:${elementId(edge.target)}`;
		if (seen.has(key)) return false;
		seen.add(key);
		return true;
	}));
}
var _components$1 = /* @__PURE__ */ new WeakMap();
var StateManager = class extends EventTarget {
	constructor(..._args) {
		super(..._args);
		_classPrivateFieldInitSpec(this, _components$1, /* @__PURE__ */ new Map());
	}
	set(element, pluginName, data) {
		let byPlugin = _classPrivateFieldGet2(_components$1, this).get(element);
		const isNew = !byPlugin;
		if (!byPlugin) {
			byPlugin = /* @__PURE__ */ new Map();
			_classPrivateFieldGet2(_components$1, this).set(element, byPlugin);
		}
		byPlugin.set(pluginName, data);
		this.dispatchEvent(new CustomEvent(isNew ? "component-added" : "component-updated", { detail: {
			element,
			pluginName,
			data,
			previous: null,
			current: byPlugin
		} }));
	}
	replace(element, byPlugin) {
		if (!byPlugin.size) {
			this.remove(element);
			return;
		}
		const previous = _classPrivateFieldGet2(_components$1, this).get(element);
		if (previous?.size === byPlugin.size && [...byPlugin].every(([name, value]) => {
			const old = previous.get(name);
			return old?.type === value.type && sameSnapshot(old, value);
		})) return;
		_classPrivateFieldGet2(_components$1, this).set(element, byPlugin);
		const [pluginName, data] = byPlugin.entries().next().value;
		this.dispatchEvent(new CustomEvent(previous ? "component-updated" : "component-added", { detail: byPlugin.size === 1 ? {
			element,
			pluginName,
			data,
			previous,
			current: byPlugin
		} : {
			element,
			previous,
			current: byPlugin
		} }));
	}
	remove(element) {
		if (_classPrivateFieldGet2(_components$1, this).delete(element)) this.dispatchEvent(new CustomEvent("component-removed", { detail: { element } }));
	}
	get(element) {
		return _classPrivateFieldGet2(_components$1, this).get(element);
	}
	get elements() {
		return [..._classPrivateFieldGet2(_components$1, this).keys()];
	}
	get size() {
		return _classPrivateFieldGet2(_components$1, this).size;
	}
	countByPlugin() {
		const counts = {};
		for (const byPlugin of _classPrivateFieldGet2(_components$1, this).values()) for (const name of byPlugin.keys()) counts[name] = (counts[name] || 0) + 1;
		return counts;
	}
	clear() {
		_classPrivateFieldGet2(_components$1, this).clear();
		this.dispatchEvent(new CustomEvent("components-cleared"));
	}
	notifyPageUpdated() {
		this.dispatchEvent(new CustomEvent("page-updated"));
	}
};
var _highlighter = /* @__PURE__ */ new WeakMap();
var _registry$6 = /* @__PURE__ */ new WeakMap();
var _onSelect$2 = /* @__PURE__ */ new WeakMap();
var _onStateChange = /* @__PURE__ */ new WeakMap();
var _activation = /* @__PURE__ */ new WeakMap();
var _enableTimeout = /* @__PURE__ */ new WeakMap();
var _TargetSelector_brand = /* @__PURE__ */ new WeakSet();
var TargetSelector = class {
	constructor(highlighter, registry, onStateChange = null) {
		_classPrivateMethodInitSpec(this, _TargetSelector_brand);
		_classPrivateFieldInitSpec(this, _highlighter, void 0);
		_classPrivateFieldInitSpec(this, _registry$6, void 0);
		_classPrivateFieldInitSpec(this, _onSelect$2, null);
		_classPrivateFieldInitSpec(this, _onStateChange, null);
		_classPrivateFieldInitSpec(this, _activation, null);
		_classPrivateFieldInitSpec(this, _enableTimeout, null);
		_classPrivateFieldSet2(_highlighter, this, highlighter);
		_classPrivateFieldSet2(_registry$6, this, registry);
		_classPrivateFieldSet2(_onStateChange, this, onStateChange);
	}
	get active() {
		return _classPrivateFieldGet2(_activation, this) !== null;
	}
	enable(onSelect) {
		if (this.active) return;
		const activation = _classPrivateFieldSet2(_activation, this, new AbortController());
		_classPrivateFieldSet2(_onSelect$2, this, onSelect);
		_classPrivateFieldGet2(_onStateChange, this)?.call(this, true);
		const options = {
			capture: true,
			signal: activation.signal
		};
		const target = (event) => _assertClassBrand(_TargetSelector_brand, this, _onTarget).call(this, event);
		document.addEventListener("mouseover", target, options);
		document.addEventListener("mouseout", () => _classPrivateFieldGet2(_highlighter, this).clearHover(), options);
		document.addEventListener("keydown", (event) => _assertClassBrand(_TargetSelector_brand, this, _onKeyDown).call(this, event), options);
		_classPrivateFieldSet2(_enableTimeout, this, setTimeout(() => {
			_classPrivateFieldSet2(_enableTimeout, this, null);
			if (this.active) document.addEventListener("click", target, options);
		}, 0));
	}
	disable(clearSelection = true) {
		if (!_classPrivateFieldGet2(_activation, this)) return;
		_classPrivateFieldGet2(_activation, this).abort();
		_classPrivateFieldSet2(_activation, this, null);
		_classPrivateFieldSet2(_onSelect$2, this, null);
		_classPrivateFieldGet2(_onStateChange, this)?.call(this, false);
		if (_classPrivateFieldGet2(_enableTimeout, this)) {
			clearTimeout(_classPrivateFieldGet2(_enableTimeout, this));
			_classPrivateFieldSet2(_enableTimeout, this, null);
		}
		_classPrivateFieldGet2(_highlighter, this).clearHover();
		if (clearSelection) _classPrivateFieldGet2(_highlighter, this).deselect();
	}
	toggle(onSelect) {
		if (this.active) this.disable();
		else this.enable(onSelect);
		return this.active;
	}
	destroy() {
		this.disable();
	}
};
function _onTarget(e) {
	if (!(e.target instanceof Element) || e.target.closest("ux-inspector, .sf-toolbar")) return;
	const selector = _classPrivateFieldGet2(_registry$6, this).combinedSelector;
	const target = selector ? e.target.closest(selector) : null;
	const plugins = target ? _classPrivateFieldGet2(_registry$6, this).getForElement(target) : [];
	if (e.type === "mouseover") {
		if (target) _classPrivateFieldGet2(_highlighter, this).hover(target, plugins[0]?.name || "default");
		return;
	}
	e.preventDefault();
	e.stopPropagation();
	e.stopImmediatePropagation();
	if (target) {
		const keepInspecting = e.shiftKey;
		_classPrivateFieldGet2(_onSelect$2, this)?.call(this, target, plugins, keepInspecting);
		if (!keepInspecting) this.disable(false);
	}
}
function _onKeyDown(e) {
	if (e.key !== "Escape") return;
	e.preventDefault();
	e.stopPropagation();
	this.disable();
}
const IDLE_RUNTIME = {
	status: "detected",
	duration: null,
	httpStatus: null,
	error: null
};
var _record = /* @__PURE__ */ new WeakMap();
var _subscriptions = /* @__PURE__ */ new WeakMap();
var _runtime$1 = /* @__PURE__ */ new WeakMap();
var _LiveObserver_brand = /* @__PURE__ */ new WeakSet();
var LiveObserver = class {
	constructor() {
		_classPrivateMethodInitSpec(this, _LiveObserver_brand);
		_classPrivateFieldInitSpec(this, _record, null);
		_classPrivateFieldInitSpec(this, _subscriptions, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _runtime$1, /* @__PURE__ */ new WeakMap());
	}
	setEventRecorder(record) {
		_classPrivateFieldSet2(_record, this, record);
	}
	read(element) {
		return _classPrivateFieldGet2(_runtime$1, this).get(element) ?? { ...IDLE_RUNTIME };
	}
	connected(element) {
		this.observe(element);
		_classPrivateFieldGet2(_runtime$1, this).set(element, {
			...IDLE_RUNTIME,
			status: "connected"
		});
	}
	disconnected(element) {
		this.remove(element);
		_classPrivateFieldGet2(_runtime$1, this).set(element, {
			...IDLE_RUNTIME,
			status: "disconnected"
		});
	}
	remove(element) {
		const subscription = _classPrivateFieldGet2(_subscriptions, this).get(element);
		if (!subscription) return;
		_classPrivateFieldGet2(_subscriptions, this).delete(element);
		for (const cleanup of subscription.cleanups) try {
			cleanup();
		} catch (error) {
			_assertClassBrand(_LiveObserver_brand, this, _warn).call(this, error);
		}
	}
	destroy() {
		for (const element of _classPrivateFieldGet2(_subscriptions, this).keys()) this.remove(element);
		_classPrivateFieldSet2(_record, this, null);
	}
	observe(element) {
		const component = element.__component;
		if (_classPrivateFieldGet2(_subscriptions, this).get(element)?.component === component) return;
		this.remove(element);
		if (typeof component?.on !== "function") return;
		_classPrivateFieldGet2(_runtime$1, this).set(element, {
			...IDLE_RUNTIME,
			status: "connected"
		});
		if (!_classPrivateFieldGet2(_record, this)) return;
		const subscription = {
			component,
			cleanups: []
		};
		_classPrivateFieldGet2(_subscriptions, this).set(element, subscription);
		const on = (event, callback) => {
			const handler = (...args) => {
				if (_classPrivateFieldGet2(_subscriptions, this).get(element) !== subscription || element.__component !== component) return;
				try {
					callback(...args);
				} catch (error) {
					_assertClassBrand(_LiveObserver_brand, this, _warn).call(this, error);
				}
			};
			subscription.cleanups.push(() => component.off?.(event, handler));
			component.on(event, handler);
		};
		try {
			on("request:started", (request) => _assertClassBrand(_LiveObserver_brand, this, _requestStarted).call(this, element, request));
			on("model:set", (model, value) => {
				const name = String(model).slice(0, 200);
				_assertClassBrand(_LiveObserver_brand, this, _emit).call(this, element, "model:set", {
					model: name,
					value: safeValue(value, name)
				}, `model: ${name}`);
			});
			on("render:started", () => _assertClassBrand(_LiveObserver_brand, this, _emit).call(this, element, "render:started"));
			on("render:finished", () => _assertClassBrand(_LiveObserver_brand, this, _renderFinished).call(this, element));
			on("response:error", (response) => _assertClassBrand(_LiveObserver_brand, this, _responseError).call(this, element, response));
		} catch (error) {
			this.remove(element);
			_assertClassBrand(_LiveObserver_brand, this, _warn).call(this, error);
		}
	}
};
function _requestStarted(element, request) {
	_classPrivateFieldGet2(_runtime$1, this).set(element, {
		...IDLE_RUNTIME,
		status: "updating",
		startedAt: performance.now()
	});
	const actions = (request?.actions ?? []).flatMap((action) => action?.name ? [String(action.name).slice(0, 100)] : []);
	const models = Object.keys(request?.updated ?? {});
	const files = Object.keys(request?.files ?? {});
	const parameters = {};
	if (actions.length) parameters.actions = actions;
	if (models.length) parameters.models = models;
	if (files.length) parameters.files = files;
	_assertClassBrand(_LiveObserver_brand, this, _emit).call(this, element, "request", Object.keys(parameters).length ? parameters : null, actions.length ? `request: ${actions.join(", ")}` : "request: render");
}
function _renderFinished(element) {
	const runtime = this.read(element);
	_classPrivateFieldGet2(_runtime$1, this).set(element, {
		...runtime,
		status: "idle",
		duration: runtime.startedAt === void 0 ? null : performance.now() - runtime.startedAt,
		error: null
	});
	_assertClassBrand(_LiveObserver_brand, this, _emit).call(this, element, "render:finished");
}
function _responseError(element, response) {
	const status = response?.response?.status ?? null;
	_classPrivateFieldGet2(_runtime$1, this).set(element, {
		...this.read(element),
		status: "error",
		httpStatus: status,
		error: status ? `Request failed (${status})` : "Request failed"
	});
	_assertClassBrand(_LiveObserver_brand, this, _emit).call(this, element, "response:error", { status });
}
function _emit(element, event, detail = null, label = event) {
	const name = (element.getAttribute("data-live-name-value") || "").slice(0, 200) || "LiveComponent";
	_classPrivateFieldGet2(_record, this)?.call(this, {
		type: "livecomponent",
		event: `live:${event}`,
		target: element,
		detail,
		label: `${name}: ${label}`
	});
}
function _warn(error) {
	console.warn("[ux-inspector] LiveComponent observation failed:", error);
}
function parseAttributeValue(raw, key = "") {
	let value;
	try {
		value = JSON.parse(raw);
	} catch {
		value = raw;
	}
	return safeValue(value, key);
}
function parseActionParameters(element, controller, excluded = []) {
	const params = {};
	const prefix = `data-${controller}-`;
	for (const { name, value } of element.attributes) {
		if (!name.startsWith(prefix) || !name.endsWith("-param")) continue;
		const key = name.slice(prefix.length, -6);
		if (!excluded.includes(key)) params[key] = parseAttributeValue(value, key);
	}
	return params;
}
function parseActionDescriptor(descriptor) {
	const arrowMatch = descriptor.match(/^(.+)->(.+)#(.+)$/);
	if (arrowMatch) {
		const [method, ...options] = arrowMatch[3].split(":");
		const trigger = arrowMatch[1];
		const [eventAndFilters, scope = null] = trigger.split("@");
		const [event, ...filters] = eventAndFilters.split(".");
		return {
			event,
			trigger,
			scope,
			filters,
			controller: arrowMatch[2],
			method,
			options
		};
	}
	const hashMatch = descriptor.match(/^(.+)#(.+)$/);
	if (hashMatch) {
		const [method, ...options] = hashMatch[2].split(":");
		return {
			event: null,
			trigger: null,
			scope: null,
			filters: [],
			controller: hashMatch[1],
			method,
			options
		};
	}
	return null;
}
function _typeof(o) {
	"@babel/helpers - typeof";
	return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function(o) {
		return typeof o;
	} : function(o) {
		return o && "function" == typeof Symbol && o.constructor === Symbol && o !== Symbol.prototype ? "symbol" : typeof o;
	}, _typeof(o);
}
function toPrimitive(t, r) {
	if ("object" != _typeof(t) || !t) return t;
	var e = t[Symbol.toPrimitive];
	if (void 0 !== e) {
		var i = e.call(t, r || "default");
		if ("object" != _typeof(i)) return i;
		throw new TypeError("@@toPrimitive must return a primitive value.");
	}
	return ("string" === r ? String : Number)(t);
}
function toPropertyKey(t) {
	var i = toPrimitive(t, "string");
	return "symbol" == _typeof(i) ? i : i + "";
}
function _defineProperty(e, r, t) {
	return (r = toPropertyKey(r)) in e ? Object.defineProperty(e, r, {
		value: t,
		enumerable: !0,
		configurable: !0,
		writable: !0
	}) : e[r] = t, e;
}
var _observer = /* @__PURE__ */ new WeakMap();
var _LiveComponentPlugin_brand = /* @__PURE__ */ new WeakSet();
var LiveComponentPlugin = class {
	constructor() {
		_classPrivateMethodInitSpec(this, _LiveComponentPlugin_brand);
		_defineProperty(this, "name", "livecomponent");
		_defineProperty(this, "selectors", ["[data-controller~=\"live\"]"]);
		_classPrivateFieldInitSpec(this, _observer, new LiveObserver());
	}
	setEventRecorder(record) {
		_classPrivateFieldGet2(_observer, this).setEventRecorder(record);
	}
	observe(element) {
		_classPrivateFieldGet2(_observer, this).observe(element);
	}
	canHandle(element) {
		return element.getAttribute("data-controller")?.split(/\s+/).includes("live") ?? false;
	}
	parse(element) {
		const reference = (element) => ({
			element,
			name: element.getAttribute("data-live-name-value") || ""
		});
		return {
			type: "livecomponent",
			element,
			data: {
				name: _assertClassBrand(_LiveComponentPlugin_brand, this, _parseName).call(this, element),
				url: safeUrl(element.getAttribute("data-live-url-value")),
				fingerprint: element.getAttribute("data-live-fingerprint-value") || "",
				props: _assertClassBrand(_LiveComponentPlugin_brand, this, _parseProps).call(this, element),
				propsFromParent: _assertClassBrand(_LiveComponentPlugin_brand, this, _parsePropsFromParent).call(this, element),
				listeners: _assertClassBrand(_LiveComponentPlugin_brand, this, _parseListeners).call(this, element),
				polling: _assertClassBrand(_LiveComponentPlugin_brand, this, _parsePolling).call(this, element),
				models: _assertClassBrand(_LiveComponentPlugin_brand, this, _resolveModels).call(this, element),
				actions: _assertClassBrand(_LiveComponentPlugin_brand, this, _resolveActions$1).call(this, element),
				loading: _assertClassBrand(_LiveComponentPlugin_brand, this, _resolveLoading).call(this, element),
				children: scopedChildren(element, "[data-controller~=\"live\"]").map(reference),
				parents: ancestors(element, "[data-controller~=\"live\"]").map(reference),
				otherControllers: _assertClassBrand(_LiveComponentPlugin_brand, this, _otherControllers).call(this, element),
				runtime: _classPrivateFieldGet2(_observer, this).read(element)
			}
		};
	}
	getDisplayName(element) {
		return _assertClassBrand(_LiveComponentPlugin_brand, this, _parseName).call(this, element) || "LiveComponent";
	}
	getRelationships(element, data) {
		const edges = [];
		const d = data.data;
		for (const child of d.children) if (child.element?.isConnected) edges.push({
			source: element,
			target: child.element,
			type: "live-parent",
			label: `parent of ${child.name || "LiveComponent"}`
		});
		return edges;
	}
	matchesAttribute(name) {
		return [
			"data-controller",
			"data-model",
			"data-loading",
			"data-poll"
		].includes(name) || name.startsWith("data-live-");
	}
	getMonitoredEvents() {
		return { static: ["live:connect", "live:disconnect"] };
	}
	onEvent(entry, element) {
		if ("live:disconnect" === entry.event) {
			_classPrivateFieldGet2(_observer, this).disconnected(element);
			entry.label = `${_assertClassBrand(_LiveComponentPlugin_brand, this, _parseName).call(this, element) || "LiveComponent"}: disconnect`;
			entry.detail = null;
			return;
		}
		if ("live:connect" === entry.event) {
			_classPrivateFieldGet2(_observer, this).connected(element);
			entry.label = `${_assertClassBrand(_LiveComponentPlugin_brand, this, _parseName).call(this, element) || "LiveComponent"}: connect`;
			entry.detail = null;
		}
	}
	destroy() {
		_classPrivateFieldGet2(_observer, this).destroy();
	}
	onElementRemoved(element) {
		_classPrivateFieldGet2(_observer, this).remove(element);
	}
};
function _parseName(element) {
	return (element.getAttribute("data-live-name-value") || "").slice(0, 200);
}
function _parseProps(element) {
	const raw = element.getAttribute("data-live-props-value");
	if (!raw) return {};
	try {
		return safeValue(JSON.parse(raw));
	} catch {
		return { _raw: safeValue(raw) };
	}
}
function _parsePropsFromParent(element) {
	const raw = element.getAttribute("data-live-props-from-parent-value");
	if (!raw) return {};
	try {
		return safeValue(JSON.parse(raw));
	} catch {
		return {};
	}
}
function _parseListeners(element) {
	const raw = element.getAttribute("data-live-listeners-value");
	if (!raw) return [];
	try {
		const parsed = safeValue(JSON.parse(raw));
		return Array.isArray(parsed) ? parsed : [];
	} catch {
		return [];
	}
}
function _parsePolling(element) {
	const polling = element.getAttribute("data-poll");
	if (!polling && !element.hasAttribute("data-poll")) return null;
	const match = polling?.match(/delay\((\d+)\)/);
	return { duration: match ? `${match[1]}ms` : "2000ms" };
}
function _resolveModels(element) {
	return Array.from(element.querySelectorAll("[data-model]")).filter((c) => _assertClassBrand(_LiveComponentPlugin_brand, this, _isInLiveScope).call(this, c, element) && c.getAttribute("data-model")).map((c) => {
		const model = _assertClassBrand(_LiveComponentPlugin_brand, this, _parseModelValue).call(this, c.getAttribute("data-model") ?? "");
		return {
			...model,
			value: _assertClassBrand(_LiveComponentPlugin_brand, this, _readModelValue).call(this, c, model.name),
			element: c
		};
	});
}
function _resolveActions$1(element) {
	const actions = [];
	const allElements = [element, ...element.querySelectorAll("[data-action]")];
	for (const candidate of allElements) {
		const raw = candidate.getAttribute("data-action");
		if (!raw) continue;
		if (!_assertClassBrand(_LiveComponentPlugin_brand, this, _isInLiveScope).call(this, candidate, element)) continue;
		const descriptors = raw.split(/\s+/).filter(Boolean);
		for (const descriptor of descriptors) {
			const parsed = parseActionDescriptor(descriptor);
			if (parsed && parsed.controller === "live") actions.push({
				event: parsed.event || "click",
				method: parsed.method === "action" ? candidate.getAttribute("data-live-action-param") || parsed.method : parsed.method,
				args: parseActionParameters(candidate, "live", ["", "action"]),
				element: candidate
			});
		}
	}
	return actions;
}
function _readModelValue(element, name) {
	const input = element;
	if (SENSITIVE_KEY.test(name) || input.type === "password") return "[redacted]";
	if (element instanceof HTMLInputElement && ["checkbox", "radio"].includes(element.type)) return element.checked;
	if ("value" in element) return safeValue(input.value, name);
	return null;
}
function _resolveLoading(element) {
	return Array.from(element.querySelectorAll("[data-loading]")).filter((c) => _assertClassBrand(_LiveComponentPlugin_brand, this, _isInLiveScope).call(this, c, element)).map((c) => ({
		action: c.getAttribute("data-loading") || "show",
		element: c
	}));
}
function _isInLiveScope(candidate, root) {
	if (candidate === root) return true;
	return candidate.closest?.("[data-controller~=\"live\"]") === root;
}
function _parseModelValue(raw) {
	const parts = raw.split("|");
	if (parts.length === 1) return {
		name: parts[0].trim(),
		modifiers: []
	};
	return {
		name: parts.pop().trim(),
		modifiers: parts.map((p) => p.trim()).filter(Boolean)
	};
}
function _otherControllers(element) {
	return (element.getAttribute("data-controller") || "").split(/\s+/).filter((c) => c && c !== "live");
}
function safeCallHook(plugin, hookName, ...args) {
	const hook = plugin[hookName];
	if (typeof hook !== "function") return void 0;
	try {
		return hook.apply(plugin, args);
	} catch (error) {
		console.warn(`[ux-inspector] Plugin "${plugin.name}" hook "${hookName}" threw:`, error);
		return;
	}
}
var _plugins = /* @__PURE__ */ new WeakMap();
var _order = /* @__PURE__ */ new WeakMap();
var _watchedAttributes = /* @__PURE__ */ new WeakMap();
var PluginRegistry = class {
	constructor(plugins = []) {
		_classPrivateFieldInitSpec(this, _plugins, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _order, [
			"livecomponent",
			"turbo",
			"stimulus"
		]);
		_classPrivateFieldInitSpec(this, _watchedAttributes, null);
		_classPrivateFieldSet2(_plugins, this, new Map(plugins.map((plugin) => [plugin.name, plugin])));
	}
	get(name) {
		return _classPrivateFieldGet2(_plugins, this).get(name);
	}
	getAll() {
		const rank = (p) => {
			const i = _classPrivateFieldGet2(_order, this).indexOf(p.name);
			return i === -1 ? 99 : i;
		};
		return [..._classPrivateFieldGet2(_plugins, this).values()].sort((a, b) => rank(a) - rank(b));
	}
	getForElement(element) {
		return this.getAll().filter((plugin) => safeCallHook(plugin, "canHandle", element));
	}
	get combinedSelector() {
		const selectors = this.getAll().flatMap((p) => p.selectors);
		return selectors.length ? selectors.join(", ") : null;
	}
	notifyEvent(entry, element) {
		const plugins = new Set(this.getForElement(element));
		const categoryPlugin = _classPrivateFieldGet2(_plugins, this).get(entry.type);
		if (categoryPlugin) plugins.add(categoryPlugin);
		for (const plugin of plugins) safeCallHook(plugin, "onEvent", entry, element);
	}
	setEventRecorder(record) {
		for (const plugin of this.getAll()) safeCallHook(plugin, "setEventRecorder", record);
	}
	notifyElementRemoved(element, pluginNames) {
		for (const name of pluginNames) {
			const plugin = _classPrivateFieldGet2(_plugins, this).get(name);
			if (plugin) safeCallHook(plugin, "onElementRemoved", element);
		}
	}
	destroy() {
		for (const plugin of this.getAll()) safeCallHook(plugin, "destroy");
	}
	collectRelationships(element, data, pluginName = null) {
		const edges = [];
		const plugins = pluginName ? [_classPrivateFieldGet2(_plugins, this).get(pluginName)].filter((p) => Boolean(p)) : this.getForElement(element);
		for (const plugin of plugins) {
			const pluginEdges = safeCallHook(plugin, "getRelationships", element, data);
			if (Array.isArray(pluginEdges)) edges.push(...pluginEdges);
		}
		return edges;
	}
	collectWatchedAttributes() {
		if (_classPrivateFieldGet2(_watchedAttributes, this)) return [..._classPrivateFieldGet2(_watchedAttributes, this)];
		const attrs = /* @__PURE__ */ new Set();
		for (const plugin of _classPrivateFieldGet2(_plugins, this).values()) {
			const extra = safeCallHook(plugin, "getWatchedAttributes");
			if (Array.isArray(extra)) extra.forEach((a) => attrs.add(a));
		}
		_classPrivateFieldSet2(_watchedAttributes, this, attrs);
		return [...attrs];
	}
	isWatchedAttribute(name) {
		if (!_classPrivateFieldGet2(_watchedAttributes, this)) this.collectWatchedAttributes();
		if (_classPrivateFieldGet2(_watchedAttributes, this)?.has(name)) return true;
		for (const plugin of _classPrivateFieldGet2(_plugins, this).values()) if (safeCallHook(plugin, "matchesAttribute", name) === true) return true;
		return false;
	}
	collectPageRules(doc = document) {
		const rules = [];
		for (const plugin of this.getAll()) {
			const result = safeCallHook(plugin, "getPageRules", doc);
			if (Array.isArray(result)) rules.push(...result.map((rule) => ({
				...rule,
				framework: plugin.name
			})));
		}
		return rules;
	}
	collectExternalChanges(state, query, pending) {
		const owners = [];
		for (const element of state.elements) {
			if (pending.has(element)) continue;
			for (const [name, data] of state.get(element) ?? []) {
				const plugin = _classPrivateFieldGet2(_plugins, this).get(name);
				if (plugin && safeCallHook(plugin, "hasExternalChanges", element, data, query)) {
					owners.push(element);
					break;
				}
			}
		}
		return owners;
	}
	collectMonitoredEvents(elementsByPlugin = /* @__PURE__ */ new Map()) {
		const staticEvents = /* @__PURE__ */ new Map();
		const dynamicEvents = /* @__PURE__ */ new Map();
		for (const plugin of this.getAll()) {
			const monitored = safeCallHook(plugin, "getMonitoredEvents");
			if (!monitored) continue;
			if (Array.isArray(monitored.static) && monitored.static.length) staticEvents.set(plugin.name, monitored.static);
			if (typeof monitored.dynamic === "function") {
				const elements = elementsByPlugin.get(plugin.name) || [];
				try {
					const names = monitored.dynamic(elements);
					if (Array.isArray(names)) dynamicEvents.set(plugin.name, names);
				} catch (e) {
					console.warn(`[ux-inspector] Plugin "${plugin.name}" dynamic events threw:`, e);
				}
			}
		}
		return {
			staticEvents,
			dynamicEvents
		};
	}
};
const CONNECTED$1 = "connected";
const MISSING = "missing";
const CONFIGURED = "configured";
const DOM_ONLY$1 = "dom-only";
var _application$1 = /* @__PURE__ */ new WeakMap();
var _StimulusPlugin_brand = /* @__PURE__ */ new WeakSet();
var StimulusPlugin = class {
	constructor(application = null) {
		_classPrivateMethodInitSpec(this, _StimulusPlugin_brand);
		_defineProperty(this, "name", "stimulus");
		_defineProperty(this, "selectors", ["[data-controller]"]);
		_classPrivateFieldInitSpec(this, _application$1, null);
		this.setApplication(application);
	}
	setApplication(application) {
		_classPrivateFieldSet2(_application$1, this, typeof application?.getControllerForElementAndIdentifier === "function" ? application : null);
	}
	canHandle(element) {
		return _assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, element).length > 0;
	}
	parse(element, query = queryElements) {
		const reference = (element) => ({
			element,
			controllers: _assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, element)
		});
		const controllers = _assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, element);
		const instances = Object.fromEntries(controllers.map((identifier) => [identifier, _assertClassBrand(_StimulusPlugin_brand, this, _getController).call(this, element, identifier)]));
		const values = _assertClassBrand(_StimulusPlugin_brand, this, _parseControllerAttrs).call(this, element, "value", parseAttributeValue);
		const classes = _assertClassBrand(_StimulusPlugin_brand, this, _parseClasses).call(this, element);
		return {
			type: "stimulus",
			element,
			data: {
				controllers,
				runtimeAvailable: Boolean(_classPrivateFieldGet2(_application$1, this)),
				connectedControllers: controllers.filter((identifier) => instances[identifier]),
				values,
				valueStates: _assertClassBrand(_StimulusPlugin_brand, this, _resolveValueStates).call(this, element, controllers, instances, values),
				targets: _assertClassBrand(_StimulusPlugin_brand, this, _resolveTargets).call(this, element, controllers, instances),
				actions: _assertClassBrand(_StimulusPlugin_brand, this, _resolveActions).call(this, element, controllers, instances),
				classes,
				classStates: _assertClassBrand(_StimulusPlugin_brand, this, _resolveClassStates).call(this, controllers, instances, classes),
				outlets: _assertClassBrand(_StimulusPlugin_brand, this, _resolveOutlets).call(this, element, controllers, instances, query),
				children: scopedChildren(element, "[data-controller]").map(reference),
				parents: ancestors(element, "[data-controller]").map(reference)
			}
		};
	}
	getDisplayName(element) {
		const controllers = _assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, element);
		if (controllers.length > 1) return `${controllers[0]} (+${controllers.length - 1})`;
		return controllers[0] || "Stimulus";
	}
	getRelationships(element, data) {
		const edges = [];
		const d = data.data;
		for (const controller of d.controllers) {
			const outlets = d.outlets[controller];
			if (!outlets) continue;
			for (const outlet of outlets) for (const target of outlet.elements) if (target?.isConnected) edges.push({
				source: element,
				target,
				type: "outlet",
				label: `${controller} -> ${outlet.name}`
			});
		}
		for (const child of d.children) if (child.element?.isConnected) edges.push({
			source: element,
			target: child.element,
			type: "controller-parent",
			label: `parent of ${child.controllers.join(", ")}`
		});
		return edges;
	}
	getMonitoredEvents() {
		const COMMON_DISPATCH_NAMES = [
			"open",
			"close",
			"toggle",
			"expand",
			"collapse",
			"navigate",
			"change",
			"submit",
			"submitted",
			"clear",
			"search",
			"perform",
			"copied",
			"selected",
			"removed",
			"added",
			"show",
			"hide",
			"activate",
			"deactivate",
			"connect",
			"disconnect",
			"refresh"
		];
		return {
			static: ["stimulus:connect", "stimulus:disconnect"],
			dynamic(elements) {
				const controllers = /* @__PURE__ */ new Set();
				const actionEvents = /* @__PURE__ */ new Set();
				for (const el of elements) for (const name of (el.getAttribute("data-controller") || "").split(/\s+/).filter(Boolean)) if (name !== "live") controllers.add(name);
				for (const el of elements) {
					const walk = el.querySelectorAll("[data-action]");
					for (const target of [el, ...walk]) {
						const raw = target.getAttribute("data-action");
						if (!raw) continue;
						for (const descriptor of raw.split(/\s+/).filter(Boolean)) {
							const match = descriptor.match(/^([^->\s]+)->/) || descriptor.match(/^([^:]+:[^->\s]+)/);
							if (match) {
								const eventPart = match[1];
								if (eventPart.includes(":")) {
									const prefix = eventPart.split(":")[0];
									if (controllers.has(prefix)) actionEvents.add(eventPart);
								}
							}
						}
					}
				}
				for (const name of controllers) for (const suffix of COMMON_DISPATCH_NAMES) actionEvents.add(`${name}:${suffix}`);
				return [...actionEvents];
			}
		};
	}
	matchesAttribute(name) {
		return [
			"id",
			"class",
			"data-controller",
			"data-action"
		].includes(name) || /^data-.+-(value|target|class|outlet|param)$/.test(name);
	}
	hasExternalChanges(_element, { data }, query) {
		for (const [controller, outlets] of Object.entries(data.outlets)) for (const outlet of outlets) {
			if (!outlet.selector) continue;
			const matches = query(outlet.selector);
			const elements = data.connectedControllers.includes(controller) ? matches.filter((candidate) => (candidate.getAttribute("data-controller") ?? "").split(/\s+/).includes(outlet.name)) : matches;
			if (!sameElements(outlet.elements, elements)) return true;
		}
		return false;
	}
};
function _parseControllers(element) {
	return (element.getAttribute("data-controller") || "").split(/\s+/).filter((controller) => controller && controller !== "live");
}
function _getController(element, identifier) {
	if (!_classPrivateFieldGet2(_application$1, this)) return null;
	try {
		return _classPrivateFieldGet2(_application$1, this).getControllerForElementAndIdentifier(element, identifier) || null;
	} catch {
		return null;
	}
}
function _staticDefinition(instance, property, fallback) {
	const prototype = instance && Object.getPrototypeOf(instance);
	const ownConstructor = prototype && Object.getOwnPropertyDescriptor(prototype, "constructor");
	let constructor = ownConstructor && "value" in ownConstructor ? ownConstructor.value : null;
	while (constructor && constructor !== Function.prototype) {
		const descriptor = Object.getOwnPropertyDescriptor(constructor, property);
		if (descriptor) return "value" in descriptor ? descriptor.value : fallback;
		constructor = Object.getPrototypeOf(constructor);
	}
	return fallback;
}
function _hasMethod(instance, method) {
	let current = instance;
	while (current) {
		const descriptor = Object.getOwnPropertyDescriptor(current, method);
		if (descriptor) return "value" in descriptor && typeof descriptor.value === "function";
		current = Object.getPrototypeOf(current);
	}
	return false;
}
function _resolveValueStates(element, controllers, instances, configured) {
	const result = {};
	for (const controller of controllers) {
		const current = configured[controller] || {};
		const definitions = _assertClassBrand(_StimulusPlugin_brand, this, _staticDefinition).call(this, instances[controller], "values", {}) ?? {};
		result[controller] = [...new Set([...Object.keys(definitions), ...Object.keys(current)])].map((name) => {
			const attribute = `data-${controller}-${name.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}-value`;
			const present = element.hasAttribute(attribute);
			let value = current[name];
			const definition = Object.getOwnPropertyDescriptor(definitions, name);
			if (!present) value = safeValue(_assertClassBrand(_StimulusPlugin_brand, this, _valueDefault).call(this, definition && "value" in definition ? definition.value : void 0), name);
			return {
				name,
				value,
				status: instances[controller] ? present ? CONNECTED$1 : "default" : CONFIGURED
			};
		});
	}
	return result;
}
function _valueDefault(definition) {
	let type = definition;
	if (definition && typeof definition === "object") {
		const value = Object.getOwnPropertyDescriptor(definition, "default");
		if (value && "value" in value) return value.value;
		const declaredType = Object.getOwnPropertyDescriptor(definition, "type");
		type = declaredType && "value" in declaredType ? declaredType.value : null;
	}
	if (type === Array) return [];
	if (type === Boolean) return false;
	if (type === Number) return 0;
	if (type === Object) return {};
	if (type === String) return "";
}
function _resolveClassStates(controllers, instances, configured) {
	const result = {};
	for (const controller of controllers) {
		const current = configured[controller] || {};
		const declared = _assertClassBrand(_StimulusPlugin_brand, this, _staticDefinition).call(this, instances[controller], "classes", []);
		result[controller] = [...new Set([...Array.isArray(declared) ? declared : [], ...Object.keys(current)])].map((name) => ({
			name,
			value: current[name] || "not configured",
			status: current[name] ? instances[controller] ? CONNECTED$1 : CONFIGURED : MISSING
		}));
	}
	return result;
}
function _parseControllerAttrs(element, suffix, transform) {
	const result = {};
	for (const controller of _assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, element)) {
		const prefix = `data-${controller}-`;
		for (const attr of element.attributes) {
			if (!attr.name.startsWith(prefix) || !attr.name.endsWith(`-${suffix}`)) continue;
			const name = attr.name.slice(prefix.length, -suffix.length - 1);
			if (!name) continue;
			const key = name.replace(/(?:[_-])([a-z0-9])/g, (_, letter) => letter.toUpperCase());
			(result[controller] ??= {})[key] = transform(attr.value, key);
		}
	}
	return result;
}
function _resolveTargets(element, controllers, instances = {}) {
	const result = {};
	for (const controller of controllers) {
		const scope = element;
		const elements = [];
		const attr = `data-${controller}-target`;
		const candidates = scope.querySelectorAll(`[${attr}]`);
		for (const candidate of candidates) if (_assertClassBrand(_StimulusPlugin_brand, this, _isInScope).call(this, candidate, element, controller)) {
			const names = (candidate.getAttribute(attr) ?? "").split(/\s+/).filter(Boolean);
			for (const name of names) elements.push({
				name,
				element: candidate
			});
		}
		if (element.hasAttribute(attr)) {
			const names = (element.getAttribute(attr) ?? "").split(/\s+/).filter(Boolean);
			for (const name of names) elements.push({
				name,
				element
			});
		}
		const declared = _assertClassBrand(_StimulusPlugin_brand, this, _staticDefinition).call(this, instances[controller], "targets", []);
		const names = new Set([...Array.isArray(declared) ? declared : [], ...elements.map((item) => item.name)]);
		if (!names.size) continue;
		result[controller] = {
			elements,
			items: [...names].map((name) => {
				const matches = elements.filter((item) => item.name === name).map((item) => item.element);
				return {
					name,
					elements: matches,
					declared: Array.isArray(declared) && declared.includes(name),
					status: instances[controller] ? matches.length ? CONNECTED$1 : MISSING : matches.length ? DOM_ONLY$1 : MISSING
				};
			})
		};
	}
	return result;
}
function _resolveActions(element, controllers, instances = {}) {
	const result = Object.fromEntries(controllers.map((c) => [c, []]));
	const allElements = [element, ...element.querySelectorAll("[data-action]")];
	for (const candidate of allElements) {
		const raw = candidate.getAttribute("data-action");
		if (!raw) continue;
		const descriptors = raw.split(/\s+/).filter(Boolean);
		for (const descriptor of descriptors) {
			const parsed = parseActionDescriptor(descriptor);
			if (parsed && controllers.includes(parsed.controller)) {
				if (_assertClassBrand(_StimulusPlugin_brand, this, _isInScope).call(this, candidate, element, parsed.controller)) {
					const action = {
						event: parsed.event || _assertClassBrand(_StimulusPlugin_brand, this, _defaultActionEvent).call(this, candidate),
						method: parsed.method,
						element: candidate,
						status: instances[parsed.controller] ? _assertClassBrand(_StimulusPlugin_brand, this, _hasMethod).call(this, instances[parsed.controller], parsed.method) ? CONNECTED$1 : "missing-method" : DOM_ONLY$1
					};
					const params = parseActionParameters(candidate, parsed.controller);
					if (parsed.scope) action.scope = parsed.scope;
					if (parsed.filters.length) action.filters = parsed.filters;
					if (parsed.options.length) action.options = parsed.options;
					if (Object.keys(params).length) action.params = params;
					result[parsed.controller].push(action);
				}
			}
		}
	}
	return result;
}
function _defaultActionEvent(element) {
	return {
		BUTTON: "click",
		FORM: "submit",
		INPUT: "input",
		TEXTAREA: "input",
		SELECT: "change",
		DETAILS: "toggle"
	}[element.tagName] || "default";
}
function _resolveOutlets(element, controllers, instances, query) {
	const result = {};
	for (const controller of controllers) {
		const configured = /* @__PURE__ */ new Map();
		for (const attr of element.attributes) {
			const match = attr.name.match(new RegExp(`^data-${controller}-(.+)-outlet$`));
			if (match) {
				const name = match[1];
				const selector = attr.value;
				const elements = [...query(selector)];
				configured.set(name, {
					name,
					selector,
					elements
				});
			}
		}
		const declared = _assertClassBrand(_StimulusPlugin_brand, this, _staticDefinition).call(this, instances[controller], "outlets", []);
		const names = new Set([...Array.isArray(declared) ? declared : [], ...configured.keys()]);
		if (!names.size) continue;
		result[controller] = [...names].map((name) => {
			const item = configured.get(name) || {
				name,
				selector: "",
				elements: []
			};
			const elements = instances[controller] ? item.elements.filter((candidate) => (candidate.getAttribute("data-controller") || "").split(/\s+/).includes(name)) : item.elements;
			return {
				...item,
				elements,
				declared: Array.isArray(declared) && declared.includes(name),
				status: instances[controller] ? !item.selector || !elements.length ? MISSING : CONNECTED$1 : item.selector ? CONFIGURED : MISSING
			};
		});
	}
	return result;
}
function _parseClasses(element) {
	return _assertClassBrand(_StimulusPlugin_brand, this, _parseControllerAttrs).call(this, element, "class", (v) => v);
}
function _isInScope(candidate, root, controller) {
	if (candidate === root) return true;
	let parent = candidate.parentElement;
	while (parent && parent !== root) {
		if (parent.hasAttribute("data-controller")) {
			if (_assertClassBrand(_StimulusPlugin_brand, this, _parseControllers).call(this, parent).includes(controller)) return false;
		}
		parent = parent.parentElement;
	}
	return parent === root;
}
var _TurboPlugin_brand = /* @__PURE__ */ new WeakSet();
var TurboPlugin = class {
	constructor() {
		_classPrivateMethodInitSpec(this, _TurboPlugin_brand);
		_defineProperty(this, "name", "turbo");
		_defineProperty(this, "selectors", ["turbo-frame"]);
	}
	canHandle(element) {
		return element.tagName === "TURBO-FRAME";
	}
	parse(element, query = queryElements) {
		return {
			type: "turbo",
			element,
			data: {
				id: element.id || "(anonymous)",
				src: safeUrl(element.getAttribute("src")),
				loading: element.getAttribute("loading") || "eager",
				disabled: element.hasAttribute("disabled"),
				target: element.getAttribute("target") || "",
				autoscroll: element.hasAttribute("autoscroll"),
				busy: element.hasAttribute("busy"),
				complete: element.hasAttribute("complete"),
				childFrames: _assertClassBrand(_TurboPlugin_brand, this, _findChildFrames).call(this, element),
				parentFrame: _assertClassBrand(_TurboPlugin_brand, this, _findParentFrame).call(this, element),
				linksToFrame: _assertClassBrand(_TurboPlugin_brand, this, _findLinksToFrame).call(this, element, query),
				formsInFrame: _assertClassBrand(_TurboPlugin_brand, this, _findFormsInFrame).call(this, element, query)
			}
		};
	}
	getDisplayName(element) {
		return `Frame: ${element.id || "anonymous"}`;
	}
	getRelationships(element, data) {
		const edges = [];
		const d = data.data;
		for (const child of d.childFrames) if (child.element?.isConnected) edges.push({
			source: element,
			target: child.element,
			type: "frame-nesting",
			label: `contains frame: ${child.id}`
		});
		if (d.parentFrame?.element?.isConnected) edges.push({
			source: d.parentFrame.element,
			target: element,
			type: "frame-nesting",
			label: `parent frame: ${d.parentFrame.id}`
		});
		return edges;
	}
	onEvent(entry, element) {
		if ("turbo:before-stream-render" === entry.event && element?.tagName === "TURBO-STREAM") {
			const action = (element.getAttribute("action") || "unknown").slice(0, 80);
			const target = (element.getAttribute("target") || "").slice(0, 200);
			const targets = (element.getAttribute("targets") || "").slice(0, 500);
			const method = (element.getAttribute("method") || "").slice(0, 80);
			entry.label = `stream: ${action}${target ? ` → #${target}` : targets ? ` → ${targets}` : ""}`;
			entry.detail = {
				action,
				target,
				targets,
				method
			};
			const impacted = [];
			if (target) {
				const found = document.getElementById(target);
				if (found) impacted.push(found);
			}
			if (targets) try {
				impacted.push(...Array.from(document.querySelectorAll(targets)));
			} catch {}
			entry.relatedElements = [...new Set(impacted)].slice(0, 50);
			if (entry.relatedElements[0]) entry.target = entry.relatedElements[0];
			return;
		}
		if (element?.tagName === "TURBO-FRAME") entry.label = `${entry.event.slice(6)}: #${element.id || "anonymous"}`;
	}
	getMonitoredEvents() {
		return { static: [
			"turbo:load",
			"turbo:visit",
			"turbo:render",
			"turbo:before-visit",
			"turbo:before-render",
			"turbo:click",
			"turbo:before-cache",
			"turbo:frame-load",
			"turbo:frame-render",
			"turbo:before-frame-render",
			"turbo:frame-missing",
			"turbo:before-frame-morph",
			"turbo:morph",
			"turbo:submit-start",
			"turbo:submit-end",
			"turbo:before-fetch-request",
			"turbo:before-fetch-response",
			"turbo:fetch-request-error",
			"turbo:before-stream-render"
		] };
	}
	matchesAttribute(name) {
		return [
			"src",
			"loading",
			"disabled",
			"busy",
			"complete",
			"autoscroll",
			"target",
			"data-turbo",
			"data-turbo-frame",
			"data-turbo-permanent"
		].includes(name);
	}
	getPageRules(doc = document) {
		const rules = [];
		const add = (selector, kind, label, detail) => {
			for (const element of doc.querySelectorAll(selector)) {
				if (element.closest("ux-inspector")) continue;
				rules.push({
					kind,
					label,
					detail: detail(element),
					element
				});
			}
		};
		add("[data-turbo-permanent]", "permanent", "Permanent element", (element) => `#${element.id || "(missing id)"}`);
		add("[data-turbo=\"false\"]", "disabled", "Turbo disabled", (element) => `Direct scope on ${element.tagName.toLowerCase()}`);
		for (const element of doc.querySelectorAll("[data-turbo-frame]")) {
			if (element.closest("ux-inspector")) continue;
			const target = element.getAttribute("data-turbo-frame");
			if (element.closest("turbo-frame")?.id === target) continue;
			const detail = target === "_top" ? "_top - page navigation" : doc.getElementById(target ?? "")?.tagName === "TURBO-FRAME" ? `#${target} - resolved` : `#${target} - unresolved`;
			rules.push({
				kind: "frame-target",
				label: "Frame target",
				detail,
				element
			});
		}
		add("turbo-stream-source", "stream-source", "Stream source", (element) => element.getAttribute("src") || element.getAttribute("channel") || "connected");
		return rules;
	}
	hasExternalChanges(element, { data }, query) {
		return !sameSnapshot(data.linksToFrame, _assertClassBrand(_TurboPlugin_brand, this, _findLinksToFrame).call(this, element, query)) || !sameSnapshot(data.formsInFrame, _assertClassBrand(_TurboPlugin_brand, this, _findFormsInFrame).call(this, element, query));
	}
};
function _findChildFrames(element) {
	return Array.from(element.querySelectorAll(":scope > turbo-frame")).map((f) => ({
		id: f.id || "(anonymous)",
		element: f
	}));
}
function _findParentFrame(element) {
	const parent = element.parentElement?.closest("turbo-frame");
	if (!parent) return null;
	return {
		id: parent.id || "(anonymous)",
		element: parent
	};
}
function _findLinksToFrame(element, query) {
	return query("a[href]").filter((link) => link.closest("turbo-frame") === element || link.getAttribute("data-turbo-frame") === element.id).map((link) => ({
		href: safeUrl(link.getAttribute("href")),
		element: link
	}));
}
function _findFormsInFrame(element, query) {
	return query("form").filter((form) => form.closest("turbo-frame") === element || form.getAttribute("data-turbo-frame") === element.id).map((form) => ({
		action: safeUrl(form.getAttribute("action")),
		method: form.getAttribute("method") || "get",
		element: form
	}));
}
const NS = "http://www.w3.org/2000/svg";
const ICONS = {
	back: [["polyline", { points: "15 6 9 12 15 18" }]],
	forward: [["polyline", { points: "9 6 15 12 9 18" }]],
	target: [
		["circle", {
			cx: "12",
			cy: "12",
			r: "7"
		}],
		["circle", {
			cx: "12",
			cy: "12",
			r: "2"
		}],
		["path", { d: "M12 2v3m0 14v3M2 12h3m14 0h3" }]
	],
	components: [["path", { d: "m12 3 8 4.5v9L12 21l-8-4.5v-9L12 3Z" }], ["path", { d: "m4 7.5 8 4.5 8-4.5M12 12v9" }]],
	activity: [["path", { d: "M22 12h-4l-3 9L9 3l-3 9H2" }]],
	overlay: [["path", { d: "m12 3 9 5-9 5-9-5 9-5Z" }], ["path", { d: "m3 13 9 5 9-5" }]],
	"panel-right": [["rect", {
		x: "3",
		y: "3",
		width: "18",
		height: "18",
		rx: "2"
	}], ["path", { d: "M15 3v18M8 9l3 3-3 3" }]],
	copy: [["rect", {
		x: "8",
		y: "8",
		width: "12",
		height: "12",
		rx: "2"
	}], ["path", { d: "M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2" }]]
};
function createIcon(name) {
	const svg = document.createElementNS(NS, "svg");
	svg.setAttribute("viewBox", "0 0 24 24");
	svg.setAttribute("aria-hidden", "true");
	for (const [tag, attrs] of ICONS[name] ?? []) {
		const child = document.createElementNS(NS, tag);
		for (const [key, value] of Object.entries(attrs)) child.setAttribute(key, value);
		svg.appendChild(child);
	}
	return svg;
}
function createIconButton(name, label, onClick) {
	const button = document.createElement("button");
	button.type = "button";
	button.title = label;
	button.setAttribute("aria-label", label);
	button.appendChild(createIcon(name));
	if (onClick) button.addEventListener("click", onClick);
	return button;
}
function el(tag, props = {}, ...children) {
	const node = document.createElement(tag);
	for (const [key, value] of Object.entries(props)) {
		if (value == null) continue;
		if (key === "class") node.className = value;
		else if (key === "text") node.textContent = value;
		else if (key === "dataset" || key === "style") Object.assign(node[key], value);
		else if (key === "on") for (const [type, listener] of Object.entries(value)) node.addEventListener(type, listener);
		else if (key in node) node[key] = value;
		else node.setAttribute(key, String(value));
	}
	node.append(...children.filter((child) => child != null));
	return node;
}
function expandableTextIfLong(node, maxLength = 24) {
	return (node.textContent?.length ?? 0) > maxLength ? expandableText(node) : node;
}
function expandableText(node) {
	node.classList.add("expandable-text");
	node.setAttribute("aria-expanded", "false");
	const toggle = () => {
		const expanded = node.classList.toggle("expanded");
		node.setAttribute("aria-expanded", String(expanded));
	};
	node.addEventListener("click", toggle);
	if (!node.matches("button, a, input, select, textarea, [contenteditable=\"true\"]")) {
		node.tabIndex = 0;
		node.setAttribute("role", "button");
		node.addEventListener("keydown", (event) => {
			if (!["Enter", " "].includes(event.key)) return;
			event.preventDefault();
			toggle();
		});
	}
	return node;
}
function createEmptyState(message, hint) {
	return el("div", { class: "empty" }, el("strong", { text: message }), hint ? el("small", { text: hint }) : null);
}
function componentLabel(element) {
	const id = element.id ? `#${element.id}` : "";
	return element.tagName.toLowerCase() + id;
}
function componentIdentity(element, dataMap, registry) {
	const labels = [element.localName, element.id];
	let framework = "default";
	let name;
	for (const key of dataMap?.keys() ?? []) {
		const plugin = registry.get(key);
		const label = plugin?.getDisplayName(element);
		labels.push(key, label ?? "");
		if (plugin && name === void 0) {
			framework = key;
			name = label;
		}
	}
	return {
		name,
		framework,
		selector: componentLabel(element),
		search: labels.filter(Boolean).join(" ")
	};
}
function frameworkName(name) {
	return name === "livecomponent" ? "Live" : name[0].toUpperCase() + name.slice(1);
}
function reconcileChildren(parent, desired) {
	let current = parent.firstChild;
	for (const node of desired) {
		if (node === current) {
			current = current.nextSibling;
			continue;
		}
		if (node.parentNode === parent && parent.moveBefore) parent.moveBefore(node, current);
		else parent.insertBefore(node, current);
	}
	while (current) {
		const next = current.nextSibling;
		current.remove();
		current = next;
	}
}
var _pulses = /* @__PURE__ */ new WeakMap();
var _registry$5 = /* @__PURE__ */ new WeakMap();
var _eventMonitor$5 = /* @__PURE__ */ new WeakMap();
var ComponentCard = class {
	constructor(registry, eventMonitor) {
		_classPrivateFieldInitSpec(this, _pulses, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _registry$5, void 0);
		_classPrivateFieldInitSpec(this, _eventMonitor$5, void 0);
		_classPrivateFieldSet2(_registry$5, this, registry);
		_classPrivateFieldSet2(_eventMonitor$5, this, eventMonitor);
	}
	render(element, dataMap, identity = componentIdentity(element, dataMap, _classPrivateFieldGet2(_registry$5, this))) {
		const framework = dataMap.keys().next().value || "default";
		const tag = element.tagName.toLowerCase();
		const name = identity.name ?? tag;
		const generatedLiveId = framework === "livecomponent" && /^live-\d+(?:-\d+)?$/.test(element.id);
		const selector = identity.name === void 0 || generatedLiveId ? tag : identity.selector;
		const entries = _classPrivateFieldGet2(_eventMonitor$5, this)?.project(element) ?? [];
		const latest = entries.at(-1);
		const card = el("article", {
			class: "component",
			dataset: { framework }
		});
		const row = el("button", {
			class: "component-row",
			type: "button",
			dataset: { componentLabel: `${name}, ${frameworkName(framework)} component` }
		}, el("span", { class: "identity" }, el("strong", { text: name }), el("small", {
			class: "selector",
			text: selector
		})), createIcon("forward"));
		card.appendChild(row);
		this.updateActivity(card, entries.length, latest?.label || latest?.event || "");
		return card;
	}
	updateActivity(row, count, lastLabel = "") {
		const action = row?.matches?.(".component-row") ? row : row?.querySelector?.(".component-row");
		if (!action) return false;
		let activity = action.querySelector(".activity");
		if (!count) {
			cancelAnimationFrame(_classPrivateFieldGet2(_pulses, this).get(action) ?? 0);
			_classPrivateFieldGet2(_pulses, this).delete(action);
			action.classList.remove("activity-pulse");
			activity?.remove();
			action.setAttribute("aria-label", action.dataset.componentLabel ?? "");
			return true;
		}
		if (!activity) {
			activity = el("span", { class: "activity" });
			action.insertBefore(activity, action.lastElementChild);
		}
		const label = `${count} captured event${count === 1 ? "" : "s"}`;
		const title = lastLabel ? `${label} · Latest: ${lastLabel}` : label;
		if (activity.textContent === String(count) && activity.title === title) return true;
		const increased = count > Number(activity.textContent);
		activity.textContent = String(count);
		activity.setAttribute("aria-label", label);
		activity.title = title;
		action.setAttribute("aria-label", `${action.dataset.componentLabel}, ${label}`);
		if (increased && action.isConnected) {
			action.classList.remove("activity-pulse");
			cancelAnimationFrame(_classPrivateFieldGet2(_pulses, this).get(action) ?? 0);
			_classPrivateFieldGet2(_pulses, this).set(action, requestAnimationFrame(() => {
				_classPrivateFieldGet2(_pulses, this).delete(action);
				if (action.isConnected) action.classList.add("activity-pulse");
			}));
		}
		return true;
	}
	destroy() {
		for (const frame of _classPrivateFieldGet2(_pulses, this).values()) cancelAnimationFrame(frame);
		_classPrivateFieldGet2(_pulses, this).clear();
	}
};
var _state$5 = /* @__PURE__ */ new WeakMap();
var _registry$4 = /* @__PURE__ */ new WeakMap();
var _eventMonitor$4 = /* @__PURE__ */ new WeakMap();
var _visual$1 = /* @__PURE__ */ new WeakMap();
var _cardRenderer = /* @__PURE__ */ new WeakMap();
var _rows$1 = /* @__PURE__ */ new WeakMap();
var _targets = /* @__PURE__ */ new WeakMap();
var _lifetime$7 = /* @__PURE__ */ new WeakMap();
var _selectedPageRule = /* @__PURE__ */ new WeakMap();
var _pageRuleRows = /* @__PURE__ */ new WeakMap();
var _ComponentList_brand = /* @__PURE__ */ new WeakSet();
var ComponentList = class {
	constructor(state, registry, eventMonitor, visual) {
		_classPrivateMethodInitSpec(this, _ComponentList_brand);
		_defineProperty(this, "element", el("div", {
			class: "components",
			"aria-live": "polite"
		}));
		_classPrivateFieldInitSpec(this, _state$5, void 0);
		_classPrivateFieldInitSpec(this, _registry$4, void 0);
		_classPrivateFieldInitSpec(this, _eventMonitor$4, void 0);
		_classPrivateFieldInitSpec(this, _visual$1, void 0);
		_classPrivateFieldInitSpec(this, _cardRenderer, void 0);
		_classPrivateFieldInitSpec(this, _rows$1, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _targets, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldInitSpec(this, _lifetime$7, new AbortController());
		_classPrivateFieldInitSpec(this, _selectedPageRule, null);
		_classPrivateFieldInitSpec(this, _pageRuleRows, /* @__PURE__ */ new Map());
		_classPrivateFieldSet2(_state$5, this, state);
		_classPrivateFieldSet2(_registry$4, this, registry);
		_classPrivateFieldSet2(_eventMonitor$4, this, eventMonitor);
		_classPrivateFieldSet2(_visual$1, this, visual);
		_classPrivateFieldSet2(_cardRenderer, this, new ComponentCard(registry, eventMonitor));
		const { signal } = _classPrivateFieldGet2(_lifetime$7, this);
		this.element.addEventListener("keydown", (event) => _assertClassBrand(_ComponentList_brand, this, _onComponentKeydown).call(this, event), { signal });
		for (const type of [
			"click",
			"pointerover",
			"pointerout",
			"focusin",
			"focusout"
		]) this.element.addEventListener(type, (event) => _assertClassBrand(_ComponentList_brand, this, _interact).call(this, event), { signal });
	}
	refresh(filters, query, selected) {
		_classPrivateFieldGet2(_pageRuleRows, this).clear();
		const nodes = [];
		const pageRules = _assertClassBrand(_ComponentList_brand, this, _pageRules).call(this, filters, query);
		if (pageRules) nodes.push(pageRules);
		if (_classPrivateFieldGet2(_selectedPageRule, this) && ![..._classPrivateFieldGet2(_pageRuleRows, this).values()].some((rule) => _assertClassBrand(_ComponentList_brand, this, _isSelectedRule).call(this, rule))) this.clearPageRuleSelection();
		const nextRows = /* @__PURE__ */ new Map();
		for (const element of _classPrivateFieldGet2(_state$5, this).elements) {
			const dataMap = _classPrivateFieldGet2(_state$5, this).get(element);
			if (!dataMap || ![...dataMap.keys()].some((name) => filters.has(name))) continue;
			const identity = componentIdentity(element, dataMap, _classPrivateFieldGet2(_registry$4, this));
			const signature = identity.search;
			if (query && !signature.toLowerCase().includes(query)) continue;
			const previous = _classPrivateFieldGet2(_rows$1, this).get(element);
			const row = previous?.signature === signature ? previous : {
				element: _classPrivateFieldGet2(_cardRenderer, this).render(element, dataMap, identity),
				signature
			};
			_classPrivateFieldGet2(_targets, this).set(row.element, {
				element,
				framework: dataMap.keys().next().value
			});
			nextRows.set(element, row);
			nodes.push(row.element);
		}
		_classPrivateFieldSet2(_rows$1, this, nextRows);
		this.select(selected);
		if (!nodes.length) nodes.push(createEmptyState(_classPrivateFieldGet2(_state$5, this).size ? "No matching components" : "No UX components detected", _classPrivateFieldGet2(_state$5, this).size ? "Change the search or framework filters." : "Pick a component from the page or interact with it to begin."));
		reconcileChildren(this.element, nodes);
	}
	clearActivities() {
		for (const row of _classPrivateFieldGet2(_rows$1, this).values()) _classPrivateFieldGet2(_cardRenderer, this).updateActivity(row.element, 0);
	}
	destroy() {
		_classPrivateFieldGet2(_lifetime$7, this).abort();
		_classPrivateFieldGet2(_cardRenderer, this).destroy();
		_classPrivateFieldGet2(_rows$1, this).clear();
		_classPrivateFieldGet2(_pageRuleRows, this).clear();
		_classPrivateFieldSet2(_selectedPageRule, this, null);
		_classPrivateFieldSet2(_targets, this, /* @__PURE__ */ new WeakMap());
		this.element.replaceChildren();
	}
	updateActivity(component) {
		const row = _classPrivateFieldGet2(_rows$1, this).get(component)?.element;
		if (!row) return;
		const entries = _classPrivateFieldGet2(_eventMonitor$4, this)?.project(component) ?? [];
		const latest = entries.at(-1);
		_classPrivateFieldGet2(_cardRenderer, this).updateActivity(row, entries.length, latest?.label || latest?.event || "");
	}
	select(element) {
		if (element) this.clearPageRuleSelection();
		for (const [candidate, { element: card }] of _classPrivateFieldGet2(_rows$1, this)) {
			const selected = candidate === element;
			card.classList.toggle("selected", selected);
			const action = card.querySelector(".component-row");
			if (selected) action?.setAttribute("aria-current", "page");
			else action?.removeAttribute("aria-current");
		}
	}
	clearPageRuleSelection() {
		if (!_classPrivateFieldGet2(_selectedPageRule, this)) return false;
		_classPrivateFieldSet2(_selectedPageRule, this, null);
		_assertClassBrand(_ComponentList_brand, this, _syncPageRuleSelection).call(this);
		_classPrivateFieldGet2(_visual$1, this).onClearPreview?.();
		_classPrivateFieldGet2(_visual$1, this).onClearSelection?.();
		return true;
	}
};
function _pageRules(filters, query) {
	if (!filters.has("turbo")) return null;
	const rules = (_classPrivateFieldGet2(_registry$4, this).collectPageRules?.() ?? []).filter((rule) => `${rule.kind} ${rule.label} ${rule.detail ?? ""}`.toLowerCase().includes(query));
	if (!rules.length) return null;
	return el("section", {
		class: "group",
		dataset: { pageRules: "" }
	}, el("h2", {
		class: "title",
		text: "Turbo page rules"
	}), el("div", { class: "content" }, ...rules.map((rule) => _assertClassBrand(_ComponentList_brand, this, _pageRule).call(this, rule))));
}
function _pageRule(rule) {
	const target = {
		element: rule.element,
		framework: rule.framework,
		label: rule.label
	};
	const row = el("button", {
		class: "key-value page-rule",
		type: "button",
		"aria-pressed": String(_assertClassBrand(_ComponentList_brand, this, _isSelectedRule).call(this, rule))
	}, el("strong", {
		class: "key",
		text: rule.label
	}), rule.detail ? el("span", {
		class: "value",
		text: rule.detail
	}) : null);
	_classPrivateFieldGet2(_targets, this).set(row, target);
	_classPrivateFieldGet2(_pageRuleRows, this).set(row, rule);
	return row;
}
function _interact(event) {
	const row = event.target.closest(".component, .page-rule");
	const target = row && _classPrivateFieldGet2(_targets, this).get(row);
	if (!target) return;
	const related = event.relatedTarget;
	if (related instanceof Node && row.contains(related)) return;
	if (event.type === "click") if (row.matches(".component")) this.element.dispatchEvent(new CustomEvent("drill-into", { detail: target }));
	else {
		const rule = _classPrivateFieldGet2(_pageRuleRows, this).get(row);
		if (_assertClassBrand(_ComponentList_brand, this, _isSelectedRule).call(this, rule)) {
			this.clearPageRuleSelection();
			return;
		}
		_classPrivateFieldSet2(_selectedPageRule, this, rule);
		_assertClassBrand(_ComponentList_brand, this, _syncPageRuleSelection).call(this);
		target.element.scrollIntoView({
			block: "center",
			behavior: "smooth"
		});
		_classPrivateFieldGet2(_visual$1, this).onSelect?.(target);
	}
	else if (event.type === "pointerover" || event.type === "focusin") _classPrivateFieldGet2(_visual$1, this).onPreview?.(target);
	else _classPrivateFieldGet2(_visual$1, this).onClearPreview?.();
}
function _isSelectedRule(rule) {
	return _classPrivateFieldGet2(_selectedPageRule, this)?.element === rule.element && _classPrivateFieldGet2(_selectedPageRule, this).kind === rule.kind;
}
function _syncPageRuleSelection() {
	for (const [row, rule] of _classPrivateFieldGet2(_pageRuleRows, this)) row.setAttribute("aria-pressed", String(_assertClassBrand(_ComponentList_brand, this, _isSelectedRule).call(this, rule)));
}
function _onComponentKeydown(event) {
	if (![
		"ArrowUp",
		"ArrowDown",
		"Home",
		"End"
	].includes(event.key)) return;
	const rows = [...this.element.querySelectorAll(".component-row")];
	if (!rows.length) return;
	const current = rows.indexOf(event.target?.closest?.(".component-row"));
	if (current < 0) return;
	const next = event.key === "Home" ? 0 : event.key === "End" ? rows.length - 1 : Math.min(rows.length - 1, Math.max(0, current + (event.key === "ArrowDown" ? 1 : -1)));
	event.preventDefault();
	rows[next].focus();
}
var _TreeViewer;
var TreeViewer = class TreeViewer {
	static render(data, maxDepth = 4) {
		const container = el("div", { class: "tree" });
		_buildNodes.call(TreeViewer, container, data, 0, maxDepth, /* @__PURE__ */ new WeakSet());
		return container;
	}
};
_TreeViewer = TreeViewer;
function _buildNodes(parent, data, depth, maxDepth, seen) {
	if (data === null || data === void 0) {
		parent.append(el("div", { class: "row" }, el("span", {
			class: "v-nul",
			text: "null"
		})));
		return;
	}
	if (typeof data !== "object") {
		parent.append(el("div", { class: "row" }, el("span", {
			class: _valueClass.call(_TreeViewer, data),
			text: _formatValue.call(_TreeViewer, data)
		})));
		return;
	}
	if (seen.has(data)) {
		parent.append(el("div", { class: "row" }, el("span", {
			class: "type",
			text: "[circular]"
		})));
		return;
	}
	seen.add(data);
	if (Array.isArray(data)) {
		parent.classList.add("array");
		for (const rawValue of data) {
			const isObject = rawValue !== null && typeof rawValue === "object";
			if (isObject && depth < maxDepth) {
				const nest = el("div", { class: "nest" });
				_buildNodes.call(_TreeViewer, nest, rawValue, depth + 1, maxDepth, seen);
				parent.append(el("details", {
					class: "array-item",
					open: false
				}, el("summary", {}, el("span", {
					class: "type",
					text: Array.isArray(rawValue) ? `Array(${rawValue.length})` : "{...}"
				})), nest));
			} else {
				const value = isObject ? el("span", {
					class: "type",
					text: "[max depth]"
				}) : el("span", {
					class: _valueClass.call(_TreeViewer, rawValue),
					text: _formatValue.call(_TreeViewer, rawValue)
				});
				parent.append(el("div", { class: "row array-item" }, value));
			}
		}
		return;
	}
	const entries = Object.entries(data);
	for (const [key, rawValue] of entries) {
		const value = SENSITIVE_KEY.test(key) ? "[redacted]" : rawValue;
		const isObject = value !== null && typeof value === "object";
		if (isObject && depth < maxDepth) {
			const nest = el("div", { class: "nest" });
			_buildNodes.call(_TreeViewer, nest, value, depth + 1, maxDepth, seen);
			parent.append(el("details", { open: false }, el("summary", {}, el("span", {
				class: "key",
				text: key
			}), el("span", {
				class: "colon",
				text: ":"
			}), el("span", {
				class: "type",
				text: Array.isArray(value) ? "Array(" + value.length + ")" : "{...}"
			})), nest));
		} else {
			const val = isObject ? el("span", {
				class: "type",
				text: "[max depth]"
			}) : el("span", {
				class: _valueClass.call(_TreeViewer, value),
				text: _formatValue.call(_TreeViewer, value)
			});
			parent.append(el("div", { class: "row" }, el("span", {
				class: "key",
				text: key
			}), el("span", {
				class: "colon",
				text: ":"
			}), val));
		}
	}
}
function _valueClass(value) {
	if (value === null || value === void 0) return "v-nul";
	if (typeof value === "string") return "v-str";
	if (typeof value === "number") return "v-num";
	if (typeof value === "boolean") return "v-bool";
	return "type";
}
function _formatValue(value) {
	if (value === null || value === void 0) return "null";
	if (typeof value === "string") return "\"" + value + "\"";
	return String(value);
}
const MAX_DISPLAYED_CLASSES = 2;
function makeField(key, value, options = {}) {
	value = SENSITIVE_KEY.test(key) ? "[redacted]" : value;
	const structured = value !== null && typeof value === "object";
	const row = el("dd", { class: "value" });
	renderValue(row, value, options);
	if (!options.multiline && !options.vertical && typeof value === "string" && value.length > 32) {
		expandableText(row);
		row.dataset.long = "";
	}
	if (options.status) row.prepend(el("span", {
		class: "value-status",
		dataset: { status: options.status },
		text: options.status.replaceAll("-", " ")
	}));
	return el("div", {
		class: "key-value",
		dataset: {
			fieldKey: key,
			...structured ? { structured: "" } : {},
			...options.vertical ? { vertical: "" } : {},
			...options.changed ? { changed: "" } : {}
		},
		title: options.changed ? `Changed from ${formatInline(safeValue(options.previous, key))}` : null
	}, expandableTextIfLong(el("dt", {
		class: "key",
		text: key
	})), structured ? el("dd", {
		class: "value-meta",
		text: structureSummary(value)
	}) : null, row);
}
function renderValue(container, value, options = {}) {
	container.textContent = "";
	container.className = "value";
	if (options.multiline && typeof value === "string") {
		const values = value.split(",").map((item) => item.trim()).filter(Boolean);
		container.append(el("span", { class: "value-list" }, ...values.map((item) => el("span", {
			class: "value-list__item",
			text: item
		}))));
	} else if (value === "") container.append(el("span", {
		class: "nul",
		text: "(empty)"
	}));
	else if (typeof value === "boolean") container.append(el("span", {
		class: "bool",
		text: String(value)
	}));
	else if (value === null || value === void 0) container.append(el("span", {
		class: "nul",
		text: "null"
	}));
	else if (typeof value === "object") container.append(TreeViewer.render(value, 3));
	else if (typeof value === "number") container.append(el("span", {
		class: "num",
		text: String(value)
	}));
	else {
		container.textContent = String(value);
		container.classList.add("string");
	}
}
function makeElementField(labelStr, element, options = {}) {
	const label = options.badge || labelStr;
	const emit = (target, name) => target?.dispatchEvent(new CustomEvent(name, {
		bubbles: true,
		composed: true,
		detail: {
			element,
			framework: options.framework,
			label
		}
	}));
	const pill = expandableText(el("button", {
		class: "target-pill",
		type: "button",
		"aria-label": `${labelStr}: show element on page`,
		"aria-pressed": "false",
		on: {
			pointerenter: (event) => emit(event.currentTarget, "preview-element"),
			pointerleave: (event) => emit(event.currentTarget, "clear-element-preview"),
			focus: (event) => emit(event.currentTarget, "preview-element"),
			blur: (event) => emit(event.currentTarget, "clear-element-preview"),
			click: (event) => {
				event.stopPropagation();
				element.scrollIntoView({
					block: "center",
					behavior: "smooth"
				});
				event.currentTarget?.setAttribute("aria-pressed", "true");
				emit(event.currentTarget, "select-element");
			}
		}
	}, describeElementPill(element)));
	const field = el("div", {
		class: "key-value",
		dataset: {
			fieldKey: `element:${labelStr}`,
			element: ""
		}
	}, expandableTextIfLong(el("dt", {
		class: "key",
		text: labelStr
	})), el("dd", { class: "value" }, options.detail && options.detail !== "connected" ? expandableText(el("span", {
		class: "value-note",
		text: options.detail
	})) : null, pill));
	const parameters = Object.entries(options.parameters || {});
	if (!parameters.length) return field;
	return el("dl", { class: "compound-field" }, field, ...parameters.map(([key, value]) => {
		const parameter = makeField(key, value);
		parameter.classList.add("action-parameter");
		return parameter;
	}));
}
function makeKeyValueList(children, options = {}) {
	const nodes = children.filter(Boolean);
	if (!nodes.length) return null;
	return el("dl", { class: ["key-values", options.class].filter(Boolean).join(" ") }, ...nodes);
}
function formatInline(value) {
	const safe = safeValue(value);
	if (safe && typeof safe === "object") return JSON.stringify(safe);
	return String(safe ?? "null");
}
function structureSummary(value) {
	const count = Array.isArray(value) ? value.length : Object.keys(value).length;
	return `${Array.isArray(value) ? "Array" : "Object"} · ${count}`;
}
function makeGroup(title, children, options = {}) {
	const nodes = children.filter(Boolean);
	const empty = !nodes.length;
	if (empty && !options.empty) return null;
	const body = empty ? null : el("div", { class: "content" }, ...nodes);
	const key = options.key || title.toLowerCase().replace(/[^a-z0-9]+/g, "-").replace(/^-|-$/g, "");
	if (options.static) return el("section", {
		class: "group",
		dataset: {
			group: key,
			static: ""
		}
	}, el("div", { class: "title" }, options.icon ? el("span", { class: "icon" }, createIcon(options.icon)) : null, el("span", {
		class: "name",
		text: title
	})), body);
	return el("details", {
		class: "group",
		open: empty ? false : options.open ?? !options.collapsed,
		dataset: {
			group: key,
			...empty ? { empty: "" } : {}
		}
	}, el("summary", {
		class: "title",
		"aria-disabled": empty ? "true" : null,
		"aria-label": empty ? `${title}, none` : null,
		tabindex: empty ? -1 : null,
		on: empty ? { click: (event) => event.preventDefault() } : null
	}, options.icon ? el("span", { class: "icon" }, createIcon(options.icon)) : null, el("span", {
		class: "name",
		text: title
	})), body);
}
function describeElementPill(element) {
	const tag = element.tagName.toLowerCase();
	const id = element.id ? "#" + element.id : "";
	const cls = element.className && typeof element.className === "string" ? "." + element.className.trim().split(/\s+/).slice(0, MAX_DISPLAYED_CLASSES).join(".") : "";
	const frag = document.createDocumentFragment();
	frag.append(el("span", {
		class: "tag",
		text: tag
	}), el("span", {
		class: "cls",
		text: id + cls
	}));
	return frag;
}
const CONNECTED = "connected";
const DOM_ONLY = "dom-only";
function renderStimulus(d, context = {}) {
	const frag = document.createDocumentFragment();
	const changes = context.changes;
	const controllerStates = [];
	const values = [];
	const targets = [];
	const actions = [];
	const classes = [];
	const unresolvedOutlets = [];
	const qualify = d.controllers.length > 1 ? (controller, key) => `${controller}.${key}` : (_controller, key) => key;
	for (const controller of d.controllers) {
		const connected = d.connectedControllers?.includes(controller);
		const runtimeAvailable = d.runtimeAvailable;
		if (!connected) controllerStates.push(makeField(d.controllers.length > 1 ? `${controller} status` : "status", runtimeAvailable ? "inactive" : "runtime unavailable", { status: runtimeAvailable ? "not-connected" : DOM_ONLY }));
		const controllerValues = d.valueStates?.[controller] || [];
		for (const { name: key, value: val, status } of controllerValues) {
			const change = changes?.get(`values.${controller}.${key}`);
			values.push(makeField(qualify(controller, key), val, {
				changed: Boolean(change),
				previous: change?.previous,
				status
			}));
		}
		const controllerClasses = d.classStates?.[controller] || [];
		for (const item of controllerClasses) classes.push(makeField(qualify(controller, item.name), item.value, { status: item.status }));
		const targetItems = d.targets[controller]?.items || [];
		for (const target of targetItems) {
			if (!target.elements.length) continue;
			for (const [index, element] of target.elements.entries()) {
				const suffix = target.elements.length > 1 ? `[${index + 1}]` : "";
				targets.push(makeElementField(`${qualify(controller, target.name)}${suffix}`, element, {
					framework: "stimulus",
					badge: `${controller}.${target.name} target`,
					detail: target.status === CONNECTED ? null : target.status?.replaceAll("-", " ")
				}));
			}
		}
		const controllerActions = d.actions[controller];
		if (controllerActions?.length) for (const action of controllerActions) {
			const trigger = `${action.event}${(action.filters || []).map((filter) => `.${filter}`).join("")}${action.scope ? `@${action.scope}` : ""}${(action.options || []).map((option) => `:${option}`).join("")}`;
			actions.push(makeElementField(`${qualify(controller, action.method)}()`, action.element, {
				framework: "stimulus",
				badge: `${controller}#${action.method}`,
				detail: [trigger, action.status === CONNECTED ? null : action.status?.replaceAll("-", " ")].filter(Boolean).join(" · "),
				parameters: action.params
			}));
		}
		const controllerOutlets = d.outlets[controller];
		if (controllerOutlets?.length) {
			for (const outlet of controllerOutlets) if (!outlet.elements.length) unresolvedOutlets.push(makeField(`${qualify(controller, outlet.name)} outlet`, outlet.selector || "not configured", {
				status: outlet.status,
				multiline: outlet.selector?.includes(",")
			}));
		}
	}
	const groups = [
		makeGroup("Values", [makeKeyValueList([...values, ...controllerStates])], {
			key: "stimulus-values",
			icon: "components"
		}),
		makeGroup("Classes", [makeKeyValueList(classes)], {
			key: "stimulus-classes",
			icon: "components"
		}),
		makeGroup("Actions", actions, {
			key: "stimulus-actions",
			icon: "activity"
		}),
		makeGroup("Targets", [makeKeyValueList(targets)], {
			key: "stimulus-targets",
			icon: "target"
		}),
		makeGroup("Outlets", [makeKeyValueList(unresolvedOutlets)], {
			key: "stimulus-outlets",
			icon: "overlay"
		})
	].filter(Boolean);
	if (groups.length) {
		const container = document.createElement("div");
		container.className = "groups";
		container.append(...groups);
		frag.append(container);
	}
	return frag;
}
function renderLive(d, context = {}) {
	const frag = document.createDocumentFragment();
	const runtime = d.runtime;
	const changes = context.changes;
	const props = [];
	if ([
		"updating",
		"error",
		"disconnected"
	].includes(runtime.status)) props.push(makeField("status", runtime.status));
	const modelBindings = [];
	const actions = [];
	const listeners = [];
	const configuration = [];
	const propEntries = Object.entries(d.props).filter(([key]) => !key.startsWith("@"));
	if (propEntries.length) for (const [key, val] of propEntries) {
		const change = changes?.get(`props.${key}`);
		props.push(makeField(key, val, {
			changed: Boolean(change),
			previous: change?.previous
		}));
	}
	const parentPropEntries = Object.entries(d.propsFromParent);
	if (parentPropEntries.length) for (const [key, val] of parentPropEntries) {
		const change = changes?.get(`propsFromParent.${key}`);
		props.push(makeField(`${key} · from parent`, val, {
			changed: Boolean(change),
			previous: change?.previous
		}));
	}
	if (d.models.length) for (const model of d.models) {
		modelBindings.push(makeElementField(`${model.name} model`, model.element, {
			framework: "livecomponent",
			badge: `model: ${model.name}`,
			detail: model.modifiers.join(", ")
		}));
		if (!(model.name in d.props) && !(model.name in d.propsFromParent)) props.push(makeField(model.name, model.value));
	}
	if (d.actions.length) for (const action of d.actions) {
		actions.push(makeElementField(`${action.event} → ${action.method}()`, action.element, {
			framework: "livecomponent",
			badge: `action: ${action.method}`
		}));
		for (const [key, value] of Object.entries(action.args || {})) actions.push(makeField(`${action.method}.${key}`, value));
	}
	if (d.listeners.length) for (const listener of d.listeners) listeners.push(makeField(listener.event, `${listener.action}()`));
	if (d.loading.length) for (const item of d.loading) configuration.push(makeElementField(`loading: ${item.action}`, item.element, {
		framework: "livecomponent",
		badge: `loading: ${item.action}`
	}));
	if (d.polling) configuration.push(makeField("poll interval", d.polling.duration));
	const groups = [
		makeGroup("Props", [makeKeyValueList(props)], {
			key: "livecomponent-props",
			icon: "components"
		}),
		makeGroup("Models", [makeKeyValueList(modelBindings)], {
			key: "livecomponent-models",
			icon: "target"
		}),
		makeGroup("Configuration", [makeKeyValueList(configuration)], {
			key: "livecomponent-configuration",
			icon: "components"
		}),
		makeGroup("Actions", [makeKeyValueList(actions)], {
			key: "livecomponent-actions",
			icon: "activity"
		}),
		makeGroup("Listeners", [makeKeyValueList(listeners)], {
			key: "livecomponent-listeners",
			icon: "activity"
		})
	].filter(Boolean);
	if (groups.length) {
		const container = document.createElement("div");
		container.className = "groups";
		container.append(...groups);
		frag.append(container);
	}
	return frag;
}
function renderTurbo(d, context = {}) {
	const frag = document.createDocumentFragment();
	const state = [makeField("loading", d.loading)];
	const actions = [];
	if (d.src) state.push(makeField("src", d.src));
	if (d.target) state.push(makeField("target", d.target));
	for (const key of [
		"disabled",
		"autoscroll",
		"busy",
		"complete"
	]) if (d[key]) state.push(makeField(key, true));
	const links = d.linksToFrame.map((link, index) => makeElementField(`link${d.linksToFrame.length > 1 ? `[${index + 1}]` : ""}`, link.element, {
		framework: "turbo",
		badge: "Frame link",
		detail: link.href
	}));
	const forms = d.formsInFrame.map((form, index) => makeElementField(`form${d.formsInFrame.length > 1 ? `[${index + 1}]` : ""}`, form.element, {
		framework: "turbo",
		badge: "Frame form",
		detail: `${form.method.toUpperCase()} ${form.action || "(current URL)"}`
	}));
	for (const entry of context.events || []) {
		if (entry.event !== "turbo:before-stream-render") continue;
		const detail = entry.detail ?? {};
		const action = detail.action || "stream";
		const target = detail.target ? `#${detail.target}` : detail.targets || "document";
		actions.push(makeField(action, target));
	}
	const groups = [
		makeGroup("Frame", [makeKeyValueList(state)], {
			key: "turbo-frame",
			icon: "components"
		}),
		makeGroup("Actions", [makeKeyValueList(actions)], {
			key: "turbo-actions",
			icon: "activity"
		}),
		makeGroup("Links", [makeKeyValueList(links)], {
			key: "turbo-links",
			icon: "overlay"
		}),
		makeGroup("Forms", [makeKeyValueList(forms)], {
			key: "turbo-forms",
			icon: "overlay"
		})
	].filter(Boolean);
	if (groups.length) {
		const container = document.createElement("div");
		container.className = "groups";
		container.append(...groups);
		frag.append(container);
	}
	return frag;
}
function renderComponent(data, context) {
	switch (data.type) {
		case "stimulus": return renderStimulus(data.data, context);
		case "livecomponent": return renderLive(data.data, context);
		case "turbo": return renderTurbo(data.data, context);
		default: return null;
	}
}
var _registry$3 = /* @__PURE__ */ new WeakMap();
var _render = /* @__PURE__ */ new WeakMap();
var _eventMonitor$3 = /* @__PURE__ */ new WeakMap();
var _relationshipEngine$2 = /* @__PURE__ */ new WeakMap();
var _state$4 = /* @__PURE__ */ new WeakMap();
var _onDrillInto = /* @__PURE__ */ new WeakMap();
var _element$3 = /* @__PURE__ */ new WeakMap();
var _target = /* @__PURE__ */ new WeakMap();
var _identityKey = /* @__PURE__ */ new WeakMap();
var _eventListener$1 = /* @__PURE__ */ new WeakMap();
var _lifetime$6 = /* @__PURE__ */ new WeakMap();
var _previousData = /* @__PURE__ */ new WeakMap();
var _recentChanges = /* @__PURE__ */ new WeakMap();
var _changeTimer = /* @__PURE__ */ new WeakMap();
var _savedGroups = /* @__PURE__ */ new WeakMap();
var _ComponentDetail_brand = /* @__PURE__ */ new WeakSet();
var ComponentDetail = class {
	constructor({ registry, eventMonitor, relationshipEngine, state, render = renderComponent }, onDrillInto, uiState = {}) {
		_classPrivateMethodInitSpec(this, _ComponentDetail_brand);
		_classPrivateFieldInitSpec(this, _registry$3, void 0);
		_classPrivateFieldInitSpec(this, _render, void 0);
		_classPrivateFieldInitSpec(this, _eventMonitor$3, void 0);
		_classPrivateFieldInitSpec(this, _relationshipEngine$2, void 0);
		_classPrivateFieldInitSpec(this, _state$4, void 0);
		_classPrivateFieldInitSpec(this, _onDrillInto, void 0);
		_classPrivateFieldInitSpec(this, _element$3, null);
		_classPrivateFieldInitSpec(this, _target, null);
		_classPrivateFieldInitSpec(this, _identityKey, "");
		_classPrivateFieldInitSpec(this, _eventListener$1, null);
		_classPrivateFieldInitSpec(this, _lifetime$6, new AbortController());
		_classPrivateFieldInitSpec(this, _previousData, null);
		_classPrivateFieldInitSpec(this, _recentChanges, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _changeTimer, void 0);
		_classPrivateFieldInitSpec(this, _savedGroups, /* @__PURE__ */ new Map());
		_classPrivateFieldSet2(_registry$3, this, registry);
		_classPrivateFieldSet2(_render, this, render);
		_classPrivateFieldSet2(_eventMonitor$3, this, eventMonitor);
		_classPrivateFieldSet2(_relationshipEngine$2, this, relationshipEngine);
		_classPrivateFieldSet2(_state$4, this, state);
		_classPrivateFieldSet2(_onDrillInto, this, onDrillInto);
		_classPrivateFieldSet2(_savedGroups, this, new Map(Object.entries(uiState.groups || {})));
	}
	render(target, dataMap) {
		this.destroy();
		_classPrivateFieldSet2(_lifetime$6, this, new AbortController());
		_classPrivateFieldSet2(_target, this, target);
		_classPrivateFieldSet2(_previousData, this, dataMap);
		_classPrivateFieldSet2(_element$3, this, el("div", { class: "detail pane" }));
		_assertClassBrand(_ComponentDetail_brand, this, _replaceContent).call(this, dataMap, null);
		if (_classPrivateFieldGet2(_eventMonitor$3, this)) {
			_classPrivateFieldSet2(_eventListener$1, this, (entry) => {
				if (entry.target !== target && !(entry.target && target.contains(entry.target))) return;
				const footer = _classPrivateFieldGet2(_element$3, this)?.querySelector(".activity-label");
				if (footer) footer.dataset.framework = entry.type || "default";
			});
			_classPrivateFieldGet2(_eventMonitor$3, this).addListener(_classPrivateFieldGet2(_eventListener$1, this));
		}
		const onUpdate = (event) => {
			const detail = event.detail;
			if (detail?.element !== target) return;
			const current = _classPrivateFieldGet2(_state$4, this).get(target);
			if (!current) return;
			const previous = detail.previous || _classPrivateFieldGet2(_previousData, this);
			_classPrivateFieldSet2(_previousData, this, current);
			_assertClassBrand(_ComponentDetail_brand, this, _replaceContent).call(this, current, previous ?? null);
		};
		_classPrivateFieldGet2(_state$4, this).addEventListener?.("component-updated", onUpdate, { signal: _classPrivateFieldGet2(_lifetime$6, this).signal });
		return _classPrivateFieldGet2(_element$3, this);
	}
	destroy() {
		_assertClassBrand(_ComponentDetail_brand, this, _rememberGroups).call(this);
		if (_classPrivateFieldGet2(_eventListener$1, this)) _classPrivateFieldGet2(_eventMonitor$3, this)?.removeListener(_classPrivateFieldGet2(_eventListener$1, this));
		_classPrivateFieldSet2(_eventListener$1, this, null);
		_classPrivateFieldGet2(_lifetime$6, this).abort();
		clearTimeout(_classPrivateFieldGet2(_changeTimer, this));
		_classPrivateFieldGet2(_recentChanges, this).clear();
		_classPrivateFieldSet2(_element$3, this, _classPrivateFieldSet2(_target, this, _classPrivateFieldSet2(_previousData, this, null)));
		_classPrivateFieldSet2(_identityKey, this, "");
	}
	getUiState() {
		_assertClassBrand(_ComponentDetail_brand, this, _rememberGroups).call(this);
		return { groups: Object.fromEntries(_classPrivateFieldGet2(_savedGroups, this)) };
	}
	get activityCount() {
		const target = _classPrivateFieldGet2(_target, this);
		return (target ? _classPrivateFieldGet2(_eventMonitor$3, this)?.project(target) ?? [] : []).length;
	}
};
function _identity(target, dataMap) {
	const { name, framework, selector } = componentIdentity(target, dataMap, _classPrivateFieldGet2(_registry$3, this));
	const identity = name ?? selector;
	const key = JSON.stringify([
		identity,
		framework,
		selector
	]);
	if (key === _classPrivateFieldGet2(_identityKey, this)) return _classPrivateFieldGet2(_element$3, this)?.firstElementChild;
	_classPrivateFieldSet2(_identityKey, this, key);
	const meaningfulSelector = framework !== "livecomponent" && selector !== target.localName;
	const title = el("h2", { text: identity });
	const subtitle = meaningfulSelector ? el("p", {
		class: "detail-selector",
		text: selector
	}) : null;
	return el("section", { class: "detail-head" }, expandableTextIfLong(title), el("span", {
		class: "framework",
		dataset: { framework },
		text: frameworkName(framework)
	}), subtitle ? expandableTextIfLong(subtitle, 32) : null);
}
function _data(dataMap, previousData) {
	const body = el("div", {
		class: "detail-body",
		id: "component-detail-controller",
		role: "region",
		"aria-label": "Component details"
	});
	const target = _classPrivateFieldGet2(_target, this);
	const events = _classPrivateFieldGet2(_eventMonitor$3, this)?.getEntriesForElement(target) ?? [];
	let primary = true;
	for (const [name, data] of dataMap) {
		const changes = _assertClassBrand(_ComponentDetail_brand, this, _changes).call(this, previousData?.get(name), data);
		const plugin = _classPrivateFieldGet2(_registry$3, this).get(name);
		let rendered = _classPrivateFieldGet2(_render, this).call(this, data, {
			changes,
			framework: name,
			events
		});
		let groups = rendered?.matches?.(".groups") ? rendered : rendered?.querySelector?.(".groups");
		if (!groups && rendered && (rendered.childNodes.length || rendered.textContent?.trim())) {
			groups = el("div", { class: "groups" }, makeGroup("Details", [rendered], {
				key: `${name}-details`,
				icon: "components"
			}));
			rendered = groups;
		}
		if (!groups) groups = el("div", { class: "groups" });
		if (primary) _assertClassBrand(_ComponentDetail_brand, this, _mergeGroups).call(this, groups, _assertClassBrand(_ComponentDetail_brand, this, _relationships).call(this, target, name));
		if (!groups.children.length) {
			primary = false;
			continue;
		}
		const detail = el("div", {
			class: "framework-detail",
			dataset: { framework: name }
		}, expandableText(el("h3", { text: `${frameworkName(name)}: ${plugin?.getDisplayName(target) || componentLabel(target)}` })), groups);
		body.appendChild(detail);
		primary = false;
	}
	body.dataset.frameworks = String(body.children.length);
	return body;
}
function _mergeGroups(groups, additions) {
	for (const addition of additions) {
		const existing = groups.querySelector(`:scope > [data-group="${addition.dataset.group}"]`);
		if (!existing) {
			groups.appendChild(addition);
			continue;
		}
		const source = addition.querySelector(":scope > .content");
		const destination = existing.querySelector(":scope > .content");
		if (source && destination) destination.append(...source.childNodes);
	}
}
function _relationships(target, framework) {
	const edges = _classPrivateFieldGet2(_relationshipEngine$2, this)?.getRelatedTo(target) ?? [];
	const groups = {
		parents: [],
		children: [],
		outlets: [],
		related: []
	};
	const seen = Object.fromEntries(Object.keys(groups).map((key) => [key, /* @__PURE__ */ new Set()]));
	for (const edge of edges) {
		const related = edge.source === target ? edge.target : edge.source;
		const name = componentIdentity(related, _classPrivateFieldGet2(_state$4, this).get(related), _classPrivateFieldGet2(_registry$3, this)).name ?? componentLabel(related);
		const [kind, detail] = _assertClassBrand(_ComponentDetail_brand, this, _describeRelationship).call(this, edge, target);
		if (seen[kind].has(related)) continue;
		seen[kind].add(related);
		const selector = related.id ? `#${related.id}` : componentLabel(related);
		const secondary = edge.type === "outlet" ? selector : selector === related.localName ? detail : `${detail} · ${selector}`;
		groups[kind].push(el("button", {
			class: "key-value relation",
			type: "button",
			"aria-label": `${detail} ${name} ${selector}`,
			on: { click: () => _classPrivateFieldGet2(_onDrillInto, this)?.call(this, related) }
		}, el("strong", {
			class: "key",
			text: name
		}), el("small", {
			class: "value",
			text: secondary
		}), createIcon("forward")));
	}
	return [
		["parents", "Parent"],
		["children", "Children"],
		["outlets", "Outlets"],
		["related", "Related"]
	].filter(([key]) => key !== "outlets" || framework !== "turbo").map(([key, title]) => groups[key].length ? makeGroup(title, [el("div", { class: "relations" }, ...groups[key])], {
		key: `${framework}-${key === "parents" ? "parent" : key}`,
		icon: key === "outlets" ? "overlay" : "components"
	}) : null).filter((group) => Boolean(group));
}
function _describeRelationship(edge, target) {
	const outgoing = edge.source === target;
	if (edge.type === "outlet") {
		const outlet = edge.label?.split(" -> ").at(-1) || "component";
		return ["outlets", outgoing ? `${outlet} outlet` : `used by ${outlet} outlet`];
	}
	if ([
		"controller-parent",
		"dom-parent",
		"live-parent",
		"frame-nesting"
	].includes(edge.type)) return [outgoing ? "children" : "parents", `${outgoing ? "child" : "parent"} ${edge.type === "frame-nesting" ? "frame" : "component"}`];
	return ["related", edge.label || edge.type.replaceAll("-", " ")];
}
function _activityFooter() {
	const target = _classPrivateFieldGet2(_target, this);
	return el("div", {
		class: "activity-label group",
		dataset: { framework: (target ? _classPrivateFieldGet2(_eventMonitor$3, this)?.getEntriesForElement(target)?.at(-1) : void 0)?.type || "default" }
	}, el("span", { class: "title" }, el("span", { class: "icon" }, createIcon("activity")), el("span", {
		class: "name",
		text: "Activity"
	})));
}
function _replaceContent(dataMap, previousData) {
	const active = (_classPrivateFieldGet2(_element$3, this)?.getRootNode())?.activeElement ?? null;
	const activeKey = active && _classPrivateFieldGet2(_element$3, this)?.contains(active) ? active.closest(".key-value")?.dataset.fieldKey : null;
	_assertClassBrand(_ComponentDetail_brand, this, _rememberGroups).call(this);
	const content = _assertClassBrand(_ComponentDetail_brand, this, _data).call(this, dataMap, previousData);
	if (!_classPrivateFieldGet2(_savedGroups, this).size) content.querySelectorAll("details.group").forEach((group) => {
		group.open = !group.hasAttribute("data-empty");
	});
	const identity = _assertClassBrand(_ComponentDetail_brand, this, _identity).call(this, _classPrivateFieldGet2(_target, this), dataMap);
	const current = _classPrivateFieldGet2(_element$3, this)?.querySelector(".detail-body");
	if (current) {
		if (identity !== _classPrivateFieldGet2(_element$3, this)?.firstElementChild) _classPrivateFieldGet2(_element$3, this)?.firstElementChild?.replaceWith(identity);
		current.replaceChildren(...content.childNodes);
		current.dataset.frameworks = content.dataset.frameworks;
	} else _classPrivateFieldGet2(_element$3, this)?.append(identity, content, _assertClassBrand(_ComponentDetail_brand, this, _activityFooter).call(this));
	for (const group of _classPrivateFieldGet2(_element$3, this)?.querySelectorAll("details.group[data-group]") ?? []) {
		const details = group;
		const key = details.dataset.group ?? "";
		if (details.hasAttribute("data-empty")) details.open = false;
		else if (_classPrivateFieldGet2(_savedGroups, this).has(key)) details.open = _classPrivateFieldGet2(_savedGroups, this).get(key);
	}
	if (activeKey) ([..._classPrivateFieldGet2(_element$3, this)?.querySelectorAll(".key-value") ?? []].find((candidate) => candidate.dataset.fieldKey === activeKey)?.querySelector("button, [tabindex]"))?.focus({ preventScroll: true });
}
function _rememberGroups() {
	for (const group of _classPrivateFieldGet2(_element$3, this)?.querySelectorAll("details.group[data-group]") ?? []) {
		const details = group;
		_classPrivateFieldGet2(_savedGroups, this).set(details.dataset.group ?? "", details.open);
	}
}
function _changes(previous, current) {
	if (!previous) return _classPrivateFieldGet2(_recentChanges, this);
	let changed = false;
	const compare = (before, after, path, nested = false) => {
		const a = before || {};
		const b = after || {};
		for (const key of new Set([...Object.keys(a), ...Object.keys(b)])) {
			const field = `${path}.${key}`;
			if (nested) compare(a[key], b[key], field);
			else if (!sameSnapshot(a[key], b[key])) {
				_classPrivateFieldGet2(_recentChanges, this).set(field, {
					previous: a[key],
					current: b[key]
				});
				changed = true;
			}
		}
	};
	for (const group of [
		"props",
		"propsFromParent",
		"values"
	]) compare(previous.data?.[group], current.data?.[group], group, group === "values");
	if (changed) {
		clearTimeout(_classPrivateFieldGet2(_changeTimer, this));
		_classPrivateFieldSet2(_changeTimer, this, setTimeout(() => {
			_classPrivateFieldGet2(_recentChanges, this).clear();
			for (const field of _classPrivateFieldGet2(_element$3, this)?.querySelectorAll("[data-changed]") ?? []) {
				field.removeAttribute("data-changed");
				field.removeAttribute("title");
			}
		}, 1800));
	}
	return _classPrivateFieldGet2(_recentChanges, this);
}
var _levels = /* @__PURE__ */ new WeakMap();
var _headers = /* @__PURE__ */ new WeakMap();
var _content = /* @__PURE__ */ new WeakMap();
var _element$2 = /* @__PURE__ */ new WeakMap();
var _DrillStack_brand = /* @__PURE__ */ new WeakSet();
var DrillStack = class extends EventTarget {
	constructor(root, { id = "root", title = "Components" } = {}) {
		super();
		_classPrivateMethodInitSpec(this, _DrillStack_brand);
		_classPrivateFieldInitSpec(this, _levels, []);
		_classPrivateFieldInitSpec(this, _headers, void 0);
		_classPrivateFieldInitSpec(this, _content, void 0);
		_classPrivateFieldInitSpec(this, _element$2, void 0);
		_classPrivateFieldSet2(_headers, this, el("div", { class: "stack-nav" }));
		_classPrivateFieldSet2(_content, this, el("div", { class: "stack-body" }));
		_classPrivateFieldSet2(_element$2, this, el("section", { class: "stack pane" }, _classPrivateFieldGet2(_headers, this), _classPrivateFieldGet2(_content, this)));
		this.push({
			id,
			title,
			content: root
		}, false);
	}
	get element() {
		return _classPrivateFieldGet2(_element$2, this);
	}
	get depth() {
		return _classPrivateFieldGet2(_levels, this).length;
	}
	get current() {
		return _classPrivateFieldGet2(_levels, this).at(-1) ?? null;
	}
	push({ id, title, typePill, content, element, framework }, notify = true) {
		const previous = this.current;
		if (previous) {
			previous.scrollTop = _classPrivateFieldGet2(_content, this).scrollTop;
			previous.content.hidden = true;
		}
		const index = _classPrivateFieldGet2(_levels, this).length;
		const header = el("button", {
			class: "stack-link",
			type: "button",
			"aria-current": "page",
			"aria-label": index ? `Back from ${title}` : title,
			on: { click: () => {
				if (index === _classPrivateFieldGet2(_levels, this).length - 1) this.pop();
				else this.popTo(index);
			} }
		}, createIcon("back"), el("span", {
			class: "stack-title",
			text: title
		}), typePill ? el("span", {
			class: "stack-badge",
			dataset: { framework: framework ?? "" },
			text: typePill
		}) : null);
		const layer = el("div", { class: "stack-page pane" }, content);
		const level = {
			id,
			title,
			header,
			content: layer,
			element,
			scrollTop: 0
		};
		_classPrivateFieldGet2(_levels, this).push(level);
		_classPrivateFieldGet2(_headers, this).appendChild(header);
		_classPrivateFieldGet2(_content, this).appendChild(layer);
		_classPrivateFieldGet2(_content, this).scrollTop = 0;
		_assertClassBrand(_DrillStack_brand, this, _syncNavigation).call(this);
		if (notify) this.dispatchEvent(new CustomEvent("drill-push", { detail: { level } }));
		return level;
	}
	pop() {
		if (this.depth <= 1) return null;
		const removed = _classPrivateFieldGet2(_levels, this).pop();
		removed.header.remove();
		removed.content.remove();
		const current = this.current;
		current.content.hidden = false;
		_classPrivateFieldGet2(_content, this).scrollTop = current.scrollTop;
		_assertClassBrand(_DrillStack_brand, this, _syncNavigation).call(this);
		this.dispatchEvent(new CustomEvent("drill-pop", { detail: {
			removed,
			current
		} }));
		return removed;
	}
	popTo(index) {
		if (index < 0 || index >= this.depth || index === this.depth - 1) return;
		while (this.depth > index + 1) this.pop();
	}
	setRootTitle(title) {
		const root = _classPrivateFieldGet2(_levels, this)[0];
		if (!root) return;
		root.title = title;
		const label = root.header.querySelector(".stack-title");
		if (label) label.textContent = title;
	}
};
function _syncNavigation() {
	const currentIndex = _classPrivateFieldGet2(_levels, this).length - 1;
	const previousIndex = currentIndex - 1;
	for (const [index, level] of _classPrivateFieldGet2(_levels, this).entries()) {
		const current = index === currentIndex;
		level.header.classList.toggle("previous", index === previousIndex);
		if (current) level.header.setAttribute("aria-current", "page");
		else level.header.removeAttribute("aria-current");
		level.header.setAttribute("aria-label", current ? level.title : `Back to ${level.title}`);
	}
	_classPrivateFieldGet2(_element$2, this).classList.toggle("is-drilled", this.depth > 1);
}
var _drillStack = /* @__PURE__ */ new WeakMap();
var _drillDetails = /* @__PURE__ */ new WeakMap();
var _nextDrillId = /* @__PURE__ */ new WeakMap();
var _detailStates = /* @__PURE__ */ new WeakMap();
var _pendingDetail = /* @__PURE__ */ new WeakMap();
var _restoreQueued = /* @__PURE__ */ new WeakMap();
var _destroyed = /* @__PURE__ */ new WeakMap();
var _lifetime$5 = /* @__PURE__ */ new WeakMap();
var _state$3 = /* @__PURE__ */ new WeakMap();
var _registry$2 = /* @__PURE__ */ new WeakMap();
var _eventMonitor$2 = /* @__PURE__ */ new WeakMap();
var _relationshipEngine$1 = /* @__PURE__ */ new WeakMap();
var _activity$1 = /* @__PURE__ */ new WeakMap();
var _callbacks = /* @__PURE__ */ new WeakMap();
var _DetailNavigation_brand = /* @__PURE__ */ new WeakSet();
var _onDetailPreview = /* @__PURE__ */ new WeakMap();
var _onDetailClear = /* @__PURE__ */ new WeakMap();
var _onDetailSelect = /* @__PURE__ */ new WeakMap();
var DetailNavigation = class {
	constructor(state, registry, eventMonitor, relationshipEngine, root, activity, callbacks) {
		_classPrivateMethodInitSpec(this, _DetailNavigation_brand);
		_classPrivateFieldInitSpec(this, _drillStack, void 0);
		_classPrivateFieldInitSpec(this, _drillDetails, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _nextDrillId, 0);
		_classPrivateFieldInitSpec(this, _detailStates, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _pendingDetail, null);
		_classPrivateFieldInitSpec(this, _restoreQueued, false);
		_classPrivateFieldInitSpec(this, _destroyed, false);
		_classPrivateFieldInitSpec(this, _lifetime$5, new AbortController());
		_classPrivateFieldInitSpec(this, _state$3, void 0);
		_classPrivateFieldInitSpec(this, _registry$2, void 0);
		_classPrivateFieldInitSpec(this, _eventMonitor$2, void 0);
		_classPrivateFieldInitSpec(this, _relationshipEngine$1, void 0);
		_classPrivateFieldInitSpec(this, _activity$1, void 0);
		_classPrivateFieldInitSpec(this, _callbacks, void 0);
		_classPrivateFieldInitSpec(this, _onDetailPreview, (event) => _classPrivateFieldGet2(_callbacks, this).preview(event.detail));
		_classPrivateFieldInitSpec(this, _onDetailClear, () => _classPrivateFieldGet2(_callbacks, this).clearPreview());
		_classPrivateFieldInitSpec(this, _onDetailSelect, (event) => _classPrivateFieldGet2(_callbacks, this).select(event.detail));
		_classPrivateFieldSet2(_state$3, this, state);
		_classPrivateFieldSet2(_registry$2, this, registry);
		_classPrivateFieldSet2(_eventMonitor$2, this, eventMonitor);
		_classPrivateFieldSet2(_relationshipEngine$1, this, relationshipEngine);
		_classPrivateFieldSet2(_activity$1, this, activity);
		_classPrivateFieldSet2(_callbacks, this, callbacks);
		_classPrivateFieldSet2(_drillStack, this, new DrillStack(root));
		_classPrivateFieldGet2(_drillStack, this).addEventListener("drill-push", () => callbacks.change());
		_classPrivateFieldGet2(_drillStack, this).addEventListener("drill-pop", (event) => _assertClassBrand(_DetailNavigation_brand, this, _onDrillPop).call(this, event.detail));
		state.addEventListener("component-removed", (event) => _assertClassBrand(_DetailNavigation_brand, this, _onComponentRemoved).call(this, event.detail?.element), { signal: _classPrivateFieldGet2(_lifetime$5, this).signal });
		state.addEventListener("components-cleared", () => _assertClassBrand(_DetailNavigation_brand, this, _onComponentRemoved).call(this, this.focusedComponent), { signal: _classPrivateFieldGet2(_lifetime$5, this).signal });
	}
	get element() {
		return _classPrivateFieldGet2(_drillStack, this).element;
	}
	get focusedComponent() {
		return _classPrivateFieldGet2(_drillStack, this).current?.element ?? null;
	}
	get depth() {
		return _classPrivateFieldGet2(_drillStack, this).depth;
	}
	setRootTitle(title) {
		_classPrivateFieldGet2(_drillStack, this).setRootTitle(title);
	}
	drillInto(element, dataMap = _classPrivateFieldGet2(_state$3, this).get(element)) {
		var _this$nextDrillId;
		if (!dataMap) return;
		_classPrivateFieldGet2(_callbacks, this).showComponents();
		if (_classPrivateFieldGet2(_drillStack, this).current?.element === element) {
			this.restoreActivity();
			_classPrivateFieldGet2(_callbacks, this).open();
			return;
		}
		_classPrivateFieldGet2(_activity$1, this).close(false);
		const locator = _assertClassBrand(_DetailNavigation_brand, this, _componentLocator).call(this, element, dataMap);
		const stateKey = `${locator[1]}:${locator[2]}`;
		const detail = new ComponentDetail({
			registry: _classPrivateFieldGet2(_registry$2, this),
			eventMonitor: _classPrivateFieldGet2(_eventMonitor$2, this),
			relationshipEngine: _classPrivateFieldGet2(_relationshipEngine$1, this),
			state: _classPrivateFieldGet2(_state$3, this)
		}, (related) => this.drillInto(related), _classPrivateFieldGet2(_detailStates, this).get(stateKey));
		const content = el("div", { class: "drill-detail pane" }, detail.render(element, dataMap));
		content.addEventListener("preview-element", _classPrivateFieldGet2(_onDetailPreview, this));
		content.addEventListener("clear-element-preview", _classPrivateFieldGet2(_onDetailClear, this));
		content.addEventListener("select-element", _classPrivateFieldGet2(_onDetailSelect, this));
		let title = element.tagName.toLowerCase();
		const framework = dataMap.keys().next().value || "default";
		const plugin = _classPrivateFieldGet2(_registry$2, this).get(framework);
		if (plugin) title = plugin.getDisplayName(element);
		const id = `component-${_classPrivateFieldSet2(_nextDrillId, this, (_this$nextDrillId = _classPrivateFieldGet2(_nextDrillId, this), ++_this$nextDrillId))}`;
		_classPrivateFieldGet2(_drillDetails, this).set(id, {
			detail,
			stateKey,
			content,
			locator
		});
		_classPrivateFieldGet2(_drillStack, this).push({
			id,
			title,
			typePill: framework === "livecomponent" ? void 0 : element.tagName.toLowerCase() + (element.id ? `#${element.id}` : ""),
			content,
			element,
			framework
		});
		_classPrivateFieldGet2(_callbacks, this).open();
		_classPrivateFieldGet2(_callbacks, this).select({
			element,
			framework
		});
		_classPrivateFieldGet2(_activity$1, this).open(id, content, element, title);
	}
	drillBack() {
		return Boolean(_classPrivateFieldGet2(_drillStack, this).pop());
	}
	restoreActivity() {
		const current = _classPrivateFieldGet2(_drillStack, this).current;
		const record = current && _classPrivateFieldGet2(_drillDetails, this).get(current.id);
		if (this.element.hidden || !current?.element || !record || _classPrivateFieldGet2(_activity$1, this).id === current.id) return;
		_classPrivateFieldGet2(_activity$1, this).open(current.id, record.content, current.element, current.title);
	}
	clearFocus() {
		let cleared = false;
		while (this.drillBack()) cleared = true;
		return cleared;
	}
	suspendForNavigation() {
		const current = _classPrivateFieldGet2(_drillStack, this).current;
		const dataMap = current?.element ? _classPrivateFieldGet2(_state$3, this).get(current.element) : null;
		if (dataMap && current?.element) _classPrivateFieldSet2(_pendingDetail, this, _assertClassBrand(_DetailNavigation_brand, this, _componentLocator).call(this, current.element, dataMap));
		while (this.drillBack());
	}
	resumeAfterNavigation() {
		_assertClassBrand(_DetailNavigation_brand, this, _restorePendingDetail).call(this);
	}
	destroy() {
		_classPrivateFieldSet2(_destroyed, this, true);
		_classPrivateFieldGet2(_lifetime$5, this).abort();
		for (const id of _classPrivateFieldGet2(_drillDetails, this).keys()) _assertClassBrand(_DetailNavigation_brand, this, _destroyDrillDetail).call(this, id);
	}
};
function _componentLocator(element, dataMap) {
	const signature = _assertClassBrand(_DetailNavigation_brand, this, _componentSignature).call(this, element, dataMap);
	const candidates = _classPrivateFieldGet2(_state$3, this).elements.filter((candidate) => {
		const current = _classPrivateFieldGet2(_state$3, this).get(candidate);
		return current && _assertClassBrand(_DetailNavigation_brand, this, _componentSignature).call(this, candidate, current) === signature;
	});
	return [
		element.id,
		signature,
		Math.max(0, candidates.indexOf(element)),
		candidates.length
	];
}
function _componentSignature(element, dataMap) {
	return `${element.tagName.toLowerCase()}|${[...dataMap.keys()].map((name) => {
		return `${name}:${_classPrivateFieldGet2(_registry$2, this).get(name)?.getDisplayName(element) || element.tagName.toLowerCase()}`;
	}).join("|")}`;
}
function _onComponentRemoved(element) {
	const current = _classPrivateFieldGet2(_drillStack, this).current;
	if (!element || !current || current.element !== element) return;
	const record = _classPrivateFieldGet2(_drillDetails, this).get(current.id);
	if (record) _classPrivateFieldSet2(_pendingDetail, this, record.locator);
	while (this.drillBack());
	if (_classPrivateFieldGet2(_restoreQueued, this)) return;
	_classPrivateFieldSet2(_restoreQueued, this, true);
	queueMicrotask(() => {
		_classPrivateFieldSet2(_restoreQueued, this, false);
		_assertClassBrand(_DetailNavigation_brand, this, _restorePendingDetail).call(this);
	});
}
function _restorePendingDetail() {
	if (_classPrivateFieldGet2(_destroyed, this)) return false;
	if (!_classPrivateFieldGet2(_pendingDetail, this)) return false;
	const [id, signature, ordinal, count] = _classPrivateFieldGet2(_pendingDetail, this);
	_classPrivateFieldSet2(_pendingDetail, this, null);
	let match = null;
	if (id) {
		const candidate = document.getElementById(id);
		const dataMap = candidate ? _classPrivateFieldGet2(_state$3, this).get(candidate) : null;
		if (candidate && dataMap && _assertClassBrand(_DetailNavigation_brand, this, _componentSignature).call(this, candidate, dataMap) === signature) match = candidate;
	}
	if (!match) {
		const candidates = _classPrivateFieldGet2(_state$3, this).elements.filter((element) => {
			const dataMap = _classPrivateFieldGet2(_state$3, this).get(element);
			return dataMap && _assertClassBrand(_DetailNavigation_brand, this, _componentSignature).call(this, element, dataMap) === signature;
		});
		if (candidates.length === count) match = candidates[ordinal] || null;
	}
	if (match) this.drillInto(match);
	return Boolean(match);
}
function _onDrillPop({ removed, current }) {
	_assertClassBrand(_DetailNavigation_brand, this, _destroyDrillDetail).call(this, removed.id);
	this.restoreActivity();
	if (current.element) {
		const dataMap = _classPrivateFieldGet2(_state$3, this).get(current.element);
		_classPrivateFieldGet2(_callbacks, this).select({
			element: current.element,
			framework: dataMap?.keys().next().value || "default"
		});
	} else _classPrivateFieldGet2(_callbacks, this).clearSelection();
	_classPrivateFieldGet2(_callbacks, this).change();
}
function _destroyDrillDetail(id) {
	const record = _classPrivateFieldGet2(_drillDetails, this).get(id);
	if (!record) return;
	_classPrivateFieldGet2(_detailStates, this).set(record.stateKey, record.detail.getUiState());
	record.content.removeEventListener("preview-element", _classPrivateFieldGet2(_onDetailPreview, this));
	record.content.removeEventListener("clear-element-preview", _classPrivateFieldGet2(_onDetailClear, this));
	record.content.removeEventListener("select-element", _classPrivateFieldGet2(_onDetailSelect, this));
	if (_classPrivateFieldGet2(_activity$1, this).id === id) _classPrivateFieldGet2(_activity$1, this).close();
	record.detail.destroy();
	_classPrivateFieldGet2(_drillDetails, this).delete(id);
}
var _lifetime$4 = /* @__PURE__ */ new WeakMap();
var _drag = /* @__PURE__ */ new WeakMap();
var _options = /* @__PURE__ */ new WeakMap();
var _ResizeHandle_brand = /* @__PURE__ */ new WeakSet();
var ResizeHandle = class {
	constructor(options) {
		_classPrivateMethodInitSpec(this, _ResizeHandle_brand);
		_defineProperty(this, "element", void 0);
		_classPrivateFieldInitSpec(this, _lifetime$4, new AbortController());
		_classPrivateFieldInitSpec(this, _drag, null);
		_classPrivateFieldInitSpec(this, _options, void 0);
		_classPrivateFieldSet2(_options, this, options);
		this.element = el("div", {
			class: options.className,
			role: "separator",
			tabIndex: 0,
			"aria-label": options.label,
			"aria-orientation": options.axis === "width" ? "vertical" : "horizontal"
		});
		const { signal } = _classPrivateFieldGet2(_lifetime$4, this);
		this.element.addEventListener("pointerdown", (event) => _assertClassBrand(_ResizeHandle_brand, this, _start).call(this, event), { signal });
		this.element.addEventListener("keydown", (event) => {
			const direction = (options.axis === "width" ? ["ArrowLeft", "ArrowRight"] : ["ArrowUp", "ArrowDown"]).indexOf(event.key);
			if (direction < 0) return;
			event.preventDefault();
			_assertClassBrand(_ResizeHandle_brand, this, _resize).call(this, options.read() + (direction === 0 ? 16 : -16));
		}, { signal });
	}
	destroy() {
		_assertClassBrand(_ResizeHandle_brand, this, _stop).call(this);
		_classPrivateFieldGet2(_lifetime$4, this).abort();
	}
};
function _resize(size) {
	const value = _classPrivateFieldGet2(_options, this).write(size);
	this.element.setAttribute("aria-valuenow", String(Math.round(value)));
}
function _start(event) {
	if (event.button !== 0) return;
	event.preventDefault();
	_assertClassBrand(_ResizeHandle_brand, this, _stop).call(this);
	_classPrivateFieldSet2(_drag, this, new AbortController());
	const { signal } = _classPrivateFieldGet2(_drag, this);
	const coordinate = _classPrivateFieldGet2(_options, this).axis === "width" ? "clientX" : "clientY";
	const start = event[coordinate];
	const size = _classPrivateFieldGet2(_options, this).read();
	_classPrivateFieldGet2(_options, this).target.toggleAttribute("data-resizing", true);
	this.element.setPointerCapture?.(event.pointerId);
	this.element.addEventListener("pointermove", (move) => {
		if (move.pointerId === event.pointerId) _assertClassBrand(_ResizeHandle_brand, this, _resize).call(this, size + start - move[coordinate]);
	}, { signal });
	for (const type of [
		"pointerup",
		"pointercancel",
		"lostpointercapture"
	]) this.element.addEventListener(type, () => _assertClassBrand(_ResizeHandle_brand, this, _stop).call(this), { signal });
}
function _stop() {
	_classPrivateFieldGet2(_drag, this)?.abort();
	_classPrivateFieldSet2(_drag, this, null);
	_classPrivateFieldGet2(_options, this).target.removeAttribute("data-resizing");
}
var _timeline$2 = /* @__PURE__ */ new WeakMap();
var _home = /* @__PURE__ */ new WeakMap();
var _restore = /* @__PURE__ */ new WeakMap();
var _id = /* @__PURE__ */ new WeakMap();
var _drawer = /* @__PURE__ */ new WeakMap();
var _resizeHandle$1 = /* @__PURE__ */ new WeakMap();
var _sizes = /* @__PURE__ */ new WeakMap();
var ActivityDrawer = class {
	constructor(timeline, home, restore) {
		_classPrivateFieldInitSpec(this, _timeline$2, void 0);
		_classPrivateFieldInitSpec(this, _home, void 0);
		_classPrivateFieldInitSpec(this, _restore, void 0);
		_classPrivateFieldInitSpec(this, _id, null);
		_classPrivateFieldInitSpec(this, _drawer, null);
		_classPrivateFieldInitSpec(this, _resizeHandle$1, null);
		_classPrivateFieldInitSpec(this, _sizes, /* @__PURE__ */ new WeakMap());
		_classPrivateFieldSet2(_timeline$2, this, timeline);
		_classPrivateFieldSet2(_home, this, home);
		_classPrivateFieldSet2(_restore, this, restore);
	}
	get id() {
		return _classPrivateFieldGet2(_id, this);
	}
	open(id, content, element, query) {
		this.close(false);
		const drawer = el("section", {
			class: "pane drawer",
			id: `component-activity-${id}`,
			"aria-label": `Activity for ${query || "component"}`
		}, _classPrivateFieldGet2(_timeline$2, this).element);
		content.appendChild(drawer);
		const savedHeight = _classPrivateFieldGet2(_sizes, this).get(content);
		if (savedHeight !== void 0) {
			drawer.style.height = `${savedHeight}px`;
			drawer.toggleAttribute("data-sized", true);
		}
		_classPrivateFieldSet2(_resizeHandle$1, this, new ResizeHandle({
			target: drawer,
			axis: "height",
			label: "Resize Activity",
			className: "drawer-resize",
			read: () => drawer.getBoundingClientRect().height,
			write: (height) => {
				const minimum = Number.parseFloat(getComputedStyle(drawer).minHeight) || 40;
				const available = drawer.parentElement?.clientHeight || height;
				const detailMinimum = 6 * (Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16);
				const value = Math.min(Math.max(minimum, available - detailMinimum), Math.max(minimum, height));
				drawer.toggleAttribute("data-sized", true);
				drawer.style.height = `${value}px`;
				_classPrivateFieldGet2(_sizes, this).set(content, value);
				return value;
			}
		}));
		drawer.prepend(_classPrivateFieldGet2(_resizeHandle$1, this).element);
		_classPrivateFieldSet2(_drawer, this, drawer);
		_classPrivateFieldSet2(_id, this, id);
		_classPrivateFieldGet2(_timeline$2, this).configure({
			contextual: true,
			frameworks: null,
			element,
			query: element ? "" : query
		});
		_classPrivateFieldGet2(_timeline$2, this).expandFirstVisible();
	}
	close(restore = true) {
		if (!_classPrivateFieldGet2(_id, this)) return false;
		_classPrivateFieldGet2(_home, this).appendChild(_classPrivateFieldGet2(_timeline$2, this).element);
		_classPrivateFieldGet2(_resizeHandle$1, this)?.destroy();
		_classPrivateFieldGet2(_drawer, this)?.remove();
		_classPrivateFieldSet2(_drawer, this, null);
		_classPrivateFieldSet2(_id, this, null);
		if (restore) _classPrivateFieldGet2(_restore, this).call(this);
		return true;
	}
};
const FRAMEWORKS = [
	["stimulus", "Stimulus"],
	["livecomponent", "Live"],
	["turbo", "Turbo"]
];
var _state$2 = /* @__PURE__ */ new WeakMap();
var _eventMonitor$1 = /* @__PURE__ */ new WeakMap();
var _timeline$1 = /* @__PURE__ */ new WeakMap();
var _host$2 = /* @__PURE__ */ new WeakMap();
var _list$1 = /* @__PURE__ */ new WeakMap();
var _navigation = /* @__PURE__ */ new WeakMap();
var _activity = /* @__PURE__ */ new WeakMap();
var _element$1 = /* @__PURE__ */ new WeakMap();
var _components = /* @__PURE__ */ new WeakMap();
var _componentPanel = /* @__PURE__ */ new WeakMap();
var _activityPanel = /* @__PURE__ */ new WeakMap();
var _search = /* @__PURE__ */ new WeakMap();
var _filters = /* @__PURE__ */ new WeakMap();
var _query$1 = /* @__PURE__ */ new WeakMap();
var _monitorLabel = /* @__PURE__ */ new WeakMap();
var _actions = /* @__PURE__ */ new WeakMap();
var _actionButtons = /* @__PURE__ */ new WeakMap();
var _logTools = /* @__PURE__ */ new WeakMap();
var _activitySearch = /* @__PURE__ */ new WeakMap();
var _activityFilters = /* @__PURE__ */ new WeakMap();
var _activityFilterButtons = /* @__PURE__ */ new WeakMap();
var _lifetime$3 = /* @__PURE__ */ new WeakMap();
var _eventListener = /* @__PURE__ */ new WeakMap();
var _packages = /* @__PURE__ */ new WeakMap();
var _filterButtons = /* @__PURE__ */ new WeakMap();
var _refreshFrame$1 = /* @__PURE__ */ new WeakMap();
var _resizeHandle = /* @__PURE__ */ new WeakMap();
var _view = /* @__PURE__ */ new WeakMap();
var _activityCount = /* @__PURE__ */ new WeakMap();
var _pendingActivity = /* @__PURE__ */ new WeakMap();
var _listDirty = /* @__PURE__ */ new WeakMap();
var _onPreview = /* @__PURE__ */ new WeakMap();
var _onClearPreview = /* @__PURE__ */ new WeakMap();
var _onSelect$1 = /* @__PURE__ */ new WeakMap();
var _onClearSelection = /* @__PURE__ */ new WeakMap();
var _selectedComponent = /* @__PURE__ */ new WeakMap();
var _Panel_brand = /* @__PURE__ */ new WeakSet();
var Panel = class {
	constructor(state, registry, eventMonitor, timeline, host, relationshipEngine, packages = {}) {
		_classPrivateMethodInitSpec(this, _Panel_brand);
		_classPrivateFieldInitSpec(this, _state$2, void 0);
		_classPrivateFieldInitSpec(this, _eventMonitor$1, void 0);
		_classPrivateFieldInitSpec(this, _timeline$1, void 0);
		_classPrivateFieldInitSpec(this, _host$2, void 0);
		_classPrivateFieldInitSpec(this, _list$1, void 0);
		_classPrivateFieldInitSpec(this, _navigation, void 0);
		_classPrivateFieldInitSpec(this, _activity, void 0);
		_classPrivateFieldInitSpec(this, _element$1, void 0);
		_classPrivateFieldInitSpec(this, _components, void 0);
		_classPrivateFieldInitSpec(this, _componentPanel, void 0);
		_classPrivateFieldInitSpec(this, _activityPanel, void 0);
		_classPrivateFieldInitSpec(this, _search, void 0);
		_classPrivateFieldInitSpec(this, _filters, new Set(FRAMEWORKS.map(([name]) => name)));
		_classPrivateFieldInitSpec(this, _query$1, "");
		_classPrivateFieldInitSpec(this, _monitorLabel, void 0);
		_classPrivateFieldInitSpec(this, _actions, {});
		_classPrivateFieldInitSpec(this, _actionButtons, {});
		_classPrivateFieldInitSpec(this, _logTools, void 0);
		_classPrivateFieldInitSpec(this, _activitySearch, void 0);
		_classPrivateFieldInitSpec(this, _activityFilters, new Set(FRAMEWORKS.map(([name]) => name)));
		_classPrivateFieldInitSpec(this, _activityFilterButtons, {});
		_classPrivateFieldInitSpec(this, _lifetime$3, new AbortController());
		_classPrivateFieldInitSpec(this, _eventListener, null);
		_classPrivateFieldInitSpec(this, _packages, void 0);
		_classPrivateFieldInitSpec(this, _filterButtons, {});
		_classPrivateFieldInitSpec(this, _refreshFrame$1, null);
		_classPrivateFieldInitSpec(this, _resizeHandle, void 0);
		_classPrivateFieldInitSpec(this, _view, "components");
		_classPrivateFieldInitSpec(this, _activityCount, 0);
		_classPrivateFieldInitSpec(this, _pendingActivity, /* @__PURE__ */ new Set());
		_classPrivateFieldInitSpec(this, _listDirty, true);
		_classPrivateFieldInitSpec(this, _onPreview, null);
		_classPrivateFieldInitSpec(this, _onClearPreview, null);
		_classPrivateFieldInitSpec(this, _onSelect$1, null);
		_classPrivateFieldInitSpec(this, _onClearSelection, null);
		_classPrivateFieldInitSpec(this, _selectedComponent, null);
		_classPrivateFieldSet2(_state$2, this, state);
		_classPrivateFieldSet2(_eventMonitor$1, this, eventMonitor);
		_classPrivateFieldSet2(_timeline$1, this, timeline);
		_classPrivateFieldSet2(_host$2, this, host);
		_classPrivateFieldSet2(_packages, this, packages);
		_classPrivateFieldSet2(_list$1, this, new ComponentList(state, registry, eventMonitor, {
			onPreview: (target) => _classPrivateFieldGet2(_onPreview, this)?.call(this, target),
			onClearPreview: () => _classPrivateFieldGet2(_onClearPreview, this)?.call(this),
			onSelect: (target) => _classPrivateFieldGet2(_onSelect$1, this)?.call(this, target),
			onClearSelection: () => _classPrivateFieldGet2(_onClearSelection, this)?.call(this)
		}));
		_classPrivateFieldSet2(_element$1, this, el("aside", {
			class: "inspector pane",
			"aria-label": "Symfony UX Inspector"
		}));
		const tools = _assertClassBrand(_Panel_brand, this, _tools).call(this);
		_classPrivateFieldSet2(_resizeHandle, this, new ResizeHandle({
			target: _classPrivateFieldGet2(_element$1, this),
			axis: "width",
			label: "Resize inspector",
			className: "panel-resize",
			read: () => _classPrivateFieldGet2(_element$1, this).getBoundingClientRect().width,
			write: (width) => _classPrivateFieldGet2(_host$2, this).setPanelWidth(width)
		}));
		_classPrivateFieldGet2(_element$1, this).append(_classPrivateFieldGet2(_resizeHandle, this).element, _assertClassBrand(_Panel_brand, this, _header).call(this), tools, _assertClassBrand(_Panel_brand, this, _filterBar).call(this));
		_classPrivateFieldSet2(_components, this, _classPrivateFieldGet2(_list$1, this).element);
		_classPrivateFieldSet2(_activityPanel, this, el("section", {
			id: "panel-activity",
			hidden: true,
			role: "region",
			tabindex: "0",
			"aria-label": "Activity"
		}, _classPrivateFieldGet2(_timeline$1, this).element));
		_classPrivateFieldSet2(_activity, this, new ActivityDrawer(timeline, _classPrivateFieldGet2(_activityPanel, this), () => {
			timeline.configure({
				contextual: false,
				element: null,
				frameworks: _classPrivateFieldGet2(_activityFilters, this),
				query: _classPrivateFieldGet2(_activitySearch, this).value
			});
		}));
		_classPrivateFieldSet2(_navigation, this, new DetailNavigation(state, registry, eventMonitor, relationshipEngine, _classPrivateFieldGet2(_components, this), _classPrivateFieldGet2(_activity, this), {
			showComponents: () => _assertClassBrand(_Panel_brand, this, _switchTab).call(this, "components", false),
			open: () => this.open(),
			change: () => {
				_assertClassBrand(_Panel_brand, this, _updateDrillUi).call(this);
				this.refresh();
			},
			select: (target) => {
				_classPrivateFieldSet2(_selectedComponent, this, target.element);
				_classPrivateFieldGet2(_list$1, this).select(target.element);
				_classPrivateFieldGet2(_onSelect$1, this)?.call(this, target);
			},
			clearSelection: () => {
				_classPrivateFieldSet2(_selectedComponent, this, null);
				_classPrivateFieldGet2(_list$1, this).select(null);
				_classPrivateFieldGet2(_onClearSelection, this)?.call(this);
			},
			preview: (target) => _classPrivateFieldGet2(_onPreview, this)?.call(this, target),
			clearPreview: () => _classPrivateFieldGet2(_onClearPreview, this)?.call(this)
		}));
		_classPrivateFieldSet2(_componentPanel, this, _classPrivateFieldGet2(_navigation, this).element);
		_classPrivateFieldGet2(_componentPanel, this).id = "panel-components";
		_classPrivateFieldGet2(_componentPanel, this).setAttribute("role", "region");
		_classPrivateFieldGet2(_componentPanel, this).setAttribute("tabindex", "0");
		_classPrivateFieldGet2(_componentPanel, this).setAttribute("aria-label", "Components");
		_classPrivateFieldGet2(_element$1, this).append(el("main", {}, _classPrivateFieldGet2(_componentPanel, this), _classPrivateFieldGet2(_activityPanel, this)), _assertClassBrand(_Panel_brand, this, _footer).call(this));
		_classPrivateFieldGet2(_components, this).addEventListener("drill-into", (event) => this.drillInto(event.detail.element));
		const refresh = () => {
			if (_classPrivateFieldGet2(_refreshFrame$1, this) !== null) return;
			_classPrivateFieldSet2(_refreshFrame$1, this, requestAnimationFrame(() => {
				_classPrivateFieldSet2(_refreshFrame$1, this, null);
				_assertClassBrand(_Panel_brand, this, _flush).call(this);
			}));
		};
		for (const name of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared",
			"page-updated"
		]) {
			const listener = () => {
				_classPrivateFieldSet2(_listDirty, this, true);
				refresh();
			};
			state.addEventListener(name, listener, { signal: _classPrivateFieldGet2(_lifetime$3, this).signal });
		}
		_classPrivateFieldSet2(_eventListener, this, (entry, removed = []) => {
			for (const changed of [entry, ...removed]) for (const element of activityElements(changed)) _classPrivateFieldGet2(_pendingActivity, this).add(element);
			refresh();
		});
		eventMonitor?.addListener(_classPrivateFieldGet2(_eventListener, this));
		this.refresh();
	}
	get element() {
		return _classPrivateFieldGet2(_element$1, this);
	}
	get isComponentListVisible() {
		return _classPrivateFieldGet2(_view, this) === "components";
	}
	get focusedComponent() {
		return _classPrivateFieldGet2(_navigation, this).focusedComponent;
	}
	setActionCallbacks(callbacks) {
		_classPrivateFieldSet2(_actions, this, { ...callbacks });
	}
	setVisualCallbacks({ onPreview, onClearPreview, onSelect, onClearSelection }) {
		_classPrivateFieldSet2(_onPreview, this, onPreview ?? null);
		_classPrivateFieldSet2(_onClearPreview, this, onClearPreview ?? null);
		_classPrivateFieldSet2(_onSelect$1, this, onSelect ?? null);
		_classPrivateFieldSet2(_onClearSelection, this, onClearSelection ?? null);
	}
	setTargetModeActive(active) {
		const button = _classPrivateFieldGet2(_actionButtons, this).target;
		const label = active ? "Stop inspecting" : "Inspect page components";
		button.classList.toggle("active", Boolean(active));
		button.setAttribute("aria-pressed", String(Boolean(active)));
		button.setAttribute("aria-label", label);
		button.title = active ? "Click to inspect. Shift-click to continue." : label;
		_classPrivateFieldGet2(_monitorLabel, this).textContent = active ? "Inspecting" : "Watching";
	}
	setOverlayActive(active) {
		const button = _classPrivateFieldGet2(_actionButtons, this).overlay;
		const label = active ? "Hide all components" : "Show all components";
		button.classList.toggle("active", Boolean(active));
		button.setAttribute("aria-pressed", String(Boolean(active)));
		button.setAttribute("aria-label", label);
		_classPrivateFieldGet2(_monitorLabel, this).textContent = active ? "Overlay enabled" : "Watching";
	}
	clearActivities() {
		_classPrivateFieldGet2(_pendingActivity, this).clear();
		_classPrivateFieldGet2(_list$1, this).clearActivities();
		_assertClassBrand(_Panel_brand, this, _updateCounts).call(this);
	}
	open() {
		_classPrivateFieldGet2(_host$2, this)?.open();
	}
	close() {
		_classPrivateFieldGet2(_host$2, this)?.close();
	}
	navigate(view) {
		_assertClassBrand(_Panel_brand, this, _switchTab).call(this, view === "log" ? "log" : "components");
		this.open();
		if (view === "search") {
			while (this.drillBack());
			_classPrivateFieldGet2(_search, this).focus();
		}
	}
	refresh() {
		_classPrivateFieldSet2(_listDirty, this, true);
		_assertClassBrand(_Panel_brand, this, _flush).call(this);
	}
	drillInto(element, dataMap = _classPrivateFieldGet2(_state$2, this).get(element)) {
		_classPrivateFieldGet2(_navigation, this).drillInto(element, dataMap);
	}
	drillBack() {
		return _classPrivateFieldGet2(_list$1, this).clearPageRuleSelection() || _classPrivateFieldGet2(_navigation, this).drillBack();
	}
	clearPageRuleSelection() {
		_classPrivateFieldGet2(_list$1, this).clearPageRuleSelection();
	}
	clearFocus() {
		this.clearPageRuleSelection();
		return _classPrivateFieldGet2(_navigation, this).clearFocus();
	}
	suspendForNavigation() {
		this.clearPageRuleSelection();
		_classPrivateFieldGet2(_navigation, this).suspendForNavigation();
	}
	resumeAfterNavigation() {
		_classPrivateFieldGet2(_navigation, this).resumeAfterNavigation();
	}
	destroy() {
		_classPrivateFieldGet2(_resizeHandle, this).destroy();
		if (_classPrivateFieldGet2(_refreshFrame$1, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_refreshFrame$1, this));
		_classPrivateFieldGet2(_navigation, this).destroy();
		_classPrivateFieldGet2(_list$1, this).destroy();
		_classPrivateFieldGet2(_pendingActivity, this).clear();
		_classPrivateFieldGet2(_lifetime$3, this).abort();
		if (_classPrivateFieldGet2(_eventListener, this)) _classPrivateFieldGet2(_eventMonitor$1, this)?.removeListener(_classPrivateFieldGet2(_eventListener, this));
	}
};
function _flush() {
	if (_classPrivateFieldGet2(_refreshFrame$1, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_refreshFrame$1, this));
	_classPrivateFieldSet2(_refreshFrame$1, this, null);
	_assertClassBrand(_Panel_brand, this, _updateCounts).call(this);
	if (_classPrivateFieldGet2(_listDirty, this) && this.isComponentListVisible) {
		_classPrivateFieldGet2(_list$1, this).refresh(_classPrivateFieldGet2(_filters, this), _classPrivateFieldGet2(_query$1, this), _classPrivateFieldGet2(_selectedComponent, this));
		_classPrivateFieldGet2(_navigation, this).setRootTitle(`Components (${_classPrivateFieldGet2(_state$2, this).size})`);
		_classPrivateFieldSet2(_listDirty, this, false);
	}
	for (const element of _classPrivateFieldGet2(_pendingActivity, this)) _classPrivateFieldGet2(_list$1, this).updateActivity(element);
	_classPrivateFieldGet2(_pendingActivity, this).clear();
}
function _header() {
	_classPrivateFieldGet2(_actionButtons, this).target = _assertClassBrand(_Panel_brand, this, _toggleButton).call(this, "target", "target", "Inspect page components", () => _classPrivateFieldGet2(_actions, this).target?.());
	_classPrivateFieldGet2(_actionButtons, this).overlay = _assertClassBrand(_Panel_brand, this, _toggleButton).call(this, "overlay", "overlay", "Show all components", () => _classPrivateFieldGet2(_actions, this).overlay?.());
	_classPrivateFieldGet2(_actionButtons, this).activity = _assertClassBrand(_Panel_brand, this, _toggleButton).call(this, "activity", "activity", "Show activity", () => _assertClassBrand(_Panel_brand, this, _toggleActivity).call(this));
	_classPrivateFieldGet2(_actionButtons, this).activity.appendChild(el("b", { text: "0" }));
	const close = createIconButton("panel-right", "Hide inspector", () => this.close());
	close.className = "icon-button";
	return el("header", {}, el("div", { class: "header-actions" }, _classPrivateFieldGet2(_actionButtons, this).target, _classPrivateFieldGet2(_actionButtons, this).overlay), el("strong", {
		id: "ux-inspector-title",
		text: "UX Inspector"
	}), el("div", { class: "header-actions header-end" }, _classPrivateFieldGet2(_actionButtons, this).activity, close));
}
function _switchTab(name, restoreActivity = true) {
	_classPrivateFieldSet2(_view, this, name === "log" ? "log" : "components");
	const activityVisible = _classPrivateFieldGet2(_view, this) === "log";
	const restored = activityVisible && _classPrivateFieldGet2(_activity, this).close();
	_classPrivateFieldGet2(_componentPanel, this).hidden = activityVisible;
	_classPrivateFieldGet2(_activityPanel, this).hidden = !activityVisible;
	_assertClassBrand(_Panel_brand, this, _updateDrillUi).call(this);
	_classPrivateFieldGet2(_logTools, this).hidden = !activityVisible;
	if (activityVisible && !restored) _classPrivateFieldGet2(_timeline$1, this).refresh();
	else if (!activityVisible) {
		if (restoreActivity) _classPrivateFieldGet2(_navigation, this).restoreActivity();
		if (_classPrivateFieldGet2(_listDirty, this)) _assertClassBrand(_Panel_brand, this, _flush).call(this);
	}
}
function _toggleActivity() {
	if (_classPrivateFieldGet2(_view, this) === "log") {
		_assertClassBrand(_Panel_brand, this, _switchTab).call(this, "components");
		return;
	}
	_assertClassBrand(_Panel_brand, this, _openGlobalActivity).call(this);
}
function _syncActivityControl() {
	const active = _classPrivateFieldGet2(_view, this) === "log";
	const count = _classPrivateFieldGet2(_activityCount, this);
	const label = active ? "Show components" : "Show activity";
	const accessibleLabel = count ? `${label}, ${count} ${count === 1 ? "activity" : "activities"}` : label;
	const button = _classPrivateFieldGet2(_actionButtons, this).activity;
	button.classList.toggle("active", active);
	button.setAttribute("aria-pressed", String(active));
	button.setAttribute("aria-label", accessibleLabel);
	button.title = label;
	const badge = button.querySelector("b");
	if (badge) {
		badge.textContent = String(count);
		badge.hidden = !count;
	}
}
function _tools() {
	const buttons = _assertClassBrand(_Panel_brand, this, _frameworkButtons).call(this, _classPrivateFieldGet2(_activityFilters, this), _classPrivateFieldGet2(_activityFilterButtons, this), () => _classPrivateFieldGet2(_timeline$1, this).configure({ frameworks: _classPrivateFieldGet2(_activityFilters, this) }));
	_classPrivateFieldSet2(_activitySearch, this, el("input", {
		type: "search",
		placeholder: "Filter activity…",
		"aria-label": "Filter activity",
		on: { input: () => _classPrivateFieldGet2(_timeline$1, this).configure({ query: _classPrivateFieldGet2(_activitySearch, this).value }) }
	}));
	_classPrivateFieldSet2(_logTools, this, el("div", {
		class: "activity-tools",
		hidden: true
	}, el("div", {
		class: "filter-list",
		role: "group",
		"aria-label": "Filter activity by framework"
	}, ...buttons), _classPrivateFieldGet2(_activitySearch, this)));
	return _classPrivateFieldGet2(_logTools, this);
}
function _toggleButton(name, icon, label, callback) {
	const button = createIconButton(icon, label, callback);
	button.className = "icon-button";
	button.dataset.action = name;
	button.setAttribute("aria-pressed", "false");
	return button;
}
function _filterBar() {
	_classPrivateFieldSet2(_search, this, el("input", {
		type: "search",
		placeholder: "Find a component…",
		"aria-label": "Find a component",
		on: { input: () => {
			_classPrivateFieldSet2(_query$1, this, _classPrivateFieldGet2(_search, this).value.trim().toLowerCase());
			this.refresh();
		} }
	}));
	return el("div", { class: "filters" }, el("div", { class: "filter-list" }, ..._assertClassBrand(_Panel_brand, this, _frameworkButtons).call(this, _classPrivateFieldGet2(_filters, this), _classPrivateFieldGet2(_filterButtons, this), () => this.refresh())), _classPrivateFieldGet2(_search, this));
}
function _frameworkButtons(filters, buttons, update) {
	return FRAMEWORKS.map(([name, label]) => {
		const button = el("button", {
			class: "filter active",
			type: "button",
			"aria-pressed": "true",
			dataset: { framework: name },
			on: { click: (event) => {
				const target = event.currentTarget;
				const active = target.classList.toggle("active");
				target.setAttribute("aria-pressed", String(active));
				if (active) filters.add(name);
				else filters.delete(name);
				update();
			} }
		}, el("span", { text: label }), el("b", { text: "0" }));
		buttons[name] = button;
		return button;
	});
}
function _footer() {
	_classPrivateFieldSet2(_monitorLabel, this, el("span", { text: "Watching" }));
	return el("footer", {}, el("span", {
		class: "monitor-status",
		role: "status"
	}, el("span", {
		class: "live-dot",
		"aria-hidden": "true"
	}), _classPrivateFieldGet2(_monitorLabel, this)));
}
function _updateCounts() {
	const byPlugin = _classPrivateFieldGet2(_state$2, this).countByPlugin();
	const activityByPlugin = {
		...Object.fromEntries(FRAMEWORKS.map(([name]) => [name, 0])),
		...Object.fromEntries(Object.entries(Object.groupBy(_classPrivateFieldGet2(_eventMonitor$1, this)?.project() ?? [], (entry) => entry.type)).map(([type, entries]) => [type, entries.length]))
	};
	for (const [name, label] of FRAMEWORKS) {
		const button = _classPrivateFieldGet2(_filterButtons, this)[name];
		const count = byPlugin[name] || 0;
		const installed = _classPrivateFieldGet2(_packages, this)[name];
		const status = count ? "detected" : installed === false ? "not-installed" : installed === true ? "idle" : "unknown";
		const badge = button.querySelector("b");
		if (badge) badge.textContent = installed === false ? "-" : String(count);
		button.classList.toggle("unavailable", installed === false);
		button.dataset.status = status;
		button.title = count ? `${count} detected on this page` : installed === false ? `${label} package not installed` : installed === true ? `${label} installed; none detected on this page` : "None detected on this page";
		button.setAttribute("aria-label", `${label}: ${button.title}`);
		const activityButton = _classPrivateFieldGet2(_activityFilterButtons, this)[name];
		const activityBadge = activityButton.querySelector("b");
		if (activityBadge) activityBadge.textContent = String(activityByPlugin[name]);
		activityButton.title = `${activityByPlugin[name]} ${label} ${activityByPlugin[name] === 1 ? "activity" : "activities"}`;
		activityButton.setAttribute("aria-label", activityButton.title);
	}
	_classPrivateFieldSet2(_activityCount, this, Object.values(activityByPlugin).reduce((sum, count) => sum + count, 0));
	_assertClassBrand(_Panel_brand, this, _syncActivityControl).call(this);
}
function _updateDrillUi() {
	const componentsVisible = _classPrivateFieldGet2(_view, this) === "components";
	const filters = _classPrivateFieldGet2(_element$1, this).querySelector(".filters");
	if (filters) filters.hidden = !componentsVisible || _classPrivateFieldGet2(_navigation, this).depth > 1;
	_assertClassBrand(_Panel_brand, this, _syncActivityControl).call(this);
}
function _openGlobalActivity() {
	_classPrivateFieldGet2(_activitySearch, this).value = "";
	_classPrivateFieldGet2(_timeline$1, this).configure({ query: "" });
	_assertClassBrand(_Panel_brand, this, _switchTab).call(this, "log");
}
const MAX_ENTRIES = 100;
var _monitor = /* @__PURE__ */ new WeakMap();
var _element = /* @__PURE__ */ new WeakMap();
var _list = /* @__PURE__ */ new WeakMap();
var _filterEmpty = /* @__PURE__ */ new WeakMap();
var _onHighlight = /* @__PURE__ */ new WeakMap();
var _onSelect = /* @__PURE__ */ new WeakMap();
var _rafId = /* @__PURE__ */ new WeakMap();
var _paused = /* @__PURE__ */ new WeakMap();
var _query = /* @__PURE__ */ new WeakMap();
var _elementFilter = /* @__PURE__ */ new WeakMap();
var _frameworks = /* @__PURE__ */ new WeakMap();
var _entries = /* @__PURE__ */ new WeakMap();
var _rows = /* @__PURE__ */ new WeakMap();
var _nextDetailId = /* @__PURE__ */ new WeakMap();
var _selectedEntry = /* @__PURE__ */ new WeakMap();
var _contextual = /* @__PURE__ */ new WeakMap();
var _onEntry = /* @__PURE__ */ new WeakMap();
var _Timeline_brand = /* @__PURE__ */ new WeakSet();
var Timeline = class {
	constructor(monitor, callbacks = {}) {
		_classPrivateMethodInitSpec(this, _Timeline_brand);
		_classPrivateFieldInitSpec(this, _monitor, void 0);
		_classPrivateFieldInitSpec(this, _element, void 0);
		_classPrivateFieldInitSpec(this, _list, void 0);
		_classPrivateFieldInitSpec(this, _filterEmpty, void 0);
		_classPrivateFieldInitSpec(this, _onHighlight, null);
		_classPrivateFieldInitSpec(this, _onSelect, null);
		_classPrivateFieldInitSpec(this, _rafId, null);
		_classPrivateFieldInitSpec(this, _paused, false);
		_classPrivateFieldInitSpec(this, _query, "");
		_classPrivateFieldInitSpec(this, _elementFilter, null);
		_classPrivateFieldInitSpec(this, _frameworks, null);
		_classPrivateFieldInitSpec(this, _entries, []);
		_classPrivateFieldInitSpec(this, _rows, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _nextDetailId, 0);
		_classPrivateFieldInitSpec(this, _selectedEntry, null);
		_classPrivateFieldInitSpec(this, _contextual, false);
		_classPrivateFieldInitSpec(this, _onEntry, () => {
			if (_classPrivateFieldGet2(_paused, this) || _classPrivateFieldGet2(_rafId, this) !== null) return;
			_classPrivateFieldSet2(_rafId, this, requestAnimationFrame(() => _assertClassBrand(_Timeline_brand, this, _flushEntries).call(this)));
		});
		_classPrivateFieldSet2(_monitor, this, monitor);
		_classPrivateFieldSet2(_onHighlight, this, callbacks.onHighlight || null);
		_classPrivateFieldSet2(_onSelect, this, callbacks.onSelect || null);
		_classPrivateFieldSet2(_entries, this, monitor.entries || []);
		_classPrivateFieldSet2(_list, this, el("ol", {
			class: "events",
			"aria-label": "Captured UX events",
			"aria-live": "polite",
			on: { keydown: (event) => _assertClassBrand(_Timeline_brand, this, _onKeydown).call(this, event) }
		}));
		_classPrivateFieldSet2(_filterEmpty, this, createEmptyState("No matches."));
		_classPrivateFieldGet2(_filterEmpty, this).hidden = true;
		_classPrivateFieldSet2(_element, this, el("div", { class: "timeline" }, _classPrivateFieldGet2(_list, this), _classPrivateFieldGet2(_filterEmpty, this)));
		monitor.addListener(_classPrivateFieldGet2(_onEntry, this));
	}
	get element() {
		return _classPrivateFieldGet2(_element, this);
	}
	get paused() {
		return _classPrivateFieldGet2(_paused, this);
	}
	get projectedEntries() {
		return _classPrivateFieldGet2(_monitor, this).project();
	}
	configure({ query = _classPrivateFieldGet2(_query, this), element = _classPrivateFieldGet2(_elementFilter, this), frameworks = _classPrivateFieldGet2(_frameworks, this), contextual = _classPrivateFieldGet2(_contextual, this) }) {
		const contextChanged = contextual !== _classPrivateFieldGet2(_contextual, this);
		const render = contextChanged || element !== _classPrivateFieldGet2(_elementFilter, this);
		_classPrivateFieldSet2(_query, this, query.trim().toLowerCase());
		_classPrivateFieldSet2(_elementFilter, this, element);
		_classPrivateFieldSet2(_frameworks, this, frameworks ? new Set(frameworks) : null);
		_classPrivateFieldSet2(_contextual, this, contextual);
		if (contextChanged) {
			_classPrivateFieldSet2(_selectedEntry, this, null);
			_classPrivateFieldGet2(_rows, this).clear();
		}
		if (render) if (_classPrivateFieldGet2(_paused, this)) _assertClassBrand(_Timeline_brand, this, _renderEntries).call(this);
		else _assertClassBrand(_Timeline_brand, this, _renderSnapshot).call(this);
		else _assertClassBrand(_Timeline_brand, this, _applyFilter).call(this);
	}
	async copySelected() {
		const clipboard = navigator.clipboard;
		const entry = _classPrivateFieldGet2(_selectedEntry, this);
		if (!entry || !clipboard?.writeText) return false;
		const diagnostic = _assertClassBrand(_Timeline_brand, this, _diagnostic).call(this, entry);
		try {
			await clipboard.writeText(JSON.stringify(diagnostic));
			return true;
		} catch {
			return false;
		}
	}
	flush() {
		if (!_classPrivateFieldGet2(_rafId, this)) return;
		cancelAnimationFrame(_classPrivateFieldGet2(_rafId, this));
		_assertClassBrand(_Timeline_brand, this, _flushEntries).call(this);
	}
	destroy() {
		_classPrivateFieldGet2(_monitor, this).removeListener(_classPrivateFieldGet2(_onEntry, this));
		if (_classPrivateFieldGet2(_rafId, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_rafId, this));
		_classPrivateFieldSet2(_rafId, this, null);
		_classPrivateFieldSet2(_paused, this, true);
		_classPrivateFieldGet2(_rows, this).clear();
		_classPrivateFieldSet2(_entries, this, []);
		_classPrivateFieldSet2(_selectedEntry, this, null);
		_classPrivateFieldSet2(_onHighlight, this, null);
		_classPrivateFieldSet2(_onSelect, this, null);
		_classPrivateFieldGet2(_element, this).remove();
	}
	pause() {
		if (_classPrivateFieldGet2(_paused, this)) return false;
		_classPrivateFieldSet2(_paused, this, true);
		if (_classPrivateFieldGet2(_rafId, this)) cancelAnimationFrame(_classPrivateFieldGet2(_rafId, this));
		_classPrivateFieldSet2(_rafId, this, null);
		_classPrivateFieldGet2(_onHighlight, this)?.call(this, null);
		return true;
	}
	resume() {
		if (!_classPrivateFieldGet2(_paused, this)) return false;
		_classPrivateFieldSet2(_paused, this, false);
		_assertClassBrand(_Timeline_brand, this, _renderSnapshot).call(this);
		return true;
	}
	clear() {
		if (_classPrivateFieldGet2(_rafId, this)) cancelAnimationFrame(_classPrivateFieldGet2(_rafId, this));
		_classPrivateFieldSet2(_rafId, this, null);
		_classPrivateFieldSet2(_selectedEntry, this, null);
		_classPrivateFieldGet2(_monitor, this).clear();
		_classPrivateFieldSet2(_entries, this, []);
		_assertClassBrand(_Timeline_brand, this, _renderEntries).call(this);
		_classPrivateFieldGet2(_element, this).dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: null } }));
	}
	refresh() {
		if (_classPrivateFieldGet2(_paused, this)) return;
		_assertClassBrand(_Timeline_brand, this, _renderSnapshot).call(this);
	}
	expandFirstVisible() {
		const disclosure = [..._classPrivateFieldGet2(_list, this).querySelectorAll(".event:not([hidden])")].find((candidate) => _assertClassBrand(_Timeline_brand, this, _hasDetail).call(this, candidate._inspectorEntry))?.querySelector(".disclosure");
		if (disclosure?.getAttribute("aria-expanded") !== "true") disclosure?.click();
	}
	renderDetail(entry) {
		if (!entry) return createEmptyState("Select an activity to inspect.");
		const copy = el("button", {
			class: "icon-button event-copy",
			type: "button",
			title: "Copy activity",
			"aria-label": "Copy activity",
			dataset: { control: "copy" },
			on: { click: async () => {
				const copied = await this.copySelected();
				const label = copied ? "Activity copied" : "Copy failed";
				copy.classList.toggle("copied", copied);
				copy.setAttribute("aria-label", label);
				copy.title = label;
				setTimeout(() => {
					copy.classList.remove("copied");
					copy.setAttribute("aria-label", "Copy activity");
					copy.title = "Copy activity";
				}, 900);
			} }
		}, createIcon("copy"));
		const fields = [];
		if (entry.activityKind === "turbo-fetch") {
			const fetch = entry.fetch;
			fields.push(makeField("Intent", fetch.intent === "prefetch" ? "Prefetch" : "Fetch"), makeField("Request", [fetch.method, fetch.url].filter(Boolean).join(" ")), makeField("Status", fetch.pending ? "Pending" : fetch.status ?? "Completed"), makeField("Duration", fetch.pending ? "Pending" : _assertClassBrand(_Timeline_brand, this, _formatDuration).call(this, fetch.duration)));
			if (fetch.priority) fields.push(makeField("Priority", fetch.priority));
			if ((entry.occurrences ?? 0) > 1) fields.push(makeField("Operations", entry.occurrences));
			fields.push(makeField("Raw hooks", entry.rawEntries.length));
		} else if (entry.activityKind === "live-rerender") {
			const live = entry.live;
			const changes = Object.fromEntries(live.changes.map((change) => [change.model, change.value]));
			fields.push(makeField("Trigger", live.trigger));
			if (live.actions.length) fields.push(makeField("Calls", live.actions));
			if (Object.keys(changes).length) fields.push(makeField("Changes", changes));
			fields.push(makeField("Hooks", live.hooks));
			fields.push(makeField("Status", live.status));
			if (live.duration != null) fields.push(makeField("Duration", _assertClassBrand(_Timeline_brand, this, _formatDuration).call(this, live.duration)));
		} else if (entry.detail != null) {
			const values = typeof entry.detail === "object" && !Array.isArray(entry.detail) ? Object.entries(entry.detail) : [["value", entry.detail]];
			for (const [name, value] of values) fields.push(makeField(name, value));
		}
		return el("section", {
			class: "activity-detail",
			dataset: { framework: entry.type || "default" }
		}, el("div", { class: "event-actions" }, copy), makeKeyValueList(fields), entry.activityKind === "turbo-fetch" ? _assertClassBrand(_Timeline_brand, this, _renderRawEvents).call(this, entry.rawEntries) : null);
	}
};
function _flushEntries() {
	_classPrivateFieldSet2(_rafId, this, null);
	if (!_classPrivateFieldGet2(_paused, this)) _assertClassBrand(_Timeline_brand, this, _renderSnapshot).call(this);
}
function _renderSnapshot() {
	if (_classPrivateFieldGet2(_rafId, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_rafId, this));
	_classPrivateFieldSet2(_rafId, this, null);
	_classPrivateFieldSet2(_entries, this, _classPrivateFieldGet2(_monitor, this).entries || []);
	_assertClassBrand(_Timeline_brand, this, _renderEntries).call(this);
}
function _renderEntries() {
	const selectedAnchor = _classPrivateFieldGet2(_selectedEntry, this) && _assertClassBrand(_Timeline_brand, this, _anchor).call(this, _classPrivateFieldGet2(_selectedEntry, this));
	const root = _classPrivateFieldGet2(_element, this).getRootNode();
	const focused = root.activeElement;
	const focusedItem = focused?.closest(".event");
	const focusedAnchor = focusedItem?._inspectorEntry && _assertClassBrand(_Timeline_brand, this, _anchor).call(this, focusedItem._inspectorEntry);
	const focusedControl = focused?.dataset.control;
	_classPrivateFieldGet2(_onHighlight, this)?.call(this, null);
	const entries = (_classPrivateFieldGet2(_paused, this) ? projectActivity(_classPrivateFieldGet2(_entries, this), !_classPrivateFieldGet2(_contextual, this)) : _classPrivateFieldGet2(_monitor, this).project(null, !_classPrivateFieldGet2(_contextual, this))).slice(-MAX_ENTRIES).map((entry) => {
		const previous = _classPrivateFieldGet2(_rows, this).get(_assertClassBrand(_Timeline_brand, this, _anchor).call(this, entry))?._inspectorEntry;
		return previous && _assertClassBrand(_Timeline_brand, this, _sameEntries).call(this, previous, entry) ? previous : entry;
	});
	_classPrivateFieldSet2(_selectedEntry, this, selectedAnchor ? entries.find((entry) => _assertClassBrand(_Timeline_brand, this, _anchor).call(this, entry) === selectedAnchor) || null : null);
	_classPrivateFieldSet2(_rows, this, new Map(entries.map((entry) => {
		const anchor = _assertClassBrand(_Timeline_brand, this, _anchor).call(this, entry);
		const previous = _classPrivateFieldGet2(_rows, this).get(anchor);
		const owner = _assertClassBrand(_Timeline_brand, this, _owner).call(this, entry);
		return [anchor, previous?._inspectorEntry === entry && previous._navigationTarget === _assertClassBrand(_Timeline_brand, this, _navigationTarget).call(this, owner) ? previous : _assertClassBrand(_Timeline_brand, this, _renderEntry).call(this, entry, owner)];
	})));
	reconcileChildren(_classPrivateFieldGet2(_list, this), _classPrivateFieldGet2(_rows, this).size ? [..._classPrivateFieldGet2(_rows, this).values()].reverse() : [createEmptyState("No events captured yet.")]);
	_assertClassBrand(_Timeline_brand, this, _applyFilter).call(this);
	if (focusedAnchor && root.activeElement !== focused) {
		const row = _classPrivateFieldGet2(_rows, this).get(focusedAnchor);
		const control = focusedControl ? row?.querySelector(`[data-control="${focusedControl}"]:not([hidden])`) : null;
		const fallback = _classPrivateFieldGet2(_list, this).querySelector(".event:not([hidden]) button");
		(control && !row?.hidden ? control : fallback)?.focus({ preventScroll: true });
	}
}
function _owner(entry) {
	return entry.owner || entry.target?.closest?.("[data-controller], turbo-frame") || null;
}
function _navigationTarget(owner) {
	return owner?.isConnected ? owner : null;
}
function _anchor(entry) {
	return entry.id ?? entry.rawEntries?.[0] ?? entry;
}
function _sameEntries(previous, current) {
	if (previous === current) return true;
	return !!previous.rawEntries && !!current.rawEntries && previous.rawEntries.length === current.rawEntries.length && previous.rawEntries.every((entry, index) => entry === current.rawEntries?.[index]);
}
function _renderEntry(entry, owner) {
	var _this$nextDetailId;
	const type = entry.type || "unknown";
	const eventName = _assertClassBrand(_Timeline_brand, this, _eventIdentity).call(this, entry);
	const targetIdentity = _assertClassBrand(_Timeline_brand, this, _targetIdentity).call(this, entry.target ?? null);
	const visibleTarget = _assertClassBrand(_Timeline_brand, this, _visibleContext).call(this, entry, targetIdentity, owner);
	const navigationTarget = _assertClassBrand(_Timeline_brand, this, _navigationTarget).call(this, owner);
	const hasDetail = _assertClassBrand(_Timeline_brand, this, _hasDetail).call(this, entry);
	const selected = hasDetail && _classPrivateFieldGet2(_selectedEntry, this) === entry;
	const detailId = `uxli-activity-detail-${_classPrivateFieldSet2(_nextDetailId, this, (_this$nextDetailId = _classPrivateFieldGet2(_nextDetailId, this), ++_this$nextDetailId))}`;
	const disclosure = el(hasDetail ? "button" : "div", {
		class: "disclosure",
		...hasDetail ? {
			type: "button",
			"aria-expanded": String(selected),
			"aria-controls": detailId,
			dataset: { control: "disclosure" }
		} : {},
		"aria-label": [
			`${frameworkName(type)} activity`,
			eventName,
			targetIdentity,
			`${(entry.time / 1e3).toFixed(3)} seconds`
		].filter(Boolean).join(", ")
	}, el("span", {
		class: "event-dot",
		"aria-hidden": "true"
	}), el("strong", {
		class: "name",
		text: eventName
	}), visibleTarget ? el("span", {
		class: "event-target",
		text: visibleTarget
	}) : null);
	const navigationIdentity = _assertClassBrand(_Timeline_brand, this, _targetIdentity).call(this, navigationTarget);
	const goLabel = navigationIdentity ? `Go to component ${navigationIdentity}` : `Go to component for ${entry.event}`;
	const goBtn = el("button", {
		class: "icon-button event-go",
		type: "button",
		title: goLabel,
		"aria-label": goLabel,
		hidden: !navigationTarget,
		dataset: { control: "go" }
	}, createIcon("target"));
	const row = el("div", {
		class: `event-row ${type}${selected ? " selected" : ""}${hasDetail ? "" : " compact"}`,
		dataset: { framework: type }
	}, disclosure, (entry.occurrences ?? 0) > 1 ? el("span", {
		class: "event-count",
		text: String(entry.occurrences),
		title: `${entry.occurrences} identical operations`
	}) : null, goBtn);
	const detail = hasDetail ? el("div", {
		class: "event-detail",
		id: detailId,
		hidden: !selected
	}) : null;
	if (selected && detail) detail.appendChild(this.renderDetail(entry));
	const item = el("li", {
		class: "event",
		dataset: { search: _assertClassBrand(_Timeline_brand, this, _searchText).call(this, entry, eventName, targetIdentity, owner) }
	}, row, detail);
	item._inspectorEntry = entry;
	item._navigationTarget = navigationTarget;
	if (hasDetail && detail) disclosure.addEventListener("click", () => _assertClassBrand(_Timeline_brand, this, _select).call(this, entry, row, detail));
	if (navigationTarget) {
		row.addEventListener("mouseenter", () => {
			goBtn.hidden = !navigationTarget.isConnected;
			if (navigationTarget.isConnected) _classPrivateFieldGet2(_onHighlight, this)?.call(this, navigationTarget, type);
		});
		row.addEventListener("mouseleave", () => _classPrivateFieldGet2(_onHighlight, this)?.call(this, null));
	}
	goBtn.addEventListener("click", () => {
		goBtn.hidden = !navigationTarget?.isConnected;
		if (goBtn.hidden) return;
		_classPrivateFieldGet2(_onHighlight, this)?.call(this, null);
		if (navigationTarget) _classPrivateFieldGet2(_onSelect, this)?.call(this, navigationTarget);
	});
	return item;
}
function _hasDetail(entry) {
	if (!entry) return false;
	if (entry.activityKind === "turbo-fetch" || entry.activityKind === "live-rerender") return true;
	if (entry.detail == null) return false;
	if (Array.isArray(entry.detail)) return entry.detail.length > 0;
	if (typeof entry.detail === "object") return Object.keys(entry.detail).length > 0;
	return true;
}
function _renderRawEvents(entries) {
	return el("details", {
		class: "raw-events",
		open: false
	}, el("summary", {}, el("span", { text: "Raw events" }), el("span", {
		class: "raw-count",
		text: String(entries.length)
	})), el("ol", { class: "raw-list" }, ...entries.map((entry) => el("li", {}, el("strong", { text: entry.event }), el("span", { text: `${(entry.time / 1e3).toFixed(3)}s` })))));
}
function _select(entry, row, detail) {
	const open = _classPrivateFieldGet2(_selectedEntry, this) !== entry || !row.classList.contains("selected");
	_classPrivateFieldSet2(_selectedEntry, this, open ? entry : null);
	for (const item of _classPrivateFieldGet2(_list, this).querySelectorAll(".event")) {
		const candidate = item.querySelector(".event-row");
		const candidateDetail = item.querySelector(".event-detail");
		const selected = open && candidate === row;
		candidate?.classList.toggle("selected", selected);
		candidate?.querySelector(".disclosure")?.setAttribute("aria-expanded", String(selected));
		if (candidateDetail) {
			candidateDetail.hidden = !selected;
			if (!selected) candidateDetail.replaceChildren();
		}
	}
	if (open && !detail.firstChild) detail.appendChild(this.renderDetail(entry));
	_classPrivateFieldGet2(_element, this).dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: _classPrivateFieldGet2(_selectedEntry, this) } }));
}
function _applyFilter() {
	const items = Array.from(_classPrivateFieldGet2(_list, this).querySelectorAll(".event"));
	let visible = 0;
	for (const item of items) {
		const matchesFramework = !_classPrivateFieldGet2(_frameworks, this) || _classPrivateFieldGet2(_frameworks, this).has(item._inspectorEntry?.type ?? "");
		const matchesElement = !_classPrivateFieldGet2(_elementFilter, this) || _assertClassBrand(_Timeline_brand, this, _matchesElement).call(this, item._inspectorEntry, _classPrivateFieldGet2(_elementFilter, this));
		const matches = matchesFramework && matchesElement && (!_classPrivateFieldGet2(_query, this) || (item.dataset.search ?? "").includes(_classPrivateFieldGet2(_query, this)));
		item.hidden = !matches;
		if (matches) visible++;
	}
	_classPrivateFieldGet2(_filterEmpty, this).hidden = items.length === 0 || visible > 0;
	const selected = _classPrivateFieldGet2(_list, this).querySelector(".event-row.selected")?.closest(".event");
	if (_classPrivateFieldGet2(_selectedEntry, this) && (!selected || selected.hidden)) {
		selected?.querySelector(".event-row")?.classList.remove("selected");
		selected?.querySelector(".disclosure")?.setAttribute("aria-expanded", "false");
		const detail = selected?.querySelector(".event-detail");
		if (detail) {
			detail.hidden = true;
			detail.replaceChildren();
		}
		_classPrivateFieldSet2(_selectedEntry, this, null);
		_classPrivateFieldGet2(_element, this).dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: null } }));
	}
}
function _matchesElement(entry, element) {
	return entry?.owner === element || entry?.target === element || (entry?.relatedElements?.includes(element) ?? false) || (entry?.rawEntries?.some((raw) => raw.owner === element || raw.target === element || raw.relatedElements?.includes(element)) ?? false);
}
function _onKeydown(event) {
	if (![
		"ArrowUp",
		"ArrowDown",
		"Home",
		"End"
	].includes(event.key)) return;
	const buttons = Array.from(_classPrivateFieldGet2(_list, this).querySelectorAll("button.disclosure"));
	if (!buttons.length) return;
	const current = buttons.indexOf(event.target?.closest?.(".disclosure"));
	const next = event.key === "Home" ? 0 : event.key === "End" ? buttons.length - 1 : Math.min(buttons.length - 1, Math.max(0, current + (event.key === "ArrowDown" ? 1 : -1)));
	event.preventDefault();
	buttons[next].focus();
}
function _targetIdentity(target) {
	if (!target?.tagName) return "";
	return target.tagName.toLowerCase() + (target.id ? `#${target.id}` : "");
}
function _eventIdentity(entry) {
	if (entry.activityKind && entry.activityKind !== "repeated") return entry.label || entry.event;
	if (_classPrivateFieldGet2(_contextual, this)) return entry.event;
	const label = entry.label || entry.event;
	if (entry.type !== "livecomponent") return label;
	const component = entry.target?.dataset?.liveNameValue;
	return component && label.startsWith(`${component}: `) ? label.slice(component.length + 2) : entry.event?.replace(/^live:/, "") || label;
}
function _visibleContext(entry, targetIdentity, owner) {
	if (entry.type === "livecomponent") return entry.target?.dataset?.liveNameValue || "";
	if (!owner || ["html", "body"].includes(targetIdentity)) return "";
	return targetIdentity;
}
function _detailedTargetIdentity(target) {
	const identity = _assertClassBrand(_Timeline_brand, this, _targetIdentity).call(this, target);
	if (!identity) return "";
	return identity + (target.className && typeof target.className === "string" ? "." + target.className.trim().split(/\s+/).filter(Boolean).slice(0, 3).join(".") : "");
}
function _searchText(entry, eventName, targetIdentity, owner) {
	const rawEvents = (entry.rawEntries || []).map((raw) => raw.event).join(" ");
	const fetch = entry.fetch ? `${entry.fetch.intent} ${entry.fetch.method} ${entry.fetch.url} ${entry.fetch.status ?? ""} ${entry.fetch.priority}` : "";
	const dataset = owner?.dataset;
	return `${eventName} ${entry.type || "unknown"} ${targetIdentity} ${owner?.id || ""} ${dataset?.controller || ""} ${dataset?.liveNameValue || ""} ${rawEvents} ${fetch}`.toLowerCase();
}
function _formatDuration(milliseconds) {
	if (typeof milliseconds !== "number" || !Number.isFinite(milliseconds)) return "Unknown";
	if (milliseconds < 1) return "<1 ms";
	if (milliseconds < 1e3) return `${Math.round(milliseconds)} ms`;
	return `${(milliseconds / 1e3).toFixed(2)} s`;
}
function _diagnostic(entry) {
	const base = {
		event: entry.event,
		framework: entry.type,
		time: Number((entry.time / 1e3).toFixed(3)),
		label: entry.label,
		target: entry.target instanceof Element ? _assertClassBrand(_Timeline_brand, this, _detailedTargetIdentity).call(this, entry.target) : void 0
	};
	if (entry.activityKind !== "turbo-fetch") return {
		...base,
		detail: entry.detail
	};
	return {
		...base,
		operation: {
			...entry.fetch,
			occurrences: entry.occurrences,
			rawHooks: entry.rawEntries.length
		},
		rawEvents: entry.rawEntries.map((raw) => ({
			event: raw.event,
			time: Number((raw.time / 1e3).toFixed(3)),
			target: raw.target instanceof Element ? _assertClassBrand(_Timeline_brand, this, _detailedTargetIdentity).call(this, raw.target) : void 0,
			detail: raw.detail
		}))
	};
}
function bindOpenShortcut(open, signal) {
	let prefix = false;
	document.addEventListener("keydown", (event) => {
		if (event.composedPath().some((target) => target instanceof HTMLElement && (target.matches("input, textarea, select") || target.isContentEditable || target.closest("[contenteditable]:not([contenteditable=\"false\"])"))) || event.isComposing || event.repeat || event.altKey || event.ctrlKey || event.metaKey) {
			prefix = false;
			return;
		}
		const key = event.key.toLowerCase();
		if (prefix && key === "x") open();
		prefix = key === "u";
	}, { signal });
}
function createPullTab(host, open, signal) {
	const button = el("button", {
		type: "button",
		"aria-label": "Open Inspector",
		text: "UX"
	});
	const tab = el("div", { class: "pull-tab" }, button);
	let rem = 16;
	const measure = () => rem = Number.parseFloat(getComputedStyle(document.documentElement).fontSize) || 16;
	const reset = () => tab.removeAttribute("data-near");
	measure();
	window.addEventListener("resize", measure, {
		passive: true,
		signal
	});
	window.addEventListener("blur", reset, { signal });
	document.addEventListener("pointerleave", reset, { signal });
	document.addEventListener("pointermove", (event) => {
		if (!host.isConnected || host.isOpen || event.pointerType === "touch") return;
		const near = window.innerWidth - event.clientX <= 2.5 * rem && Math.abs(event.clientY - window.innerHeight / 2) <= 3.25 * rem;
		if (near !== tab.hasAttribute("data-near")) tab.toggleAttribute("data-near", near);
	}, {
		passive: true,
		signal
	});
	button.addEventListener("click", () => {
		reset();
		open();
	}, { signal });
	return tab;
}
var _container = /* @__PURE__ */ new WeakMap();
var _hover = /* @__PURE__ */ new WeakMap();
var _selected = /* @__PURE__ */ new WeakMap();
var _all = /* @__PURE__ */ new WeakMap();
var _events = /* @__PURE__ */ new WeakMap();
var _allVisible = /* @__PURE__ */ new WeakMap();
var _state$1 = /* @__PURE__ */ new WeakMap();
var _registry$1 = /* @__PURE__ */ new WeakMap();
var _lifetime$2 = /* @__PURE__ */ new WeakMap();
var _resizeObserver = /* @__PURE__ */ new WeakMap();
var _observed = /* @__PURE__ */ new WeakMap();
var _Highlighter_brand = /* @__PURE__ */ new WeakSet();
var Highlighter = class {
	constructor(root = document.documentElement, state = null, registry = null) {
		_classPrivateMethodInitSpec(this, _Highlighter_brand);
		_classPrivateFieldInitSpec(this, _container, void 0);
		_classPrivateFieldInitSpec(this, _hover, null);
		_classPrivateFieldInitSpec(this, _selected, null);
		_classPrivateFieldInitSpec(this, _all, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _events, /* @__PURE__ */ new Map());
		_classPrivateFieldInitSpec(this, _allVisible, false);
		_classPrivateFieldInitSpec(this, _state$1, void 0);
		_classPrivateFieldInitSpec(this, _registry$1, void 0);
		_classPrivateFieldInitSpec(this, _lifetime$2, new AbortController());
		_classPrivateFieldInitSpec(this, _resizeObserver, void 0);
		_classPrivateFieldInitSpec(this, _observed, /* @__PURE__ */ new Map());
		_classPrivateFieldSet2(_state$1, this, state);
		_classPrivateFieldSet2(_registry$1, this, registry);
		_classPrivateFieldSet2(_container, this, document.createElement("div"));
		_classPrivateFieldGet2(_container, this).className = "overlay";
		_classPrivateFieldGet2(_container, this).setAttribute("data-ux-inspector-overlay", "");
		root.appendChild(_classPrivateFieldGet2(_container, this));
		_classPrivateFieldSet2(_resizeObserver, this, new ResizeObserver((entries) => {
			for (const entry of entries) _assertClassBrand(_Highlighter_brand, this, _refreshTarget).call(this, entry.target);
		}));
		const update = (event) => {
			if (_classPrivateFieldGet2(_allVisible, this)) _assertClassBrand(_Highlighter_brand, this, _renderAll).call(this, event.detail?.element);
		};
		for (const event of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) state?.addEventListener(event, update, { signal: _classPrivateFieldGet2(_lifetime$2, this).signal });
	}
	get visible() {
		return _classPrivateFieldGet2(_allVisible, this);
	}
	hover(element, framework = "default", label = "") {
		_classPrivateFieldSet2(_hover, this, _assertClassBrand(_Highlighter_brand, this, _box).call(this, element, framework, "hover", label, _classPrivateFieldGet2(_hover, this)));
	}
	clearHover() {
		_assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, _classPrivateFieldGet2(_hover, this));
		_classPrivateFieldSet2(_hover, this, null);
	}
	select(element, framework = "default", label = "") {
		_classPrivateFieldSet2(_selected, this, _assertClassBrand(_Highlighter_brand, this, _box).call(this, element, framework, "selected", label, _classPrivateFieldGet2(_selected, this)));
	}
	deselect() {
		_assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, _classPrivateFieldGet2(_selected, this));
		_classPrivateFieldSet2(_selected, this, null);
	}
	showAll() {
		if (_classPrivateFieldGet2(_allVisible, this)) return;
		_classPrivateFieldSet2(_allVisible, this, true);
		_assertClassBrand(_Highlighter_brand, this, _renderAll).call(this);
	}
	hideAll() {
		_classPrivateFieldSet2(_allVisible, this, false);
		for (const box of _classPrivateFieldGet2(_all, this).values()) _assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, box);
		_classPrivateFieldGet2(_all, this).clear();
	}
	toggleAll() {
		if (_classPrivateFieldGet2(_allVisible, this)) this.hideAll();
		else this.showAll();
		return _classPrivateFieldGet2(_allVisible, this);
	}
	pulse(element, framework = "default", label = "") {
		if (!element?.isConnected) return;
		let entry = _classPrivateFieldGet2(_events, this).get(element);
		if (entry) {
			if (entry.timer) clearTimeout(entry.timer);
			if (entry.raf !== null) cancelAnimationFrame(entry.raf);
			entry.count = entry.label === label ? entry.count + 1 : 1;
			entry.label = label;
			entry.box.dataset.framework = framework;
			_assertClassBrand(_Highlighter_brand, this, _position).call(this, entry.box, element.getBoundingClientRect());
		} else {
			const box = _assertClassBrand(_Highlighter_brand, this, _box).call(this, element, framework, "event");
			const caption = document.createElement("span");
			box.appendChild(caption);
			entry = {
				box,
				caption,
				label,
				count: 1,
				timer: null,
				raf: null
			};
			_classPrivateFieldGet2(_events, this).set(element, entry);
		}
		const current = entry;
		current.caption.textContent = current.count > 1 ? `${label} ×${current.count}` : label;
		current.box.classList.remove("pulse");
		current.raf = requestAnimationFrame(() => {
			current.raf = null;
			current.box.classList.add("pulse");
		});
		current.timer = setTimeout(() => _assertClassBrand(_Highlighter_brand, this, _removeEvent).call(this, element, current), _EVENT_LIFETIME._);
	}
	refresh() {
		for (const element of _classPrivateFieldGet2(_observed, this).keys()) _assertClassBrand(_Highlighter_brand, this, _refreshTarget).call(this, element);
	}
	clearAll() {
		this.clearHover();
		this.deselect();
		this.hideAll();
		for (const [element, entry] of _classPrivateFieldGet2(_events, this)) _assertClassBrand(_Highlighter_brand, this, _removeEvent).call(this, element, entry);
	}
	destroy() {
		this.clearAll();
		_classPrivateFieldGet2(_lifetime$2, this).abort();
		_classPrivateFieldGet2(_resizeObserver, this).disconnect();
		_classPrivateFieldGet2(_container, this).remove();
	}
};
function _renderAll(changed) {
	const elements = new Set(_classPrivateFieldGet2(_state$1, this)?.elements);
	for (const [element, box] of _classPrivateFieldGet2(_all, this)) {
		if (elements.has(element) && element.isConnected) continue;
		_assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, box);
		_classPrivateFieldGet2(_all, this).delete(element);
	}
	for (const element of elements) {
		if (!element.isConnected || _classPrivateFieldGet2(_all, this).has(element) && element !== changed) continue;
		const framework = _classPrivateFieldGet2(_registry$1, this)?.getForElement(element)[0]?.name ?? "default";
		const label = _classPrivateFieldGet2(_registry$1, this)?.get(framework)?.getDisplayName(element) ?? "";
		_classPrivateFieldGet2(_all, this).set(element, _assertClassBrand(_Highlighter_brand, this, _box).call(this, element, framework, "all", label, _classPrivateFieldGet2(_all, this).get(element)));
	}
}
function _box(element, framework, mode, label = "", previous) {
	const box = previous ?? document.createElement("div");
	if (box.dataset.framework !== framework) box.dataset.framework = framework;
	if (box._target !== element) {
		_assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, box, false);
		box._target = element;
		const count = _classPrivateFieldGet2(_observed, this).get(element) ?? 0;
		if (!count) _classPrivateFieldGet2(_resizeObserver, this).observe(element, { box: "border-box" });
		_classPrivateFieldGet2(_observed, this).set(element, count + 1);
	}
	if (label) {
		const caption = box.firstElementChild ?? box.appendChild(document.createElement("span"));
		if (caption.textContent !== label) caption.textContent = label;
	} else box.firstElementChild?.remove();
	_assertClassBrand(_Highlighter_brand, this, _position).call(this, box, element.getBoundingClientRect());
	if (!previous) {
		box.className = "box";
		box.dataset.mode = mode;
		_classPrivateFieldGet2(_container, this).appendChild(box);
	}
	return box;
}
function _refreshTarget(element) {
	if (!_classPrivateFieldGet2(_observed, this).has(element)) return;
	const rect = element.isConnected ? element.getBoundingClientRect() : null;
	const event = _classPrivateFieldGet2(_events, this).get(element);
	if (!rect && event) _assertClassBrand(_Highlighter_brand, this, _removeEvent).call(this, element, event);
	for (const box of [
		_classPrivateFieldGet2(_all, this).get(element),
		event?.box,
		_classPrivateFieldGet2(_hover, this),
		_classPrivateFieldGet2(_selected, this)
	]) {
		if (box?._target !== element) continue;
		if (rect) _assertClassBrand(_Highlighter_brand, this, _position).call(this, box, rect);
		else _assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, box);
	}
	if (!rect) _classPrivateFieldGet2(_all, this).delete(element);
	if (!_classPrivateFieldGet2(_hover, this)?._target) _classPrivateFieldSet2(_hover, this, null);
	if (!_classPrivateFieldGet2(_selected, this)?._target) _classPrivateFieldSet2(_selected, this, null);
}
function _removeBox(box, remove = true) {
	if (!box) return;
	const target = box._target;
	if (target) {
		const count = (_classPrivateFieldGet2(_observed, this).get(target) ?? 1) - 1;
		if (count) _classPrivateFieldGet2(_observed, this).set(target, count);
		else {
			_classPrivateFieldGet2(_observed, this).delete(target);
			_classPrivateFieldGet2(_resizeObserver, this).unobserve(target);
		}
		delete box._target;
	}
	if (remove) box.remove();
}
function _removeEvent(element, entry) {
	if (_classPrivateFieldGet2(_events, this).get(element) !== entry) return;
	if (entry.timer) clearTimeout(entry.timer);
	if (entry.raf !== null) cancelAnimationFrame(entry.raf);
	_assertClassBrand(_Highlighter_brand, this, _removeBox).call(this, entry.box);
	_classPrivateFieldGet2(_events, this).delete(element);
}
function _position(box, rect) {
	const gutter = _BOX_GUTTER._;
	for (const [property, value] of Object.entries({
		translate: `${rect.left - gutter}px ${rect.top - gutter}px`,
		width: `${rect.width + gutter * 2}px`,
		height: `${rect.height + gutter * 2}px`
	})) if (box.style.getPropertyValue(property) !== value) box.style.setProperty(property, value);
}
var _EVENT_LIFETIME = { _: 1200 };
var _BOX_GUTTER = { _: 2 };
var _registry = /* @__PURE__ */ new WeakMap();
var _state = /* @__PURE__ */ new WeakMap();
var _detector = /* @__PURE__ */ new WeakMap();
var _targetSelector = /* @__PURE__ */ new WeakMap();
var _visual = /* @__PURE__ */ new WeakMap();
var _panel = /* @__PURE__ */ new WeakMap();
var _pullTab = /* @__PURE__ */ new WeakMap();
var _eventMonitor = /* @__PURE__ */ new WeakMap();
var _timeline = /* @__PURE__ */ new WeakMap();
var _relationshipEngine = /* @__PURE__ */ new WeakMap();
var _lifetime$1 = /* @__PURE__ */ new WeakMap();
var _refreshFrame = /* @__PURE__ */ new WeakMap();
var _readyFrame = /* @__PURE__ */ new WeakMap();
var _dynamicEventsQueued = /* @__PURE__ */ new WeakMap();
var _phase = /* @__PURE__ */ new WeakMap();
var _host$1 = /* @__PURE__ */ new WeakMap();
var _config$1 = /* @__PURE__ */ new WeakMap();
var _application = /* @__PURE__ */ new WeakMap();
var _InspectorRuntime_brand = /* @__PURE__ */ new WeakSet();
var InspectorRuntime = class {
	constructor(host, shadow, config, application) {
		_classPrivateMethodInitSpec(this, _InspectorRuntime_brand);
		_classPrivateFieldInitSpec(this, _registry, void 0);
		_classPrivateFieldInitSpec(this, _state, new StateManager());
		_classPrivateFieldInitSpec(this, _detector, void 0);
		_classPrivateFieldInitSpec(this, _targetSelector, void 0);
		_classPrivateFieldInitSpec(this, _visual, void 0);
		_classPrivateFieldInitSpec(this, _panel, void 0);
		_classPrivateFieldInitSpec(this, _pullTab, null);
		_classPrivateFieldInitSpec(this, _eventMonitor, void 0);
		_classPrivateFieldInitSpec(this, _timeline, void 0);
		_classPrivateFieldInitSpec(this, _relationshipEngine, void 0);
		_classPrivateFieldInitSpec(this, _lifetime$1, new AbortController());
		_classPrivateFieldInitSpec(this, _refreshFrame, null);
		_classPrivateFieldInitSpec(this, _readyFrame, null);
		_classPrivateFieldInitSpec(this, _dynamicEventsQueued, false);
		_classPrivateFieldInitSpec(this, _phase, "observing");
		_classPrivateFieldInitSpec(this, _host$1, void 0);
		_classPrivateFieldInitSpec(this, _config$1, void 0);
		_classPrivateFieldInitSpec(this, _application, void 0);
		_classPrivateFieldSet2(_host$1, this, host);
		_classPrivateFieldSet2(_config$1, this, config);
		_classPrivateFieldSet2(_application, this, application);
		_classPrivateFieldSet2(_registry, this, new PluginRegistry([
			new LiveComponentPlugin(),
			new TurboPlugin(),
			new StimulusPlugin(application())
		]));
		_classPrivateFieldSet2(_eventMonitor, this, new EventMonitor(500, (draft) => {
			if (draft.target && _classPrivateFieldGet2(_phase, this) === "observing") _classPrivateFieldGet2(_registry, this).notifyEvent(draft, draft.target);
			draft.owner = _assertClassBrand(_InspectorRuntime_brand, this, _findNearestComponent).call(this, draft.target);
		}));
		const monitor = _classPrivateFieldGet2(_eventMonitor, this);
		_classPrivateFieldGet2(_registry, this).setEventRecorder((entry) => monitor.record(entry));
		_classPrivateFieldSet2(_detector, this, new ComponentDetector(_classPrivateFieldGet2(_registry, this), _classPrivateFieldGet2(_state, this), host, _classPrivateFieldGet2(_config$1, this).ignore_selectors || []));
		_classPrivateFieldSet2(_visual, this, new Highlighter(shadow, _classPrivateFieldGet2(_state, this), _classPrivateFieldGet2(_registry, this)));
		_classPrivateFieldSet2(_targetSelector, this, new TargetSelector(_classPrivateFieldGet2(_visual, this), _classPrivateFieldGet2(_registry, this), (active) => _classPrivateFieldGet2(_panel, this).setTargetModeActive(active)));
		_classPrivateFieldSet2(_relationshipEngine, this, new RelationshipEngine(_classPrivateFieldGet2(_registry, this), _classPrivateFieldGet2(_state, this)));
		_classPrivateFieldSet2(_timeline, this, new Timeline(_classPrivateFieldGet2(_eventMonitor, this), {
			onHighlight: (element, framework) => element ? _classPrivateFieldGet2(_visual, this).hover(element, framework) : _classPrivateFieldGet2(_visual, this).clearHover(),
			onSelect: (element) => {
				const component = _assertClassBrand(_InspectorRuntime_brand, this, _findNearestComponent).call(this, element);
				if (component) _classPrivateFieldGet2(_panel, this).drillInto(component);
			}
		}));
		_classPrivateFieldGet2(_eventMonitor, this).addListener((entry) => _assertClassBrand(_InspectorRuntime_brand, this, _onEvent).call(this, entry));
		_classPrivateFieldSet2(_panel, this, new Panel(_classPrivateFieldGet2(_state, this), _classPrivateFieldGet2(_registry, this), _classPrivateFieldGet2(_eventMonitor, this), _classPrivateFieldGet2(_timeline, this), host, _classPrivateFieldGet2(_relationshipEngine, this), _classPrivateFieldGet2(_config$1, this).packages || {}));
		_classPrivateFieldGet2(_panel, this).setActionCallbacks({
			target: () => this.toggleTargetMode(),
			overlay: () => this.toggleOverlay()
		});
		_classPrivateFieldGet2(_panel, this).setVisualCallbacks({
			onPreview: ({ element, framework, label }) => _classPrivateFieldGet2(_visual, this).hover(element, framework, label),
			onClearPreview: () => _classPrivateFieldGet2(_visual, this).clearHover(),
			onSelect: ({ element, framework, label }) => _classPrivateFieldGet2(_visual, this).select(element, framework, label),
			onClearSelection: () => _classPrivateFieldGet2(_visual, this).deselect()
		});
		shadow.append(_classPrivateFieldGet2(_panel, this).element);
		_assertClassBrand(_InspectorRuntime_brand, this, _registerPluginStaticEvents).call(this);
		_classPrivateFieldGet2(_eventMonitor, this).start();
		const { signal } = _classPrivateFieldGet2(_lifetime$1, this);
		if (config.pull_tab !== false) {
			_classPrivateFieldSet2(_pullTab, this, createPullTab(host, () => {
				host.open();
				_classPrivateFieldGet2(_panel, this).element.querySelector("[aria-label=\"Hide inspector\"]")?.focus({ preventScroll: true });
			}, signal));
			shadow.append(_classPrivateFieldGet2(_pullTab, this));
		}
		if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => this.scan(), {
			once: true,
			signal
		});
		bindOpenShortcut(() => host.open(), signal);
		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape" && _classPrivateFieldGet2(_panel, this).drillBack()) event.preventDefault();
		}, { signal });
		window.addEventListener("scroll", () => this.refreshVisual(), {
			capture: true,
			passive: true,
			signal
		});
		const updateDynamicEvents = () => {
			if (_classPrivateFieldGet2(_dynamicEventsQueued, this)) return;
			_classPrivateFieldSet2(_dynamicEventsQueued, this, true);
			queueMicrotask(() => {
				_classPrivateFieldSet2(_dynamicEventsQueued, this, false);
				if (host.isConnected && _classPrivateFieldGet2(_phase, this) !== "destroyed") _assertClassBrand(_InspectorRuntime_brand, this, _registerPluginDynamicEvents).call(this);
			});
		};
		for (const type of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) _classPrivateFieldGet2(_state, this).addEventListener(type, updateDynamicEvents, { signal });
		this.scan();
		_classPrivateFieldGet2(_detector, this).observe();
		_classPrivateFieldSet2(_readyFrame, this, requestAnimationFrame(() => {
			_classPrivateFieldSet2(_readyFrame, this, null);
			this.scan();
			host.setAttribute("ready", "");
		}));
	}
	getStatus() {
		const components = _classPrivateFieldGet2(_state, this).countByPlugin();
		return {
			installed: { ..._classPrivateFieldGet2(_config$1, this).packages },
			used: {
				stimulus: Boolean(document.querySelector("[data-controller]")),
				livecomponent: Boolean(document.querySelector("[data-controller~=\"live\"]")),
				turbo: Boolean(globalThis.Turbo || customElements.get("turbo-frame") || document.querySelector("turbo-frame, turbo-stream-source"))
			},
			components
		};
	}
	close() {
		_classPrivateFieldGet2(_panel, this).clearPageRuleSelection();
		_classPrivateFieldGet2(_targetSelector, this).disable();
		_classPrivateFieldGet2(_visual, this).clearHover();
		_classPrivateFieldGet2(_visual, this).deselect();
		if (_classPrivateFieldGet2(_panel, this).element.contains(_classPrivateFieldGet2(_host$1, this).shadowRoot?.activeElement ?? null)) _classPrivateFieldGet2(_pullTab, this)?.querySelector("button")?.focus({ preventScroll: true });
	}
	refreshVisual() {
		if (_classPrivateFieldGet2(_refreshFrame, this) !== null || _classPrivateFieldGet2(_phase, this) !== "observing") return;
		_classPrivateFieldSet2(_refreshFrame, this, requestAnimationFrame(() => {
			_classPrivateFieldSet2(_refreshFrame, this, null);
			_classPrivateFieldGet2(_visual, this).refresh();
		}));
	}
	scan() {
		if (_classPrivateFieldGet2(_phase, this) !== "observing") return;
		const application = _classPrivateFieldGet2(_application, this).call(this);
		_classPrivateFieldGet2(_registry, this).get("stimulus")?.setApplication(application);
		_classPrivateFieldGet2(_detector, this).scan();
		_assertClassBrand(_InspectorRuntime_brand, this, _registerPluginDynamicEvents).call(this);
	}
	clear() {
		this.clearLog();
		_classPrivateFieldGet2(_visual, this).clearAll();
		_classPrivateFieldGet2(_panel, this).clearFocus();
		_classPrivateFieldGet2(_panel, this).setOverlayActive(false);
		_classPrivateFieldGet2(_panel, this).refresh();
	}
	clearLog() {
		_classPrivateFieldGet2(_timeline, this).clear();
		_classPrivateFieldGet2(_panel, this).clearActivities();
	}
	toggleLogPaused() {
		if (_classPrivateFieldGet2(_timeline, this).paused) _classPrivateFieldGet2(_timeline, this).resume();
		else _classPrivateFieldGet2(_timeline, this).pause();
		return Boolean(_classPrivateFieldGet2(_timeline, this).paused);
	}
	inspectElement(element) {
		if (!element || _classPrivateFieldGet2(_phase, this) !== "observing") return;
		const data = _classPrivateFieldGet2(_detector, this).inspect(element);
		if (!data) return;
		_classPrivateFieldGet2(_visual, this).select(element, data.keys().next().value);
		_classPrivateFieldGet2(_panel, this).drillInto(element, data);
	}
	toggleTargetMode() {
		return _classPrivateFieldGet2(_targetSelector, this).toggle((element) => this.inspectElement(element));
	}
	toggleOverlay() {
		const visible = Boolean(_classPrivateFieldGet2(_visual, this).toggleAll());
		_classPrivateFieldGet2(_panel, this).setOverlayActive(visible);
		return visible;
	}
	suspend() {
		if (_classPrivateFieldGet2(_phase, this) !== "observing") return;
		_classPrivateFieldSet2(_phase, this, "suspended");
		_classPrivateFieldGet2(_pullTab, this)?.removeAttribute("data-near");
		if (_classPrivateFieldGet2(_refreshFrame, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_refreshFrame, this));
		if (_classPrivateFieldGet2(_readyFrame, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_readyFrame, this));
		_classPrivateFieldSet2(_refreshFrame, this, _classPrivateFieldSet2(_readyFrame, this, null));
		_classPrivateFieldGet2(_panel, this).suspendForNavigation();
		_classPrivateFieldGet2(_targetSelector, this).disable();
		_classPrivateFieldGet2(_visual, this).clearAll();
		_classPrivateFieldGet2(_detector, this).disconnect();
	}
	resume() {
		if (_classPrivateFieldGet2(_phase, this) === "destroyed") return;
		if (_classPrivateFieldGet2(_phase, this) === "observing") {
			this.scan();
			return;
		}
		_classPrivateFieldSet2(_phase, this, "observing");
		_classPrivateFieldGet2(_state, this).clear();
		_classPrivateFieldGet2(_relationshipEngine, this).invalidate();
		_classPrivateFieldGet2(_detector, this).observe();
		this.scan();
		_classPrivateFieldGet2(_panel, this).resumeAfterNavigation();
		_classPrivateFieldGet2(_host$1, this).setAttribute("ready", "");
	}
	destroy() {
		if (_classPrivateFieldGet2(_phase, this) === "destroyed") return;
		_classPrivateFieldSet2(_phase, this, "destroyed");
		_classPrivateFieldGet2(_lifetime$1, this).abort();
		if (_classPrivateFieldGet2(_refreshFrame, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_refreshFrame, this));
		if (_classPrivateFieldGet2(_readyFrame, this) !== null) cancelAnimationFrame(_classPrivateFieldGet2(_readyFrame, this));
		_classPrivateFieldGet2(_panel, this).destroy();
		_classPrivateFieldGet2(_timeline, this).destroy();
		_classPrivateFieldGet2(_targetSelector, this).destroy();
		_classPrivateFieldGet2(_visual, this).destroy();
		_classPrivateFieldGet2(_eventMonitor, this).destroy();
		_classPrivateFieldGet2(_relationshipEngine, this).destroy();
		_classPrivateFieldGet2(_detector, this).destroy();
		_classPrivateFieldGet2(_registry, this).destroy();
	}
};
function _onEvent(entry) {
	if (_classPrivateFieldGet2(_phase, this) !== "observing") return;
	if (entry.type === "livecomponent" && entry.target) _classPrivateFieldGet2(_detector, this).refresh(entry.target);
	if (_classPrivateFieldGet2(_host$1, this).isOpen && _classPrivateFieldGet2(_panel, this).isComponentListVisible && entry.relatedElements?.length) for (const element of entry.relatedElements) _classPrivateFieldGet2(_visual, this).pulse(element, entry.type, entry.label || entry.event);
	const component = _assertClassBrand(_InspectorRuntime_brand, this, _findNearestComponent).call(this, entry.target);
	if (!component) return;
	if (!_classPrivateFieldGet2(_host$1, this).isOpen || !_classPrivateFieldGet2(_panel, this).isComponentListVisible) return;
	const framework = _classPrivateFieldGet2(_state, this).get(component)?.keys().next().value ?? entry.type;
	_classPrivateFieldGet2(_visual, this).pulse(component, framework, entry.label || entry.event);
}
function _findNearestComponent(element) {
	let current = element ?? null;
	while (current) {
		if (_classPrivateFieldGet2(_state, this).get(current)) return current;
		current = current.parentElement;
	}
	return null;
}
function _registerPluginStaticEvents() {
	const { staticEvents } = _classPrivateFieldGet2(_registry, this).collectMonitoredEvents();
	for (const [pluginName, events] of staticEvents) _classPrivateFieldGet2(_eventMonitor, this).monitorEvents(events, pluginName);
}
function _registerPluginDynamicEvents() {
	const byPlugin = /* @__PURE__ */ new Map();
	for (const element of _classPrivateFieldGet2(_state, this).elements) for (const pluginName of _classPrivateFieldGet2(_state, this).get(element)?.keys() ?? []) {
		if (!byPlugin.has(pluginName)) byPlugin.set(pluginName, []);
		byPlugin.get(pluginName).push(element);
	}
	const { dynamicEvents } = _classPrivateFieldGet2(_registry, this).collectMonitoredEvents(byPlugin);
	for (const [pluginName, events] of dynamicEvents) _classPrivateFieldGet2(_eventMonitor, this).setDynamicEvents(events, pluginName);
}
const OPEN_ATTRIBUTE = "data-ux-inspector-open";
const WIDTH_PROPERTY = "--ux-inspector-width";
var _host = /* @__PURE__ */ new WeakMap();
var _style = /* @__PURE__ */ new WeakMap();
var _width = /* @__PURE__ */ new WeakMap();
var DockLayout = class {
	constructor(host) {
		_classPrivateFieldInitSpec(this, _host, void 0);
		_classPrivateFieldInitSpec(this, _style, null);
		_classPrivateFieldInitSpec(this, _width, null);
		_classPrivateFieldSet2(_host, this, host);
	}
	resize(width) {
		_classPrivateFieldSet2(_width, this, Math.round(Math.min(Math.max(208, width), Math.max(208, window.innerWidth * .8))));
		_classPrivateFieldGet2(_host, this).style.setProperty("--panel-width", `${_classPrivateFieldGet2(_width, this)}px`);
		this.sync();
		return _classPrivateFieldGet2(_width, this);
	}
	sync() {
		if (!_classPrivateFieldGet2(_host, this).isConnected || !_classPrivateFieldGet2(_host, this).hasAttribute("open")) {
			this.detach();
			return;
		}
		if (!_classPrivateFieldGet2(_style, this)) {
			_classPrivateFieldSet2(_style, this, document.createElement("style"));
			_classPrivateFieldGet2(_style, this).dataset.uxInspectorLayout = "";
			_classPrivateFieldGet2(_style, this).textContent = `@media (min-width:42.5rem){html[${OPEN_ATTRIBUTE}]{box-sizing:border-box!important;padding-right:var(${WIDTH_PROPERTY},21.25rem)!important}}`;
		}
		if (!_classPrivateFieldGet2(_style, this).isConnected) document.head.append(_classPrivateFieldGet2(_style, this));
		if (_classPrivateFieldGet2(_width, this) !== null) {
			_classPrivateFieldSet2(_width, this, Math.min(_classPrivateFieldGet2(_width, this), Math.max(208, window.innerWidth * .8)));
			_classPrivateFieldGet2(_host, this).style.setProperty("--panel-width", `${_classPrivateFieldGet2(_width, this)}px`);
			document.documentElement.style.setProperty(WIDTH_PROPERTY, `${_classPrivateFieldGet2(_width, this)}px`);
		}
		document.documentElement.setAttribute(OPEN_ATTRIBUTE, "");
	}
	detach() {
		document.documentElement.removeAttribute(OPEN_ATTRIBUTE);
		document.documentElement.style.removeProperty(WIDTH_PROPERTY);
		_classPrivateFieldGet2(_style, this)?.remove();
	}
};
let stylesheet;
let stimulusApplication = null;
function availableStimulusApplication() {
	const application = stimulusApplication ?? globalThis.Stimulus;
	return typeof application?.getControllerForElementAndIdentifier === "function" ? application : null;
}
var _runtime = /* @__PURE__ */ new WeakMap();
var _config = /* @__PURE__ */ new WeakMap();
var _layout = /* @__PURE__ */ new WeakMap();
var _lifetime = /* @__PURE__ */ new WeakMap();
var _teardown = /* @__PURE__ */ new WeakMap();
var _UXInspector_brand = /* @__PURE__ */ new WeakSet();
var UXInspector = class extends HTMLElement {
	constructor(..._args) {
		super(..._args);
		_classPrivateMethodInitSpec(this, _UXInspector_brand);
		_classPrivateFieldInitSpec(this, _runtime, null);
		_classPrivateFieldInitSpec(this, _config, {});
		_classPrivateFieldInitSpec(this, _layout, new DockLayout(this));
		_classPrivateFieldInitSpec(this, _lifetime, new AbortController());
		_classPrivateFieldInitSpec(this, _teardown, null);
	}
	connectedCallback() {
		if (_classPrivateFieldGet2(_teardown, this) !== null) clearTimeout(_classPrivateFieldGet2(_teardown, this));
		_classPrivateFieldSet2(_teardown, this, null);
		if (_classPrivateFieldGet2(_runtime, this)) {
			_classPrivateFieldGet2(_runtime, this).resume();
			_classPrivateFieldGet2(_layout, this).sync();
			return;
		}
		_assertClassBrand(_UXInspector_brand, this, _readConfig).call(this);
		const shadow = this.shadowRoot ?? this.attachShadow({ mode: "open" });
		shadow.replaceChildren();
		if (stylesheet?.text) {
			const sheet = new CSSStyleSheet();
			sheet.replaceSync(stylesheet.text);
			shadow.adoptedStyleSheets = [sheet];
		} else {
			const link = document.createElement("link");
			link.rel = "stylesheet";
			link.href = this.getAttribute("data-css-url") || stylesheet?.url || "";
			shadow.append(link);
		}
		_classPrivateFieldSet2(_lifetime, this, new AbortController());
		const { signal } = _classPrivateFieldGet2(_lifetime, this);
		_classPrivateFieldSet2(_runtime, this, new InspectorRuntime(this, shadow, _classPrivateFieldGet2(_config, this), availableStimulusApplication));
		document.addEventListener("turbo:before-cache", () => {
			_classPrivateFieldGet2(_runtime, this)?.suspend();
			_classPrivateFieldGet2(_layout, this).detach();
		}, { signal });
		document.addEventListener("turbo:render", () => {
			if (!this.isConnected) return;
			_classPrivateFieldGet2(_runtime, this)?.resume();
			_classPrivateFieldGet2(_layout, this).sync();
		}, { signal });
		window.addEventListener("resize", () => {
			_classPrivateFieldGet2(_layout, this).sync();
			_classPrivateFieldGet2(_runtime, this)?.refreshVisual();
		}, {
			signal,
			passive: true
		});
		_classPrivateFieldGet2(_layout, this).sync();
	}
	disconnectedCallback() {
		_classPrivateFieldGet2(_layout, this).detach();
		_classPrivateFieldGet2(_runtime, this)?.suspend();
		_classPrivateFieldSet2(_teardown, this, setTimeout(() => {
			_classPrivateFieldGet2(_runtime, this)?.destroy();
			_classPrivateFieldSet2(_runtime, this, null);
			_classPrivateFieldGet2(_lifetime, this).abort();
			_classPrivateFieldSet2(_teardown, this, null);
		}, 1e3));
	}
	get isOpen() {
		return this.hasAttribute("open");
	}
	getStatus() {
		return _classPrivateFieldGet2(_runtime, this)?.getStatus() ?? {};
	}
	open() {
		this.scan();
		this.setAttribute("open", "");
		_classPrivateFieldGet2(_layout, this).sync();
	}
	close() {
		this.removeAttribute("open");
		_classPrivateFieldGet2(_layout, this).sync();
		_classPrivateFieldGet2(_runtime, this)?.close();
	}
	toggle() {
		if (this.isOpen) this.close();
		else this.open();
	}
	setPanelWidth(width) {
		const value = _classPrivateFieldGet2(_layout, this).resize(width);
		_classPrivateFieldGet2(_runtime, this)?.refreshVisual();
		return value;
	}
	scan() {
		_classPrivateFieldGet2(_runtime, this)?.scan();
	}
	clear() {
		_classPrivateFieldGet2(_runtime, this)?.clear();
	}
	clearLog() {
		_classPrivateFieldGet2(_runtime, this)?.clearLog();
	}
	toggleLogPaused() {
		return _classPrivateFieldGet2(_runtime, this)?.toggleLogPaused() ?? false;
	}
	inspectElement(element) {
		_classPrivateFieldGet2(_runtime, this)?.inspectElement(element);
	}
	toggleTargetMode() {
		return _classPrivateFieldGet2(_runtime, this)?.toggleTargetMode() ?? false;
	}
	toggleOverlay() {
		return _classPrivateFieldGet2(_runtime, this)?.toggleOverlay() ?? false;
	}
};
function _readConfig() {
	const raw = this.getAttribute("data-config");
	if (!raw) return;
	try {
		_classPrivateFieldSet2(_config, this, JSON.parse(raw));
	} catch (error) {
		console.warn("[ux-inspector] Invalid config JSON:", error.message);
	}
}
function registerUXInspector(styles) {
	stylesheet = styles;
	if (!customElements.get("ux-inspector")) customElements.define("ux-inspector", UXInspector);
}
function connectStimulus(application) {
	const valid = typeof application?.getControllerForElementAndIdentifier === "function";
	stimulusApplication = valid ? application : null;
	document.querySelector("ux-inspector")?.scan();
	return valid;
}
registerUXInspector({ text: inspector_default });
export { UXInspector, connectStimulus };
