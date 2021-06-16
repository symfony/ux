/*
 * Helper to convert a deep object of data into a format
 * that can be transmitted as GET or POST data.
 *
 * Likely there is an easier way to do this with no duplication.
 */

const buildFormKey = function(key, parentKeys) {
    let fieldName = '';
    [...parentKeys, key].forEach((name) => {
        fieldName += fieldName ? `[${name}]` : name;
    });

    return fieldName;
}

/**
 * @param {FormData} formData
 * @param {Object} data
 * @param {Array} parentKeys
 */
const addObjectToFormData = function(formData, data, parentKeys) {
    // todo - handles files
    Object.keys(data).forEach((key => {
        let value = data[key];

        // TODO: there is probably a better way to normalize this
        if (value === true) {
            value = 1;
        }
        if (value === false) {
            value = 0;
        }
        // don't send null values at all
        if (value === null) {
            return;
        }

        // handle embedded objects
        if (typeof value === 'object' && value !== null) {
            addObjectToFormData(formData, value, [...parentKeys, key]);

            return;
        }

        formData.append(buildFormKey(key, parentKeys), value);
    }));
}

/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @param {Array} parentKeys
 */
const addObjectToSearchParams = function(searchParams, data, parentKeys) {
    Object.keys(data).forEach((key => {
        let value = data[key];

        // TODO: there is probably a better way to normalize this
        // TODO: duplication
        if (value === true) {
            value = 1;
        }
        if (value === false) {
            value = 0;
        }
        // don't send null values at all
        if (value === null) {
            return;
        }

        // handle embedded objects
        if (typeof value === 'object' && value !== null) {
            addObjectToSearchParams(searchParams, value, [...parentKeys, key]);

            return;
        }

        searchParams.set(buildFormKey(key, parentKeys), value);
    }));
}

/**
 * @param {Object} data
 * @return {FormData}
 */
export function buildFormData(data) {
    const formData = new FormData();

    addObjectToFormData(formData, data, []);

    return formData;
}

/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @return {URLSearchParams}
 */
export function buildSearchParams(searchParams, data) {
    addObjectToSearchParams(searchParams, data, []);

    return searchParams;
}
