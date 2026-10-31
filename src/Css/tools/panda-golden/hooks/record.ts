import { appendFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { threadId } from 'node:worker_threads';
import { expect } from 'vitest';

const serialize = (value: unknown): { json: unknown; undefinedValues?: true } | { unsupported: string } => {
    try {
        let undefinedValues = false;
        const json = JSON.stringify(value, function (_key, v) {
            // JSON writes undefined as null in an array: same CSS, but Panda's types only accept null
            if (v === undefined && Array.isArray(this)) undefinedValues = true;
            if (typeof v === 'function' || typeof v === 'symbol' || typeof v === 'bigint') {
                throw new Error(typeof v);
            }
            if (v instanceof Map || v instanceof Set) {
                throw new Error(v.constructor.name);
            }
            return v;
        });
        return {
            json: json === undefined ? null : JSON.parse(json),
            ...(undefinedValues ? { undefinedValues: true as const } : {}),
        };
    } catch (error) {
        return { unsupported: `non-JSON value (${(error as Error).message})` };
    }
};

const callSite = (): string | null => {
    const frame = (new Error().stack ?? '').split('\n').find((line) => /__tests__\/.+\.test\.tsx?:\d+:\d+/.test(line));
    const match = frame?.match(/([^\s(]+__tests__\/[^\s:]+\.test\.tsx?):(\d+):\d+/);
    return match ? `${match[1]}:${match[2]}` : null;
};

export const record = (kind: string, payload: Record<string, unknown>): void => {
    const outDir = process.env.GOLDEN_OUT;
    if (!outDir) return;

    mkdirSync(outDir, { recursive: true });
    const state = expect.getState();
    appendFileSync(
        join(outDir, `${process.pid}-${threadId}.jsonl`),
        JSON.stringify({
            kind,
            scenario: process.env.GOLDEN_SCENARIO ?? 'fixture',
            testPath: state.testPath ?? null,
            testName: state.currentTestName ?? null,
            callSite: callSite(),
            ...serialize(payload),
        }) + '\n'
    );
};
