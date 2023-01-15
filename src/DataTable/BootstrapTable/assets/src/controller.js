'use strict';
Object.defineProperty(exports, "__esModule", { value: true });
const stimulus_1 = require("@hotwired/stimulus");
class default_1 extends stimulus_1.Controller {
    connect() {
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element');
        }
        const payload = this.viewValue;
        if (Array.isArray(payload.options) && 0 === payload.options.length) {
            payload.options = {};
        }
        this._dispatchEvent('bootstrap-table:pre-connect', { options: payload.options });
        this.bootstrapTable(Object.assign({ columns: payload.columns, data: payload.data }, payload.options));
        this._dispatchEvent('bootstrap-table:post-connect', { options: payload.options });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
exports.default = default_1;
//# sourceMappingURL=controller.js.map