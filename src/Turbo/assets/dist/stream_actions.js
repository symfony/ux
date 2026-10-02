import { StreamActions, visit } from "@hotwired/turbo";
StreamActions.redirect = function() {
	const url = this.getAttribute("url");
	if (null === url) throw new Error("The \"url\" attribute is required on <turbo-stream action=\"redirect\">.");
	const target = new URL(url, document.baseURI);
	if ("http:" !== target.protocol && "https:" !== target.protocol) throw new Error(`The "url" attribute of <turbo-stream action="redirect"> must use the http or https scheme, "${target.protocol}" given.`);
	if (target.origin !== window.location.origin) {
		window.location.assign(target.href);
		return;
	}
	visit(target.href, { action: this.hasAttribute("advance") ? "advance" : "replace" });
};
