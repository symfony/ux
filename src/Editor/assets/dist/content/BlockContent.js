import { EditorContent, EditorContentFormat } from './EditorContent.js';
export class BlockContent extends EditorContent {
    blocks;
    schemaVersion;
    constructor(blocks, schemaVersion = '1.0', metadata = {}) {
        super(EditorContentFormat.Blocks, metadata);
        this.blocks = blocks;
        this.schemaVersion = schemaVersion;
    }
    getRaw() { return this.blocks; }
    isEmpty() { return this.blocks.length === 0; }
    static from(payload, metadata = {}) {
        return new BlockContent(payload.blocks ?? [], payload.version ?? '1.0', metadata);
    }
}
