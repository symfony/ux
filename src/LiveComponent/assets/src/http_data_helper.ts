/*
 * Helper to convert a deep object of data into a format
 * that can be transmitted as GET or POST data.
 *
 * Likely there is an easier way to do this with no duplication.
 */

const buildFormKey = function(key: string, parentKeys: string[]) {
    let fieldName = '';
    [...parentKeys, key].forEach((name) => {
        fieldName += fieldName ? `[${name}]` : name;
    });

    return fieldName;
}

const addObjectToFormData = function(formData: FormData, data: any, parentKeys: string[]) {
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
        if (typeof value === 'object') {
            addObjectToFormData(formData, value, [...parentKeys, key]);

            return;
        }

        formData.append(buildFormKey(key, parentKeys), value);
    }));
}

const addObjectToSearchParams = function(searchParams: URLSearchParams, data: any, parentKeys: string[]) {
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
        if (typeof value === 'object') {
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
export function buildFormData(data: any): FormData {
    const formData = new FormData();

    addObjectToFormData(formData, data, []);

    return formData;
}

/**
 * @param {URLSearchParams} searchParams
 * @param {Object} data
 * @return {URLSearchParams}
 */
export function buildSearchParams(searchParams: URLSearchParams, data: any): URLSearchParams {
    addObjectToSearchParams(searchParams, data, []);

    return searchParams;
}
