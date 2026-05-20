import { EditorContent } from './EditorContent.js';
export interface Block {
    type: string;
    data: Record<string, unknown>;
    id?: string;
}
export declare class BlockContent extends EditorContent<Block[]> {
    readonly blocks: Block[];
    readonly schemaVersion: string;
    constructor(blocks: Block[], schemaVersion?: string, metadata?: Record<string, unknown>);
    getRaw(): Block[];
    isEmpty(): boolean;
    static from(payload: {
        version?: string;
        blocks?: Block[];
    }, metadata?: Record<string, unknown>): BlockContent;
}
