let EditorContentFormat = /* @__PURE__ */ function(EditorContentFormat) {
	EditorContentFormat["Html"] = "html";
	EditorContentFormat["Blocks"] = "blocks";
	EditorContentFormat["Page"] = "page";
	return EditorContentFormat;
}({});
var EditorContent = class {
	constructor(format, metadata = {}) {
		this.format = format;
		this.metadata = metadata;
	}
};
export { EditorContent, EditorContentFormat };
