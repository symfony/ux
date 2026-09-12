// jsdom lacks these browser APIs.
// ResizeObserver is a no-op: nothing here asserts on resize notifications.
// moveBefore emulates the one observable guarantee the UI relies on, keeping
// focus across a reorder. The fallback taken when the API is absent is covered
// by "reorders retained children on engines without moveBefore".
globalThis.ResizeObserver = class {
    observe() {}
    unobserve() {}
    disconnect() {}
};

Element.prototype.moveBefore ??= function (node, child) {
    const root = this.getRootNode();
    const focused = root.activeElement;
    this.insertBefore(node, child);
    if (focused && node.contains(focused)) focused.focus({ preventScroll: true });
};
