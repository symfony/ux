import { AbstractEditorController } from '../controller.js';
export interface BlockInstance {
    save(): Promise<{
        version?: string;
        blocks: Array<{
            type: string;
            data: Record<string, unknown>;
        }>;
    }>;
    destroy?(): void;
}
export declare abstract class AbstractBlockController extends AbstractEditorController<BlockInstance> {
    serialize(instance: BlockInstance): Promise<object>;
}
