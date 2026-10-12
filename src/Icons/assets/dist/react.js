import { mapNodes, resolveIcon, splitAttributes } from "./index.js";
import { createElement } from "react";
const REACT_PROP_NAMES = {
	class: "className",
	for: "htmlFor",
	tabindex: "tabIndex"
};
function UXIcon({ icon, ...props }) {
	const [attributes, passthrough] = splitAttributes(props);
	return createElement("svg", {
		...toReactProps(resolveIcon(icon, attributes)),
		...passthrough
	}, ...mapNodes(icon.children, (tag, attributes, children) => createElement(tag, toReactProps(attributes), ...children)));
}
function toReactProps(attributes) {
	const props = {};
	for (const [name, value] of Object.entries(attributes)) if (name === "style") props.style = parseStyle(value);
	else if (name.startsWith("aria-") || name.startsWith("data-")) props[name] = value;
	else props[REACT_PROP_NAMES[name] ?? camelize(name)] = value;
	return props;
}
function parseStyle(style) {
	const properties = {};
	for (const declaration of style.split(";")) {
		const index = declaration.indexOf(":");
		if (index === -1) continue;
		const property = declaration.slice(0, index).trim();
		properties[property.startsWith("--") ? property : camelize(property)] = declaration.slice(index + 1).trim();
	}
	return properties;
}
function camelize(name) {
	return name.replace(/[-:]([a-z])/g, (_, letter) => letter.toUpperCase());
}
export { UXIcon };
