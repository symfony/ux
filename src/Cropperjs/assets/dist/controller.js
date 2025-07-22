import { Controller } from "@hotwired/stimulus";
import Cropper from "cropperjs";

//#region src/controller.ts
var CropperController = class extends Controller {
	static values = {
		publicUrl: String,
		options: Object
	};
	connect() {
		const img = document.createElement("img");
		img.classList.add("cropperjs-image");
		img.src = this.publicUrlValue;
		const parent = this.element.parentNode;
		if (!parent) throw new Error("Missing parent node for Cropperjs");
		parent.appendChild(img);
		const options = this.optionsValue;
		this.dispatchEvent("pre-connect", {
			options,
			img
		});
		const cropper = new Cropper(img, options);
		img.addEventListener("crop", (event) => {
			this.element.value = JSON.stringify(event.detail);
		});
		this.dispatchEvent("connect", {
			cropper,
			options,
			img
		});
	}
	dispatchEvent(name, payload) {
		this.dispatch(name, {
			detail: payload,
			prefix: "cropperjs"
		});
	}
};

//#endregion
export { CropperController as default };