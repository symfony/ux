import { AbstractEditorController } from '../controller.js';

export interface BlockInstance {
  save(): Promise<{ version?: string; blocks: Array<{ type: string; data: Record<string, unknown> }> }>;
  destroy?(): void;
}

export abstract class AbstractBlockController extends AbstractEditorController<BlockInstance> {
  async serialize(instance: BlockInstance): Promise<object> { return await instance.save(); }
}
