import { mapNodes, resolveIcon, splitAttributes } from "./index.js";
import { defineComponent, h } from "vue";
const UXIcon = defineComponent({
	name: "UXIcon",
	inheritAttrs: false,
	props: { icon: {
		type: Object,
		required: true
	} },
	setup(props, { attrs }) {
		return () => {
			const [attributes, passthrough] = splitAttributes(attrs);
			return h("svg", {
				...resolveIcon(props.icon, attributes),
				...passthrough
			}, mapNodes(props.icon.children, (tag, attributes, children) => h(tag, attributes, children)));
		};
	}
});
export { UXIcon };
