import { Controller } from "@hotwired/stimulus";
var _Class = class extends Controller {
	constructor(..._args) {
		super(..._args);
		this.inFlight = false;
		this.cachedValue = null;
		this.revealed = false;
		this.originalButtonHtml = null;
		this.originalValueHtml = null;
	}
	connect() {
		if (this.hasButtonTarget) {
			this.originalButtonHtml = this.buttonTarget.innerHTML;
			this.resetTrigger();
		}
		if (this.hasValueTarget) this.originalValueHtml = this.valueTarget.innerHTML;
		this.clearError();
	}
	resetTrigger() {
		if (this.hasButtonTarget) {
			this.buttonTarget.disabled = false;
			this.buttonTarget.setAttribute("aria-busy", "false");
			if (this.toggleValue) {
				this.buttonTarget.removeAttribute("data-disclose-revealed");
				this.buttonTarget.setAttribute("aria-label", this.revealLabelValue);
			}
			this.buttonTarget.innerHTML = this.originalButtonHtml ?? this.maskValue;
		}
		this.revealed = false;
	}
	async reveal() {
		if (this.inFlight) return;
		if (this.cachedValue !== null) {
			this.displayValue(this.cachedValue);
			this.dispatch("content-loaded", { detail: { value: this.cachedValue } });
			return;
		}
		this.inFlight = true;
		this.dispatch("start");
		if (this.hasButtonTarget) {
			this.buttonTarget.disabled = true;
			this.buttonTarget.setAttribute("aria-busy", "true");
			if (!this.toggleValue) this.buttonTarget.textContent = this.loadingLabelValue;
		}
		this.clearError();
		try {
			const response = await fetch(this.urlValue, { headers: { Accept: "application/json" } });
			const data = await response.json().catch(() => ({}));
			if (response.status === 429) {
				this.showError(this.rateLimitedLabelValue);
				this.dispatch("rate-limited", { detail: data });
				return;
			}
			if (!response.ok) {
				this.showError(this.errorLabelValue);
				this.dispatch("error", { detail: data });
				return;
			}
			const value = this.renderHtmlValue ? String(data.html ?? data.value ?? "") : String(data.value ?? "");
			this.cachedValue = value;
			this.displayValue(value);
			this.dispatch("content-loaded", { detail: { value } });
		} catch (error) {
			this.showError(this.errorLabelValue);
			this.dispatch("error", { detail: { error: String(error) } });
		} finally {
			this.inFlight = false;
			if (this.hasButtonTarget) {
				if (this.toggleValue ? !this.revealed : !this.buttonTarget.hidden) this.resetTrigger();
			}
		}
	}
	hide() {
		if (this.hasValueTarget) this.valueTarget.innerHTML = this.originalValueHtml ?? "";
		if (this.hasContentTarget) this.contentTarget.hidden = true;
		if (this.hasHideButtonTarget) this.hideButtonTarget.hidden = true;
		if (this.hasButtonTarget) {
			if (!this.toggleValue) this.buttonTarget.hidden = false;
			this.resetTrigger();
		}
		this.clearError();
		this.dispatch("hidden");
	}
	toggle() {
		if (this.revealed) this.hide();
		else this.reveal();
	}
	displayValue(value) {
		if (this.hasValueTarget) if (this.renderHtmlValue) this.valueTarget.innerHTML = value;
		else this.valueTarget.textContent = value;
		if (this.hasContentTarget) this.contentTarget.hidden = false;
		if (this.hasHideButtonTarget && !this.toggleValue) this.hideButtonTarget.hidden = false;
		if (this.hasButtonTarget) {
			this.buttonTarget.disabled = false;
			this.buttonTarget.setAttribute("aria-busy", "false");
			if (this.toggleValue) {
				this.buttonTarget.setAttribute("data-disclose-revealed", "true");
				this.buttonTarget.setAttribute("aria-label", this.hideLabelValue);
				this.revealed = true;
			} else this.buttonTarget.hidden = true;
		}
		this.clearError();
	}
	clearError() {
		if (this.hasErrorTarget) {
			this.errorTarget.hidden = true;
			this.errorTarget.textContent = "";
		}
	}
	showError(message) {
		if (this.hasErrorTarget) {
			this.errorTarget.textContent = message;
			this.errorTarget.hidden = false;
		}
	}
};
_Class.targets = [
	"button",
	"content",
	"value",
	"hideButton",
	"error"
];
_Class.values = {
	url: String,
	mask: {
		type: String,
		default: "••••••"
	},
	renderHtml: {
		type: Boolean,
		default: false
	},
	toggle: {
		type: Boolean,
		default: false
	},
	revealLabel: {
		type: String,
		default: "Reveal"
	},
	hideLabel: {
		type: String,
		default: "Hide"
	},
	loadingLabel: {
		type: String,
		default: "Loading"
	},
	errorLabel: {
		type: String,
		default: "Unable to disclose."
	},
	rateLimitedLabel: {
		type: String,
		default: "Rate limit exceeded. Try again later."
	}
};
export { _Class as default };
