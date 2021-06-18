"use strict";

Object.defineProperty(exports, "__esModule", {
  value: true
});
exports.setDeepData = setDeepData;
exports.doesDeepPropertyExist = doesDeepPropertyExist;
exports.normalizeModelName = normalizeModelName;

function _typeof(obj) { "@babel/helpers - typeof"; if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

// post.user.username
function setDeepData(data, propertyPath, value) {
  // cheap way to deep clone simple data
  var finalData = JSON.parse(JSON.stringify(data));
  var currentLevelData = finalData;
  var parts = propertyPath.split('.'); // change currentLevelData to the final depth object

  for (var i = 0; i < parts.length - 1; i++) {
    currentLevelData = currentLevelData[parts[i]];
  } // now finally change the key on that deeper object


  var finalKey = parts[parts.length - 1]; // make sure the currentLevelData is an object, not a scalar
  // if it is, it means the initial data didn't know that sub-properties
  // could be exposed. Or, you're just trying to set some deep
  // path - e.g. post.title - onto some property that is, for example,
  // an integer (2).

  if (_typeof(currentLevelData) !== 'object') {
    var lastPart = parts.pop();
    throw new Error("Cannot set data-model=\"".concat(propertyPath, "\". They parent \"").concat(parts.join(','), "\" data does not appear to be an object (it's \"").concat(currentLevelData, "\"). Did you forget to add exposed={\"").concat(lastPart, "\"} to its LiveProp?"));
  } // represents a situation where the key you're setting *is* an object,
  // but the key we're setting is a new key. This, perhaps, could be
  // allowed. But right now, all keys should be initialized with the
  // initial data.


  if (currentLevelData[finalKey] === undefined) {
    var _lastPart = parts.pop();

    if (parts.length > 0) {
      console.warn("The property used in data-model=\"".concat(propertyPath, "\" was never initialized. Did you forget to add exposed={\"").concat(_lastPart, "\"} to its LiveProp?"));
    } else {
      console.warn("The property used in data-model=\"".concat(propertyPath, "\" was never initialized. Did you forget to expose \"").concat(_lastPart, "\" as a LiveProp?"));
    }
  }

  currentLevelData[finalKey] = value;
  return finalData;
}
/**
 * Checks if the given propertyPath is for a valid top-level key.
 *
 * @param {Object} data
 * @param {string} propertyPath
 * @return {boolean}
 */


function doesDeepPropertyExist(data, propertyPath) {
  var parts = propertyPath.split('.');
  return data[parts[0]] !== undefined;
}
/**
 * Normalizes model names with [] into the "." syntax.
 *
 * For example: "user[firstName]" becomes "user.firstName"
 *
 * @param {string} model
 * @return {string}
 */


function normalizeModelName(model) {
  return model.split('[') // ['object', 'foo', 'bar', 'ya']
  .map(function (s) {
    return s.replace(']', '');
  }).join('.');
}