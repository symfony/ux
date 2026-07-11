import { AbstractEditorController } from "../controller.js";
interface BlockInstance {
  save(): Promise<{
    version?: string;
    blocks: Array<{
      type: string;
      data: Record<string, unknown>;
    }>;
  }>;
  destroy?(): void;
}
declare abstract class AbstractBlockController extends AbstractEditorController<BlockInstance> {
  serialize(instance: BlockInstance): Promise<object>;
}
export { AbstractBlockController, BlockInstance };