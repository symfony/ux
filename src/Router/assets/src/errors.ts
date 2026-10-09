export class RouteNotFoundError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'RouteNotFoundError';
    }
}

export class MissingMandatoryParametersError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'MissingMandatoryParametersError';
    }
}

export class InvalidParameterError extends Error {
    constructor(message: string) {
        super(message);
        this.name = 'InvalidParameterError';
    }
}
