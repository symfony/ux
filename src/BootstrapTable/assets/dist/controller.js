'use strict';
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
const stimulus_1 = require("@hotwired/stimulus");
require("bootstrap-table");
const jquery_1 = __importDefault(require("jquery"));
class default_1 extends stimulus_1.Controller {
    connect() {
        if (!(this.element instanceof HTMLTableElement)) {
            throw new Error('invalid Element');
        }
        this._dispatchEvent('bootstrap-table:pre-connect', { data: this.rowsValue, columns: this.columnsValue, options: this.optionsValue });
        (0, jquery_1.default)(this.element).bootstrapTable(Object.assign({ columns: this.columnsValue, data: this.rowsValue }, this.optionsValue));
        this._dispatchEvent('bootstrap-table:post-connect', { data: this.rowsValue, columns: this.columnsValue, options: this.optionsValue });
    }
    _dispatchEvent(name, payload) {
        this.element.dispatchEvent(new CustomEvent(name, { detail: payload }));
    }
}
exports.default = default_1;
default_1.values = {
    rows: Array,
    columns: Array,
    options: { string: Option }
};
//# sourceMappingURL=controller.js.map