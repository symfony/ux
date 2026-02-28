import { Controller } from "@hotwired/stimulus";
import SwupDebugPlugin from "@swup/debug-plugin";
import SwupFadeTheme from "@swup/fade-theme";
import SwupFormsPlugin from "@swup/forms-plugin";
import SwupSlideTheme from "@swup/slide-theme";
import Swup from "swup";
var _Class = class extends Controller {
	connect() {
		const dataContainers = this.containersValue;
		const mainElement = this.mainElementValue || dataContainers[0] || "#swup";
		const allElements = [mainElement].concat(dataContainers);
		const options = {
			containers: allElements.filter((item, index) => {
				return allElements.indexOf(item) === index;
			}),
			plugins: ["slide" === this.themeValue ? new SwupSlideTheme({ mainElement }) : new SwupFadeTheme({ mainElement }), new SwupFormsPlugin()]
		};
		if (this.hasMainElementValue) options.mainElement = this.mainElementValue;
		if (this.hasAnimateHistoryBrowsingValue) options.animateHistoryBrowsing = this.animateHistoryBrowsingValue;
		if (this.hasAnimationSelectorValue) options.animationSelector = this.animationSelectorValue;
		if (this.hasCacheValue) options.cache = this.cacheValue;
		if (this.hasLinkSelectorValue) options.linkSelector = this.linkSelectorValue;
		if (this.debugValue) options.plugins.push(new SwupDebugPlugin());
		this.dispatchEvent("pre-connect", { options });
		const swup = new Swup(options);
		this.dispatchEvent("connect", {
			swup,
			options
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "swup"
		});
	}
};
_Class.values = {
	animateHistoryBrowsing: Boolean,
	animationSelector: String,
	cache: Boolean,
	containers: Array,
	linkSelector: String,
	theme: String,
	debug: Boolean,
	mainElement: String
};
export { _Class as default };