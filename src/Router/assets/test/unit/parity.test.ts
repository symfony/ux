import { describe, expect, it } from 'vitest';
import { createRouter, type RequestContext, type Routes } from '../../src/router_controller';
import fixture from '../fixtures/parity.json';

type ParityCase = {
    description: string;
    name: string;
    parameters: Record<string, unknown>;
    referenceType: 'path' | 'relative' | 'url' | 'network';
    context: RequestContext;
    expected?: string;
    error?: { name: string; message: string };
};

const routes = fixture.routes as unknown as Routes;

describe('parity with Symfony UrlGenerator', () => {
    it.each(fixture.cases as ParityCase[])('$description', (testCase) => {
        const { path, url } = createRouter({ routes, context: testCase.context });
        const name = testCase.name as never;
        const generate = (): string => {
            switch (testCase.referenceType) {
                case 'path':
                    return path(name, testCase.parameters as never);
                case 'relative':
                    return path(name, testCase.parameters as never, true);
                case 'url':
                    return url(name, testCase.parameters as never);
                case 'network':
                    return url(name, testCase.parameters as never, true);
            }
        };

        if (testCase.error) {
            expect(generate).toThrowError(
                expect.objectContaining({ name: testCase.error.name, message: testCase.error.message })
            );
        } else {
            expect(generate()).toBe(testCase.expected);
        }
    });
});
