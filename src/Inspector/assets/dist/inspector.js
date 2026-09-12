var inspector_default = "@layer base {\n  :host {\n    --panel: light-dark(oklch(95.5% .004 270), oklch(19% .006 270));\n    --surface: light-dark(#fff, oklch(14.5% .004 270));\n    --surface-hover: color-mix(in srgb, var(--text) 5%, var(--surface));\n    --surface-zebra: color-mix(in srgb, var(--text) 2.5%, var(--surface));\n    --control-hover: color-mix(in srgb, var(--text) 5%, transparent);\n    --control-active: light-dark(#ffffffeb, #0000005c);\n    --line-soft: light-dark(#14192312, #ffffff16);\n    --detail-edge: light-dark(#0000001f, #00000080);\n    --text: light-dark(oklch(19% .014 270), oklch(97% .004 270));\n    --muted: light-dark(oklch(42% .012 270), oklch(79% .01 270));\n    --faint: light-dark(oklch(54% .01 270), oklch(70% .01 270));\n    --focus: light-dark(#000000e0, #ffffffe6);\n    --scroll-thumb: light-dark(#0000002e, #fff3);\n    --scroll-thumb-hover: light-dark(#00000061, #fff6);\n    --accent-ui: light-dark(oklch(54% .2 255), oklch(70% .16 252));\n    --stimulus: light-dark(#12825c, #43d69b);\n    --livecomponent: light-dark(#a45c00, #ffb84d);\n    --turbo: light-dark(#6f35b5, #b985ff);\n    --panel-width: 21.25rem;\n    --font-ui: system-ui, -apple-system, BlinkMacSystemFont, \"Segoe UI\", sans-serif;\n    --mono: ui-monospace, \"SFMono-Regular\", Menlo, monospace;\n    --space-1: .25rem;\n    --space-2: .5rem;\n    --space-3: .75rem;\n    --space-4: 1rem;\n    --space-6: 1.5rem;\n    --text-xs: .6875rem;\n    --text-sm: .75rem;\n    --text-md: .8125rem;\n    --group-title-size: .5625rem;\n    --key-value-size: .6875rem;\n    --weight: 400;\n    --weight-strong: 500;\n    --target: 2.5rem;\n    --radius-sm: .25rem;\n    --radius-md: .375rem;\n    z-index: 2147483644;\n    font: var(--weight) var(--text-md)/1.5 var(--font-ui);\n    -webkit-font-smoothing: antialiased;\n    -moz-osx-font-smoothing: grayscale;\n    pointer-events: none;\n    position: fixed;\n    inset: 0;\n  }\n\n  :host(:not([open])) {\n    width: 0;\n    height: 100%;\n    inset: auto 0 0 auto;\n  }\n\n  :host *, :host :before, :host :after {\n    box-sizing: border-box;\n  }\n\n  [hidden] {\n    display: none !important;\n  }\n\n  button, input {\n    font: inherit;\n    border: 0;\n  }\n\n  button {\n    color: inherit;\n    cursor: pointer;\n    background: none;\n  }\n\n  button:focus-visible, input:focus-visible {\n    outline: 2px solid var(--focus);\n    outline-offset: -2px;\n  }\n\n  svg {\n    fill: none;\n    stroke: currentColor;\n    stroke-linecap: round;\n    stroke-linejoin: round;\n    stroke-width: 1.75px;\n    width: 1rem;\n    height: 1rem;\n  }\n\n  [data-framework=\"stimulus\"] {\n    --framework: var(--stimulus);\n  }\n\n  [data-framework=\"livecomponent\"] {\n    --framework: var(--livecomponent);\n  }\n\n  [data-framework=\"turbo\"] {\n    --framework: var(--turbo);\n  }\n\n  [data-framework=\"default\"] {\n    --framework: var(--muted);\n  }\n\n  .pane {\n    flex-direction: column;\n    min-height: 0;\n    display: flex;\n  }\n\n  :where(.stack-title, .stack-badge, .selector, .detail-head h2, .detail-selector, .expandable-text, .key-value > .value, .tree .key, .relation > .key, .relation > .value, .disclosure .name, .event-target, .raw-list strong) {\n    text-overflow: ellipsis;\n    white-space: nowrap;\n    overflow: hidden;\n  }\n}\n\n@layer components {\n  .inspector {\n    color: var(--text);\n    color-scheme: dark;\n    z-index: 2;\n    width: min(var(--panel-width), 100vw);\n    background: var(--panel);\n    border-left: 1px solid var(--line-soft);\n    visibility: hidden;\n    pointer-events: auto;\n    height: 100dvh;\n    position: absolute;\n    inset: 0 0 0 auto;\n    overflow: hidden;\n    translate: 100%;\n    container: inspector / inline-size;\n\n    :host([ready]) & {\n      transition: translate .18s cubic-bezier(.2, 0, 0, 1);\n    }\n\n    :host([open]) & {\n      visibility: visible;\n      translate: 0;\n    }\n  }\n\n  .pull-tab {\n    color: var(--text);\n    position: fixed;\n    inset: 0;\n    overflow: clip;\n\n    :host([open]) & {\n      display: none;\n    }\n\n    &:before {\n      content: \"\";\n      background: var(--muted);\n      opacity: 0;\n      width: 3px;\n      transition: opacity .12s;\n      position: absolute;\n      inset: 0 0 0 auto;\n    }\n\n    & > button {\n      border: 1px solid var(--line-soft);\n      border-radius: var(--radius-md) 0 0 var(--radius-md);\n      background: var(--panel);\n      width: 2.5rem;\n      height: 2.5rem;\n      font-weight: var(--weight-strong);\n      pointer-events: auto;\n      border-right: 0;\n      transition: translate .12s;\n      position: absolute;\n      top: 50%;\n      right: 0;\n      translate: 2rem -50%;\n    }\n\n    &:is([data-near], :focus-within) {\n      &:before {\n        opacity: 1;\n      }\n\n      & > button {\n        translate: 0 -50%;\n      }\n    }\n\n    @media (hover: none) {\n      & > button {\n        translate: 0 -50%;\n      }\n    }\n  }\n\n  .panel-resize {\n    z-index: 5;\n    cursor: ew-resize;\n    touch-action: none;\n    width: .5rem;\n    position: absolute;\n    inset: 0 auto 0 0;\n\n    &:after {\n      background: var(--faint);\n      content: \"\";\n      opacity: 0;\n      width: 1px;\n      transition: opacity .12s;\n      position: absolute;\n      inset: 0 auto 0 0;\n    }\n  }\n\n  .panel-resize:is(:hover, :focus-visible):after, .inspector[data-resizing] .panel-resize:after {\n    opacity: .8;\n  }\n\n  .inspector > header {\n    min-height: var(--target);\n    padding: 0 var(--space-3);\n    background: var(--panel);\n    box-shadow: inset 0 -.5px var(--line-soft);\n    grid-template-columns: 1fr auto 1fr;\n    align-items: center;\n    display: grid;\n\n    & > strong {\n      color: var(--muted);\n      font-size: var(--text-xs);\n      font-weight: var(--weight);\n      letter-spacing: .12em;\n      text-transform: uppercase;\n      grid-column: 2;\n    }\n  }\n\n  .header-actions {\n    gap: var(--space-1);\n    display: flex;\n  }\n\n  .header-end {\n    grid-column: 3;\n    justify-self: end;\n  }\n\n  .icon-button {\n    width: var(--target);\n    min-height: var(--target);\n    border-radius: var(--radius-sm);\n    color: var(--muted);\n    background: none;\n    flex: none;\n    place-items: center;\n    padding: 0;\n    transition: background-color .12s, color .12s;\n    display: inline-grid;\n\n    &:hover, &.active {\n      color: var(--text);\n    }\n\n    &[data-action=\"target\"].active {\n      color: var(--stimulus);\n\n      & svg {\n        stroke-width: 2.25px;\n      }\n    }\n  }\n\n  [data-action=\"activity\"] {\n    position: relative;\n\n    & b {\n      background: var(--control-hover);\n      min-width: 1rem;\n      height: 1rem;\n      box-shadow: inset 0 0 0 .5px var(--line-soft);\n      color: var(--muted);\n      font: var(--weight) .5625rem/1rem var(--mono);\n      text-align: center;\n      border-radius: 99rem;\n      padding: 0 .25rem;\n      position: absolute;\n      top: 0;\n      right: -.125rem;\n    }\n  }\n\n  .activity-tools, .filters {\n    background: var(--surface);\n    box-shadow: inset 0 -1px var(--line-soft);\n    flex: none;\n    display: grid;\n\n    & > input {\n      border-radius: var(--radius-sm);\n      appearance: none;\n      background: var(--panel);\n      width: calc(100% - 1.25rem);\n      min-height: 2rem;\n      color: var(--text);\n      margin: .5rem .625rem;\n      padding: 0 .625rem;\n\n      &:hover {\n        background: var(--surface-hover);\n      }\n\n      &::placeholder {\n        color: var(--faint);\n      }\n    }\n  }\n\n  .filter-list {\n    background: var(--panel);\n    align-items: center;\n    gap: .125rem;\n    padding: .125rem;\n    display: flex;\n  }\n\n  .filter {\n    justify-content: center;\n    align-items: center;\n    gap: var(--space-2);\n    min-width: 0;\n    min-height: 2.25rem;\n    padding: 0 var(--space-2);\n    color: var(--faint);\n    background: none;\n    border-radius: 0;\n    flex: 1;\n    transition: background-color .12s, color .12s;\n    display: inline-flex;\n\n    &:hover {\n      background: var(--control-hover);\n      color: var(--text);\n    }\n\n    &.active, &[role=\"tab\"][aria-selected=\"true\"] {\n      border-radius: var(--radius-sm);\n      background: var(--surface);\n      color: var(--text);\n      box-shadow: 0 .5px 1px #0000002e;\n    }\n\n    &.unavailable {\n      opacity: .5;\n    }\n  }\n\n  .filter b, .activity, .event-count {\n    min-width: 1rem;\n    padding: .0625rem var(--space-1);\n    background: color-mix(in srgb, var(--text) 7%, transparent);\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.25 var(--mono);\n    text-align: center;\n    font-variant-numeric: tabular-nums;\n    border-radius: 99rem;\n  }\n\n  .inspector > main {\n    background: var(--surface);\n    flex: 1;\n    min-height: 0;\n    overflow: hidden;\n  }\n\n  :where(.stack-body, .timeline) {\n    scrollbar-color: var(--scroll-thumb) transparent;\n    scrollbar-width: thin;\n  }\n\n  :where(.stack-body, .timeline):is(:hover, :focus-within) {\n    scrollbar-color: var(--scroll-thumb-hover) transparent;\n  }\n\n  .stack {\n    height: 100%;\n    position: relative;\n  }\n\n  .stack-nav {\n    flex: none;\n    display: none;\n  }\n\n  .stack.is-drilled .stack-nav {\n    z-index: 2;\n    width: var(--target);\n    height: 2.75rem;\n    display: block;\n    position: absolute;\n    top: 0;\n    left: 0;\n  }\n\n  .stack-link {\n    align-items: center;\n    gap: var(--space-2);\n    width: 100%;\n    min-height: 2.75rem;\n    padding: var(--space-2) var(--space-3);\n    background: var(--panel);\n    color: var(--muted);\n    text-align: start;\n    display: flex;\n  }\n\n  .stack-link:hover {\n    background: var(--control-hover);\n    color: var(--text);\n  }\n\n  .stack.is-drilled .stack-link {\n    display: none;\n  }\n\n  .stack.is-drilled .stack-link.previous {\n    width: var(--target);\n    background: none;\n    justify-content: center;\n    min-height: 2.75rem;\n    padding: 0;\n    display: flex;\n  }\n\n  .stack.is-drilled .stack-link.previous :is(.stack-title, .stack-badge) {\n    clip-path: inset(50%);\n    white-space: nowrap;\n    width: 1px;\n    height: 1px;\n    position: absolute;\n    overflow: hidden;\n  }\n\n  .stack-link > svg {\n    flex: none;\n  }\n\n  .stack-title {\n    min-width: 0;\n    color: var(--text);\n    font-size: var(--text-md);\n    font-weight: var(--weight-strong);\n    flex: 1;\n  }\n\n  .stack-badge {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n  }\n\n  .stack-body {\n    flex: 1;\n    min-height: 0;\n    position: relative;\n    overflow: auto;\n  }\n\n  .stack-page {\n    height: 100%;\n  }\n\n  .drill-detail {\n    background: var(--surface);\n    flex: 1;\n    height: 100%;\n    overflow: hidden;\n  }\n\n  .component {\n    background: var(--surface);\n    transition: background-color .1s;\n\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    &:hover, &:focus-within {\n      background: var(--surface-hover);\n    }\n\n    &.selected {\n      background: var(--control-active);\n    }\n  }\n\n  .component-row {\n    align-items: center;\n    gap: var(--space-2);\n    width: 100%;\n    min-width: 0;\n    min-height: 2.75rem;\n    padding: 0 var(--space-3);\n    cursor: pointer;\n    text-align: start;\n    background: none;\n    display: flex;\n\n    &.activity-pulse .activity {\n      animation: .7s cubic-bezier(.2, 0, 0, 1) activity-count-pulse;\n    }\n  }\n\n  .identity {\n    flex: 1;\n    min-width: 0;\n\n    & strong {\n      color: var(--text);\n      font-size: var(--text-md);\n      font-weight: var(--weight-strong);\n      letter-spacing: -.01em;\n      text-overflow: ellipsis;\n      white-space: nowrap;\n      display: block;\n      overflow: hidden;\n    }\n  }\n\n  .selector {\n    min-width: 0;\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n    align-items: center;\n    gap: .375rem;\n    display: flex;\n\n    &:before {\n      background: var(--framework);\n      content: \"\";\n      border-radius: 50%;\n      flex: none;\n      width: .3125rem;\n      height: .3125rem;\n    }\n  }\n\n  .component-row > svg {\n    color: var(--faint);\n    flex: none;\n  }\n\n  .activity {\n    transition: background-color .6s cubic-bezier(.2, 0, 0, 1), color .6s cubic-bezier(.2, 0, 0, 1);\n  }\n\n  @keyframes activity-count-pulse {\n    0%, 20% {\n      background: color-mix(in srgb, var(--framework) 22%, transparent);\n      color: var(--framework);\n    }\n  }\n\n  .detail {\n    background: var(--surface);\n    flex: 1;\n  }\n\n  .detail-head {\n    align-items: center;\n    gap: 0 var(--space-2);\n    min-height: 2.5rem;\n    padding: var(--space-1) var(--space-3);\n    background: var(--panel);\n    box-shadow: inset 0 .5px var(--detail-edge),\n        inset 0 -.5px var(--detail-edge);\n    grid-template-columns: minmax(0, 1fr) auto;\n    display: grid;\n  }\n\n  .stack.is-drilled .detail-head {\n    padding-inline-start: calc(var(--target) + var(--space-2));\n  }\n\n  .detail-head h2 {\n    min-width: 0;\n    color: var(--text);\n    font-size: var(--text-sm);\n    font-weight: var(--weight-strong);\n    letter-spacing: -.01em;\n    margin: 0;\n  }\n\n  .framework {\n    color: var(--framework);\n    font: var(--weight-strong) .5625rem/1 var(--font-ui);\n    letter-spacing: .09em;\n    text-transform: uppercase;\n    grid-column: 2;\n  }\n\n  .detail-selector {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.5 var(--mono);\n    grid-column: 1 / -1;\n    margin: 0;\n  }\n\n  .expandable-text {\n    cursor: zoom-in;\n    min-width: 0;\n    max-width: 100%;\n  }\n\n  .expandable-text.expanded {\n    overflow-wrap: anywhere;\n    text-overflow: clip;\n    white-space: pre-wrap;\n    word-break: break-word;\n    cursor: zoom-out;\n    overflow: visible;\n  }\n\n  .expandable-text:focus-visible {\n    border-radius: var(--radius-sm);\n    outline: 2px solid var(--focus);\n    outline-offset: 1px;\n  }\n\n  .detail-body {\n    flex: 1;\n    min-width: 0;\n    min-height: 0;\n    overflow: auto;\n  }\n\n  .framework-detail {\n    background: var(--surface);\n  }\n\n  .framework-detail > h3 {\n    padding: var(--space-3);\n    background: var(--panel);\n    color: var(--muted);\n    font-size: var(--text-xs);\n    font-weight: var(--weight-strong);\n    letter-spacing: .035em;\n    margin: 0;\n  }\n\n  .detail-body[data-frameworks=\"1\"] .framework-detail > h3 {\n    display: none;\n  }\n\n  .empty {\n    place-content: center;\n    gap: var(--space-2);\n    min-height: 9.5rem;\n    padding: var(--space-6);\n    color: var(--muted);\n    text-align: center;\n    display: grid;\n\n    & strong {\n      color: var(--text);\n      font-size: var(--text-md);\n      font-weight: var(--weight-strong);\n    }\n\n    & small {\n      max-width: 14rem;\n      color: var(--faint);\n      font-size: var(--text-sm);\n      text-wrap: pretty;\n    }\n  }\n\n  .page-rule {\n    text-align: start;\n    background: none;\n    width: 100%;\n  }\n\n  .page-rule:hover {\n    background: var(--surface-hover);\n  }\n\n  .inspector > footer {\n    align-items: center;\n    gap: var(--space-2);\n    min-height: 2.125rem;\n    padding: 0 var(--space-2);\n    background: var(--panel);\n    color: var(--faint);\n    font-size: var(--text-xs);\n    letter-spacing: .08em;\n    text-transform: uppercase;\n    box-shadow: inset 0 1px var(--line-soft);\n    flex: none;\n    grid-template-columns: 1fr auto 1fr;\n    display: grid;\n    position: relative;\n  }\n\n  .monitor-status {\n    font-size: var(--group-title-size);\n    letter-spacing: .1em;\n    grid-area: 1 / 1;\n    justify-self: start;\n    align-items: center;\n    font-stretch: condensed;\n    display: flex;\n  }\n\n  .live-dot {\n    width: .3125rem;\n    height: .3125rem;\n    margin-right: var(--space-1);\n    background: var(--stimulus);\n    border-radius: 50%;\n  }\n\n  .monitor-status.paused .live-dot {\n    background: var(--faint);\n  }\n\n  .groups {\n    flex-direction: column;\n    gap: 0;\n    min-width: 0;\n    display: flex;\n  }\n\n  .group {\n    background: var(--surface);\n    overflow: hidden;\n\n    &[data-static] {\n      overflow: visible;\n    }\n\n    & > .title {\n      align-items: center;\n      gap: var(--space-1);\n      min-height: 1.6rem;\n      padding: 0 var(--space-3);\n      background: var(--panel);\n      box-shadow: inset 0 .5px var(--line-soft),\n            inset 0 -.5px var(--line-soft);\n      color: var(--muted);\n      font-size: var(--group-title-size);\n      font-stretch: condensed;\n      font-weight: var(--weight-strong);\n      letter-spacing: .1em;\n      text-transform: uppercase;\n      margin: 0;\n      display: flex;\n    }\n\n    & > summary.title {\n      cursor: pointer;\n      list-style: none;\n    }\n\n    & > .title > .icon {\n      width: .875rem;\n      color: var(--framework);\n      flex: 0 0 .875rem;\n      place-items: center;\n      display: grid;\n    }\n\n    & > .title > .icon svg {\n      stroke-width: 1.5px;\n      width: .6875rem;\n      height: .6875rem;\n    }\n\n    & > .title > .name {\n      flex: 1;\n      min-width: 0;\n    }\n\n    & > summary.title::-webkit-details-marker {\n      display: none;\n    }\n\n    & > summary.title:after {\n      color: var(--faint);\n      content: \"›\";\n      opacity: 0;\n      flex: none;\n      font-size: .75rem;\n      line-height: 1;\n      transition: rotate .12s, opacity .12s;\n      rotate: 0deg;\n    }\n\n    & > summary.title:is(:hover, :focus-visible):after {\n      opacity: .65;\n    }\n\n    &[open] > summary.title:after {\n      rotate: 90deg;\n    }\n\n    & > .content {\n      grid-template-columns: var(--space-4) minmax(7.25rem, .72fr) minmax(0, 1fr) var(--space-4);\n      padding: 0;\n      display: grid;\n    }\n\n    & > .content > .key-values, & > .content .relations {\n      display: contents;\n    }\n\n    &[data-static] > .content {\n      box-shadow: inset 0 -.5px var(--line-soft);\n    }\n  }\n\n  .key-values {\n    margin: 0;\n    padding: 0;\n  }\n\n  .key-value {\n    min-height: 2rem;\n    padding: 0 var(--space-3);\n    background: none;\n    grid-template-columns: minmax(7.25rem, .72fr) minmax(0, 1fr);\n    align-items: center;\n    gap: 0;\n    transition: background-color .1s;\n    display: grid;\n    position: relative;\n  }\n\n  .group > .content .key-value {\n    grid-column: 1 / -1;\n    grid-template-columns: subgrid;\n    padding-inline: 0;\n\n    & > .key {\n      grid-column: 2;\n    }\n\n    & > .value, &[data-structured] > :is(.value-meta, .value) {\n      grid-column: 3;\n    }\n\n    &[data-vertical] {\n      grid-template-rows: 1.6rem auto;\n\n      & > .key {\n        grid-column: 2 / 4;\n        align-self: center;\n      }\n\n      & > .value {\n        padding-block: var(--space-2);\n        padding-inline: 3rem var(--target);\n        background: var(--surface-zebra);\n        white-space: normal;\n        grid-column: 1 / -1;\n      }\n    }\n  }\n\n  .compound-field {\n    grid-column: 1 / -1;\n    grid-template-columns: subgrid;\n    min-width: 0;\n    margin: 0;\n    padding: 0;\n    display: grid;\n  }\n\n  .compound-field + .compound-field {\n    box-shadow: inset 0 .5px var(--line-soft);\n  }\n\n  .compound-field > .key-value {\n    grid-column: 1 / -1;\n  }\n\n  .compound-field > .action-parameter {\n    background: var(--surface-zebra);\n    min-height: 1.625rem;\n  }\n\n  .compound-field > .action-parameter > .key {\n    color: var(--faint);\n    padding-inline-start: var(--space-4);\n  }\n\n  .key-value {\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    & :is(dt, dd) {\n      margin: 0;\n    }\n\n    & > .key {\n      color: var(--muted);\n      font-size: var(--key-value-size);\n      font-weight: var(--weight);\n      padding-inline-end: var(--space-2);\n      line-height: 1.45;\n    }\n\n    &:is([data-element], .relation) > .key {\n      color: var(--text);\n    }\n\n    & > .value {\n      overflow-wrap: anywhere;\n      min-width: 0;\n      color: var(--text);\n      font: var(--weight) var(--key-value-size)/1.45 var(--mono);\n    }\n\n    &:has(.tree) > .value {\n      white-space: normal;\n      overflow: visible;\n    }\n\n    & > .value[data-long], &[data-field-key$=\"status\"] > .value {\n      color: var(--muted);\n    }\n  }\n\n  .value-list {\n    min-width: 0;\n    padding-block: var(--space-1);\n    gap: .125rem;\n    display: grid;\n  }\n\n  .value-list__item {\n    overflow-wrap: anywhere;\n    min-width: 0;\n  }\n\n  .value-status {\n    color: var(--faint);\n    font-size: var(--key-value-size);\n    font-family: var(--font-ui);\n    margin-inline-end: var(--space-1);\n  }\n\n  .value-status[data-status=\"connected\"] {\n    display: none;\n  }\n\n  .key-value {\n    &[data-structured] {\n      padding-block: var(--space-2);\n      grid-template-columns: minmax(7.25rem, .72fr) minmax(0, 1fr);\n      align-items: center;\n    }\n\n    &[data-structured] > .key {\n      grid-column: 1;\n    }\n\n    &[data-structured] > .value-meta {\n      color: var(--faint);\n      font: var(--weight) var(--key-value-size)/1 var(--mono);\n      white-space: nowrap;\n      grid-column: 2;\n      justify-self: start;\n      padding-inline-end: var(--target);\n    }\n\n    &[data-structured] > .value {\n      width: 100%;\n      padding-top: var(--space-1);\n      grid-column: 2;\n    }\n\n    &[data-element] > .value {\n      align-items: center;\n      gap: var(--space-2);\n      min-width: 0;\n      display: flex;\n    }\n  }\n\n  .value-note {\n    color: var(--faint);\n    font-size: var(--key-value-size);\n    font-family: var(--font-ui);\n    flex: none;\n  }\n\n  .bool, .num, .string {\n    color: var(--text);\n  }\n\n  .v-str {\n    color: color-mix(in srgb, var(--accent-ui) 68%, var(--text));\n  }\n\n  .v-num {\n    color: color-mix(in srgb, var(--accent-ui) 44%, var(--text));\n  }\n\n  .v-bool {\n    color: color-mix(in srgb, var(--accent-ui) 82%, var(--text));\n  }\n\n  .num, .v-num {\n    font-variant-numeric: tabular-nums;\n  }\n\n  .nul, .v-nul {\n    color: var(--faint);\n    font-style: italic;\n  }\n\n  .tree {\n    min-width: 0;\n    font: var(--weight) var(--text-xs)/1.55 var(--mono);\n\n    & details {\n      min-width: 0;\n    }\n\n    & summary, & .row {\n      align-items: baseline;\n      column-gap: var(--space-1);\n      grid-template-columns: max-content .35rem minmax(0, 1fr);\n      min-width: 0;\n      display: grid;\n    }\n\n    & summary {\n      cursor: pointer;\n    }\n\n    & .row {\n      padding-left: 0;\n    }\n\n    & .row:has( > :only-child) {\n      grid-template-columns: minmax(0, 1fr);\n    }\n\n    & .nest {\n      min-width: 0;\n      padding-left: var(--space-2);\n    }\n\n    & .key {\n      color: var(--muted);\n    }\n\n    & .colon {\n      color: var(--faint);\n    }\n\n    &.array, & .array {\n      gap: .125rem var(--space-2);\n      flex-wrap: wrap;\n      display: flex;\n    }\n\n    & .array > .array-item {\n      flex: 0 auto;\n    }\n\n    & .array > details.array-item {\n      flex-basis: 100%;\n    }\n\n    & :is(.v-str, .v-num, .v-bool, .v-nul, .type) {\n      overflow-wrap: break-word;\n      word-break: normal;\n      min-width: 0;\n    }\n\n    & .type {\n      color: var(--faint);\n    }\n  }\n\n  .target-pill {\n    min-height: var(--target);\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1.45 var(--mono);\n    text-align: start;\n    text-overflow: ellipsis;\n    white-space: nowrap;\n    background: none;\n    border-radius: 0;\n    padding: 0;\n  }\n\n  .target-pill.expanded {\n    white-space: normal;\n  }\n\n  .target-pill:is(:hover, :focus-visible) {\n    color: var(--muted);\n  }\n\n  .target-pill .tag, .target-pill .cls {\n    color: inherit;\n    background: none;\n    padding: 0;\n  }\n\n  .key-value[data-changed] {\n    background: color-mix(in srgb, var(--framework) 8%, var(--surface));\n  }\n\n  .relations {\n    display: grid;\n  }\n\n  .relation {\n    background: var(--surface);\n    width: 100%;\n    color: var(--text);\n    text-align: start;\n    border-radius: 0;\n    transition: background-color .12s;\n\n    &:nth-child(2n) {\n      background: var(--surface-zebra);\n    }\n\n    & > .key {\n      font-size: var(--key-value-size);\n      font-weight: var(--weight);\n      line-height: 1.45;\n      display: block;\n    }\n\n    & > .value {\n      color: var(--faint);\n      font: var(--weight) var(--key-value-size)/1.45 var(--mono);\n      padding-inline-end: var(--target);\n      display: block;\n    }\n\n    & > svg {\n      width: .875rem;\n      height: .875rem;\n      color: var(--faint);\n      justify-self: center;\n    }\n  }\n\n  .group > .content .relation > svg {\n    top: 50%;\n    right: calc((var(--target) - .875rem) / 2);\n    position: absolute;\n    translate: 0 -50%;\n  }\n\n  .activity-label {\n    z-index: 1;\n    background: var(--panel);\n    width: 100%;\n    box-shadow: inset 0 .5px var(--line-soft);\n    color: var(--muted);\n    border-radius: 0;\n    flex: none;\n    padding: 0;\n    position: sticky;\n    bottom: 0;\n  }\n\n  .activity-label > .title {\n    width: 100%;\n    box-shadow: none;\n  }\n\n  .drawer {\n    background: var(--surface);\n    height: auto;\n    min-height: 2.5rem;\n    max-height: min(10rem, 40%);\n    box-shadow: 0 -.5px var(--line-soft);\n    flex: none;\n    position: relative;\n  }\n\n  .drawer[data-sized] {\n    max-height: calc(100% - 6rem);\n  }\n\n  .drawer .empty {\n    min-height: 2.5rem;\n    padding: var(--space-2);\n  }\n\n  .drawer-resize {\n    z-index: 2;\n    cursor: ns-resize;\n    touch-action: none;\n    height: .625rem;\n    position: absolute;\n    top: -.625rem;\n    left: 0;\n    right: 0;\n  }\n\n  .drawer-resize:after {\n    background: var(--faint);\n    content: \"\";\n    opacity: .35;\n    border-radius: 99rem;\n    width: 2.5rem;\n    height: 3px;\n    transition: opacity .12s;\n    position: absolute;\n    top: .125rem;\n    left: 50%;\n    translate: -50%;\n  }\n\n  .drawer-resize:is(:hover, :focus-visible):after, .drawer[data-resizing] .drawer-resize:after {\n    opacity: 1;\n  }\n\n  .drawer {\n    & > .timeline {\n      overscroll-behavior: contain;\n      flex: 1;\n      height: auto;\n      min-height: 0;\n    }\n\n    & :is(.event-target, .event-go, .event-actions) {\n      display: none;\n    }\n\n    & button.disclosure:after {\n      opacity: 0;\n      font-size: 1rem;\n    }\n\n    & .event-row:is(:hover, :focus-within) button.disclosure:after {\n      opacity: .65;\n    }\n\n    & .disclosure .name {\n      overflow-wrap: anywhere;\n      white-space: normal;\n    }\n\n    & .activity-detail .key-value:first-child > .value {\n      padding-inline-end: 0;\n    }\n  }\n\n  .events, .event {\n    margin: 0;\n    padding: 0;\n    list-style: none;\n  }\n\n  .timeline {\n    background: var(--surface);\n    height: 100%;\n    overflow: auto;\n  }\n\n  .event {\n    background: var(--surface);\n  }\n\n  .event:nth-child(2n) {\n    background: var(--surface-zebra);\n  }\n\n  .event-row {\n    min-height: var(--target);\n    box-shadow: inset 0 -.5px var(--line-soft);\n    align-items: center;\n    display: flex;\n  }\n\n  .event-row.selected {\n    background: var(--control-active);\n  }\n\n  .event-row.compact, .event-row.compact .disclosure {\n    min-height: 1.6rem;\n  }\n\n  .disclosure {\n    min-width: 0;\n    min-height: var(--target);\n    align-items: center;\n    gap: var(--space-2);\n    padding: 0 var(--space-3);\n    color: var(--text);\n    text-align: start;\n    background: none;\n    flex: 1;\n    grid-template-columns: .75rem minmax(0, 1fr) auto .5rem;\n    display: grid;\n  }\n\n  button.disclosure:after {\n    color: var(--faint);\n    content: \"›\";\n    opacity: .45;\n    grid-column: 4;\n    justify-self: end;\n    font-size: .875rem;\n    line-height: 1;\n    transition: rotate .12s;\n  }\n\n  button.disclosure[aria-expanded=\"true\"]:after {\n    rotate: 90deg;\n  }\n\n  .event-dot {\n    background: var(--framework);\n    border-radius: 50%;\n    width: .375rem;\n    height: .375rem;\n  }\n\n  .disclosure .name {\n    font-size: var(--key-value-size);\n    font-weight: var(--weight);\n    line-height: 1rem;\n  }\n\n  .event-target {\n    color: var(--faint);\n    font: var(--weight) var(--text-xs)/1rem var(--mono);\n  }\n\n  .event-go svg {\n    width: .75rem;\n    height: .75rem;\n  }\n\n  .event-go:hover {\n    background: var(--control-hover);\n  }\n\n  .event-count {\n    flex: none;\n  }\n\n  .event-detail {\n    background: color-mix(in srgb, var(--text) 2.5%, var(--surface));\n  }\n\n  .activity-detail {\n    background: none;\n    padding: 0;\n    position: relative;\n  }\n\n  .event-actions {\n    z-index: 1;\n    position: absolute;\n    top: 0;\n    right: 0;\n  }\n\n  .event-copy:hover {\n    background: none;\n  }\n\n  .event-copy.copied {\n    background: var(--control-active);\n    color: var(--text);\n  }\n\n  .activity-detail .key-values {\n    background: none;\n  }\n\n  .activity-detail .key-value {\n    min-height: var(--target);\n  }\n\n  .activity-detail .key-value:first-child > .value {\n    padding-inline-end: var(--target);\n  }\n\n  .raw-events {\n    box-shadow: inset 0 .5px var(--line-soft);\n  }\n\n  .raw-events > summary {\n    min-height: var(--target);\n    align-items: center;\n    gap: var(--space-2);\n    padding-inline: var(--space-3);\n    color: var(--muted);\n    cursor: pointer;\n    font-size: var(--text-xs);\n    font-weight: var(--weight-strong);\n    display: flex;\n  }\n\n  .raw-count {\n    color: var(--faint);\n    font-family: var(--mono);\n    font-weight: var(--weight);\n  }\n\n  .raw-list {\n    margin: 0;\n    padding: 0;\n    list-style: none;\n\n    & li {\n      justify-content: space-between;\n      align-items: center;\n      gap: var(--space-2);\n      min-height: 2rem;\n      padding-inline: var(--space-3);\n      color: var(--faint);\n      font: var(--weight) var(--text-xs)/1.3 var(--mono);\n      display: flex;\n    }\n\n    & li + li {\n      box-shadow: inset 0 .5px var(--line-soft);\n    }\n\n    & strong {\n      color: var(--muted);\n    }\n  }\n}\n\n@layer overlay {\n  @scope (.overlay) {\n    :scope {\n      z-index: 1;\n      pointer-events: none;\n      position: fixed;\n      inset: 0;\n    }\n\n    .box {\n      border: 2px solid var(--framework);\n      border-radius: var(--radius-sm);\n      background: color-mix(in srgb, var(--framework), transparent 92%);\n      pointer-events: none;\n      position: fixed;\n      top: 0;\n      left: 0;\n    }\n\n    .box[data-mode=\"hover\"] {\n      background: none;\n      border-style: dashed;\n    }\n\n    .box[data-mode=\"all\"], .box[data-mode=\"event\"] {\n      background: none;\n    }\n\n    .box[data-mode=\"selected\"] {\n      box-shadow: 0 0 0 2px color-mix(in srgb, var(--framework), transparent 55%);\n    }\n\n    .box > span {\n      border-radius: var(--radius-sm) var(--radius-sm) 0 0;\n      background: var(--framework);\n      color: #101114;\n      max-width: 12.5rem;\n      font: var(--weight-strong) .625rem/1.25 var(--font-ui);\n      text-overflow: ellipsis;\n      white-space: nowrap;\n      padding: .125rem .25rem;\n      font-stretch: semi-condensed;\n      position: absolute;\n      top: 0;\n      left: -1px;\n      overflow: hidden;\n      translate: 0 -100%;\n    }\n\n    .box[data-mode=\"event\"] > span {\n      border-radius: 0 0 var(--radius-sm);\n      font-variant-numeric: tabular-nums;\n      translate: 0;\n    }\n\n    .box[data-mode=\"event\"].pulse {\n      animation: .65s ease-out overlay-pulse;\n    }\n\n    @keyframes overlay-pulse {\n      30% {\n        box-shadow: 0 0 0 var(--space-2) color-mix(in srgb, var(--framework), transparent 65%);\n      }\n    }\n  }\n}\n\n@layer states {\n  @media (width <= 32.5rem) {\n    .inspector {\n      width: 100vw;\n    }\n\n    .panel-resize {\n      display: none;\n    }\n  }\n\n  @container inspector (width <= 18rem) {\n    .inspector > header {\n      padding-inline: var(--space-1);\n      grid-template-columns: 1fr auto;\n    }\n\n    .inspector > header > strong {\n      display: none;\n    }\n\n    .header-end {\n      grid-column: 2;\n    }\n  }\n\n  @container inspector (width <= 11.5rem) {\n    .group > .content {\n      grid-template-columns: var(--space-4) minmax(0, 1fr) var(--space-4);\n    }\n\n    .key-value {\n      min-height: calc(var(--target) + var(--space-2));\n      padding-block: var(--space-1);\n      grid-template-columns: minmax(0, 1fr);\n      align-content: center;\n    }\n\n    .key-value > .key {\n      color: var(--faint);\n      font-size: var(--key-value-size);\n      padding-inline-end: var(--target);\n    }\n\n    .key-value > .value {\n      grid-column: 1;\n    }\n\n    .group > .content .key-value > :is(.key, .value) {\n      grid-column: 2;\n    }\n\n    .group > .content .key-value[data-vertical] > .value {\n      grid-column: 1 / -1;\n    }\n\n    .key-value[data-element] > .value {\n      gap: 0 var(--space-2);\n      flex-wrap: wrap;\n    }\n  }\n\n  @media (prefers-reduced-motion: reduce) {\n    :host *, :host :before, :host :after {\n      transition: none !important;\n      animation: none !important;\n    }\n  }\n}\n";
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
var ComponentDetector = class {
	#registry;
	#state;
	#observer = null;
	#inspectorElement;
	#ignoreSelectors;
	#pending = /* @__PURE__ */ new Set();
	#flushQueued = false;
	#mutations = /* @__PURE__ */ new Map();
	#reportedPlugins = /* @__PURE__ */ new Set();
	constructor(registry, state, inspectorElement, ignoreSelectors = []) {
		this.#registry = registry;
		this.#state = state;
		this.#inspectorElement = inspectorElement;
		this.#ignoreSelectors = ignoreSelectors.filter((selector) => {
			try {
				document.createDocumentFragment().querySelector(selector);
				return true;
			} catch (e) {
				console.warn(`[ux-inspector] Ignoring invalid "ignore_selectors" entry "${selector}":`, e);
				return false;
			}
		});
	}
	scan() {
		const selector = this.#registry.combinedSelector;
		if (!selector) return;
		const seen = new Set(this.#components(document, selector));
		const query = createQueryCache();
		for (const element of seen) this.#reconcile(element, query);
		for (const existing of this.#state.elements) if (!seen.has(existing) || !existing.isConnected) this.#remove(existing);
	}
	inspect(element) {
		this.#reconcile(element, createQueryCache());
		return this.#state.get(element);
	}
	observe() {
		if (this.#observer) return;
		this.#observer = new MutationObserver((mutations) => {
			for (const mutation of mutations) {
				const target = mutation.target instanceof Element ? mutation.target : mutation.target.parentElement;
				if (target && this.#isToolingElement(target)) continue;
				const attributeName = mutation.attributeName;
				let changes = this.#mutations.get(mutation.target);
				if (!changes) this.#mutations.set(mutation.target, changes = /* @__PURE__ */ new Map());
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
			if (this.#mutations.size) this.#scheduleFlush();
		});
		this.#observer.observe(document.body || document.documentElement, {
			childList: true,
			subtree: true,
			attributes: true,
			characterData: true
		});
	}
	disconnect() {
		for (const element of this.#state.elements) this.#registry.notifyElementRemoved(element, this.#state.get(element)?.keys() ?? []);
		this.#observer?.disconnect();
		this.#observer = null;
		this.#pending.clear();
		this.#mutations.clear();
		this.#flushQueued = false;
	}
	refresh(element) {
		this.#queueOwners(element);
	}
	destroy() {
		this.disconnect();
	}
	#reconcile(element, query) {
		if (this.#isExcluded(element)) {
			if (this.#state.get(element)) this.#remove(element);
			return;
		}
		const previous = this.#state.get(element);
		const parsed = /* @__PURE__ */ new Map();
		for (const plugin of this.#registry.getForElement(element)) try {
			plugin.observe?.(element);
			parsed.set(plugin.name, plugin.parse(element, query));
		} catch (e) {
			this.#registry.notifyElementRemoved(element, [plugin.name]);
			if (!this.#reportedPlugins.has(plugin.name)) {
				this.#reportedPlugins.add(plugin.name);
				console.warn(`[ux-inspector] Plugin "${plugin.name}" parse() failed:`, e);
			}
		}
		for (const name of previous?.keys() ?? []) if (!parsed.has(name)) this.#registry.notifyElementRemoved(element, [name]);
		this.#state.replace(element, parsed);
	}
	#queue(element) {
		if (!(element instanceof Element)) return;
		this.#pending.add(element);
		this.#scheduleFlush();
	}
	#scheduleFlush() {
		if (this.#flushQueued) return;
		this.#flushQueued = true;
		queueMicrotask(() => this.#flush());
	}
	#flush() {
		const mutations = [...this.#mutations.values()].flatMap((changes) => [...changes.values()]);
		this.#mutations.clear();
		let pageChanged = false;
		for (const mutation of mutations) {
			if (mutation.type === "attributes" && !BASE_ATTRIBUTES.has(mutation.attributeName ?? "") && !this.#registry.isWatchedAttribute(mutation.attributeName ?? "")) continue;
			pageChanged = true;
			if (mutation.type === "childList") {
				for (const node of new Set(mutation.removedNodes)) if (node instanceof Element && !node.isConnected) this.#removeSubtree(node);
				for (const node of new Set(mutation.addedNodes)) if (node instanceof Element) for (const element of this.#components(node)) this.#queue(element);
			} else if (mutation.target instanceof Element) {
				const selector = this.#registry.combinedSelector;
				if (this.#state.get(mutation.target) || selector && mutation.target.matches(selector)) this.#queue(mutation.target);
			}
			this.#queueOwners(mutation.target);
		}
		const query = createQueryCache();
		if (mutations.length) for (const owner of this.#registry.collectExternalChanges(this.#state, query, this.#pending)) this.#pending.add(owner);
		const pending = [...this.#pending];
		this.#pending.clear();
		this.#flushQueued = false;
		for (const target of pending) this.#reconcile(target, query);
		if (pageChanged) this.#state.notifyPageUpdated();
	}
	#queueOwners(element) {
		let current = element instanceof Element ? element : element?.parentElement ?? null;
		while (current) {
			if (this.#state.get(current)) this.#queue(current);
			current = current.parentElement;
		}
	}
	*#components(root, selector = this.#registry.combinedSelector) {
		if (!selector || root instanceof Element && this.#isExcluded(root)) return;
		if (root instanceof Element && root.matches(selector)) yield root;
		yield* root.querySelectorAll(selector);
	}
	#removeSubtree(root) {
		for (const element of this.#state.elements) if (element === root || root.contains(element)) this.#remove(element);
	}
	#remove(element) {
		this.#pending.delete(element);
		const names = [...this.#state.get(element)?.keys() ?? []];
		this.#registry.notifyElementRemoved(element, names);
		this.#state.remove(element);
	}
	#isToolingElement(element) {
		return this.#inspectorElement.contains(element) || !!element.closest("ux-inspector, .sf-toolbar");
	}
	#isExcluded(element) {
		return !element.isConnected || this.#isToolingElement(element) || this.#ignoreSelectors.some((selector) => element.closest(selector));
	}
};
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
var EventMonitor = class {
	#entries = [];
	#sequence = 0;
	#byElement = /* @__PURE__ */ new WeakMap();
	#revision = 0;
	#projections = /* @__PURE__ */ new WeakMap();
	#globalProjections = /* @__PURE__ */ new Map();
	#frozen = /* @__PURE__ */ new WeakSet();
	#normalize;
	get revision() {
		return this.#revision;
	}
	project(element = null, compact = true) {
		let scoped = element ? this.#projections.get(element) : this.#globalProjections;
		if (!scoped) this.#projections.set(element, scoped = /* @__PURE__ */ new Map());
		let rows = scoped.get(compact);
		if (!rows) {
			rows = projectActivity(element ? this.#byElement.get(element) ?? [] : this.#entries, compact);
			freezeSnapshot(rows, this.#frozen);
			scoped.set(compact, rows);
		}
		return rows;
	}
	#changed() {
		this.#revision++;
		this.#globalProjections.clear();
	}
	#index(entry, remove = false) {
		for (const element of activityElements(entry)) {
			const entries = this.#byElement.get(element) ?? [];
			if (remove) {
				const index = entries.indexOf(entry);
				if (index !== -1) entries.splice(index, 1);
			} else entries.push(entry);
			if (entries.length) this.#byElement.set(element, entries);
			else this.#byElement.delete(element);
			this.#projections.delete(element);
		}
	}
	#maxEntries;
	#active = false;
	#listeners = [];
	#categoryMap = /* @__PURE__ */ new Map();
	#staticEvents = /* @__PURE__ */ new Set();
	constructor(maxEntries = 500, normalize = () => {}) {
		this.#normalize = normalize;
		this.#maxEntries = maxEntries;
		this.#boundHandler = this.#handleEvent.bind(this);
	}
	#boundHandler;
	get entries() {
		return [...this.#entries];
	}
	get active() {
		return this.#active;
	}
	record({ type = "unknown", event, target = null, owner = null, detail = null, label = null, relatedElements = [] }) {
		const draft = {
			id: ++this.#sequence,
			time: performance.now(),
			type,
			event,
			target: target instanceof Element ? target : null,
			owner: owner instanceof Element ? owner : null,
			detail,
			relatedElements: relatedElements.filter((element) => element instanceof Element).slice(0, 50)
		};
		if (label) draft.label = label;
		this.#normalize(draft);
		const entry = Object.freeze({
			...draft,
			detail: freezeSnapshot(snapshotEvent(draft.detail), this.#frozen),
			relatedElements: Object.freeze([...draft.relatedElements])
		});
		this.#frozen.add(entry);
		this.#entries.push(entry);
		this.#index(entry);
		const removed = Object.freeze(this.#entries.splice(0, Math.max(0, this.#entries.length - this.#maxEntries)));
		for (const expired of removed) this.#index(expired, true);
		this.#changed();
		for (const listener of this.#listeners) try {
			listener(entry, removed);
		} catch (error) {
			console.warn("[ux-inspector] Activity listener failed:", error);
		}
		return entry;
	}
	start(onEntry = null) {
		if (onEntry) this.addListener(onEntry);
		if (this.#active) return;
		this.#active = true;
		for (const eventName of this.#categoryMap.keys()) document.addEventListener(eventName, this.#boundHandler, true);
	}
	stop() {
		if (!this.#active) return;
		this.#active = false;
		for (const eventName of this.#categoryMap.keys()) document.removeEventListener(eventName, this.#boundHandler, true);
	}
	addListener(fn) {
		this.#listeners = [...this.#listeners, fn];
	}
	removeListener(fn) {
		const idx = this.#listeners.indexOf(fn);
		if (idx !== -1) this.#listeners = this.#listeners.toSpliced(idx, 1);
	}
	monitorEvents(eventNames, category = "unknown") {
		for (const name of eventNames) this.#staticEvents.add(name);
		this.#registerEvents(eventNames, category);
	}
	setDynamicEvents(eventNames, category) {
		const newSet = new Set(eventNames);
		for (const [name, cat] of this.#categoryMap) {
			if (cat !== category || newSet.has(name) || this.#staticEvents.has(name)) continue;
			this.#categoryMap.delete(name);
			if (this.#active) document.removeEventListener(name, this.#boundHandler, true);
		}
		this.#registerEvents(eventNames, category);
	}
	#registerEvents(eventNames, category) {
		for (const name of eventNames) {
			if (this.#categoryMap.has(name)) continue;
			this.#categoryMap.set(name, category);
			if (this.#active) document.addEventListener(name, this.#boundHandler, true);
		}
	}
	clear() {
		this.#entries = [];
		this.#byElement = /* @__PURE__ */ new WeakMap();
		this.#projections = /* @__PURE__ */ new WeakMap();
		this.#frozen = /* @__PURE__ */ new WeakSet();
		this.#changed();
	}
	destroy() {
		this.stop();
		this.clear();
		this.#listeners = [];
		this.#categoryMap.clear();
		this.#staticEvents.clear();
	}
	getEntriesForElement(element, { includeDescendants = false } = {}) {
		if (!includeDescendants) return [...this.#byElement.get(element) ?? []];
		return this.#entries.filter((entry) => {
			if (entry.owner === element || entry.target === element) return true;
			if (entry.relatedElements?.includes(element)) return true;
			if (entry.owner && element.contains(entry.owner)) return true;
			if (entry.target && element.contains(entry.target)) return true;
			return entry.relatedElements?.some((related) => element.contains(related)) ?? false;
		});
	}
	#handleEvent(event) {
		this.record({
			type: this.#categoryMap.get(event.type) || "unknown",
			event: event.type,
			target: event.target instanceof Element ? event.target : null,
			detail: event.detail
		});
	}
};
var RelationshipEngine = class {
	#registry;
	#state;
	#edges = [];
	#dirty = true;
	#lifetime = new AbortController();
	constructor(registry, state) {
		this.#registry = registry;
		this.#state = state;
		const invalidate = () => {
			this.#dirty = true;
		};
		for (const event of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) state.addEventListener(event, invalidate, { signal: this.#lifetime.signal });
	}
	invalidate() {
		this.#dirty = true;
	}
	getEdges() {
		if (this.#dirty) this.#rebuild();
		return [...this.#edges];
	}
	getRelatedTo(element) {
		if (this.#dirty) this.#rebuild();
		return this.#edges.filter((e) => e.source === element || e.target === element);
	}
	getEdgesBetween(a, b) {
		if (this.#dirty) this.#rebuild();
		return this.#edges.filter((e) => e.source === a && e.target === b || e.source === b && e.target === a);
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
		if (this.#dirty) this.#rebuild();
		return [...new Set(this.#edges.map((e) => e.type))];
	}
	destroy() {
		this.#lifetime.abort();
		this.#edges = [];
	}
	#rebuild() {
		this.#edges = [];
		const elements = new Set(this.#state.elements.filter((el) => el.isConnected));
		for (const element of elements) {
			const dataMap = this.#state.get(element);
			if (!dataMap) continue;
			for (const [pluginName, data] of dataMap) {
				const pluginEdges = this.#registry.collectRelationships(element, data, pluginName);
				for (const edge of pluginEdges) if (edge.source?.isConnected && edge.target?.isConnected) this.#edges.push({
					...edge,
					evidence: edge.evidence || "declared",
					declared: true
				});
			}
			this.#addStructuralEdges(element, elements);
		}
		this.#deduplicateEdges();
		this.#dirty = false;
	}
	#addStructuralEdges(element, allElements) {
		let parent = element.parentElement;
		while (parent) {
			if (allElements.has(parent)) {
				this.#edges.push({
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
	#deduplicateEdges() {
		const seen = /* @__PURE__ */ new Set();
		const ids = /* @__PURE__ */ new WeakMap();
		let nextId = 0;
		const elementId = (element) => {
			if (!ids.has(element)) ids.set(element, ++nextId);
			return ids.get(element);
		};
		this.#edges = this.#edges.filter((edge) => {
			const key = `${edge.type}:${elementId(edge.source)}:${elementId(edge.target)}`;
			if (seen.has(key)) return false;
			seen.add(key);
			return true;
		});
	}
};
var StateManager = class extends EventTarget {
	#components = /* @__PURE__ */ new Map();
	set(element, pluginName, data) {
		let byPlugin = this.#components.get(element);
		const isNew = !byPlugin;
		if (!byPlugin) {
			byPlugin = /* @__PURE__ */ new Map();
			this.#components.set(element, byPlugin);
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
		const previous = this.#components.get(element);
		if (previous?.size === byPlugin.size && [...byPlugin].every(([name, value]) => {
			const old = previous.get(name);
			return old?.type === value.type && sameSnapshot(old, value);
		})) return;
		this.#components.set(element, byPlugin);
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
		if (this.#components.delete(element)) this.dispatchEvent(new CustomEvent("component-removed", { detail: { element } }));
	}
	get(element) {
		return this.#components.get(element);
	}
	get elements() {
		return [...this.#components.keys()];
	}
	get size() {
		return this.#components.size;
	}
	countByPlugin() {
		const counts = {};
		for (const byPlugin of this.#components.values()) for (const name of byPlugin.keys()) counts[name] = (counts[name] || 0) + 1;
		return counts;
	}
	clear() {
		this.#components.clear();
		this.dispatchEvent(new CustomEvent("components-cleared"));
	}
	notifyPageUpdated() {
		this.dispatchEvent(new CustomEvent("page-updated"));
	}
};
var TargetSelector = class {
	#highlighter;
	#registry;
	#onSelect = null;
	#onStateChange = null;
	#activation = null;
	#enableTimeout = null;
	constructor(highlighter, registry, onStateChange = null) {
		this.#highlighter = highlighter;
		this.#registry = registry;
		this.#onStateChange = onStateChange;
	}
	get active() {
		return this.#activation !== null;
	}
	enable(onSelect) {
		if (this.active) return;
		const activation = this.#activation = new AbortController();
		this.#onSelect = onSelect;
		this.#onStateChange?.(true);
		const options = {
			capture: true,
			signal: activation.signal
		};
		const target = (event) => this.#onTarget(event);
		document.addEventListener("mouseover", target, options);
		document.addEventListener("mouseout", () => this.#highlighter.clearHover(), options);
		document.addEventListener("keydown", (event) => this.#onKeyDown(event), options);
		this.#enableTimeout = setTimeout(() => {
			this.#enableTimeout = null;
			if (this.active) document.addEventListener("click", target, options);
		}, 0);
	}
	disable(clearSelection = true) {
		if (!this.#activation) return;
		this.#activation.abort();
		this.#activation = null;
		this.#onSelect = null;
		this.#onStateChange?.(false);
		if (this.#enableTimeout) {
			clearTimeout(this.#enableTimeout);
			this.#enableTimeout = null;
		}
		this.#highlighter.clearHover();
		if (clearSelection) this.#highlighter.deselect();
	}
	toggle(onSelect) {
		if (this.active) this.disable();
		else this.enable(onSelect);
		return this.active;
	}
	destroy() {
		this.disable();
	}
	#onTarget(e) {
		if (!(e.target instanceof Element) || e.target.closest("ux-inspector, .sf-toolbar")) return;
		const selector = this.#registry.combinedSelector;
		const target = selector ? e.target.closest(selector) : null;
		const plugins = target ? this.#registry.getForElement(target) : [];
		if (e.type === "mouseover") {
			if (target) this.#highlighter.hover(target, plugins[0]?.name || "default");
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		if (target) {
			const keepInspecting = e.shiftKey;
			this.#onSelect?.(target, plugins, keepInspecting);
			if (!keepInspecting) this.disable(false);
		}
	}
	#onKeyDown(e) {
		if (e.key !== "Escape") return;
		e.preventDefault();
		e.stopPropagation();
		this.disable();
	}
};
const IDLE_RUNTIME = {
	status: "detected",
	duration: null,
	httpStatus: null,
	error: null
};
var LiveObserver = class {
	#record = null;
	#subscriptions = /* @__PURE__ */ new Map();
	#runtime = /* @__PURE__ */ new WeakMap();
	setEventRecorder(record) {
		this.#record = record;
	}
	read(element) {
		return this.#runtime.get(element) ?? { ...IDLE_RUNTIME };
	}
	connected(element) {
		this.observe(element);
		this.#runtime.set(element, {
			...IDLE_RUNTIME,
			status: "connected"
		});
	}
	disconnected(element) {
		this.remove(element);
		this.#runtime.set(element, {
			...IDLE_RUNTIME,
			status: "disconnected"
		});
	}
	remove(element) {
		const subscription = this.#subscriptions.get(element);
		if (!subscription) return;
		this.#subscriptions.delete(element);
		for (const cleanup of subscription.cleanups) try {
			cleanup();
		} catch (error) {
			this.#warn(error);
		}
	}
	destroy() {
		for (const element of this.#subscriptions.keys()) this.remove(element);
		this.#record = null;
	}
	observe(element) {
		const component = element.__component;
		if (this.#subscriptions.get(element)?.component === component) return;
		this.remove(element);
		if (typeof component?.on !== "function") return;
		this.#runtime.set(element, {
			...IDLE_RUNTIME,
			status: "connected"
		});
		if (!this.#record) return;
		const subscription = {
			component,
			cleanups: []
		};
		this.#subscriptions.set(element, subscription);
		const on = (event, callback) => {
			const handler = (...args) => {
				if (this.#subscriptions.get(element) !== subscription || element.__component !== component) return;
				try {
					callback(...args);
				} catch (error) {
					this.#warn(error);
				}
			};
			subscription.cleanups.push(() => component.off?.(event, handler));
			component.on(event, handler);
		};
		try {
			on("request:started", (request) => this.#requestStarted(element, request));
			on("model:set", (model, value) => {
				const name = String(model).slice(0, 200);
				this.#emit(element, "model:set", {
					model: name,
					value: safeValue(value, name)
				}, `model: ${name}`);
			});
			on("render:started", () => this.#emit(element, "render:started"));
			on("render:finished", () => this.#renderFinished(element));
			on("response:error", (response) => this.#responseError(element, response));
		} catch (error) {
			this.remove(element);
			this.#warn(error);
		}
	}
	#requestStarted(element, request) {
		this.#runtime.set(element, {
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
		this.#emit(element, "request", Object.keys(parameters).length ? parameters : null, actions.length ? `request: ${actions.join(", ")}` : "request: render");
	}
	#renderFinished(element) {
		const runtime = this.read(element);
		this.#runtime.set(element, {
			...runtime,
			status: "idle",
			duration: runtime.startedAt === void 0 ? null : performance.now() - runtime.startedAt,
			error: null
		});
		this.#emit(element, "render:finished");
	}
	#responseError(element, response) {
		const status = response?.response?.status ?? null;
		this.#runtime.set(element, {
			...this.read(element),
			status: "error",
			httpStatus: status,
			error: status ? `Request failed (${status})` : "Request failed"
		});
		this.#emit(element, "response:error", { status });
	}
	#emit(element, event, detail = null, label = event) {
		const name = (element.getAttribute("data-live-name-value") || "").slice(0, 200) || "LiveComponent";
		this.#record?.({
			type: "livecomponent",
			event: `live:${event}`,
			target: element,
			detail,
			label: `${name}: ${label}`
		});
	}
	#warn(error) {
		console.warn("[ux-inspector] LiveComponent observation failed:", error);
	}
};
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
var LiveComponentPlugin = class {
	name = "livecomponent";
	selectors = ["[data-controller~=\"live\"]"];
	#observer = new LiveObserver();
	setEventRecorder(record) {
		this.#observer.setEventRecorder(record);
	}
	observe(element) {
		this.#observer.observe(element);
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
				name: this.#parseName(element),
				url: safeUrl(element.getAttribute("data-live-url-value")),
				fingerprint: element.getAttribute("data-live-fingerprint-value") || "",
				props: this.#parseProps(element),
				propsFromParent: this.#parsePropsFromParent(element),
				listeners: this.#parseListeners(element),
				polling: this.#parsePolling(element),
				models: this.#resolveModels(element),
				actions: this.#resolveActions(element),
				loading: this.#resolveLoading(element),
				children: scopedChildren(element, "[data-controller~=\"live\"]").map(reference),
				parents: ancestors(element, "[data-controller~=\"live\"]").map(reference),
				otherControllers: this.#otherControllers(element),
				runtime: this.#observer.read(element)
			}
		};
	}
	getDisplayName(element) {
		return this.#parseName(element) || "LiveComponent";
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
			this.#observer.disconnected(element);
			entry.label = `${this.#parseName(element) || "LiveComponent"}: disconnect`;
			entry.detail = null;
			return;
		}
		if ("live:connect" === entry.event) {
			this.#observer.connected(element);
			entry.label = `${this.#parseName(element) || "LiveComponent"}: connect`;
			entry.detail = null;
		}
	}
	destroy() {
		this.#observer.destroy();
	}
	onElementRemoved(element) {
		this.#observer.remove(element);
	}
	#parseName(element) {
		return (element.getAttribute("data-live-name-value") || "").slice(0, 200);
	}
	#parseProps(element) {
		const raw = element.getAttribute("data-live-props-value");
		if (!raw) return {};
		try {
			return safeValue(JSON.parse(raw));
		} catch {
			return { _raw: safeValue(raw) };
		}
	}
	#parsePropsFromParent(element) {
		const raw = element.getAttribute("data-live-props-from-parent-value");
		if (!raw) return {};
		try {
			return safeValue(JSON.parse(raw));
		} catch {
			return {};
		}
	}
	#parseListeners(element) {
		const raw = element.getAttribute("data-live-listeners-value");
		if (!raw) return [];
		try {
			const parsed = safeValue(JSON.parse(raw));
			return Array.isArray(parsed) ? parsed : [];
		} catch {
			return [];
		}
	}
	#parsePolling(element) {
		const polling = element.getAttribute("data-poll");
		if (!polling && !element.hasAttribute("data-poll")) return null;
		const match = polling?.match(/delay\((\d+)\)/);
		return { duration: match ? `${match[1]}ms` : "2000ms" };
	}
	#resolveModels(element) {
		return Array.from(element.querySelectorAll("[data-model]")).filter((c) => this.#isInLiveScope(c, element) && c.getAttribute("data-model")).map((c) => {
			const model = this.#parseModelValue(c.getAttribute("data-model") ?? "");
			return {
				...model,
				value: this.#readModelValue(c, model.name),
				element: c
			};
		});
	}
	#resolveActions(element) {
		const actions = [];
		const allElements = [element, ...element.querySelectorAll("[data-action]")];
		for (const candidate of allElements) {
			const raw = candidate.getAttribute("data-action");
			if (!raw) continue;
			if (!this.#isInLiveScope(candidate, element)) continue;
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
	#readModelValue(element, name) {
		const input = element;
		if (/password|secret|token|csrf/i.test(name) || input.type === "password") return "[redacted]";
		if (element instanceof HTMLInputElement && ["checkbox", "radio"].includes(element.type)) return element.checked;
		if ("value" in element) return safeValue(input.value, name);
		return null;
	}
	#resolveLoading(element) {
		return Array.from(element.querySelectorAll("[data-loading]")).filter((c) => this.#isInLiveScope(c, element)).map((c) => ({
			action: c.getAttribute("data-loading") || "show",
			element: c
		}));
	}
	#isInLiveScope(candidate, root) {
		if (candidate === root) return true;
		return candidate.closest?.("[data-controller~=\"live\"]") === root;
	}
	#parseModelValue(raw) {
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
	#otherControllers(element) {
		return (element.getAttribute("data-controller") || "").split(/\s+/).filter((c) => c && c !== "live");
	}
};
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
var PluginRegistry = class {
	#plugins = /* @__PURE__ */ new Map();
	#order = [
		"livecomponent",
		"turbo",
		"stimulus"
	];
	#watchedAttributes = null;
	constructor(plugins = []) {
		this.#plugins = new Map(plugins.map((plugin) => [plugin.name, plugin]));
	}
	get(name) {
		return this.#plugins.get(name);
	}
	getAll() {
		const rank = (p) => {
			const i = this.#order.indexOf(p.name);
			return i === -1 ? 99 : i;
		};
		return [...this.#plugins.values()].sort((a, b) => rank(a) - rank(b));
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
		const categoryPlugin = this.#plugins.get(entry.type);
		if (categoryPlugin) plugins.add(categoryPlugin);
		for (const plugin of plugins) safeCallHook(plugin, "onEvent", entry, element);
	}
	setEventRecorder(record) {
		for (const plugin of this.getAll()) safeCallHook(plugin, "setEventRecorder", record);
	}
	notifyElementRemoved(element, pluginNames) {
		for (const name of pluginNames) {
			const plugin = this.#plugins.get(name);
			if (plugin) safeCallHook(plugin, "onElementRemoved", element);
		}
	}
	destroy() {
		for (const plugin of this.getAll()) safeCallHook(plugin, "destroy");
	}
	collectRelationships(element, data, pluginName = null) {
		const edges = [];
		const plugins = pluginName ? [this.#plugins.get(pluginName)].filter((p) => Boolean(p)) : this.getForElement(element);
		for (const plugin of plugins) {
			const pluginEdges = safeCallHook(plugin, "getRelationships", element, data);
			if (Array.isArray(pluginEdges)) edges.push(...pluginEdges);
		}
		return edges;
	}
	collectWatchedAttributes() {
		if (this.#watchedAttributes) return [...this.#watchedAttributes];
		const attrs = /* @__PURE__ */ new Set();
		for (const plugin of this.#plugins.values()) {
			const extra = safeCallHook(plugin, "getWatchedAttributes");
			if (Array.isArray(extra)) extra.forEach((a) => attrs.add(a));
		}
		this.#watchedAttributes = attrs;
		return [...attrs];
	}
	isWatchedAttribute(name) {
		if (!this.#watchedAttributes) this.collectWatchedAttributes();
		if (this.#watchedAttributes?.has(name)) return true;
		for (const plugin of this.#plugins.values()) if (safeCallHook(plugin, "matchesAttribute", name) === true) return true;
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
				const plugin = this.#plugins.get(name);
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
var StimulusPlugin = class {
	name = "stimulus";
	selectors = ["[data-controller]"];
	#application = null;
	constructor(application = null) {
		this.setApplication(application);
	}
	setApplication(application) {
		this.#application = application?.getControllerForElementAndIdentifier ? application : null;
	}
	canHandle(element) {
		return this.#parseControllers(element).length > 0;
	}
	parse(element, query = queryElements) {
		const reference = (element) => ({
			element,
			controllers: this.#parseControllers(element)
		});
		const controllers = this.#parseControllers(element);
		const instances = Object.fromEntries(controllers.map((identifier) => [identifier, this.#getController(element, identifier)]));
		const values = this.#parseControllerAttrs(element, "value", parseAttributeValue);
		const classes = this.#parseClasses(element);
		return {
			type: "stimulus",
			element,
			data: {
				controllers,
				runtimeAvailable: Boolean(this.#application),
				connectedControllers: controllers.filter((identifier) => instances[identifier]),
				values,
				valueStates: this.#resolveValueStates(element, controllers, instances, values),
				targets: this.#resolveTargets(element, controllers, instances),
				actions: this.#resolveActions(element, controllers, instances),
				classes,
				classStates: this.#resolveClassStates(controllers, instances, classes),
				outlets: this.#resolveOutlets(element, controllers, instances, query),
				children: scopedChildren(element, "[data-controller]").map(reference),
				parents: ancestors(element, "[data-controller]").map(reference)
			}
		};
	}
	getDisplayName(element) {
		const controllers = this.#parseControllers(element);
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
	#parseControllers(element) {
		return (element.getAttribute("data-controller") || "").split(/\s+/).filter((controller) => controller && controller !== "live");
	}
	#getController(element, identifier) {
		if (!this.#application) return null;
		try {
			return this.#application.getControllerForElementAndIdentifier(element, identifier) || null;
		} catch {
			return null;
		}
	}
	#staticDefinition(instance, property, fallback) {
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
	#hasMethod(instance, method) {
		let current = instance;
		while (current) {
			const descriptor = Object.getOwnPropertyDescriptor(current, method);
			if (descriptor) return "value" in descriptor && typeof descriptor.value === "function";
			current = Object.getPrototypeOf(current);
		}
		return false;
	}
	#resolveValueStates(element, controllers, instances, configured) {
		const result = {};
		for (const controller of controllers) {
			const current = configured[controller] || {};
			const definitions = this.#staticDefinition(instances[controller], "values", {}) ?? {};
			result[controller] = [...new Set([...Object.keys(definitions), ...Object.keys(current)])].map((name) => {
				const attribute = `data-${controller}-${name.replace(/[A-Z]/g, (letter) => `-${letter.toLowerCase()}`)}-value`;
				const present = element.hasAttribute(attribute);
				let value = current[name];
				const definition = Object.getOwnPropertyDescriptor(definitions, name);
				if (!present) value = safeValue(this.#valueDefault(definition && "value" in definition ? definition.value : void 0), name);
				return {
					name,
					value,
					status: instances[controller] ? present ? CONNECTED$1 : "default" : CONFIGURED
				};
			});
		}
		return result;
	}
	#valueDefault(definition) {
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
	#resolveClassStates(controllers, instances, configured) {
		const result = {};
		for (const controller of controllers) {
			const current = configured[controller] || {};
			const declared = this.#staticDefinition(instances[controller], "classes", []);
			result[controller] = [...new Set([...Array.isArray(declared) ? declared : [], ...Object.keys(current)])].map((name) => ({
				name,
				value: current[name] || "not configured",
				status: current[name] ? instances[controller] ? CONNECTED$1 : CONFIGURED : MISSING
			}));
		}
		return result;
	}
	#parseControllerAttrs(element, suffix, transform) {
		const result = {};
		for (const controller of this.#parseControllers(element)) {
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
	#resolveTargets(element, controllers, instances = {}) {
		const result = {};
		for (const controller of controllers) {
			const scope = element;
			const elements = [];
			const attr = `data-${controller}-target`;
			const candidates = scope.querySelectorAll(`[${attr}]`);
			for (const candidate of candidates) if (this.#isInScope(candidate, element, controller)) {
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
			const declared = this.#staticDefinition(instances[controller], "targets", []);
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
	#resolveActions(element, controllers, instances = {}) {
		const result = Object.fromEntries(controllers.map((c) => [c, []]));
		const allElements = [element, ...element.querySelectorAll("[data-action]")];
		for (const candidate of allElements) {
			const raw = candidate.getAttribute("data-action");
			if (!raw) continue;
			const descriptors = raw.split(/\s+/).filter(Boolean);
			for (const descriptor of descriptors) {
				const parsed = parseActionDescriptor(descriptor);
				if (parsed && controllers.includes(parsed.controller)) {
					if (this.#isInScope(candidate, element, parsed.controller)) {
						const action = {
							event: parsed.event || this.#defaultActionEvent(candidate),
							method: parsed.method,
							element: candidate,
							status: instances[parsed.controller] ? this.#hasMethod(instances[parsed.controller], parsed.method) ? CONNECTED$1 : "missing-method" : DOM_ONLY$1
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
	#defaultActionEvent(element) {
		return {
			BUTTON: "click",
			FORM: "submit",
			INPUT: "input",
			TEXTAREA: "input",
			SELECT: "change",
			DETAILS: "toggle"
		}[element.tagName] || "default";
	}
	#resolveOutlets(element, controllers, instances, query) {
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
			const declared = this.#staticDefinition(instances[controller], "outlets", []);
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
	#parseClasses(element) {
		return this.#parseControllerAttrs(element, "class", (v) => v);
	}
	#isInScope(candidate, root, controller) {
		if (candidate === root) return true;
		let parent = candidate.parentElement;
		while (parent && parent !== root) {
			if (parent.hasAttribute("data-controller")) {
				if (this.#parseControllers(parent).includes(controller)) return false;
			}
			parent = parent.parentElement;
		}
		return parent === root;
	}
};
var TurboPlugin = class {
	name = "turbo";
	selectors = ["turbo-frame"];
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
				childFrames: this.#findChildFrames(element),
				parentFrame: this.#findParentFrame(element),
				linksToFrame: this.#findLinksToFrame(element, query),
				formsInFrame: this.#findFormsInFrame(element, query)
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
		return !sameSnapshot(data.linksToFrame, this.#findLinksToFrame(element, query)) || !sameSnapshot(data.formsInFrame, this.#findFormsInFrame(element, query));
	}
	#findChildFrames(element) {
		return Array.from(element.querySelectorAll(":scope > turbo-frame")).map((f) => ({
			id: f.id || "(anonymous)",
			element: f
		}));
	}
	#findParentFrame(element) {
		const parent = element.parentElement?.closest("turbo-frame");
		if (!parent) return null;
		return {
			id: parent.id || "(anonymous)",
			element: parent
		};
	}
	#findLinksToFrame(element, query) {
		return query("a[href]").filter((link) => link.closest("turbo-frame") === element || link.getAttribute("data-turbo-frame") === element.id).map((link) => ({
			href: safeUrl(link.getAttribute("href")),
			element: link
		}));
	}
	#findFormsInFrame(element, query) {
		return query("form").filter((form) => form.closest("turbo-frame") === element || form.getAttribute("data-turbo-frame") === element.id).map((form) => ({
			action: safeUrl(form.getAttribute("action")),
			method: form.getAttribute("method") || "get",
			element: form
		}));
	}
};
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
	pause: [["path", { d: "M9 5v14M15 5v14" }]],
	clear: [["path", { d: "M4 7h16M9 7V4h6v3m3 0-1 13H7L6 7m4 4v5m4-5v5" }]],
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
var ComponentCard = class {
	#pulses = /* @__PURE__ */ new Map();
	#registry;
	#eventMonitor;
	constructor(registry, eventMonitor) {
		this.#registry = registry;
		this.#eventMonitor = eventMonitor;
	}
	render(element, dataMap, identity = componentIdentity(element, dataMap, this.#registry)) {
		const framework = dataMap.keys().next().value || "default";
		const tag = element.tagName.toLowerCase();
		const name = identity.name ?? tag;
		const generatedLiveId = framework === "livecomponent" && /^live-\d+(?:-\d+)?$/.test(element.id);
		const selector = identity.name === void 0 || generatedLiveId ? tag : identity.selector;
		const entries = this.#eventMonitor?.project(element) ?? [];
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
			cancelAnimationFrame(this.#pulses.get(action) ?? 0);
			this.#pulses.delete(action);
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
			cancelAnimationFrame(this.#pulses.get(action) ?? 0);
			this.#pulses.set(action, requestAnimationFrame(() => {
				this.#pulses.delete(action);
				if (action.isConnected) action.classList.add("activity-pulse");
			}));
		}
		return true;
	}
	destroy() {
		for (const frame of this.#pulses.values()) cancelAnimationFrame(frame);
		this.#pulses.clear();
	}
};
var ComponentList = class {
	element = el("div", {
		class: "components",
		"aria-live": "polite"
	});
	#state;
	#registry;
	#eventMonitor;
	#visual;
	#cardRenderer;
	#rows = /* @__PURE__ */ new Map();
	#targets = /* @__PURE__ */ new WeakMap();
	#lifetime = new AbortController();
	constructor(state, registry, eventMonitor, visual) {
		this.#state = state;
		this.#registry = registry;
		this.#eventMonitor = eventMonitor;
		this.#visual = visual;
		this.#cardRenderer = new ComponentCard(registry, eventMonitor);
		const { signal } = this.#lifetime;
		this.element.addEventListener("keydown", (event) => this.#onComponentKeydown(event), { signal });
		for (const type of [
			"click",
			"pointerover",
			"pointerout",
			"focusin",
			"focusout"
		]) this.element.addEventListener(type, (event) => this.#interact(event), { signal });
	}
	refresh(filters, query, selected) {
		const nodes = [];
		const pageRules = this.#pageRules(filters, query);
		if (pageRules) nodes.push(pageRules);
		const nextRows = /* @__PURE__ */ new Map();
		for (const element of this.#state.elements) {
			const dataMap = this.#state.get(element);
			if (!dataMap || ![...dataMap.keys()].some((name) => filters.has(name))) continue;
			const identity = componentIdentity(element, dataMap, this.#registry);
			const signature = identity.search;
			if (query && !signature.toLowerCase().includes(query)) continue;
			const previous = this.#rows.get(element);
			const row = previous?.signature === signature ? previous : {
				element: this.#cardRenderer.render(element, dataMap, identity),
				signature
			};
			this.#targets.set(row.element, {
				element,
				framework: dataMap.keys().next().value
			});
			nextRows.set(element, row);
			nodes.push(row.element);
		}
		this.#rows = nextRows;
		this.select(selected);
		if (!nodes.length) nodes.push(createEmptyState(this.#state.size ? "No matching components" : "No UX components detected", this.#state.size ? "Change the search or framework filters." : "Pick a component from the page or interact with it to begin."));
		reconcileChildren(this.element, nodes);
	}
	clearActivities() {
		for (const row of this.#rows.values()) this.#cardRenderer.updateActivity(row.element, 0);
	}
	#pageRules(filters, query) {
		if (!filters.has("turbo")) return null;
		const rules = (this.#registry.collectPageRules?.() ?? []).filter((rule) => `${rule.kind} ${rule.label} ${rule.detail ?? ""}`.toLowerCase().includes(query));
		if (!rules.length) return null;
		return el("section", {
			class: "group",
			dataset: { pageRules: "" }
		}, el("h2", {
			class: "title",
			text: "Turbo page rules"
		}), el("div", { class: "content" }, ...rules.map((rule) => this.#pageRule(rule))));
	}
	#pageRule(rule) {
		const target = {
			element: rule.element,
			framework: rule.framework,
			label: rule.label
		};
		const row = el("button", {
			class: "key-value page-rule",
			type: "button",
			"aria-pressed": "false"
		}, el("strong", {
			class: "key",
			text: rule.label
		}), rule.detail ? el("span", {
			class: "value",
			text: rule.detail
		}) : null);
		this.#targets.set(row, target);
		return row;
	}
	destroy() {
		this.#lifetime.abort();
		this.#cardRenderer.destroy();
		this.#rows.clear();
		this.#targets = /* @__PURE__ */ new WeakMap();
		this.element.replaceChildren();
	}
	#interact(event) {
		const row = event.target.closest(".component, .page-rule");
		const target = row && this.#targets.get(row);
		if (!target) return;
		const related = event.relatedTarget;
		if (related instanceof Node && row.contains(related)) return;
		if (event.type === "click") if (row.matches(".component")) this.element.dispatchEvent(new CustomEvent("drill-into", { detail: target }));
		else {
			target.element.scrollIntoView({
				block: "center",
				behavior: "smooth"
			});
			row.setAttribute("aria-pressed", "true");
			this.#visual.onSelect?.(target);
		}
		else if (event.type === "pointerover" || event.type === "focusin") this.#visual.onPreview?.(target);
		else this.#visual.onClearPreview?.();
	}
	updateActivity(component) {
		const row = this.#rows.get(component)?.element;
		if (!row) return;
		const entries = this.#eventMonitor?.project(component) ?? [];
		const latest = entries.at(-1);
		this.#cardRenderer.updateActivity(row, entries.length, latest?.label || latest?.event || "");
	}
	select(element) {
		for (const [candidate, { element: card }] of this.#rows) {
			const selected = candidate === element;
			card.classList.toggle("selected", selected);
			const action = card.querySelector(".component-row");
			if (selected) action?.setAttribute("aria-current", "page");
			else action?.removeAttribute("aria-current");
		}
	}
	#onComponentKeydown(event) {
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
};
var TreeViewer = class TreeViewer {
	static render(data, maxDepth = 4) {
		const container = el("div", { class: "tree" });
		TreeViewer.#buildNodes(container, data, 0, maxDepth, /* @__PURE__ */ new WeakSet());
		return container;
	}
	static #buildNodes(parent, data, depth, maxDepth, seen) {
		if (data === null || data === void 0) {
			parent.append(el("div", { class: "row" }, el("span", {
				class: "v-nul",
				text: "null"
			})));
			return;
		}
		if (typeof data !== "object") {
			parent.append(el("div", { class: "row" }, el("span", {
				class: TreeViewer.#valueClass(data),
				text: TreeViewer.#formatValue(data)
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
					TreeViewer.#buildNodes(nest, rawValue, depth + 1, maxDepth, seen);
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
						class: TreeViewer.#valueClass(rawValue),
						text: TreeViewer.#formatValue(rawValue)
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
				TreeViewer.#buildNodes(nest, value, depth + 1, maxDepth, seen);
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
					class: TreeViewer.#valueClass(value),
					text: TreeViewer.#formatValue(value)
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
	static #valueClass(value) {
		if (value === null || value === void 0) return "v-nul";
		if (typeof value === "string") return "v-str";
		if (typeof value === "number") return "v-num";
		if (typeof value === "boolean") return "v-bool";
		return "type";
	}
	static #formatValue(value) {
		if (value === null || value === void 0) return "null";
		if (typeof value === "string") return "\"" + value + "\"";
		return String(value);
	}
};
const MAX_DISPLAYED_CLASSES = 2;
function makeField(key, value, options = {}) {
	value = SENSITIVE_KEY.test(key) ? "[redacted]" : value;
	const structured = value !== null && typeof value === "object";
	const row = el("dd", { class: "value" });
	renderValue(row, value, options);
	if (!options.multiline && !options.vertical && typeof value === "string" && value.length > 32) expandableText(row);
	if (!options.multiline && !options.vertical && typeof value === "string" && value.length > 32) row.dataset.long = "";
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
	}, String(key).length > 24 ? expandableText(el("dt", {
		class: "key",
		text: key
	})) : el("dt", {
		class: "key",
		text: key
	}), structured ? el("dd", {
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
	}, labelStr.length > 24 ? expandableText(el("dt", {
		class: "key",
		text: labelStr
	})) : el("dt", {
		class: "key",
		text: labelStr
	}), el("dd", { class: "value" }, options.detail && options.detail !== "connected" ? expandableText(el("span", {
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
function renderStimulus(data, context = {}) {
	const frag = document.createDocumentFragment();
	const d = data.data;
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
function renderLive(data, context = {}) {
	const frag = document.createDocumentFragment();
	const d = data.data;
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
function renderTurbo(data, context = {}) {
	const frag = document.createDocumentFragment();
	const d = data.data;
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
function renderComponent(name, data, context) {
	switch (name) {
		case "stimulus": return renderStimulus(data, context);
		case "livecomponent": return renderLive(data, context);
		case "turbo": return renderTurbo(data, context);
		default: return null;
	}
}
var ComponentDetail = class {
	#registry;
	#render;
	#eventMonitor;
	#relationshipEngine;
	#state;
	#onDrillInto;
	#element = null;
	#target = null;
	#identityKey = "";
	#eventListener = null;
	#lifetime = new AbortController();
	#previousData = null;
	#recentChanges = /* @__PURE__ */ new Map();
	#changeTimer;
	#savedGroups = /* @__PURE__ */ new Map();
	constructor({ registry, eventMonitor, relationshipEngine, state, render = renderComponent }, onDrillInto, uiState = {}) {
		this.#registry = registry;
		this.#render = render;
		this.#eventMonitor = eventMonitor;
		this.#relationshipEngine = relationshipEngine;
		this.#state = state;
		this.#onDrillInto = onDrillInto;
		this.#savedGroups = new Map(Object.entries(uiState.groups || {}));
	}
	render(target, dataMap) {
		this.destroy();
		this.#lifetime = new AbortController();
		this.#target = target;
		this.#previousData = dataMap;
		this.#element = el("div", { class: "detail pane" });
		this.#replaceContent(dataMap, null);
		if (this.#eventMonitor) {
			this.#eventListener = (entry) => {
				if (entry.target !== target && !(entry.target && target.contains(entry.target))) return;
				const footer = this.#element?.querySelector(".activity-label");
				if (footer) footer.dataset.framework = entry.type || "default";
			};
			this.#eventMonitor.addListener(this.#eventListener);
		}
		const onUpdate = (event) => {
			const detail = event.detail;
			if (detail?.element !== target) return;
			const current = this.#state.get(target);
			if (!current) return;
			const previous = detail.previous || this.#previousData;
			this.#previousData = current;
			this.#replaceContent(current, previous ?? null);
		};
		this.#state.addEventListener?.("component-updated", onUpdate, { signal: this.#lifetime.signal });
		return this.#element;
	}
	destroy() {
		this.#rememberGroups();
		if (this.#eventListener) this.#eventMonitor?.removeListener(this.#eventListener);
		this.#eventListener = null;
		this.#lifetime.abort();
		clearTimeout(this.#changeTimer);
		this.#recentChanges.clear();
		this.#element = this.#target = this.#previousData = null;
		this.#identityKey = "";
	}
	getUiState() {
		this.#rememberGroups();
		return { groups: Object.fromEntries(this.#savedGroups) };
	}
	get activityCount() {
		const target = this.#target;
		return (target ? this.#eventMonitor?.project(target) ?? [] : []).length;
	}
	#identity(target, dataMap) {
		const { name, framework, selector } = componentIdentity(target, dataMap, this.#registry);
		const identity = name ?? selector;
		const key = JSON.stringify([
			identity,
			framework,
			selector
		]);
		if (key === this.#identityKey) return this.#element?.firstElementChild;
		this.#identityKey = key;
		const meaningfulSelector = framework !== "livecomponent" && selector !== target.localName;
		const title = el("h2", { text: identity });
		const subtitle = meaningfulSelector ? el("p", {
			class: "detail-selector",
			text: selector
		}) : null;
		return el("section", { class: "detail-head" }, identity.length > 24 ? expandableText(title) : title, el("span", {
			class: "framework",
			dataset: { framework },
			text: frameworkName(framework)
		}), subtitle && selector.length > 32 ? expandableText(subtitle) : subtitle);
	}
	#data(dataMap, previousData) {
		const body = el("div", {
			class: "detail-body",
			id: "component-detail-controller",
			role: "region",
			"aria-label": "Component details"
		});
		const target = this.#target;
		const events = this.#eventMonitor?.getEntriesForElement(target) ?? [];
		let primary = true;
		for (const [name, data] of dataMap) {
			const changes = this.#changes(previousData?.get(name), data);
			const plugin = this.#registry.get(name);
			let rendered = this.#render(name, data, {
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
			if (primary) this.#mergeGroups(groups, this.#relationships(target, name));
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
	#mergeGroups(groups, additions) {
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
	#relationships(target, framework) {
		const edges = this.#relationshipEngine?.getRelatedTo(target) ?? [];
		const groups = {
			parents: [],
			children: [],
			outlets: [],
			related: []
		};
		const seen = Object.fromEntries(Object.keys(groups).map((key) => [key, /* @__PURE__ */ new Set()]));
		for (const edge of edges) {
			const related = edge.source === target ? edge.target : edge.source;
			const name = componentIdentity(related, this.#state.get(related), this.#registry).name ?? componentLabel(related);
			const [kind, detail] = this.#describeRelationship(edge, target);
			if (seen[kind].has(related)) continue;
			seen[kind].add(related);
			const selector = related.id ? `#${related.id}` : componentLabel(related);
			const secondary = edge.type === "outlet" ? selector : selector === related.localName ? detail : `${detail} · ${selector}`;
			groups[kind].push(el("button", {
				class: "key-value relation",
				type: "button",
				"aria-label": `${detail} ${name} ${selector}`,
				on: { click: () => this.#onDrillInto?.(related) }
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
	#describeRelationship(edge, target) {
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
	#activityFooter() {
		const target = this.#target;
		return el("div", {
			class: "activity-label group",
			dataset: { framework: (target ? this.#eventMonitor?.getEntriesForElement(target)?.at(-1) : void 0)?.type || "default" }
		}, el("span", { class: "title" }, el("span", { class: "icon" }, createIcon("activity")), el("span", {
			class: "name",
			text: "Activity"
		})));
	}
	#replaceContent(dataMap, previousData) {
		const active = (this.#element?.getRootNode())?.activeElement ?? null;
		const activeKey = active && this.#element?.contains(active) ? active.closest(".key-value")?.dataset.fieldKey : null;
		this.#rememberGroups();
		const content = this.#data(dataMap, previousData);
		if (!this.#savedGroups.size) content.querySelectorAll("details.group").forEach((group) => {
			group.open = !group.hasAttribute("data-empty");
		});
		const identity = this.#identity(this.#target, dataMap);
		const current = this.#element?.querySelector(".detail-body");
		if (current) {
			if (identity !== this.#element?.firstElementChild) this.#element?.firstElementChild?.replaceWith(identity);
			current.replaceChildren(...content.childNodes);
			current.dataset.frameworks = content.dataset.frameworks;
		} else this.#element?.append(identity, content, this.#activityFooter());
		for (const group of this.#element?.querySelectorAll("details.group[data-group]") ?? []) {
			const details = group;
			const key = details.dataset.group ?? "";
			if (details.hasAttribute("data-empty")) details.open = false;
			else if (this.#savedGroups.has(key)) details.open = this.#savedGroups.get(key);
		}
		if (activeKey) ([...this.#element?.querySelectorAll(".key-value") ?? []].find((candidate) => candidate.dataset.fieldKey === activeKey)?.querySelector("button, [tabindex]"))?.focus({ preventScroll: true });
	}
	#rememberGroups() {
		for (const group of this.#element?.querySelectorAll("details.group[data-group]") ?? []) {
			const details = group;
			this.#savedGroups.set(details.dataset.group ?? "", details.open);
		}
	}
	#changes(previous, current) {
		if (!previous) return this.#recentChanges;
		let changed = false;
		const compare = (before, after, path, nested = false) => {
			const a = before || {};
			const b = after || {};
			for (const key of new Set([...Object.keys(a), ...Object.keys(b)])) {
				const field = `${path}.${key}`;
				if (nested) compare(a[key], b[key], field);
				else if (!sameSnapshot(a[key], b[key])) {
					this.#recentChanges.set(field, {
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
			clearTimeout(this.#changeTimer);
			this.#changeTimer = setTimeout(() => {
				this.#recentChanges.clear();
				for (const field of this.#element?.querySelectorAll("[data-changed]") ?? []) {
					field.removeAttribute("data-changed");
					field.removeAttribute("title");
				}
			}, 1800);
		}
		return this.#recentChanges;
	}
};
var DrillStack = class extends EventTarget {
	#levels = [];
	#headers;
	#content;
	#element;
	constructor(root, { id = "root", title = "Components" } = {}) {
		super();
		this.#headers = el("div", { class: "stack-nav" });
		this.#content = el("div", { class: "stack-body" });
		this.#element = el("section", { class: "stack pane" }, this.#headers, this.#content);
		this.push({
			id,
			title,
			content: root
		}, false);
	}
	get element() {
		return this.#element;
	}
	get depth() {
		return this.#levels.length;
	}
	get current() {
		return this.#levels.at(-1) ?? null;
	}
	push({ id, title, typePill, content, element, framework }, notify = true) {
		const previous = this.current;
		if (previous) {
			previous.scrollTop = this.#content.scrollTop;
			previous.content.hidden = true;
		}
		const index = this.#levels.length;
		const header = el("button", {
			class: "stack-link current",
			type: "button",
			"aria-current": "page",
			"aria-label": index ? `Back from ${title}` : title,
			on: { click: () => {
				if (index === this.#levels.length - 1) this.pop();
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
		this.#levels.push(level);
		this.#headers.appendChild(header);
		this.#content.appendChild(layer);
		this.#content.scrollTop = 0;
		this.#syncNavigation();
		if (notify) this.dispatchEvent(new CustomEvent("drill-push", { detail: { level } }));
		return level;
	}
	pop() {
		if (this.depth <= 1) return null;
		const removed = this.#levels.pop();
		removed.header.remove();
		removed.content.remove();
		const current = this.current;
		current.content.hidden = false;
		this.#content.scrollTop = current.scrollTop;
		this.#syncNavigation();
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
		const root = this.#levels[0];
		if (!root) return;
		root.title = title;
		const label = root.header.querySelector(".stack-title");
		if (label) label.textContent = title;
	}
	#syncNavigation() {
		const currentIndex = this.#levels.length - 1;
		const previousIndex = currentIndex - 1;
		for (const [index, level] of this.#levels.entries()) {
			const current = index === currentIndex;
			level.header.classList.toggle("current", current);
			level.header.classList.toggle("previous", index === previousIndex);
			level.header.toggleAttribute("aria-current", current);
			level.header.setAttribute("aria-label", current ? level.title : `Back to ${level.title}`);
		}
		this.#element.classList.toggle("is-drilled", this.depth > 1);
	}
};
var DetailNavigation = class {
	#drillStack;
	#drillDetails = /* @__PURE__ */ new Map();
	#nextDrillId = 0;
	#detailStates = /* @__PURE__ */ new Map();
	#pendingDetail = null;
	#restoreQueued = false;
	#destroyed = false;
	#lifetime = new AbortController();
	#state;
	#registry;
	#eventMonitor;
	#relationshipEngine;
	#activity;
	#callbacks;
	constructor(state, registry, eventMonitor, relationshipEngine, root, activity, callbacks) {
		this.#state = state;
		this.#registry = registry;
		this.#eventMonitor = eventMonitor;
		this.#relationshipEngine = relationshipEngine;
		this.#activity = activity;
		this.#callbacks = callbacks;
		this.#drillStack = new DrillStack(root);
		this.#drillStack.addEventListener("drill-push", () => callbacks.change());
		this.#drillStack.addEventListener("drill-pop", (event) => this.#onDrillPop(event.detail));
		state.addEventListener("component-removed", (event) => this.#onComponentRemoved(event.detail?.element), { signal: this.#lifetime.signal });
		state.addEventListener("components-cleared", () => this.#onComponentRemoved(this.focusedComponent), { signal: this.#lifetime.signal });
	}
	get element() {
		return this.#drillStack.element;
	}
	get focusedComponent() {
		return this.#drillStack.current?.element ?? null;
	}
	get depth() {
		return this.#drillStack.depth;
	}
	setRootTitle(title) {
		this.#drillStack.setRootTitle(title);
	}
	drillInto(element, dataMap = this.#state.get(element)) {
		if (!dataMap) return;
		this.#callbacks.showComponents();
		if (this.#drillStack.current?.element === element) {
			this.restoreActivity();
			this.#callbacks.open();
			return;
		}
		this.#activity.close(false);
		const locator = this.#componentLocator(element, dataMap);
		const stateKey = `${locator[1]}:${locator[2]}`;
		const detail = new ComponentDetail({
			registry: this.#registry,
			eventMonitor: this.#eventMonitor,
			relationshipEngine: this.#relationshipEngine,
			state: this.#state
		}, (related) => this.drillInto(related), this.#detailStates.get(stateKey));
		const content = el("div", { class: "drill-detail pane" }, detail.render(element, dataMap));
		content.addEventListener("preview-element", this.#onDetailPreview);
		content.addEventListener("clear-element-preview", this.#onDetailClear);
		content.addEventListener("select-element", this.#onDetailSelect);
		let title = element.tagName.toLowerCase();
		const framework = dataMap.keys().next().value || "default";
		const plugin = this.#registry.get(framework);
		if (plugin) title = plugin.getDisplayName(element);
		const id = `component-${++this.#nextDrillId}`;
		this.#drillDetails.set(id, {
			detail,
			stateKey,
			content,
			locator
		});
		this.#drillStack.push({
			id,
			title,
			typePill: framework === "livecomponent" ? void 0 : element.tagName.toLowerCase() + (element.id ? `#${element.id}` : ""),
			content,
			element,
			framework
		});
		this.#callbacks.open();
		this.#callbacks.select({
			element,
			framework
		});
		this.#activity.open(id, content, element, title);
	}
	drillBack() {
		return Boolean(this.#drillStack.pop());
	}
	restoreActivity() {
		const current = this.#drillStack.current;
		const record = current && this.#drillDetails.get(current.id);
		if (this.element.hidden || !current?.element || !record || this.#activity.id === current.id) return;
		this.#activity.open(current.id, record.content, current.element, current.title);
	}
	clearFocus() {
		let cleared = false;
		while (this.drillBack()) cleared = true;
		return cleared;
	}
	suspendForNavigation() {
		const current = this.#drillStack.current;
		const dataMap = current?.element ? this.#state.get(current.element) : null;
		if (dataMap && current?.element) this.#pendingDetail = this.#componentLocator(current.element, dataMap);
		while (this.drillBack());
	}
	resumeAfterNavigation() {
		this.#restorePendingDetail();
	}
	destroy() {
		this.#destroyed = true;
		this.#lifetime.abort();
		for (const id of this.#drillDetails.keys()) this.#destroyDrillDetail(id);
	}
	#componentLocator(element, dataMap) {
		const signature = this.#componentSignature(element, dataMap);
		const candidates = this.#state.elements.filter((candidate) => {
			const current = this.#state.get(candidate);
			return current && this.#componentSignature(candidate, current) === signature;
		});
		return [
			element.id,
			signature,
			Math.max(0, candidates.indexOf(element)),
			candidates.length
		];
	}
	#componentSignature(element, dataMap) {
		return `${element.tagName.toLowerCase()}|${[...dataMap.keys()].map((name) => {
			return `${name}:${this.#registry.get(name)?.getDisplayName(element) || element.tagName.toLowerCase()}`;
		}).join("|")}`;
	}
	#onComponentRemoved(element) {
		const current = this.#drillStack.current;
		if (!element || !current || current.element !== element) return;
		const record = this.#drillDetails.get(current.id);
		if (record) this.#pendingDetail = record.locator;
		while (this.drillBack());
		if (this.#restoreQueued) return;
		this.#restoreQueued = true;
		queueMicrotask(() => {
			this.#restoreQueued = false;
			this.#restorePendingDetail();
		});
	}
	#restorePendingDetail() {
		if (this.#destroyed) return false;
		if (!this.#pendingDetail) return false;
		const [id, signature, ordinal, count] = this.#pendingDetail;
		this.#pendingDetail = null;
		let match = null;
		if (id) {
			const candidate = document.getElementById(id);
			const dataMap = candidate ? this.#state.get(candidate) : null;
			if (candidate && dataMap && this.#componentSignature(candidate, dataMap) === signature) match = candidate;
		}
		if (!match) {
			const candidates = this.#state.elements.filter((element) => {
				const dataMap = this.#state.get(element);
				return dataMap && this.#componentSignature(element, dataMap) === signature;
			});
			if (candidates.length === count) match = candidates[ordinal] || null;
		}
		if (match) this.drillInto(match);
		return Boolean(match);
	}
	#onDrillPop({ removed, current }) {
		this.#destroyDrillDetail(removed.id);
		this.restoreActivity();
		this.#callbacks.change();
		if (current.element) {
			const dataMap = this.#state.get(current.element);
			this.#callbacks.select({
				element: current.element,
				framework: dataMap?.keys().next().value || "default"
			});
		} else {
			this.#callbacks.clearSelection();
			this.#callbacks.change();
		}
	}
	#destroyDrillDetail(id) {
		const record = this.#drillDetails.get(id);
		if (!record) return;
		this.#detailStates.set(record.stateKey, record.detail.getUiState());
		record.content.removeEventListener("preview-element", this.#onDetailPreview);
		record.content.removeEventListener("clear-element-preview", this.#onDetailClear);
		record.content.removeEventListener("select-element", this.#onDetailSelect);
		if (this.#activity.id === id) this.#activity.close();
		record.detail.destroy();
		this.#drillDetails.delete(id);
	}
	#onDetailPreview = (event) => this.#callbacks.preview(event.detail);
	#onDetailClear = () => this.#callbacks.clearPreview();
	#onDetailSelect = (event) => this.#callbacks.select(event.detail);
};
var ResizeHandle = class {
	element;
	#lifetime = new AbortController();
	#drag = null;
	#options;
	constructor(options) {
		this.#options = options;
		this.element = el("div", {
			class: options.className,
			role: "separator",
			tabIndex: 0,
			"aria-label": options.label,
			"aria-orientation": options.axis === "width" ? "vertical" : "horizontal"
		});
		const { signal } = this.#lifetime;
		this.element.addEventListener("pointerdown", (event) => this.#start(event), { signal });
		this.element.addEventListener("keydown", (event) => {
			const direction = (options.axis === "width" ? ["ArrowLeft", "ArrowRight"] : ["ArrowUp", "ArrowDown"]).indexOf(event.key);
			if (direction < 0) return;
			event.preventDefault();
			this.#resize(options.read() + (direction === 0 ? 16 : -16));
		}, { signal });
	}
	destroy() {
		this.#stop();
		this.#lifetime.abort();
	}
	#resize(size) {
		const value = this.#options.write(size);
		this.element.setAttribute("aria-valuenow", String(Math.round(value)));
	}
	#start(event) {
		if (event.button !== 0) return;
		event.preventDefault();
		this.#stop();
		this.#drag = new AbortController();
		const { signal } = this.#drag;
		const coordinate = this.#options.axis === "width" ? "clientX" : "clientY";
		const start = event[coordinate];
		const size = this.#options.read();
		this.#options.target.toggleAttribute("data-resizing", true);
		this.element.setPointerCapture?.(event.pointerId);
		this.element.addEventListener("pointermove", (move) => {
			if (move.pointerId === event.pointerId) this.#resize(size + start - move[coordinate]);
		}, { signal });
		for (const type of [
			"pointerup",
			"pointercancel",
			"lostpointercapture"
		]) this.element.addEventListener(type, () => this.#stop(), { signal });
	}
	#stop() {
		this.#drag?.abort();
		this.#drag = null;
		this.#options.target.removeAttribute("data-resizing");
	}
};
var ActivityDrawer = class {
	#timeline;
	#home;
	#restore;
	#id = null;
	#drawer = null;
	#resizeHandle = null;
	#sizes = /* @__PURE__ */ new WeakMap();
	constructor(timeline, home, restore) {
		this.#timeline = timeline;
		this.#home = home;
		this.#restore = restore;
	}
	get id() {
		return this.#id;
	}
	open(id, content, element, query) {
		this.close(false);
		const drawer = el("section", {
			class: "pane drawer",
			id: `component-activity-${id}`,
			"aria-label": `Activity for ${query || "component"}`
		}, this.#timeline.element);
		content.appendChild(drawer);
		const savedHeight = this.#sizes.get(content);
		if (savedHeight !== void 0) {
			drawer.style.height = `${savedHeight}px`;
			drawer.toggleAttribute("data-sized", true);
		}
		this.#resizeHandle = new ResizeHandle({
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
				this.#sizes.set(content, value);
				return value;
			}
		});
		drawer.prepend(this.#resizeHandle.element);
		this.#drawer = drawer;
		this.#id = id;
		this.#timeline.configure({
			contextual: true,
			frameworks: null,
			element,
			query: element ? "" : query
		});
		this.#timeline.expandFirstVisible();
	}
	close(restore = true) {
		if (!this.#id) return false;
		this.#home.appendChild(this.#timeline.element);
		this.#resizeHandle?.destroy();
		this.#drawer?.remove();
		this.#drawer = null;
		this.#id = null;
		if (restore) this.#restore();
		return true;
	}
};
const FRAMEWORKS = [
	["stimulus", "Stimulus"],
	["livecomponent", "Live"],
	["turbo", "Turbo"]
];
var Panel = class {
	#state;
	#eventMonitor;
	#timeline;
	#host;
	#list;
	#navigation;
	#activity;
	#element;
	#components;
	#componentPanel;
	#activityPanel;
	#search;
	#filters = new Set(FRAMEWORKS.map(([name]) => name));
	#query = "";
	#monitorLabel;
	#actions = {};
	#actionButtons = {};
	#logTools;
	#activitySearch;
	#activityFilters = new Set(FRAMEWORKS.map(([name]) => name));
	#activityFilterButtons = {};
	#lifetime = new AbortController();
	#eventListener = null;
	#packages;
	#filterButtons = {};
	#refreshFrame = null;
	#resizeHandle;
	#view = "components";
	#activityCount = 0;
	#pendingActivity = /* @__PURE__ */ new Set();
	#listDirty = true;
	#onPreview = null;
	#onClearPreview = null;
	#onSelect = null;
	#onClearSelection = null;
	#selectedComponent = null;
	constructor(state, registry, eventMonitor, timeline, host, relationshipEngine, packages = {}) {
		this.#state = state;
		this.#eventMonitor = eventMonitor;
		this.#timeline = timeline;
		this.#host = host;
		this.#packages = packages;
		this.#list = new ComponentList(state, registry, eventMonitor, {
			onPreview: (target) => this.#onPreview?.(target),
			onClearPreview: () => this.#onClearPreview?.(),
			onSelect: (target) => this.#onSelect?.(target)
		});
		this.#element = el("aside", {
			class: "inspector pane",
			"aria-label": "Symfony UX Inspector"
		});
		const tools = this.#tools();
		this.#resizeHandle = new ResizeHandle({
			target: this.#element,
			axis: "width",
			label: "Resize inspector",
			className: "panel-resize",
			read: () => this.#element.getBoundingClientRect().width,
			write: (width) => this.#host.setPanelWidth(width)
		});
		this.#element.append(this.#resizeHandle.element, this.#header(), tools, this.#filterBar());
		this.#components = this.#list.element;
		this.#activityPanel = el("section", {
			id: "panel-activity",
			hidden: true,
			role: "region",
			tabindex: "0",
			"aria-label": "Activity"
		}, this.#timeline.element);
		this.#activity = new ActivityDrawer(timeline, this.#activityPanel, () => {
			timeline.configure({
				contextual: false,
				element: null,
				frameworks: this.#activityFilters,
				query: this.#activitySearch.value
			});
		});
		this.#navigation = new DetailNavigation(state, registry, eventMonitor, relationshipEngine, this.#components, this.#activity, {
			showComponents: () => this.#switchTab("components", false),
			open: () => this.open(),
			change: () => {
				this.#updateDrillUi();
				this.refresh();
			},
			select: (target) => {
				this.#selectedComponent = target.element;
				this.#list.select(target.element);
				this.#onSelect?.(target);
			},
			clearSelection: () => {
				this.#selectedComponent = null;
				this.#list.select(null);
				this.#onClearSelection?.();
			},
			preview: (target) => this.#onPreview?.(target),
			clearPreview: () => this.#onClearPreview?.()
		});
		this.#componentPanel = this.#navigation.element;
		this.#componentPanel.id = "panel-components";
		this.#componentPanel.setAttribute("role", "region");
		this.#componentPanel.setAttribute("tabindex", "0");
		this.#componentPanel.setAttribute("aria-label", "Components");
		this.#element.append(el("main", {}, this.#componentPanel, this.#activityPanel), this.#footer());
		this.#components.addEventListener("drill-into", (event) => this.drillInto(event.detail.element));
		const refresh = () => {
			if (this.#refreshFrame !== null) return;
			this.#refreshFrame = requestAnimationFrame(() => {
				this.#refreshFrame = null;
				this.#flush();
			});
		};
		for (const name of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared",
			"page-updated"
		]) {
			const listener = () => {
				this.#listDirty = true;
				refresh();
			};
			state.addEventListener(name, listener, { signal: this.#lifetime.signal });
		}
		this.#eventListener = (entry, removed = []) => {
			for (const changed of [entry, ...removed]) for (const element of activityElements(changed)) this.#pendingActivity.add(element);
			refresh();
		};
		eventMonitor?.addListener(this.#eventListener);
		this.refresh();
	}
	get element() {
		return this.#element;
	}
	get isComponentListVisible() {
		return this.#view === "components";
	}
	get focusedComponent() {
		return this.#navigation.focusedComponent;
	}
	setActionCallbacks(callbacks) {
		this.#actions = { ...callbacks };
	}
	setVisualCallbacks({ onPreview, onClearPreview, onSelect, onClearSelection }) {
		this.#onPreview = onPreview ?? null;
		this.#onClearPreview = onClearPreview ?? null;
		this.#onSelect = onSelect ?? null;
		this.#onClearSelection = onClearSelection ?? null;
	}
	setTargetModeActive(active) {
		const button = this.#actionButtons.target;
		const label = active ? "Stop inspecting" : "Inspect page components";
		button.classList.toggle("active", Boolean(active));
		button.setAttribute("aria-pressed", String(Boolean(active)));
		button.setAttribute("aria-label", label);
		button.title = active ? "Click to inspect. Shift-click to continue." : label;
		this.#monitorLabel.textContent = active ? "Inspecting" : "Watching";
		this.#monitorLabel.parentElement?.classList.toggle("inspecting", Boolean(active));
	}
	setOverlayActive(active) {
		const button = this.#actionButtons.overlay;
		const label = active ? "Hide all components" : "Show all components";
		button.classList.toggle("active", Boolean(active));
		button.setAttribute("aria-pressed", String(Boolean(active)));
		button.setAttribute("aria-label", label);
		this.#monitorLabel.textContent = active ? "Overlay enabled" : "Watching";
	}
	setLogPaused(paused) {
		this.#monitorLabel.textContent = paused ? "Activity paused" : "Watching";
		this.#monitorLabel.parentElement?.classList.toggle("paused", Boolean(paused));
	}
	clearActivities() {
		this.#pendingActivity.clear();
		this.#list.clearActivities();
		this.#updateCounts();
	}
	open() {
		this.#host?.open();
	}
	close() {
		this.#host?.close();
	}
	navigate(view) {
		this.#switchTab(view === "log" ? "log" : "components");
		this.open();
		if (view === "search") {
			while (this.drillBack());
			this.#search.focus();
		}
	}
	refresh() {
		this.#listDirty = true;
		this.#flush();
	}
	#flush() {
		if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
		this.#refreshFrame = null;
		this.#updateCounts();
		if (this.#listDirty && this.isComponentListVisible) {
			this.#list.refresh(this.#filters, this.#query, this.#selectedComponent);
			this.#navigation.setRootTitle(`Components (${this.#state.size})`);
			this.#listDirty = false;
		}
		for (const element of this.#pendingActivity) this.#list.updateActivity(element);
		this.#pendingActivity.clear();
	}
	drillInto(element, dataMap = this.#state.get(element)) {
		this.#navigation.drillInto(element, dataMap);
	}
	drillBack() {
		return this.#navigation.drillBack();
	}
	clearFocus() {
		return this.#navigation.clearFocus();
	}
	suspendForNavigation() {
		this.#navigation.suspendForNavigation();
	}
	resumeAfterNavigation() {
		this.#navigation.resumeAfterNavigation();
	}
	destroy() {
		this.#resizeHandle.destroy();
		if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
		this.#navigation.destroy();
		this.#list.destroy();
		this.#pendingActivity.clear();
		this.#lifetime.abort();
		if (this.#eventListener) this.#eventMonitor?.removeListener(this.#eventListener);
	}
	#header() {
		this.#actionButtons.target = this.#toggleButton("target", "target", "Inspect page components", () => this.#actions.target?.());
		this.#actionButtons.overlay = this.#toggleButton("overlay", "overlay", "Show all components", () => this.#actions.overlay?.());
		this.#actionButtons.activity = this.#toggleButton("activity", "activity", "Show activity", () => this.#toggleActivity());
		this.#actionButtons.activity.appendChild(el("b", { text: "0" }));
		const close = createIconButton("panel-right", "Hide inspector", () => this.close());
		close.className = "icon-button";
		return el("header", {}, el("div", { class: "header-actions" }, this.#actionButtons.target, this.#actionButtons.overlay), el("strong", {
			id: "ux-inspector-title",
			text: "UX Inspector"
		}), el("div", { class: "header-actions header-end" }, this.#actionButtons.activity, close));
	}
	#switchTab(name, restoreActivity = true) {
		this.#view = name === "log" ? "log" : "components";
		const activityVisible = this.#view === "log";
		const restored = activityVisible && this.#activity.close();
		this.#componentPanel.hidden = activityVisible;
		this.#activityPanel.hidden = !activityVisible;
		this.#updateDrillUi();
		this.#logTools.hidden = !activityVisible;
		if (activityVisible && !restored) this.#timeline.refresh();
		else if (!activityVisible) {
			if (restoreActivity) this.#navigation.restoreActivity();
			if (this.#listDirty) this.#flush();
		}
	}
	#toggleActivity() {
		if (this.#view === "log") {
			this.#switchTab("components");
			return;
		}
		this.#openGlobalActivity();
	}
	#syncActivityControl() {
		const active = this.#view === "log";
		const count = this.#activityCount;
		const label = active ? "Show components" : "Show activity";
		const accessibleLabel = count ? `${label}, ${count} ${count === 1 ? "activity" : "activities"}` : label;
		const button = this.#actionButtons.activity;
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
	#tools() {
		const buttons = this.#frameworkButtons(this.#activityFilters, this.#activityFilterButtons, () => this.#timeline.configure({ frameworks: this.#activityFilters }));
		this.#activitySearch = el("input", {
			type: "search",
			placeholder: "Filter activity…",
			"aria-label": "Filter activity",
			on: { input: () => this.#timeline.configure({ query: this.#activitySearch.value }) }
		});
		this.#logTools = el("div", {
			class: "activity-tools toolbar--activity",
			hidden: true
		}, el("div", {
			class: "filter-list activity-filter-list",
			role: "group",
			"aria-label": "Filter activity by framework"
		}, ...buttons), this.#activitySearch);
		return this.#logTools;
	}
	#toggleButton(name, icon, label, callback) {
		const button = createIconButton(icon, label, callback);
		button.className = "icon-button";
		button.dataset.action = name;
		button.setAttribute("aria-pressed", "false");
		return button;
	}
	#filterBar() {
		this.#search = el("input", {
			type: "search",
			placeholder: "Find a component…",
			"aria-label": "Find a component",
			on: { input: () => {
				this.#query = this.#search.value.trim().toLowerCase();
				this.refresh();
			} }
		});
		return el("div", { class: "filters" }, el("div", { class: "filter-list" }, ...this.#frameworkButtons(this.#filters, this.#filterButtons, () => this.refresh())), this.#search);
	}
	#frameworkButtons(filters, buttons, update) {
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
	#footer() {
		this.#monitorLabel = el("span", { text: "Watching" });
		return el("footer", {}, el("span", {
			class: "monitor-status",
			role: "status"
		}, el("span", {
			class: "live-dot",
			"aria-hidden": "true"
		}), this.#monitorLabel));
	}
	#updateCounts() {
		const byPlugin = this.#state.countByPlugin();
		const activityByPlugin = {
			...Object.fromEntries(FRAMEWORKS.map(([name]) => [name, 0])),
			...Object.fromEntries(Object.entries(Object.groupBy(this.#eventMonitor?.project() ?? [], (entry) => entry.type)).map(([type, entries]) => [type, entries.length]))
		};
		for (const [name, label] of FRAMEWORKS) {
			const button = this.#filterButtons[name];
			const count = byPlugin[name] || 0;
			const installed = this.#packages[name];
			const status = count ? "detected" : installed === false ? "not-installed" : installed === true ? "idle" : "unknown";
			const badge = button.querySelector("b");
			if (badge) badge.textContent = installed === false ? "-" : String(count);
			button.classList.toggle("unavailable", installed === false);
			button.dataset.status = status;
			button.title = count ? `${count} detected on this page` : installed === false ? `${label} package not installed` : installed === true ? `${label} installed; none detected on this page` : "None detected on this page";
			button.setAttribute("aria-label", `${label}: ${button.title}`);
			const activityButton = this.#activityFilterButtons[name];
			const activityBadge = activityButton.querySelector("b");
			if (activityBadge) activityBadge.textContent = String(activityByPlugin[name]);
			activityButton.title = `${activityByPlugin[name]} ${label} ${activityByPlugin[name] === 1 ? "activity" : "activities"}`;
			activityButton.setAttribute("aria-label", activityButton.title);
		}
		this.#activityCount = Object.values(activityByPlugin).reduce((sum, count) => sum + count, 0);
		this.#syncActivityControl();
	}
	#updateDrillUi() {
		const componentsVisible = this.#view === "components";
		const filters = this.#element.querySelector(".filters");
		if (filters) filters.hidden = !componentsVisible || this.#navigation.depth > 1;
		this.#syncActivityControl();
	}
	#openGlobalActivity() {
		this.#activitySearch.value = "";
		this.#timeline.configure({ query: "" });
		this.#switchTab("log");
	}
};
const MAX_ENTRIES = 100;
var Timeline = class {
	#monitor;
	#element;
	#list;
	#filterEmpty;
	#onHighlight = null;
	#onSelect = null;
	#rafId = null;
	#paused = false;
	#query = "";
	#elementFilter = null;
	#frameworks = null;
	#entries = [];
	#rows = /* @__PURE__ */ new Map();
	#nextDetailId = 0;
	#selectedEntry = null;
	#contextual = false;
	constructor(monitor, callbacks = {}) {
		this.#monitor = monitor;
		this.#onHighlight = callbacks.onHighlight || null;
		this.#onSelect = callbacks.onSelect || null;
		this.#entries = monitor.entries || [];
		this.#list = el("ol", {
			class: "events",
			"aria-label": "Captured UX events",
			"aria-live": "polite",
			on: { keydown: (event) => this.#onKeydown(event) }
		});
		this.#filterEmpty = createEmptyState("No matches.");
		this.#filterEmpty.hidden = true;
		this.#element = el("div", { class: "timeline" }, this.#list, this.#filterEmpty);
	}
	get element() {
		return this.#element;
	}
	get paused() {
		return this.#paused;
	}
	get projectedEntries() {
		return this.#monitor.project();
	}
	configure({ query = this.#query, element = this.#elementFilter, frameworks = this.#frameworks, contextual = this.#contextual }) {
		const contextChanged = contextual !== this.#contextual;
		const render = contextChanged || element !== this.#elementFilter;
		this.#query = query.trim().toLowerCase();
		this.#elementFilter = element;
		this.#frameworks = frameworks ? new Set(frameworks) : null;
		this.#contextual = contextual;
		if (contextChanged) {
			this.#selectedEntry = null;
			this.#rows.clear();
		}
		if (render) if (this.#paused) this.#renderEntries();
		else this.#renderSnapshot();
		else this.#applyFilter();
	}
	async copySelected() {
		const clipboard = navigator.clipboard;
		const entry = this.#selectedEntry;
		if (!entry || !clipboard?.writeText) return false;
		const diagnostic = this.#diagnostic(entry);
		try {
			await clipboard.writeText(JSON.stringify(diagnostic));
			return true;
		} catch {
			return false;
		}
	}
	addEntry(_entry) {
		if (this.#paused || this.#rafId !== null) return;
		this.#rafId = requestAnimationFrame(() => this.#flushEntries());
	}
	#flushEntries() {
		this.#rafId = null;
		if (!this.#paused) this.#renderSnapshot();
	}
	flush() {
		if (!this.#rafId) return;
		cancelAnimationFrame(this.#rafId);
		this.#flushEntries();
	}
	destroy() {
		if (this.#rafId !== null) cancelAnimationFrame(this.#rafId);
		this.#rafId = null;
		this.#paused = true;
		this.#rows.clear();
		this.#entries = [];
		this.#selectedEntry = null;
		this.#onHighlight = null;
		this.#onSelect = null;
		this.#element.remove();
	}
	pause() {
		if (this.#paused) return false;
		this.#paused = true;
		if (this.#rafId) cancelAnimationFrame(this.#rafId);
		this.#rafId = null;
		this.#onHighlight?.(null);
		return true;
	}
	resume() {
		if (!this.#paused) return false;
		this.#paused = false;
		this.#renderSnapshot();
		return true;
	}
	clear() {
		if (this.#rafId) cancelAnimationFrame(this.#rafId);
		this.#rafId = null;
		this.#selectedEntry = null;
		this.#monitor.clear();
		this.#entries = [];
		this.#renderEntries();
		this.#element.dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: null } }));
	}
	refresh() {
		if (this.#paused) return;
		this.#renderSnapshot();
	}
	expandFirstVisible() {
		const disclosure = [...this.#list.querySelectorAll(".event:not([hidden])")].find((candidate) => this.#hasDetail(candidate._inspectorEntry))?.querySelector(".disclosure");
		if (disclosure?.getAttribute("aria-expanded") !== "true") disclosure?.click();
	}
	#renderSnapshot() {
		if (this.#rafId !== null) cancelAnimationFrame(this.#rafId);
		this.#rafId = null;
		this.#entries = this.#monitor.entries || [];
		this.#renderEntries();
	}
	#renderEntries() {
		const selectedAnchor = this.#selectedEntry && this.#anchor(this.#selectedEntry);
		const root = this.#element.getRootNode();
		const focused = root.activeElement;
		const focusedItem = focused?.closest(".event");
		const focusedAnchor = focusedItem?._inspectorEntry && this.#anchor(focusedItem._inspectorEntry);
		const focusedControl = focused?.dataset.control;
		this.#onHighlight?.(null);
		const entries = (this.#paused ? projectActivity(this.#entries, !this.#contextual) : this.#monitor.project(null, !this.#contextual)).slice(-MAX_ENTRIES).map((entry) => {
			const previous = this.#rows.get(this.#anchor(entry))?._inspectorEntry;
			return previous && this.#sameEntries(previous, entry) ? previous : entry;
		});
		this.#selectedEntry = selectedAnchor ? entries.find((entry) => this.#anchor(entry) === selectedAnchor) || null : null;
		this.#rows = new Map(entries.map((entry) => {
			const anchor = this.#anchor(entry);
			const previous = this.#rows.get(anchor);
			const owner = this.#owner(entry);
			return [anchor, previous?._inspectorEntry === entry && previous._navigationTarget === this.#navigationTarget(owner) ? previous : this.#renderEntry(entry, owner)];
		}));
		reconcileChildren(this.#list, this.#rows.size ? [...this.#rows.values()].reverse() : [createEmptyState("No events captured yet.")]);
		this.#applyFilter();
		if (focusedAnchor && root.activeElement !== focused) {
			const row = this.#rows.get(focusedAnchor);
			const control = focusedControl ? row?.querySelector(`[data-control="${focusedControl}"]:not([hidden])`) : null;
			const fallback = this.#list.querySelector(".event:not([hidden]) button");
			(control && !row?.hidden ? control : fallback)?.focus({ preventScroll: true });
		}
	}
	#owner(entry) {
		return entry.owner || entry.target?.closest?.("[data-controller], turbo-frame") || null;
	}
	#navigationTarget(owner) {
		return owner?.isConnected ? owner : null;
	}
	#anchor(entry) {
		return entry.id ?? entry.rawEntries?.[0] ?? entry;
	}
	#sameEntries(previous, current) {
		if (previous === current) return true;
		return !!previous.rawEntries && !!current.rawEntries && previous.rawEntries.length === current.rawEntries.length && previous.rawEntries.every((entry, index) => entry === current.rawEntries?.[index]);
	}
	#renderEntry(entry, owner) {
		const type = entry.type || "unknown";
		const eventName = this.#eventIdentity(entry);
		const targetIdentity = this.#targetIdentity(entry.target ?? null);
		const visibleTarget = this.#visibleContext(entry, targetIdentity, owner);
		const navigationTarget = this.#navigationTarget(owner);
		const hasDetail = this.#hasDetail(entry);
		const selected = hasDetail && this.#selectedEntry === entry;
		const detailId = `uxli-activity-detail-${++this.#nextDetailId}`;
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
		const navigationIdentity = this.#targetIdentity(navigationTarget);
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
			dataset: { search: this.#searchText(entry, eventName, targetIdentity, owner) }
		}, row, detail);
		item._inspectorEntry = entry;
		item._navigationTarget = navigationTarget;
		if (hasDetail && detail) disclosure.addEventListener("click", () => this.#select(entry, row, detail));
		if (navigationTarget) {
			row.addEventListener("mouseenter", () => {
				goBtn.hidden = !navigationTarget.isConnected;
				if (navigationTarget.isConnected) this.#onHighlight?.(navigationTarget, type);
			});
			row.addEventListener("mouseleave", () => this.#onHighlight?.(null));
		}
		goBtn.addEventListener("click", () => {
			goBtn.hidden = !navigationTarget?.isConnected;
			if (goBtn.hidden) return;
			this.#onHighlight?.(null);
			if (navigationTarget) this.#onSelect?.(navigationTarget);
		});
		return item;
	}
	#hasDetail(entry) {
		if (!entry) return false;
		if (entry.activityKind === "turbo-fetch" || entry.activityKind === "live-rerender") return true;
		if (entry.detail == null) return false;
		if (Array.isArray(entry.detail)) return entry.detail.length > 0;
		if (typeof entry.detail === "object") return Object.keys(entry.detail).length > 0;
		return true;
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
			fields.push(makeField("Intent", fetch.intent === "prefetch" ? "Prefetch" : "Fetch"), makeField("Request", [fetch.method, fetch.url].filter(Boolean).join(" ")), makeField("Status", fetch.pending ? "Pending" : fetch.status ?? "Completed"), makeField("Duration", fetch.pending ? "Pending" : this.#formatDuration(fetch.duration)));
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
			if (live.duration != null) fields.push(makeField("Duration", this.#formatDuration(live.duration)));
		} else if (entry.detail != null) {
			const values = typeof entry.detail === "object" && !Array.isArray(entry.detail) ? Object.entries(entry.detail) : [["value", entry.detail]];
			for (const [name, value] of values) fields.push(makeField(name, value));
		}
		return el("section", {
			class: "activity-detail",
			dataset: { framework: entry.type || "default" }
		}, el("div", { class: "event-actions" }, copy), makeKeyValueList(fields), entry.activityKind === "turbo-fetch" ? this.#renderRawEvents(entry.rawEntries) : null);
	}
	#renderRawEvents(entries) {
		return el("details", {
			class: "raw-events",
			open: false
		}, el("summary", {}, el("span", { text: "Raw events" }), el("span", {
			class: "raw-count",
			text: String(entries.length)
		})), el("ol", { class: "raw-list" }, ...entries.map((entry) => el("li", {}, el("strong", { text: entry.event }), el("span", { text: `${(entry.time / 1e3).toFixed(3)}s` })))));
	}
	#select(entry, row, detail) {
		const open = this.#selectedEntry !== entry || !row.classList.contains("selected");
		this.#selectedEntry = open ? entry : null;
		for (const item of this.#list.querySelectorAll(".event")) {
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
		this.#element.dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: this.#selectedEntry } }));
	}
	#applyFilter() {
		const items = Array.from(this.#list.querySelectorAll(".event"));
		let visible = 0;
		for (const item of items) {
			const matchesFramework = !this.#frameworks || this.#frameworks.has(item._inspectorEntry?.type ?? "");
			const matchesElement = !this.#elementFilter || this.#matchesElement(item._inspectorEntry, this.#elementFilter);
			const matches = matchesFramework && matchesElement && (!this.#query || (item.dataset.search ?? "").includes(this.#query));
			item.hidden = !matches;
			if (matches) visible++;
		}
		this.#filterEmpty.hidden = items.length === 0 || visible > 0;
		const selected = this.#list.querySelector(".event-row.selected")?.closest(".event");
		if (this.#selectedEntry && (!selected || selected.hidden)) {
			selected?.querySelector(".event-row")?.classList.remove("selected");
			selected?.querySelector(".disclosure")?.setAttribute("aria-expanded", "false");
			const detail = selected?.querySelector(".event-detail");
			if (detail) {
				detail.hidden = true;
				detail.replaceChildren();
			}
			this.#selectedEntry = null;
			this.#element.dispatchEvent(new CustomEvent("activity-selected", { detail: { entry: null } }));
		}
	}
	#matchesElement(entry, element) {
		return entry?.owner === element || entry?.target === element || (entry?.relatedElements?.includes(element) ?? false) || (entry?.rawEntries?.some((raw) => raw.owner === element || raw.target === element || raw.relatedElements?.includes(element)) ?? false);
	}
	#onKeydown(event) {
		if (![
			"ArrowUp",
			"ArrowDown",
			"Home",
			"End"
		].includes(event.key)) return;
		const buttons = Array.from(this.#list.querySelectorAll("button.disclosure"));
		if (!buttons.length) return;
		const current = buttons.indexOf(event.target?.closest?.(".disclosure"));
		const next = event.key === "Home" ? 0 : event.key === "End" ? buttons.length - 1 : Math.min(buttons.length - 1, Math.max(0, current + (event.key === "ArrowDown" ? 1 : -1)));
		event.preventDefault();
		buttons[next].focus();
	}
	#targetIdentity(target) {
		if (!target?.tagName) return "";
		return target.tagName.toLowerCase() + (target.id ? `#${target.id}` : "");
	}
	#eventIdentity(entry) {
		if (entry.activityKind && entry.activityKind !== "repeated") return entry.label || entry.event;
		if (this.#contextual) return entry.event;
		const label = entry.label || entry.event;
		if (entry.type !== "livecomponent") return label;
		const component = entry.target?.dataset?.liveNameValue;
		return component && label.startsWith(`${component}: `) ? label.slice(component.length + 2) : entry.event?.replace(/^live:/, "") || label;
	}
	#visibleContext(entry, targetIdentity, owner) {
		if (entry.type === "livecomponent") return entry.target?.dataset?.liveNameValue || "";
		if (!owner || ["html", "body"].includes(targetIdentity)) return "";
		return targetIdentity;
	}
	#detailedTargetIdentity(target) {
		const identity = this.#targetIdentity(target);
		if (!identity) return "";
		return identity + (target.className && typeof target.className === "string" ? "." + target.className.trim().split(/\s+/).filter(Boolean).slice(0, 3).join(".") : "");
	}
	#searchText(entry, eventName, targetIdentity, owner) {
		const rawEvents = (entry.rawEntries || []).map((raw) => raw.event).join(" ");
		const fetch = entry.fetch ? `${entry.fetch.intent} ${entry.fetch.method} ${entry.fetch.url} ${entry.fetch.status ?? ""} ${entry.fetch.priority}` : "";
		const dataset = owner?.dataset;
		return `${eventName} ${entry.type || "unknown"} ${targetIdentity} ${owner?.id || ""} ${dataset?.controller || ""} ${dataset?.liveNameValue || ""} ${rawEvents} ${fetch}`.toLowerCase();
	}
	#formatDuration(milliseconds) {
		if (typeof milliseconds !== "number" || !Number.isFinite(milliseconds)) return "Unknown";
		if (milliseconds < 1) return "<1 ms";
		if (milliseconds < 1e3) return `${Math.round(milliseconds)} ms`;
		return `${(milliseconds / 1e3).toFixed(2)} s`;
	}
	#diagnostic(entry) {
		const base = {
			event: entry.event,
			framework: entry.type,
			time: Number((entry.time / 1e3).toFixed(3)),
			label: entry.label,
			target: entry.target instanceof Element ? this.#detailedTargetIdentity(entry.target) : void 0
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
				target: raw.target instanceof Element ? this.#detailedTargetIdentity(raw.target) : void 0,
				detail: raw.detail
			}))
		};
	}
};
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
var Highlighter = class Highlighter {
	static #EVENT_LIFETIME = 1200;
	static #BOX_GUTTER = 2;
	#container;
	#hover = null;
	#selected = null;
	#all = /* @__PURE__ */ new Map();
	#events = /* @__PURE__ */ new Map();
	#allVisible = false;
	#state;
	#registry;
	#lifetime = new AbortController();
	#resizeObserver;
	#observed = /* @__PURE__ */ new Map();
	constructor(root = document.documentElement, state = null, registry = null) {
		this.#state = state;
		this.#registry = registry;
		this.#container = document.createElement("div");
		this.#container.className = "overlay";
		this.#container.setAttribute("data-ux-inspector-overlay", "");
		root.appendChild(this.#container);
		this.#resizeObserver = new ResizeObserver((entries) => {
			for (const entry of entries) this.#refreshTarget(entry.target);
		});
		const update = (event) => {
			if (this.#allVisible) this.#renderAll(event.detail?.element);
		};
		for (const event of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) state?.addEventListener(event, update, { signal: this.#lifetime.signal });
	}
	get visible() {
		return this.#allVisible;
	}
	hover(element, framework = "default", label = "") {
		this.#hover = this.#box(element, framework, "hover", label, this.#hover);
	}
	clearHover() {
		this.#removeBox(this.#hover);
		this.#hover = null;
	}
	select(element, framework = "default", label = "") {
		this.#selected = this.#box(element, framework, "selected", label, this.#selected);
	}
	deselect() {
		this.#removeBox(this.#selected);
		this.#selected = null;
	}
	showAll() {
		if (this.#allVisible) return;
		this.#allVisible = true;
		this.#renderAll();
	}
	hideAll() {
		this.#allVisible = false;
		for (const box of this.#all.values()) this.#removeBox(box);
		this.#all.clear();
	}
	toggleAll() {
		if (this.#allVisible) this.hideAll();
		else this.showAll();
		return this.#allVisible;
	}
	pulse(element, framework = "default", label = "") {
		if (!element?.isConnected) return;
		let entry = this.#events.get(element);
		if (entry) {
			if (entry.timer) clearTimeout(entry.timer);
			if (entry.raf !== null) cancelAnimationFrame(entry.raf);
			entry.count = entry.label === label ? entry.count + 1 : 1;
			entry.label = label;
			entry.box.dataset.framework = framework;
			this.#position(entry.box, element.getBoundingClientRect());
		} else {
			const box = this.#box(element, framework, "event");
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
			this.#events.set(element, entry);
		}
		const current = entry;
		current.caption.textContent = current.count > 1 ? `${label} ×${current.count}` : label;
		current.box.classList.remove("pulse");
		current.raf = requestAnimationFrame(() => {
			current.raf = null;
			current.box.classList.add("pulse");
		});
		current.timer = setTimeout(() => this.#removeEvent(element, current), Highlighter.#EVENT_LIFETIME);
	}
	refresh() {
		for (const element of this.#observed.keys()) this.#refreshTarget(element);
	}
	clearAll() {
		this.clearHover();
		this.deselect();
		this.hideAll();
		for (const [element, entry] of this.#events) this.#removeEvent(element, entry);
	}
	destroy() {
		this.clearAll();
		this.#lifetime.abort();
		this.#resizeObserver.disconnect();
		this.#container.remove();
	}
	#renderAll(changed) {
		const elements = new Set(this.#state?.elements);
		for (const [element, box] of this.#all) {
			if (elements.has(element) && element.isConnected) continue;
			this.#removeBox(box);
			this.#all.delete(element);
		}
		for (const element of elements) {
			if (!element.isConnected || this.#all.has(element) && element !== changed) continue;
			const framework = this.#registry?.getForElement(element)[0]?.name ?? "default";
			const label = this.#registry?.get(framework)?.getDisplayName(element) ?? "";
			this.#all.set(element, this.#box(element, framework, "all", label, this.#all.get(element)));
		}
	}
	#box(element, framework, mode, label = "", previous) {
		const box = previous ?? document.createElement("div");
		if (box.dataset.framework !== framework) box.dataset.framework = framework;
		if (box._target !== element) {
			this.#removeBox(box, false);
			box._target = element;
			const count = this.#observed.get(element) ?? 0;
			if (!count) this.#resizeObserver.observe(element, { box: "border-box" });
			this.#observed.set(element, count + 1);
		}
		if (label) {
			const caption = box.firstElementChild ?? box.appendChild(document.createElement("span"));
			if (caption.textContent !== label) caption.textContent = label;
		} else box.firstElementChild?.remove();
		this.#position(box, element.getBoundingClientRect());
		if (!previous) {
			box.className = "box";
			box.dataset.mode = mode;
			this.#container.appendChild(box);
		}
		return box;
	}
	#refreshTarget(element) {
		if (!this.#observed.has(element)) return;
		const rect = element.isConnected ? element.getBoundingClientRect() : null;
		const event = this.#events.get(element);
		if (!rect && event) this.#removeEvent(element, event);
		for (const box of [
			this.#all.get(element),
			event?.box,
			this.#hover,
			this.#selected
		]) {
			if (box?._target !== element) continue;
			if (rect) this.#position(box, rect);
			else this.#removeBox(box);
		}
		if (!rect) this.#all.delete(element);
		if (!this.#hover?._target) this.#hover = null;
		if (!this.#selected?._target) this.#selected = null;
	}
	#removeBox(box, remove = true) {
		if (!box) return;
		const target = box._target;
		if (target) {
			const count = (this.#observed.get(target) ?? 1) - 1;
			if (count) this.#observed.set(target, count);
			else {
				this.#observed.delete(target);
				this.#resizeObserver.unobserve(target);
			}
			delete box._target;
		}
		if (remove) box.remove();
	}
	#removeEvent(element, entry) {
		if (this.#events.get(element) !== entry) return;
		if (entry.timer) clearTimeout(entry.timer);
		if (entry.raf !== null) cancelAnimationFrame(entry.raf);
		this.#removeBox(entry.box);
		this.#events.delete(element);
	}
	#position(box, rect) {
		const gutter = Highlighter.#BOX_GUTTER;
		for (const [property, value] of Object.entries({
			translate: `${rect.left - gutter}px ${rect.top - gutter}px`,
			width: `${rect.width + gutter * 2}px`,
			height: `${rect.height + gutter * 2}px`
		})) if (box.style.getPropertyValue(property) !== value) box.style.setProperty(property, value);
	}
};
var InspectorRuntime = class {
	#registry;
	#state = new StateManager();
	#detector;
	#targetSelector;
	#visual;
	#panel;
	#pullTab = null;
	#eventMonitor;
	#timeline;
	#relationshipEngine;
	#lifetime = new AbortController();
	#refreshFrame = null;
	#readyFrame = null;
	#dynamicEventsQueued = false;
	#phase = "observing";
	#host;
	#config;
	#application;
	constructor(host, shadow, config, application) {
		this.#host = host;
		this.#config = config;
		this.#application = application;
		this.#registry = new PluginRegistry([
			new LiveComponentPlugin(),
			new TurboPlugin(),
			new StimulusPlugin(application())
		]);
		this.#eventMonitor = new EventMonitor(500, (draft) => {
			if (draft.target && this.#phase === "observing") this.#registry.notifyEvent(draft, draft.target);
			draft.owner = this.#findNearestComponent(draft.target);
		});
		const monitor = this.#eventMonitor;
		this.#registry.setEventRecorder((entry) => monitor.record(entry));
		this.#detector = new ComponentDetector(this.#registry, this.#state, host, this.#config.ignore_selectors || []);
		this.#visual = new Highlighter(shadow, this.#state, this.#registry);
		this.#targetSelector = new TargetSelector(this.#visual, this.#registry, (active) => this.#panel.setTargetModeActive(active));
		this.#relationshipEngine = new RelationshipEngine(this.#registry, this.#state);
		this.#timeline = new Timeline(this.#eventMonitor, {
			onHighlight: (element, framework) => element ? this.#visual.hover(element, framework) : this.#visual.clearHover(),
			onSelect: (element) => {
				const component = this.#findNearestComponent(element);
				if (component) this.#panel.drillInto(component);
			}
		});
		this.#eventMonitor.addListener((entry) => this.#onEvent(entry));
		this.#panel = new Panel(this.#state, this.#registry, this.#eventMonitor, this.#timeline, host, this.#relationshipEngine, this.#config.packages || {});
		this.#panel.setActionCallbacks({
			target: () => this.toggleTargetMode(),
			overlay: () => this.toggleOverlay(),
			pause: () => this.toggleLogPaused(),
			clearLog: () => this.clearLog()
		});
		this.#panel.setVisualCallbacks({
			onPreview: ({ element, framework, label }) => this.#visual.hover(element, framework, label),
			onClearPreview: () => this.#visual.clearHover(),
			onSelect: ({ element, framework, label }) => this.#visual.select(element, framework, label),
			onClearSelection: () => this.#visual.deselect()
		});
		shadow.append(this.#panel.element);
		this.#registerPluginStaticEvents();
		const timeline = this.#timeline;
		this.#eventMonitor.start((entry) => timeline.addEntry(entry));
		const { signal } = this.#lifetime;
		if (config.pull_tab !== false) {
			this.#pullTab = createPullTab(host, () => {
				host.open();
				this.#panel.element.querySelector("[aria-label=\"Hide inspector\"]")?.focus({ preventScroll: true });
			}, signal);
			shadow.append(this.#pullTab);
		}
		if (document.readyState === "loading") document.addEventListener("DOMContentLoaded", () => this.scan(), {
			once: true,
			signal
		});
		bindOpenShortcut(() => host.open(), signal);
		document.addEventListener("keydown", (event) => {
			if (event.key === "Escape" && this.#panel.drillBack()) event.preventDefault();
		}, { signal });
		window.addEventListener("scroll", () => this.refreshVisual(), {
			capture: true,
			passive: true,
			signal
		});
		const updateDynamicEvents = () => {
			if (this.#dynamicEventsQueued) return;
			this.#dynamicEventsQueued = true;
			queueMicrotask(() => {
				this.#dynamicEventsQueued = false;
				if (host.isConnected && this.#phase !== "destroyed") this.#registerPluginDynamicEvents();
			});
		};
		for (const type of [
			"component-added",
			"component-updated",
			"component-removed",
			"components-cleared"
		]) this.#state.addEventListener(type, updateDynamicEvents, { signal });
		this.scan();
		this.#detector.observe();
		this.#readyFrame = requestAnimationFrame(() => {
			this.#readyFrame = null;
			this.scan();
			host.setAttribute("ready", "");
		});
	}
	setStimulusApplication(application) {
		this.#registry.get("stimulus")?.setApplication(application);
		this.scan();
	}
	getStatus() {
		const components = this.#state.countByPlugin();
		return {
			installed: { ...this.#config.packages },
			used: {
				stimulus: Boolean(document.querySelector("[data-controller]")),
				livecomponent: Boolean(document.querySelector("[data-controller~=\"live\"]")),
				turbo: Boolean(globalThis.Turbo || customElements.get("turbo-frame") || document.querySelector("turbo-frame, turbo-stream-source"))
			},
			components
		};
	}
	close() {
		this.#targetSelector.disable();
		this.#visual.clearHover();
		this.#visual.deselect();
		if (this.#panel.element.contains(this.#host.shadowRoot?.activeElement ?? null)) this.#pullTab?.querySelector("button")?.focus({ preventScroll: true });
	}
	refreshVisual() {
		if (this.#refreshFrame !== null || this.#phase !== "observing") return;
		this.#refreshFrame = requestAnimationFrame(() => {
			this.#refreshFrame = null;
			this.#visual.refresh();
		});
	}
	scan() {
		if (this.#phase !== "observing") return;
		const application = this.#application();
		if (application) this.#registry.get("stimulus")?.setApplication(application);
		this.#detector.scan();
		this.#registerPluginDynamicEvents();
	}
	clear() {
		this.clearLog();
		this.#visual.clearAll();
		this.#panel.clearFocus();
		this.#panel.setOverlayActive(false);
		this.#panel.refresh();
	}
	clearLog() {
		this.#timeline.clear();
		this.#panel.clearActivities();
	}
	toggleLogPaused() {
		if (this.#timeline.paused) this.#timeline.resume();
		else this.#timeline.pause();
		return Boolean(this.#timeline.paused);
	}
	inspectElement(element) {
		if (!element || this.#phase !== "observing") return;
		const data = this.#detector.inspect(element);
		if (!data) return;
		this.#visual.select(element, data.keys().next().value);
		this.#panel.drillInto(element, data);
	}
	toggleTargetMode() {
		return this.#targetSelector.toggle((element) => this.inspectElement(element));
	}
	toggleOverlay() {
		const visible = Boolean(this.#visual.toggleAll());
		this.#panel.setOverlayActive(visible);
		return visible;
	}
	#onEvent(entry) {
		if (this.#phase !== "observing") return;
		if (entry.type === "livecomponent" && entry.target) this.#detector.refresh(entry.target);
		if (this.#host.isOpen && this.#panel.isComponentListVisible && entry.relatedElements?.length) for (const element of entry.relatedElements) this.#visual.pulse(element, entry.type, entry.label || entry.event);
		const component = this.#findNearestComponent(entry.target);
		if (!component) return;
		if (!this.#host.isOpen || !this.#panel.isComponentListVisible) return;
		const framework = this.#state.get(component)?.keys().next().value ?? entry.type;
		this.#visual.pulse(component, framework, entry.label || entry.event);
	}
	#findNearestComponent(element) {
		let current = element ?? null;
		while (current) {
			if (this.#state.get(current)) return current;
			current = current.parentElement;
		}
		return null;
	}
	#registerPluginStaticEvents() {
		const { staticEvents } = this.#registry.collectMonitoredEvents();
		for (const [pluginName, events] of staticEvents) this.#eventMonitor.monitorEvents(events, pluginName);
	}
	#registerPluginDynamicEvents() {
		const byPlugin = /* @__PURE__ */ new Map();
		for (const element of this.#state.elements) for (const pluginName of this.#state.get(element)?.keys() ?? []) {
			if (!byPlugin.has(pluginName)) byPlugin.set(pluginName, []);
			byPlugin.get(pluginName).push(element);
		}
		const { dynamicEvents } = this.#registry.collectMonitoredEvents(byPlugin);
		for (const [pluginName, events] of dynamicEvents) this.#eventMonitor.setDynamicEvents(events, pluginName);
	}
	suspend() {
		if (this.#phase !== "observing") return;
		this.#phase = "suspended";
		this.#pullTab?.removeAttribute("data-near");
		if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
		if (this.#readyFrame !== null) cancelAnimationFrame(this.#readyFrame);
		this.#refreshFrame = this.#readyFrame = null;
		this.#panel.suspendForNavigation();
		this.#targetSelector.disable();
		this.#visual.clearAll();
		this.#detector.disconnect();
	}
	resume() {
		if (this.#phase === "destroyed") return;
		if (this.#phase === "observing") {
			this.scan();
			return;
		}
		this.#phase = "observing";
		this.#state.clear();
		this.#relationshipEngine.invalidate();
		this.#detector.observe();
		this.scan();
		this.#panel.resumeAfterNavigation();
		this.#host.setAttribute("ready", "");
	}
	destroy() {
		if (this.#phase === "destroyed") return;
		this.#phase = "destroyed";
		this.#lifetime.abort();
		if (this.#refreshFrame !== null) cancelAnimationFrame(this.#refreshFrame);
		if (this.#readyFrame !== null) cancelAnimationFrame(this.#readyFrame);
		this.#panel.destroy();
		this.#timeline.destroy();
		this.#targetSelector.destroy();
		this.#visual.destroy();
		this.#eventMonitor.destroy();
		this.#relationshipEngine.destroy();
		this.#detector.destroy();
		this.#registry.destroy();
	}
};
const OPEN_ATTRIBUTE = "data-ux-inspector-open";
const WIDTH_PROPERTY = "--ux-inspector-width";
var DockLayout = class {
	#host;
	#style = null;
	#width = null;
	constructor(host) {
		this.#host = host;
	}
	resize(width) {
		this.#width = Math.round(Math.min(Math.max(208, width), Math.max(208, window.innerWidth * .8)));
		this.#host.style.setProperty("--panel-width", `${this.#width}px`);
		this.sync();
		return this.#width;
	}
	sync() {
		if (!this.#host.isConnected || !this.#host.hasAttribute("open")) {
			this.detach();
			return;
		}
		if (!this.#style) {
			this.#style = document.createElement("style");
			this.#style.dataset.uxInspectorLayout = "";
			this.#style.textContent = `@media (min-width:42.5rem){html[${OPEN_ATTRIBUTE}]{box-sizing:border-box!important;padding-right:var(${WIDTH_PROPERTY},21.25rem)!important}}`;
		}
		if (!this.#style.isConnected) document.head.append(this.#style);
		if (this.#width !== null) {
			this.#width = Math.min(this.#width, Math.max(208, window.innerWidth * .8));
			this.#host.style.setProperty("--panel-width", `${this.#width}px`);
			document.documentElement.style.setProperty(WIDTH_PROPERTY, `${this.#width}px`);
		}
		document.documentElement.setAttribute(OPEN_ATTRIBUTE, "");
	}
	detach() {
		document.documentElement.removeAttribute(OPEN_ATTRIBUTE);
		document.documentElement.style.removeProperty(WIDTH_PROPERTY);
		this.#style?.remove();
	}
};
let stylesheet;
let stimulusApplication = null;
function availableStimulusApplication() {
	const application = stimulusApplication ?? globalThis.Stimulus;
	return typeof application?.getControllerForElementAndIdentifier === "function" ? application : null;
}
var UXInspector = class extends HTMLElement {
	#runtime = null;
	#config = {};
	#layout = new DockLayout(this);
	#lifetime = new AbortController();
	#teardown = null;
	connectedCallback() {
		if (this.#teardown !== null) clearTimeout(this.#teardown);
		this.#teardown = null;
		if (this.#runtime) {
			this.#runtime.resume();
			this.#layout.sync();
			return;
		}
		this.#readConfig();
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
		this.#lifetime = new AbortController();
		const { signal } = this.#lifetime;
		this.#runtime = new InspectorRuntime(this, shadow, this.#config, availableStimulusApplication);
		document.addEventListener("turbo:before-cache", () => {
			this.#runtime?.suspend();
			this.#layout.detach();
		}, { signal });
		document.addEventListener("turbo:render", () => {
			if (!this.isConnected) return;
			this.#runtime?.resume();
			this.#layout.sync();
		}, { signal });
		window.addEventListener("resize", () => {
			this.#layout.sync();
			this.#runtime?.refreshVisual();
		}, {
			signal,
			passive: true
		});
		this.#layout.sync();
	}
	disconnectedCallback() {
		this.#layout.detach();
		this.#runtime?.suspend();
		this.#teardown = setTimeout(() => {
			this.#runtime?.destroy();
			this.#runtime = null;
			this.#lifetime.abort();
			this.#teardown = null;
		}, 1e3);
	}
	get isOpen() {
		return this.hasAttribute("open");
	}
	getStatus() {
		return this.#runtime?.getStatus() ?? {};
	}
	setStimulusApplication(application) {
		this.#runtime?.setStimulusApplication(application);
	}
	open() {
		this.scan();
		this.setAttribute("open", "");
		this.#layout.sync();
	}
	close() {
		this.removeAttribute("open");
		this.#layout.sync();
		this.#runtime?.close();
	}
	toggle() {
		if (this.isOpen) this.close();
		else this.open();
	}
	setPanelWidth(width) {
		const value = this.#layout.resize(width);
		this.#runtime?.refreshVisual();
		return value;
	}
	scan() {
		this.#runtime?.scan();
	}
	clear() {
		this.#runtime?.clear();
	}
	clearLog() {
		this.#runtime?.clearLog();
	}
	toggleLogPaused() {
		return this.#runtime?.toggleLogPaused() ?? false;
	}
	inspectElement(element) {
		this.#runtime?.inspectElement(element);
	}
	toggleTargetMode() {
		return this.#runtime?.toggleTargetMode() ?? false;
	}
	toggleOverlay() {
		return this.#runtime?.toggleOverlay() ?? false;
	}
	#readConfig() {
		const raw = this.getAttribute("data-config");
		if (!raw) return;
		try {
			this.#config = JSON.parse(raw);
		} catch (error) {
			console.warn("[ux-inspector] Invalid config JSON:", error.message);
		}
	}
};
function registerUXInspector(styles) {
	stylesheet = styles;
	if (!customElements.get("ux-inspector")) customElements.define("ux-inspector", UXInspector);
}
function connectStimulus(application) {
	const valid = Boolean(application?.getControllerForElementAndIdentifier);
	stimulusApplication = valid ? application : null;
	document.querySelector("ux-inspector")?.setStimulusApplication(stimulusApplication);
	return valid;
}
registerUXInspector({ text: inspector_default });
export { UXInspector, connectStimulus };
