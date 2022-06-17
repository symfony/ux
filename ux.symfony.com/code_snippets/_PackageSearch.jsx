import React, { Component } from 'react';

export default class extends Component {
    constructor() {
      super();

      this.state = { search: '' };
    }

    render() {
        return (
            <div>
                <input
                    value={this.state.search}
                    onChange={(event) => this.setState({search: event.target.value})}
                />

                <div className="row">
                    {this.filteredPackages().map(item => (
                        <a key={item.name} href={item.url}>
                            <img src={item.imageUrl} />
                            <h4>{item.humanName}</h4>
                        </a>
                    ))}
                </div>
            </div>
        );
    }

    filteredPackages() {
        return this.props.packages.filter((uxPackage) => {
            return uxPackage.humanName.includes(this.state.search)
        });
    }
}
