import React from 'react';

export default function (props) {
    if (props.packages.length === 0) {
        return 'No packages found';
    }

    return (
        props.packages.map(item => (
                <div class="PackageListItem">
                    <div class="PackageListItem__icon" style={`--gradient: ${item.gradient}`}>
                        <img src={item.imageUrl} alt={`Image for the ${item.humanName} UX package`} />
                    </div>
                    <h3 class="PackageListItem__title">
                        <a href={item.url}>{item.humanName}</a>
                    </h3>
                </div>
        ))
    );
}
