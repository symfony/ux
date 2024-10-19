import React from 'react';

export default function (props) {
  if (props.packages.length === 0) {
    return 'No packages found';
  }

  return (
    <div className="PackageList">
      {props.packages.map(item => (
        <div className="PackageListItem" key={item.id}>
          <div className="PackageListItem__icon" style={{'--gradient': item.gradient}}>
            <img src={item.imageUrl} alt={`Image for the ${item.humanName} UX package`}/>
          </div>
          <h3 className="PackageListItem__label">
            <a href={item.url}>{item.humanName}</a>
          </h3>
        </div>
      ))}
    </div>
  );
}
