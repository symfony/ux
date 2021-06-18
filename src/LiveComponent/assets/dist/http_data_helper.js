"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.buildFormData = buildFormData;
exports.buildSearchParams = buildSearchParams;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

function _toConsumableArray(arr) { return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _unsupportedIterableToArray(arr) || _nonIterableSpread(); }

function _nonIterableSpread() { throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method."); }

function _unsupportedIterableToArray(o, minLen) { if (!o) return; if (typeof o === "string") return _arrayLikeToArray(o, minLen); var n = Object.prototype.toString.call(o).slice(8, -1); if (n === "Object" && o.constructor) n = o.constructor.name; if (n === "Map" || n === "Set") return Array.from(o); if (n === "Arguments" || /^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(n)) return _arrayLikeToArray(o, minLen); }

function _iterableToArray(iter) { if (typeof Symbol !== "undefined" && iter[Symbol.iterator] != null || iter["@@iterator"] != null) return Array.from(iter); }

function _arrayWithoutHoles(arr) { if (Array.isArray(arr)) return _arrayLikeToArray(arr); }

function _arrayLikeToArray(arr, len) { if (len == null || len > arr.length) len = arr.length; for (var i = 0, arr2 = new Array(len); i < len; i++) { arr2[i] = arr[i]; } return arr2; }

/*
 * Helper to convert a deep object of data into a format
 * that can be transmitted as GET or POST data.
 *
 * Likely there is an easier way to do this with no duplication.
 */
var buildFormKey = function buildFormKey(key, parentKeys) {
  var fieldName = '';
  [].concat(_toConsumableArray(parentKeys), [key]).forEach(function (name) {
    fieldName += fieldName ? "[".concat(name, "]") : name;
  });
  return fieldName;
};
/**
 * @param {FormData} formData
 * @param {Object} data
 * @param {Array} parentKeys
 */


var addObjectToFormData = function addObjectToFormData(formData, data, parentKeys) {
  // todo - handles files
  Object.keys(data).forEach(function (key) {
    var value = data[key]; // TODO: there is probably a better way to normalize this

    if (value === true) {
      value = 1;
    }

    if (value === false) {
      value = 0;
    } // don't send null values at all


    if (value === null) {
      return;
    } // handle embedded objects


    if (_typeof(value) === 'object' && value !== null) {
      addObjectToFormData(formData, value, [].concat(_toConsumableArray(parentKeys), [key]));
      return;
    }

    formData.append(buildFormKey(key, parentKeys), value);
  });
};
/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @param {Array} parentKeys
 */


var addObjectToSearchParams = function addObjectToSearchParams(searchParams, data, parentKeys) {
  Object.keys(data).forEach(function (key) {
    var value = data[key]; // TODO: there is probably a better way to normalize this
    // TODO: duplication

    if (value === true) {
      value = 1;
    }

    if (value === false) {
      value = 0;
    } // don't send null values at all


    if (value === null) {
      return;
    } // handle embedded objects


    if (_typeof(value) === 'object' && value !== null) {
      addObjectToSearchParams(searchParams, value, [].concat(_toConsumableArray(parentKeys), [key]));
      return;
    }

    searchParams.set(buildFormKey(key, parentKeys), value);
  });
};
/**
 * @param {Object} data
 * @return {FormData}
 */


function buildFormData(data) {
  var formData = new FormData();
  addObjectToFormData(formData, data, []);
  return formData;
}
/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @return {URLSearchParams}
 */


function buildSearchParams(searchParams, data) {
  addObjectToSearchParams(searchParams, data, []);
  return searchParams;
}