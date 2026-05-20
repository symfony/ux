import { AbstractEditorController } from '../controller.js';
export class AbstractBlockController extends AbstractEditorController {
    async serialize(instance) { return await instance.save(); }
}
