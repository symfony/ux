export enum EditorContentFormat {
  Html = 'html',
  Blocks = 'blocks',
  Page = 'page',
}

export abstract class EditorContent<T = unknown> {
  constructor(public readonly format: EditorContentFormat, public readonly metadata: Record<string, unknown> = {}) {}

  abstract getRaw(): T;
  abstract isEmpty(): boolean;
}
