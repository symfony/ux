"use strict";

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;

var _stimulus = require("stimulus");

var _morphdom = _interopRequireDefault(require("morphdom"));

var _directives_parser = require("./directives_parser");

var _string_utils = require("./string_utils");

var _http_data_helper = require("./http_data_helper");

var _set_deep_data = require("./set_deep_data");

require("./polyfills");

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

function _slicedToArray(arr, i) { return _arrayWithHoles(arr) || _iterableToArrayLimit(arr, i) || _unsupportedIterableToArray(arr, i) || _nonIterableRest(); }

function _nonIterableRest() { throw new TypeError("Invalid attempt to destructure non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _iterableToArrayLimit(arr, i) { var _i = arr && (typeof Symbol !== "undefined" && arr[Symbol.iterator] || arr["@@iterator"]); if (_i == null) return; var _arr = []; var _n = true; var _d = false; var _s, _e; try { for (_i = _i.call(arr); !(_n = (_s = _i.next()).done); _n = true) { _arr.push(_s.value); if (i && _arr.length === i) break; } } catch (err) { _d = true; _e = err; } finally { try { if (!_n && _i["return"] != null) _i["return"](); } finally { if (_d) throw _e; } } return _arr; }

function _arrayWithHoles(arr) { if (Array.isArray(arr)) return arr; }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

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

var DEFAULT_DEBOUNCE = '150';

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

    _defineProperty(_assertThisInitialized(_this), "renderDebounceTimeout", null);

    _defineProperty(_assertThisInitialized(_this), "actionDebounceTimeout", null);

    _defineProperty(_assertThisInitialized(_this), "renderPromiseStack", new PromiseStack());

    _defineProperty(_assertThisInitialized(_this), "pollingIntervals", []);

    _defineProperty(_assertThisInitialized(_this), "isWindowUnloaded", false);

    _defineProperty(_assertThisInitialized(_this), "markAsWindowUnloaded", function () {
      _this.isWindowUnloaded = true;
    });

    return _this;
  }

  _createClass(_default, [{
    key: "initialize",
    value: function initialize() {
      this.markAsWindowUnloaded = this.markAsWindowUnloaded.bind(this);
    }
  }, {
    key: "connect",
    value: function connect() {
      // hide "loading" elements to begin with
      // This is done with CSS, but only for the most basic cases
      // TODO: document that the user should do this manually in other cases
      this._onLoadingFinish();

      if (this.element.dataset.poll !== undefined) {
        this._initiatePolling(this.element.dataset.poll);
      }

      window.addEventListener('beforeunload', this.markAsWindowUnloaded);

      this._dispatchEvent('live:connect');
    }
  }, {
    key: "disconnect",
    value: function disconnect() {
      this.pollingIntervals.forEach(function (interval) {
        clearInterval(interval);
      });
      window.removeEventListener('beforeunload', this.markAsWindowUnloaded);
    }
    /**
     * Called to update one piece of the model
     */

  }, {
    key: "update",
    value: function update(event) {
      var value = event.target.value; // todo - handle modifiers like "defer"

      this._updateModelFromElement(event.target, value, true);
    }
  }, {
    key: "updateDefer",
    value: function updateDefer(event) {
      var value = event.target.value; // todo - handle modifiers like "defer"

      this._updateModelFromElement(event.target, value, false);
    }
  }, {
    key: "action",
    value: function action(event) {
      var _this2 = this;

      // TODO - add validation for this in case it's missing
      // using currentTarget means that the data-action and data-action-name
      // must live on the same element: you can't add
      // data-action="click->live#action" on a parent element and
      // expect it to use the data-action-name from the child element
      // that actually received the click
      var rawAction = event.currentTarget.dataset.actionName; // data-action-name="prevent|debounce(1000)|save"

      var directives = (0, _directives_parser.parseDirectives)(rawAction);
      directives.forEach(function (directive) {
        // set here so it can be delayed with debouncing below
        var _executeAction = function _executeAction() {
          // if any normal renders are waiting to start, cancel them
          // allow the action to start and finish
          // this covers a case where you "blur" a field to click "save"
          // the "change" event will trigger first & schedule a re-render
          // then the action Ajax will start. We want to avoid the
          // re-render request from starting after the debounce and
          // taking precedence
          _this2._clearWaitingDebouncedRenders();

          _this2._makeRequest(directive.action);
        };

        var handled = false;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'prevent':
              event.preventDefault();
              break;

            case 'stop':
              event.stopPropagation();
              break;

            case 'self':
              if (event.target !== event.currentTarget) {
                return;
              }

              break;

            case 'debounce':
              var length = modifier.value ? modifier.value : DEFAULT_DEBOUNCE; // clear any pending renders

              if (_this2.actionDebounceTimeout) {
                clearTimeout(_this2.actionDebounceTimeout);
                _this2.actionDebounceTimeout = null;
              }

              _this2.actionDebounceTimeout = setTimeout(function () {
                _this2.actionDebounceTimeout = null;

                _executeAction();
              }, length);
              handled = true;
              break;

            default:
              console.warn("Unknown modifier ".concat(modifier.name, " in action ").concat(rawAction));
          }
        });

        if (!handled) {
          _executeAction();
        }
      });
    }
  }, {
    key: "$render",
    value: function $render() {
      this._makeRequest(null);
    }
  }, {
    key: "_updateModelFromElement",
    value: function _updateModelFromElement(element, value, shouldRender) {
      var model = element.dataset.model || element.getAttribute('name');

      if (!model) {
        var clonedElement = element.cloneNode();
        clonedElement.innerHTML = '';
        throw new Error("The update() method could not be called for \"".concat(clonedElement.outerHTML, "\": the element must either have a \"data-model\" or \"name\" attribute set to the model name."));
      }

      this.$updateModel(model, value, element, shouldRender);
    }
  }, {
    key: "$updateModel",
    value: function $updateModel(model, value, element) {
      var _this3 = this;

      var shouldRender = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : true;
      var directives = (0, _directives_parser.parseDirectives)(model);

      if (directives.length > 1) {
        throw new Error("The data-model=\"".concat(model, "\" format is invalid: it does not support multiple directives (i.e. remove any spaces)."));
      }

      var directive = directives[0];

      if (directive.args.length > 0 || directive.named.length > 0) {
        throw new Error("The data-model=\"".concat(model, "\" format is invalid: it does not support passing arguments to the model."));
      }

      var modelName = (0, _set_deep_data.normalizeModelName)(directive.action); // if there is a "validatedFields" data, it means this component wants
      // to track which fields have been / should be validated.
      // in that case, when the model is updated, mark that it should be validated

      if (this.dataValue.validatedFields !== undefined) {
        var validatedFields = _toConsumableArray(this.dataValue.validatedFields);

        if (validatedFields.indexOf(modelName) === -1) {
          validatedFields.push(modelName);
        }

        this.dataValue = (0, _set_deep_data.setDeepData)(this.dataValue, 'validatedFields', validatedFields);
      }

      if (!(0, _set_deep_data.doesDeepPropertyExist)(this.dataValue, modelName)) {
        console.warn("Model \"".concat(modelName, "\" is not a valid data-model value"));
      } // we do not send old and new data to the server
      // we merge in the new data now
      // TODO: handle edge case for top-level of a model with "exposed" props
      // For example, suppose there is a "post" field but "post.title" is exposed.
      // If there is a data-model="post", then the "post" data - which was
      // previously an array with "id" and "title" fields - will now be set
      // directly to the new post id (e.g. 4). From a saving standpoint,
      // that is fine: the server sees the "4" and uses it for the post data.
      // However, there is an edge case where the user changes data-model="post"
      // and then, for some reason, they don't want an immediate re-render.
      // Then, then modify the data-model="post.title" field. In theory,
      // we should be smart enough to convert the post data - which is now
      // the string "4" - back into an array with [id=4, title=new_title].


      this.dataValue = (0, _set_deep_data.setDeepData)(this.dataValue, modelName, value);
      directive.modifiers.forEach(function (modifier) {
        switch (modifier.name) {
          // there are currently no data-model modifiers
          default:
            throw new Error("Unknown modifier ".concat(modifier.name, " used in data-model=\"").concat(model, "\""));
        }
      });

      if (shouldRender) {
        // clear any pending renders
        this._clearWaitingDebouncedRenders(); // todo - make timeout configurable with a value


        this.renderDebounceTimeout = setTimeout(function () {
          _this3.renderDebounceTimeout = null;

          _this3.$render();
        }, this.debounceValue || DEFAULT_DEBOUNCE);
      }
    }
  }, {
    key: "_makeRequest",
    value: function _makeRequest(action) {
      var _this4 = this;

      var _this$urlValue$split = this.urlValue.split('?'),
          _this$urlValue$split2 = _slicedToArray(_this$urlValue$split, 2),
          url = _this$urlValue$split2[0],
          queryString = _this$urlValue$split2[1];

      var params = new URLSearchParams(queryString || '');
      var fetchOptions = {
        headers: {
          'Accept': 'application/vnd.live-component+json'
        }
      };

      if (action) {
        url += "/".concat(encodeURIComponent(action));

        if (this.csrfValue) {
          fetchOptions.headers['X-CSRF-TOKEN'] = this.csrfValue;
        }
      }

      if (!action && this._willDataFitInUrl()) {
        (0, _http_data_helper.buildSearchParams)(params, this.dataValue);
        fetchOptions.method = 'GET';
      } else {
        fetchOptions.method = 'POST';
        fetchOptions.body = (0, _http_data_helper.buildFormData)(this.dataValue);
      } // todo: make this work for specific actions, or models


      this._onLoadingStart();

      var paramsString = params.toString();
      var thisPromise = fetch("".concat(url).concat(paramsString.length > 0 ? "?".concat(paramsString) : ''), fetchOptions);
      this.renderPromiseStack.addPromise(thisPromise);
      thisPromise.then(function (response) {
        // if another re-render is scheduled, do not "run it over"
        // todo: think if this should behave differently for actions
        if (_this4.renderDebounceTimeout) {
          return;
        }

        var isMostRecent = _this4.renderPromiseStack.removePromise(thisPromise);

        if (isMostRecent) {
          response.json().then(function (data) {
            _this4._processRerender(data);
          });
        }
      });
    }
    /**
     * Processes the response from an AJAX call and uses it to re-render.
     *
     * @todo Make this truly private
     *
     * @private
     */

  }, {
    key: "_processRerender",
    value: function _processRerender(data) {
      // check if the page is navigating away
      if (this.isWindowUnloaded) {
        return;
      }

      if (data.redirect_url) {
        // action returned a redirect
        if (typeof Turbo !== 'undefined') {
          Turbo.visit(data.redirect_url);
        } else {
          window.location = data.redirect_url;
        }

        return;
      }

      if (!this._dispatchEvent('live:render', data, true, true)) {
        // preventDefault() was called
        return;
      } // remove the loading behavior now so that when we morphdom
      // "diffs" the elements, any loading differences will not cause
      // elements to appear different unnecessarily


      this._onLoadingFinish(); // merge/patch in the new HTML


      this._executeMorphdom(data.html); // "data" holds the new, updated data


      this.dataValue = data.data;
    }
  }, {
    key: "_clearWaitingDebouncedRenders",
    value: function _clearWaitingDebouncedRenders() {
      if (this.renderDebounceTimeout) {
        clearTimeout(this.renderDebounceTimeout);
        this.renderDebounceTimeout = null;
      }
    }
  }, {
    key: "_onLoadingStart",
    value: function _onLoadingStart() {
      this._handleLoadingToggle(true);
    }
  }, {
    key: "_onLoadingFinish",
    value: function _onLoadingFinish() {
      this._handleLoadingToggle(false);
    }
  }, {
    key: "_handleLoadingToggle",
    value: function _handleLoadingToggle(isLoading) {
      var _this5 = this;

      this._getLoadingDirectives().forEach(function (_ref) {
        var element = _ref.element,
            directives = _ref.directives;

        // so we can track, at any point, if an element is in a "loading" state
        if (isLoading) {
          _this5._addAttributes(element, ['data-live-is-loading']);
        } else {
          _this5._removeAttributes(element, ['data-live-is-loading']);
        }

        directives.forEach(function (directive) {
          _this5._handleLoadingDirective(element, isLoading, directive);
        });
      });
    }
    /**
     * @param {Element} element
     * @param {boolean} isLoading
     * @param {Directive} directive
     * @private
     */

  }, {
    key: "_handleLoadingDirective",
    value: function _handleLoadingDirective(element, isLoading, directive) {
      var _this6 = this;

      var finalAction = parseLoadingAction(directive.action, isLoading);
      var loadingDirective = null;

      switch (finalAction) {
        case 'show':
          // todo error on args - e.g. show(foo)
          loadingDirective = function loadingDirective() {
            _this6._showElement(element);
          };

          break;

        case 'hide':
          // todo error on args
          loadingDirective = function loadingDirective() {
            return _this6._hideElement(element);
          };

          break;

        case 'addClass':
          loadingDirective = function loadingDirective() {
            return _this6._addClass(element, directive.args);
          };

          break;

        case 'removeClass':
          loadingDirective = function loadingDirective() {
            return _this6._removeClass(element, directive.args);
          };

          break;

        case 'addAttribute':
          loadingDirective = function loadingDirective() {
            return _this6._addAttributes(element, directive.args);
          };

          break;

        case 'removeAttribute':
          loadingDirective = function loadingDirective() {
            return _this6._removeAttributes(element, directive.args);
          };

          break;

        default:
          throw new Error("Unknown data-loading action \"".concat(finalAction, "\""));
      }

      var isHandled = false;
      directive.modifiers.forEach(function (modifier) {
        switch (modifier.name) {
          case 'delay':
            // if loading has *stopped*, the delay modifier has no effect
            if (!isLoading) {
              break;
            }

            var delayLength = modifier.value || 200;
            setTimeout(function () {
              if (element.hasAttribute('data-live-is-loading')) {
                loadingDirective();
              }
            }, delayLength);
            isHandled = true;
            break;

          default:
            throw new Error("Unknown modifier ".concat(modifier.name, " used in the loading directive ").concat(directive.getString()));
        }
      }); // execute the loading directive

      if (!isHandled) {
        loadingDirective();
      }
    }
  }, {
    key: "_getLoadingDirectives",
    value: function _getLoadingDirectives() {
      var loadingDirectives = [];
      this.element.querySelectorAll('[data-loading]').forEach(function (element) {
        // use "show" if the attribute is empty
        var directives = (0, _directives_parser.parseDirectives)(element.dataset.loading || 'show');
        loadingDirectives.push({
          element: element,
          directives: directives
        });
      });
      return loadingDirectives;
    }
  }, {
    key: "_showElement",
    value: function _showElement(element) {
      // TODO - allow different "display" types
      element.style.display = 'inline-block';
    }
  }, {
    key: "_hideElement",
    value: function _hideElement(element) {
      element.style.display = 'none';
    }
  }, {
    key: "_addClass",
    value: function _addClass(element, classes) {
      var _element$classList;

      (_element$classList = element.classList).add.apply(_element$classList, _toConsumableArray((0, _string_utils.combineSpacedArray)(classes)));
    }
  }, {
    key: "_removeClass",
    value: function _removeClass(element, classes) {
      var _element$classList2;

      (_element$classList2 = element.classList).remove.apply(_element$classList2, _toConsumableArray((0, _string_utils.combineSpacedArray)(classes))); // remove empty class="" to avoid morphdom "diff" problem


      if (element.classList.length === 0) {
        this._removeAttributes(element, ['class']);
      }
    }
  }, {
    key: "_addAttributes",
    value: function _addAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.setAttribute(attribute, '');
      });
    }
  }, {
    key: "_removeAttributes",
    value: function _removeAttributes(element, attributes) {
      attributes.forEach(function (attribute) {
        element.removeAttribute(attribute);
      });
    }
  }, {
    key: "_willDataFitInUrl",
    value: function _willDataFitInUrl() {
      // if the URL gets remotely close to 2000 chars, it may not fit
      return Object.values(this.dataValue).join(',').length < 1500;
    }
  }, {
    key: "_executeMorphdom",
    value: function _executeMorphdom(newHtml) {
      var _this7 = this;

      // https://stackoverflow.com/questions/494143/creating-a-new-dom-element-from-an-html-string-using-built-in-dom-methods-or-pro#answer-35385518
      function htmlToElement(html) {
        var template = document.createElement('template');
        html = html.trim();
        template.innerHTML = html;
        return template.content.firstChild;
      }

      var newElement = htmlToElement(newHtml);
      (0, _morphdom.default)(this.element, newElement, {
        onBeforeElUpdated: function onBeforeElUpdated(fromEl, toEl) {
          // https://github.com/patrick-steele-idem/morphdom#can-i-make-morphdom-blaze-through-the-dom-tree-even-faster-yes
          if (fromEl.isEqualNode(toEl)) {
            return false;
          } // avoid updating child components: they will handle themselves


          if (fromEl.hasAttribute('data-controller') && fromEl.getAttribute('data-controller').split(' ').indexOf('live') !== -1 && fromEl !== _this7.element) {
            return false;
          }

          return true;
        }
      });
    }
  }, {
    key: "_initiatePolling",
    value: function _initiatePolling(rawPollConfig) {
      var _this8 = this;

      var directives = (0, _directives_parser.parseDirectives)(rawPollConfig || '$render');
      directives.forEach(function (directive) {
        var duration = 2000;
        directive.modifiers.forEach(function (modifier) {
          switch (modifier.name) {
            case 'delay':
              if (modifier.value) {
                duration = modifier.value;
              }

              break;

            default:
              console.warn("Unknown modifier \"".concat(modifier.name, "\" in data-poll \"").concat(rawPollConfig, "\"."));
          }
        });

        _this8.startPoll(directive.action, duration);
      });
    }
  }, {
    key: "startPoll",
    value: function startPoll(actionName, duration) {
      var _this9 = this;

      var callback;

      if (actionName.charAt(0) === '$') {
        callback = function callback() {
          _this9[actionName]();
        };
      } else {
        callback = function callback() {
          _this9._makeRequest(actionName);
        };
      }

      this.pollingIntervals.push(setInterval(function () {
        callback();
      }, duration));
    }
  }, {
    key: "_dispatchEvent",
    value: function _dispatchEvent(name) {
      var payload = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : null;
      var canBubble = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : true;
      var cancelable = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : false;
      var userEvent = new CustomEvent(name, {
        bubbles: canBubble,
        cancelable: cancelable,
        detail: payload
      });
      return this.element.dispatchEvent(userEvent);
    }
  }]);

  return _default;
}(_stimulus.Controller);
/**
 * Tracks the current "re-render" promises.
 *
 * @todo extract to a module
 */


