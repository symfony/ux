/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
'use strict';

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports["default"] = void 0;

var _stimulus = require("stimulus");

var _cropperjs = _interopRequireDefault(require("cropperjs"));

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { "default": obj }; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } }

function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); return Constructor; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); if (superClass) _setPrototypeOf(subClass, superClass); }

function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf || function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }

function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }

function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } return _assertThisInitialized(self); }

function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }

function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Date.prototype.toString.call(Reflect.construct(Date, [], function () {})); return true; } catch (e) { return false; } }

function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }

var _default = /*#__PURE__*/function (_Controller) {
  _inherits(_default, _Controller);

  var _super = _createSuper(_default);

  function _default() {
    _classCallCheck(this, _default);

    return _super.apply(this, arguments);
  }

  _createClass(_default, [{
    key: "connect",
    value: function connect() {
      var _this = this;

      // Create image view
      var img = document.createElement('img');
      img.classList.add('cropperjs-image');
      img.src = this.element.getAttribute('data-public-url');
      var parent = this.element.parentNode;
      parent.appendChild(img); // Build the cropper

      var options = {
        viewMode: parseInt(this.element.getAttribute('data-view-mode')),
        dragMode: this.element.getAttribute('data-drag-mode'),
        responsive: this.element.hasAttribute('data-responsive'),
        restore: this.element.hasAttribute('data-restore'),
        checkCrossOrigin: this.element.hasAttribute('data-check-cross-origin'),
        checkOrientation: this.element.hasAttribute('data-check-orientation'),
        modal: this.element.hasAttribute('data-modal'),
        guides: this.element.hasAttribute('data-guides'),
        center: this.element.hasAttribute('data-center'),
        highlight: this.element.hasAttribute('data-highlight'),
        background: this.element.hasAttribute('data-background'),
        autoCrop: this.element.hasAttribute('data-auto-crop'),
        autoCropArea: parseFloat(this.element.getAttribute('data-auto-crop-area')),
        movable: this.element.hasAttribute('data-movable'),
        rotatable: this.element.hasAttribute('data-rotatable'),
        scalable: this.element.hasAttribute('data-scalable'),
        zoomable: this.element.hasAttribute('data-zoomable'),
        zoomOnTouch: this.element.hasAttribute('data-zoom-on-touch'),
        zoomOnWheel: this.element.hasAttribute('data-zoom-on-wheel'),
        wheelZoomRatio: parseFloat(this.element.getAttribute('data-wheel-zoom-ratio')),
        cropBoxMovable: this.element.hasAttribute('data-crop-box-movable'),
        cropBoxResizable: this.element.hasAttribute('data-crop-box-resizable'),
        toggleDragModeOnDblclick: this.element.hasAttribute('data-toggle-drag-mode-on-dblclick'),
        minContainerWidth: parseInt(this.element.getAttribute('data-min-container-width')),
        minContainerHeight: parseInt(this.element.getAttribute('data-min-container-height')),
        minCanvasWidth: parseInt(this.element.getAttribute('data-min-canvas-width')),
        minCanvasHeight: parseInt(this.element.getAttribute('data-min-canvas-height')),
        minCropBoxWidth: parseInt(this.element.getAttribute('data-min-crop-box-width')),
        minCropBoxHeight: parseInt(this.element.getAttribute('data-min-crop-box-height'))
      };

      if (this.element.getAttribute('data-aspect-ratio')) {
        options.aspectRatio = parseFloat(this.element.getAttribute('data-aspect-ratio'));
      }

      if (this.element.getAttribute('data-initial-aspect-ratio')) {
        options.initialAspectRatio = parseFloat(this.element.getAttribute('data-initial-aspect-ratio'));
      }

      var cropper = new _cropperjs["default"](img, options);
      img.addEventListener('crop', function (event) {
        _this.element.value = JSON.stringify(event.detail);
      });

      this._dispatchEvent('cropperjs:connect', {
        cropper: cropper,
        options: options,
        img: img
      });
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