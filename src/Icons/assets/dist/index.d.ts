interface UXIconData<Name extends string = string> {
  readonly name: Name;
  readonly attributes: Readonly<Record<string, string | boolean>>;
  readonly children: readonly UXIconNode[];
}
type UXIconNode = string | UXIconElement;
type UXIconElement = readonly [tag: string, attributes: Readonly<Record<string, string>>, children?: readonly UXIconNode[]];
type UXIconScalar = string | number | boolean | null | undefined;
type UXIconAttributeValue = UXIconScalar | readonly UXIconScalar[] | Readonly<Record<string, UXIconScalar>>;
type UXIconAttributes = Record<string, UXIconAttributeValue>;
declare function resolveIcon(icon: UXIconData, attributes?: UXIconAttributes): Record<string, string>;
declare function renderIcon(icon: UXIconData, attributes?: UXIconAttributes): string;
declare function createIconElement(icon: UXIconData, attributes?: UXIconAttributes): SVGSVGElement;
declare function mapNodes<T>(nodes: readonly UXIconNode[], create: (tag: string, attributes: Readonly<Record<string, string>>, children: (T | string)[]) => T): (T | string)[];
declare function splitAttributes(values: Record<string, unknown>): [UXIconAttributes, Record<string, unknown>];
export { UXIconAttributeValue, UXIconAttributes, UXIconData, UXIconElement, UXIconNode, createIconElement, mapNodes, renderIcon, resolveIcon, splitAttributes };