exports.default = _default;

_defineProperty(_default, "values", {
  url: String,
  data: Object,
  csrf: String,

  /**
   * The Debounce timeout.
   *
   * Default: 150
   */
  debounce: Number
});

var PromiseStack = /*#__PURE__*/function () {
  function PromiseStack() {
    _classCallCheck(this, PromiseStack);

    _defineProperty(this, "stack", []);
  }

  _createClass(PromiseStack, [{
    key: "addPromise",
    value: function addPromise(promise) {
      this.stack.push(promise);
    }
    /**
     * Removes the promise AND returns if it is the most recent.
     *
     * @param {Promise} promise
     * @return {boolean}
     */

  }, {
    key: "removePromise",
    value: function removePromise(promise) {
      var index = this.findPromiseIndex(promise); // promise was not found - it was removed because a new Promise
      // already resolved before it

      if (index === -1) {
        return false;
      } // "save" whether this is the most recent or not


      var isMostRecent = this.stack.length === index + 1; // remove all promises starting from the oldest up through this one

      this.stack.splice(0, index + 1);
      return isMostRecent;
    }
  }, {
    key: "findPromiseIndex",
    value: function findPromiseIndex(promise) {
      return this.stack.findIndex(function (item) {
        return item === promise;
      });
    }
  }]);

  return PromiseStack;
}();

var parseLoadingAction = function parseLoadingAction(action, isLoading) {
  switch (action) {
    case 'show':
      return isLoading ? 'show' : 'hide';

    case 'hide':
      return isLoading ? 'hide' : 'show';

    case 'addClass':
      return isLoading ? 'addClass' : 'removeClass';

    case 'removeClass':
      return isLoading ? 'removeClass' : 'addClass';

    case 'addAttribute':
      return isLoading ? 'addAttribute' : 'removeAttribute';

    case 'removeAttribute':
      return isLoading ? 'removeAttribute' : 'addAttribute';
  }

  throw new Error("Unknown data-loading action \"".concat(action, "\""));
};