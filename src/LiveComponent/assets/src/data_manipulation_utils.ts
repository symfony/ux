export function getDeepData(data: any, propertyPath: string) {
    const { currentLevelData, finalKey } = parseDeepData(data, propertyPath);

    if (currentLevelData === undefined) {
        return undefined;
    }

    return currentLevelData[finalKey];
}

// post.user.username
const parseDeepData = (data: any, propertyPath: string) => {
    const finalData = JSON.parse(JSON.stringify(data));

    let currentLevelData = finalData;
    const parts = propertyPath.split('.');

    // change currentLevelData to the final depth object
    for (let i = 0; i < parts.length - 1; i++) {
        currentLevelData = currentLevelData[parts[i]];
    }

    // now finally change the key on that deeper object
    const finalKey = parts[parts.length - 1];

    return {
        currentLevelData,
        finalData,
        finalKey,
        parts,
    };
};

/**
 * @internal
 */
export function setDeepData(data: any, propertyPath: string, value: any): any {
    const { currentLevelData, finalData, finalKey, parts } = parseDeepData(data, propertyPath);

    // make sure the currentLevelData is an object, not a scalar
    // if it is, it means the initial data didn't know that sub-properties
    // could be exposed. Or, you're just trying to set some deep
    // path - e.g. post.title - onto some property that is, for example,
    // an integer (2).
    if (typeof currentLevelData !== 'object') {
        const lastPart = parts.pop();

        if (typeof currentLevelData === 'undefined') {
            throw new Error(
                `Cannot set data-model="${propertyPath}". The parent "${parts.join(
                    '.'
                )}" data does not exist. Did you forget to expose "${parts[0]}" as a LiveProp?`
            );
        }

        throw new Error(
            `Cannot set data-model="${propertyPath}". The parent "${parts.join(
                '.'
            )}" data does not appear to be an object (it's "${currentLevelData}"). Did you forget to add exposed={"${lastPart}"} to its LiveProp?`
        );
    }

    // represents a situation where the key you're setting *is* an object,
    // but the key we're setting is a new key. Currently, all keys should
    // be initialized with the initial data.
    if (currentLevelData[finalKey] === undefined) {
        const lastPart = parts.pop();
        if (parts.length > 0) {
            throw new Error(
                `The model name ${propertyPath} was never initialized. Did you forget to add exposed={"${lastPart}"} to its LiveProp?`
            );
        }

        throw new Error(
            `The model name "${propertyPath}" was never initialized. Did you forget to expose "${lastPart}" as a LiveProp? Available models values are: ${
                Object.keys(data).length > 0 ? Object.keys(data).join(', ') : '(none)'
            }`
        );
    }

    currentLevelData[finalKey] = value;

    return finalData;
}
