"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.default = void 0;
var _stimulus = require("@hotwired/stimulus");
var _quill = _interopRequireDefault(require("quill"));
var _quillImageUploader = _interopRequireDefault(require("quill-image-uploader"));
var _axios = _interopRequireDefault(require("axios"));
require("quill-image-uploader/dist/quill.imageUploader.min.css");
require("quill-emoji/dist/quill-emoji.css");
var Emoji = _interopRequireWildcard(require("quill-emoji"));
var _quillBlotFormatter = _interopRequireDefault(require("quill-blot-formatter"));
var _customImage = _interopRequireDefault(require("./customImage"));
function _getRequireWildcardCache(nodeInterop) { if (typeof WeakMap !== "function") return null; var cacheBabelInterop = new WeakMap(); var cacheNodeInterop = new WeakMap(); return (_getRequireWildcardCache = function _getRequireWildcardCache(nodeInterop) { return nodeInterop ? cacheNodeInterop : cacheBabelInterop; })(nodeInterop); }
function _interopRequireWildcard(obj, nodeInterop) { if (!nodeInterop && obj && obj.__esModule) { return obj; } if (obj === null || _typeof(obj) !== "object" && typeof obj !== "function") { return { default: obj }; } var cache = _getRequireWildcardCache(nodeInterop); if (cache && cache.has(obj)) { return cache.get(obj); } var newObj = {}; var hasPropertyDescriptor = Object.defineProperty && Object.getOwnPropertyDescriptor; for (var key in obj) { if (key !== "default" && Object.prototype.hasOwnProperty.call(obj, key)) { var desc = hasPropertyDescriptor ? Object.getOwnPropertyDescriptor(obj, key) : null; if (desc && (desc.get || desc.set)) { Object.defineProperty(newObj, key, desc); } else { newObj[key] = obj[key]; } } } newObj.default = obj; if (cache) { cache.set(obj, newObj); } return newObj; }
function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }
function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }
function _defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, _toPropertyKey(descriptor.key), descriptor); } }
function _createClass(Constructor, protoProps, staticProps) { if (protoProps) _defineProperties(Constructor.prototype, protoProps); if (staticProps) _defineProperties(Constructor, staticProps); Object.defineProperty(Constructor, "prototype", { writable: false }); return Constructor; }
function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function"); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, writable: true, configurable: true } }); Object.defineProperty(subClass, "prototype", { writable: false }); if (superClass) _setPrototypeOf(subClass, superClass); }
function _setPrototypeOf(o, p) { _setPrototypeOf = Object.setPrototypeOf ? Object.setPrototypeOf.bind() : function _setPrototypeOf(o, p) { o.__proto__ = p; return o; }; return _setPrototypeOf(o, p); }
function _createSuper(Derived) { var hasNativeReflectConstruct = _isNativeReflectConstruct(); return function _createSuperInternal() { var Super = _getPrototypeOf(Derived), result; if (hasNativeReflectConstruct) { var NewTarget = _getPrototypeOf(this).constructor; result = Reflect.construct(Super, arguments, NewTarget); } else { result = Super.apply(this, arguments); } return _possibleConstructorReturn(this, result); }; }
function _possibleConstructorReturn(self, call) { if (call && (_typeof(call) === "object" || typeof call === "function")) { return call; } else if (call !== void 0) { throw new TypeError("Derived constructors may only return object or undefined"); } return _assertThisInitialized(self); }
function _assertThisInitialized(self) { if (self === void 0) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return self; }
function _isNativeReflectConstruct() { if (typeof Reflect === "undefined" || !Reflect.construct) return false; if (Reflect.construct.sham) return false; if (typeof Proxy === "function") return true; try { Boolean.prototype.valueOf.call(Reflect.construct(Boolean, [], function () {})); return true; } catch (e) { return false; } }
function _getPrototypeOf(o) { _getPrototypeOf = Object.setPrototypeOf ? Object.getPrototypeOf.bind() : function _getPrototypeOf(o) { return o.__proto__ || Object.getPrototypeOf(o); }; return _getPrototypeOf(o); }
function _defineProperty(obj, key, value) { key = _toPropertyKey(key); if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }
function _toPropertyKey(arg) { var key = _toPrimitive(arg, "string"); return _typeof(key) === "symbol" ? key : String(key); }
function _toPrimitive(input, hint) { if (_typeof(input) !== "object" || input === null) return input; var prim = input[Symbol.toPrimitive]; if (prim !== undefined) { var res = prim.call(input, hint || "default"); if (_typeof(res) !== "object") return res; throw new TypeError("@@toPrimitive must return a primitive value."); } return (hint === "string" ? String : Number)(input); }
_quill.default.register('modules/imageUploader', _quillImageUploader.default);
_quill.default.register("modules/emoji", Emoji);
_quill.default.register('modules/blotFormatter', _quillBlotFormatter.default);
_quill.default.register(_customImage.default, true);
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
      var toolbarOptionsValue = this.toolbarOptionsValue;
      var options = {
        debug: this.extraOptionsValue.debug,
        modules: {
          toolbar: toolbarOptionsValue,
          "emoji-toolbar": true,
          "emoji-shortname": true,
          blotFormatter: {
            overlay: {
              style: {
                border: '2px solid red'
              }
            }
          }
        },
        placeholder: this.extraOptionsValue.placeholder,
        theme: this.extraOptionsValue.theme
      };
      if (this.extraOptionsValue.upload_handler.path !== null && this.extraOptionsValue.upload_handler.type === 'form') {
        Object.assign(options.modules, {
          imageUploader: {
            upload: function upload(file) {
              return new Promise(function (resolve, reject) {
                var formData = new FormData();
                formData.append('file', file);
                _axios.default.post(_this.extraOptionsValue.upload_handler.path, formData).then(function (response) {
                  resolve(response.data);
                }).catch(function (err) {
                  reject('Upload failed');
                  console.log(err);
                });
              });
            }
          }
        });
      }
      if (this.extraOptionsValue.upload_handler.path !== null && this.extraOptionsValue.upload_handler.type === 'json') {
        Object.assign(options.modules, {
          imageUploader: {
            upload: function upload(file) {
              return new Promise(function (resolve, reject) {
                var reader = function reader(file) {
                  return new Promise(function (resolve) {
                    var fileReader = new FileReader();
                    fileReader.onload = function () {
                      return resolve(fileReader.result);
                    };
                    fileReader.readAsDataURL(file);
                  });
                };
                reader(file).then(function (result) {
                  return _axios.default.post(_this.extraOptionsValue.upload_handler.path, result, {
                    headers: {
                      'Content-Type': 'application/json'
                    }
                  }).then(function (response) {
                    resolve(response.data);
                  }).catch(function (err) {
                    reject('Upload failed');
                    console.log(err);
                  });
                });
              });
            }
          }
        });
      }
      if (typeof this.extraOptionsValue.height === "string") {
        this.editorContainerTarget.style.height = this.extraOptionsValue.height;
      }
      var quill = new _quill.default(this.editorContainerTarget, options);
      quill.on('text-change', function () {
        var quillContent = quill.root.innerHTML;
        var inputContent = _this.inputTarget;
        inputContent.value = quillContent;
      });
    }
  }]);
  return _default;
}(_stimulus.Controller);
exports.default = _default;
_defineProperty(_default, "targets", ['input', 'editorContainer']);
_defineProperty(_default, "values", {
  toolbarOptions: {
    type: Array,
    default: []
  },
  extraOptions: {
    type: Object,
    default: {}
  }
});