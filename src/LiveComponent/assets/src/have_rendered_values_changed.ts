export function haveRenderedValuesChanged(originalDataJson: string, currentDataJson: string, newDataJson: string): boolean {
    /*
     * Right now, if the "data" on the new value is different than
     * the "original data" on the child element, then we force re-render
     * the child component. There may be some other cases that we
     * add later if they come up. Either way, this is probably the
     * behavior we want most of the time, but it's not perfect. For
     * example, if the child component has some a writable prop that
     * has changed since the initial render, re-rendering the child
     * component from the parent component will "eliminate" that
     * change.
     */

    // if the original data matches the new data, then the parent
    // hasn't changed how they render the child.
    if (originalDataJson === newDataJson) {
        return false;
    }

    // The child component IS now being rendered in a "new way".
    // This means that at least one of the "data" pieces used
    // to render the child component has changed.
    // However, the piece of data that changed might simply
    // match the "current data" of that child component. In that case,
    // there is no point to re-rendering.
    // And, to be safe (in case the child has some "private LiveProp"
    // that has been modified), we want to avoid rendering.


    // if the current data exactly matches the new data, then definitely
    // do not re-render.
    if (currentDataJson === newDataJson) {
        return false;
    }

    // here, we will compare the original data for the child component
    // with the new data. What we're looking for are they keys that
    // have changed between the original "child rendering" and the
    // new "child rendering".
    const originalData = JSON.parse(originalDataJson);
    const newData = JSON.parse(newDataJson);
    const changedKeys = Object.keys(newData);
    Object.entries(originalData).forEach(([key, value]) => {
        // if any key in the new data is different than a key in the
        // current data, then we *should* re-render. But if all the
        // keys in the new data equal
        if (value === newData[key]) {
            // value is equal, remove from changedKeys
            changedKeys.splice(changedKeys.indexOf(key), 1);
        }
    });

    // now that we know which keys have changed between originally
    // rendering the child component and this latest render, we
    // can check to see if the the child component *already* has
    // the latest value for those keys.

    const currentData = JSON.parse(currentDataJson)
    let keyHasChanged = false;
    changedKeys.forEach((key) => {
        // if any key in the new data is different than a key in the
        // current data, then we *should* re-render. But if all the
        // keys in the new data equal
        if (currentData[key] !== newData[key]) {
            keyHasChanged = true;
        }
    });

    return keyHasChanged;
}
