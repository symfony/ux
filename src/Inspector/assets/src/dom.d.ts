// Native DOM API not yet declared by the monorepo's TypeScript version, and not
// yet implemented by every engine: declared optional so a guard stays mandatory.
interface Element {
    moveBefore?(node: Node, child: Node | null): void;
}
