import React from 'react';
export default function (props) {
  if (props.packages.length === 0) {
    return /*#__PURE__*/React.createElement("div", {
      className: "alert alert-info"
    }, "Sad trombone... we haven't built any components that match this search yet!");
  }
  return /*#__PURE__*/React.createElement("div", {
    className: "row"
  }, props.packages.map(item => /*#__PURE__*/React.createElement("a", {
    key: item.name,
    href: item.url,
    className: "col-12 col-md-4 col-lg-3 mb-2"
  }, /*#__PURE__*/React.createElement("div", {
    className: "components-container p-2"
  }, /*#__PURE__*/React.createElement("div", {
    className: "d-flex"
  }, /*#__PURE__*/React.createElement("div", {
    className: "live-component-img d-flex justify-content-center align-items-center",
    style: {
      background: item.gradient
    }
  }, /*#__PURE__*/React.createElement("img", {
    width: "17px",
    height: "17px",
    src: item.imageUrl,
    alt: `Image for the ${item.humanName} UX package`
  })), /*#__PURE__*/React.createElement("h4", {
    className: "ubuntu-title ps-2 align-self-center"
  }, item.humanName))))));
}