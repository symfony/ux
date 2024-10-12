import React, { Component } from 'react';
import PackageList from '../components/PackageList.js';
export default class extends Component {
  constructor() {
    super();
    this.state = {
      search: ''
    };
  }
  render() {
    return /*#__PURE__*/React.createElement("div", null, /*#__PURE__*/React.createElement("input", {
      type: "search",
      placeholder: "This search is built in React!",
      className: "form-control",
      value: this.state.search,
      onChange: event => this.setState({
        search: event.target.value
      })
    }), /*#__PURE__*/React.createElement("div", {
      className: "mt-3"
    }, /*#__PURE__*/React.createElement(PackageList, {
      packages: this.filteredPackages()
    })));
  }
  filteredPackages() {
    if (!this.state.search) {
      return this.props.packages;
    }
    return this.props.packages.filter(uxPackage => {
      return uxPackage.humanName.toLowerCase().includes(this.state.search.toLowerCase());
    });
  }
}