// post.user.username
export function setDeepData(data, propertyPath, value) {
    // cheap way to deep clone simple data
    const finalData = JSON.parse(JSON.stringify(data));

    let currentLevelData = finalData;
    const parts = propertyPath.split('.');

    // change currentLevelData to the final depth object
    for (let i = 0; i < parts.length - 1; i++) {
        currentLevelData = currentLevelData[parts[i]];
    }

    // now finally change the key on that deeper object
    const finalKey = parts[parts.length - 1];

    // make sure the currentLevelData is an object, not a scalar
    // if it is, it means the initial data didn't know that sub-properties
    // could be exposed. Or, you're just trying to set some deep
    // path - e.g. post.title - onto some property that is, for example,
    // an integer (2).
    if (typeof currentLevelData !== 'object') {
        const lastPart = parts.pop();
        throw new Error(`Cannot set data-model="${propertyPath}". They parent "${parts.join(',')}" data does not appear to be an object (it's "${currentLevelData}"). Did you forget to add exposed={"${lastPart}"} to its LiveProp?`)
    }

    // represents a situation where the key you're setting *is* an object,
    // but the key we're setting is a new key. Currently, all keys should
    // be initialized with the initial data.
    if (currentLevelData[finalKey] === undefined) {
        const lastPart = parts.pop();
        if (parts.length > 0) {
            throw new Error(`The property used in data-model="${propertyPath}" was never initialized. Did you forget to add exposed={"${lastPart}"} to its LiveProp?`)
        } else {
            throw new Error(`The property used in data-model="${propertyPath}" was never initialized. Did you forget to expose "${lastPart}" as a LiveProp? Available models values are: ${Object.keys(data).length > 0 ? Object.keys(data).join(', ') : '(none)'}`)
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
export function doesDeepPropertyExist(data, propertyPath) {
    const parts = propertyPath.split('.');

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
export function normalizeModelName(model) {
    return model
        .split('[')
        // ['object', 'foo', 'bar', 'ya']
        .map(function (s) {
            return s.replace(']', '')
        }).join('.')
}
