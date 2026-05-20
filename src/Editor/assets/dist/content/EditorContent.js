export var EditorContentFormat;
(function (EditorContentFormat) {
    EditorContentFormat["Html"] = "html";
    EditorContentFormat["Blocks"] = "blocks";
    EditorContentFormat["Page"] = "page";
})(EditorContentFormat || (EditorContentFormat = {}));
export class EditorContent {
    format;
    metadata;
    constructor(format, metadata = {}) {
        this.format = format;
        this.metadata = metadata;
    }
}
