function resolveIcon(icon, attributes = {}) {
	const resolved = {};
	for (const [name, value] of Object.entries(icon.attributes)) if (value !== false) resolved[name] = value === true ? name.startsWith("aria-") ? "true" : "" : value;
	for (const [name, value] of Object.entries(attributes)) {
		if (value === void 0) continue;
		const htmlValue = htmlAttrValue(name, value);
		if (htmlValue === null) delete resolved[name];
		else resolved[name] = htmlValue;
	}
	if (![
		"aria-hidden",
		"aria-label",
		"aria-labelledby",
		"title"
	].some((name) => name in resolved)) resolved["aria-hidden"] = "true";
	return resolved;
}
function htmlAttrValue(name, value) {
	if (name.startsWith("aria-") && typeof value === "boolean") return String(value);
	if (name.startsWith("data-")) {
		if (value !== null && typeof value === "object") return phpJsonEncode(value);
		if (value === true) return "true";
	}
	if (value !== null && typeof value === "object") return name === "style" ? inlineStyle(value) : separatedTokenList(Object.values(value));
	if (value === true) return "";
	return value === null || value === false ? null : String(value);
}
function separatedTokenList(tokens) {
	const filtered = tokens.filter((token) => token !== null && token !== void 0 && token !== false);
	if (filtered.length === 0) return null;
	return filtered.filter((token) => token !== true).join(" ").trim();
}
function inlineStyle(declarations) {
	let style = "";
	for (const [name, value] of Object.entries(declarations)) {
		if (value === null || value === void 0 || typeof value === "boolean" || value === "") continue;
		style += /^\d+$/.test(name) ? `${trimChars(String(value), "; ")}; ` : `${name}: ${value}; `;
	}
	return style.trim() || null;
}
function phpJsonEncode(value) {
	return JSON.stringify(value).replaceAll("/", "\\/").replace(/[\u0080-\uffff]/g, (char) => `\\u${char.charCodeAt(0).toString(16).padStart(4, "0")}`);
}
function trimChars(value, characters) {
	let start = 0;
	let end = value.length;
	while (start < end && characters.includes(value[start])) start++;
	while (end > start && characters.includes(value[end - 1])) end--;
	return value.slice(start, end);
}
function renderIcon(icon, attributes = {}) {
	let html = "<svg";
	for (const [name, value] of Object.entries(resolveIcon(icon, attributes))) html += ` ${name}="${escapeRootAttribute(value)}"`;
	return `${html}>${icon.children.map(serializeNode).join("")}</svg>`;
}
function createIconElement(icon, attributes = {}) {
	return createElement([
		"svg",
		resolveIcon(icon, attributes),
		icon.children
	]);
}
function mapNodes(nodes, create) {
	return nodes.map((node) => typeof node === "string" ? node : create(node[0], node[1], mapNodes(node[2] ?? [], create)));
}
const SVG_NAMESPACE = "http://www.w3.org/2000/svg";
const ATTRIBUTE_NAMESPACES = {
	xlink: "http://www.w3.org/1999/xlink",
	xml: "http://www.w3.org/XML/1998/namespace",
	xmlns: "http://www.w3.org/2000/xmlns/"
};
function createElement([tag, attributes, children = []]) {
	const element = document.createElementNS(SVG_NAMESPACE, tag);
	for (const [name, value] of Object.entries(attributes)) {
		const namespace = ATTRIBUTE_NAMESPACES[name.split(":")[0]];
		if (namespace && (name === "xmlns" || name.includes(":"))) element.setAttributeNS(namespace, name, value);
		else element.setAttribute(name, value);
	}
	element.append(...children.map((child) => typeof child === "string" ? document.createTextNode(child) : createElement(child)));
	return element;
}
function serializeNode(node) {
	if (typeof node === "string") return escapeHtml(node, true);
	const [tag, attributes, children = []] = node;
	let html = `<${tag}`;
	for (const [name, value] of Object.entries(attributes)) html += ` ${name}="${escapeHtml(value)}"`;
	return `${html}>${children.map(serializeNode).join("")}</${tag}>`;
}
function escapeRootAttribute(value) {
	return value.replaceAll("&", "&amp;").replaceAll("\"", "&quot;").replaceAll("'", "&#039;").replaceAll("<", "&lt;").replaceAll(">", "&gt;");
}
function escapeHtml(value, text = false) {
	const escaped = value.replaceAll("&", "&amp;").replaceAll("\xA0", "&nbsp;");
	return text ? escaped.replaceAll("<", "&lt;").replaceAll(">", "&gt;") : escaped.replaceAll("\"", "&quot;");
}
function splitAttributes(values) {
	const attributes = {};
	const passthrough = {};
	for (const [name, value] of Object.entries(values)) if (value === null || [
		"string",
		"number",
		"boolean",
		"undefined"
	].includes(typeof value)) attributes[name] = value;
	else passthrough[name] = value;
	return [attributes, passthrough];
}
export { createIconElement, mapNodes, renderIcon, resolveIcon, splitAttributes };
