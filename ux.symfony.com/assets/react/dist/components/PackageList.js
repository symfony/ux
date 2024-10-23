import React from 'react';
export default function (props) {
  if (props.packages.length === 0) {
    return 'No packages found';
  }
  return /*#__PURE__*/React.createElement("div", {
    className: "PackageList"
  }, props.packages.map(item => /*#__PURE__*/React.createElement("div", {
    className: "PackageListItem",
    key: item.id
  }, /*#__PURE__*/React.createElement("div", {
    className: "PackageListItem__icon",
    style: {
      '--gradient': item.gradient
    }
  }, /*#__PURE__*/React.createElement("img", {
    src: item.imageUrl,
    alt: `Image for the ${item.humanName} UX package`
  })), /*#__PURE__*/React.createElement("h3", {
    className: "PackageListItem__label"
  }, /*#__PURE__*/React.createElement("a", {
    href: item.url
  }, item.humanName)))));
}