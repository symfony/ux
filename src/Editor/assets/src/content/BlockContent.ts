import { EditorContent, EditorContentFormat } from './EditorContent.js';

export interface Block {
  type: string;
  data: Record<string, unknown>;
  id?: string;
}

export class BlockContent extends EditorContent<Block[]> {
  constructor(
    public readonly blocks: Block[],
    public readonly schemaVersion: string = '1.0',
    metadata: Record<string, unknown> = {},
  ) {
    super(EditorContentFormat.Blocks, metadata);
  }

  getRaw(): Block[] { return this.blocks; }

  isEmpty(): boolean { return this.blocks.length === 0; }

  static from(payload: { version?: string; blocks?: Block[] }, metadata: Record<string, unknown> = {}): BlockContent {
    return new BlockContent(payload.blocks ?? [], payload.version ?? '1.0', metadata);
  }
}
