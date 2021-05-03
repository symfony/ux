'use strict';

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _stimulus = require("stimulus");

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

var _default = /*#__PURE__*/function (_Controller) {
  _inherits(_default, _Controller);

  var _super = _createSuper(_default);

  function _default() {
    var _this;

    _classCallCheck(this, _default);

    for (var _len = arguments.length, args = new Array(_len), _key = 0; _key < _len; _key++) {
      args[_key] = arguments[_key];
    }

    _this = _super.call.apply(_super, [this].concat(args));

    _defineProperty(_assertThisInitialized(_this), "index", 0);

    _defineProperty(_assertThisInitialized(_this), "controllerName", null);

    return _this;
  }

  _createClass(_default, [{
    key: "connect",
    value: function connect() {
      this.controllerName = this.context.scope.identifier;

      this._dispatchEvent('form-collection:pre-connect', {
        allowAdd: this.allowAddValue,
        allowDelete: this.allowDeleteValue
      });

      if (true === this.allowAddValue) {
        // Add button Add
        var buttonAdd = this._textToNode(this.buttonAddValue);

        this.containerTarget.prepend(buttonAdd);
      } // Add buttons Delete


      if (true === this.allowDeleteValue) {
        for (var i = 0; i < this.entryTargets.length; i++) {
          this.index = i;
          var entry = this.entryTargets[i];

          this._addDeleteButton(entry, this.index);
        }
      }

      this._dispatchEvent('form-collection:connect', {
        allowAdd: this.allowAddValue,
        allowDelete: this.allowDeleteValue
      });
    }
  }, {
    key: "add",
    value: function add(event) {
      this.index++; // Compute the new entry

      var newEntry = this.containerTarget.dataset.prototype;
      newEntry = newEntry.replace(/__name__label__/g, this.index);
      newEntry = newEntry.replace(/__name__/g, this.index);
      newEntry = this._textToNode(newEntry);

      this._dispatchEvent('form-collection:pre-add', {
        index: this.index,
        element: newEntry
      });

      this.containerTarget.append(newEntry); // Retrieve the entry from targets to make sure that this is the one

      var entry = this.entryTargets[this.entryTargets.length - 1];
      entry = this._addDeleteButton(entry, this.index);

      this._dispatchEvent('form-collection:add', {
        index: this.index,
        element: entry
      });
    }
  }, {
    key: "delete",
    value: function _delete(event) {
      var theIndexEntryToDelete = event.target.dataset.indexEntry; // Search the entry to delete from the data-index-entry attribute

      for (var i = 0; i < this.entryTargets.length; i++) {
        var entry = this.entryTargets[i];

        if (theIndexEntryToDelete === entry.dataset.indexEntry) {
          this._dispatchEvent('form-collection:pre-delete', {
            index: entry.dataset.indexEntry,
            element: entry
          });

          entry.remove();

          this._dispatchEvent('form-collection:delete', {
            index: entry.dataset.indexEntry,
            element: entry
          });
        }
      }
    }
    /**
     * Add the delete button to the entry
     * @param String entry
     * @param Number index
     * @returns {ChildNode}
     * @private
     */

  }, {
    key: "_addDeleteButton",
    value: function _addDeleteButton(entry, index) {
      // link the button and the entry by the data-index-entry attribute
      entry.dataset.indexEntry = index;

      var buttonDelete = this._textToNode(this.buttonDeleteValue);

      buttonDelete.dataset.indexEntry = index;

      if ('TR' === entry.nodeName) {
        entry.lastElementChild.append(buttonDelete);
      } else {
        entry.append(buttonDelete);
      }

      return entry;
    }
    /**
     * Convert text to Element to insert in the DOM
     * @param String text
     * @returns {ChildNode}
     * @private
     */

  }, {
    key: "_textToNode",
    value: function _textToNode(text) {
      var template = document.createElement('template');
      text = text.trim(); // Never return a text node of whitespace as the result

      template.innerHTML = text;
      return template.content.firstChild;
    }
  }, {
    key: "_dispatchEvent",
    value: function _dispatchEvent(name) {
      var payload = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      var canBubble = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : false;
      var cancelable = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      var userEvent = document.createEvent('CustomEvent');
      userEvent.initCustomEvent(name, canBubble, cancelable, payload);
      this.element.dispatchEvent(userEvent);
    }
  }]);

  return _default;
}(_stimulus.Controller);

exports["default"] = _default;

_defineProperty(_default, "targets", ['container', 'entry']);

_defineProperty(_default, "values", {
  allowAdd: Boolean,
  allowDelete: Boolean,
  buttonAdd: String,
  buttonDelete: String
});