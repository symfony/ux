/**
 * Checks the JavaScript and CSS shipped by the packages against Web Platform Baseline.
 *
 * Each file is checked against the level declared closest to it (see `baseline.ts`), and a
 * construct is reported when the feature carrying it has not reached that level yet.
 */

import { readFileSync } from 'node:fs';

import { styleText } from 'node:util';

import { transform } from 'lightningcss';
import { Visitor, parseSync } from 'oxc-parser';
import { globSync } from 'tinyglobby';

import {
    ASSET_GLOBS,
    type Baseline,
    type BaselineScope,
    DEFAULT_BASELINE,
    declaredScopes,
    scopeOf,
} from './baseline.ts';
import { CSS_FEATURES, type Feature, JS_FEATURES, isAllowed } from './baseline_features.ts';

/** One construct spotted in one place, with the feature and the level that made it a problem. */
interface Finding {
    file: string;
    line: number;
    column: number;
    construct: string;
    feature: Feature;
    scope: BaselineScope;
}

/** Findings go to stderr, so colours follow that stream and the environment it advertises. */
const paint = (format: Parameters<typeof styleText>[0], text: string) =>
    styleText(format, text, { stream: process.stderr });
const red = (text: string) => paint('red', text);
const green = (text: string) => paint('green', text);
const bold = (text: string) => paint('bold', text);
const dim = (text: string) => paint('dim', text);

/** The level a scope is held to, spelled the way web.dev spells it. */
function requiredLevel(baseline: Baseline): string {
    if ('number' === typeof baseline) {
        return `Baseline ${baseline}`;
    }

    return `Baseline ${'widely' === baseline ? 'Widely' : 'Newly'} available`;
}

/** Turns the internal CSS keys back into what someone wrote: `at-rule:container` into `@container`. */
function displayConstruct(construct: string): string {
    const [kind, ...rest] = construct.split(':');

    if ('property' === kind) {
        return rest.join(': ');
    }

    if ('selector' === kind) {
        return `:${rest[0]}`;
    }

    return 'at-rule' === kind ? `@${rest[0]}` : construct;
}

function plural(count: number, singular: string): string {
    return `${count} ${singular}${1 === count ? '' : 's'}`;
}

/** Where a feature stands today, including the date it turns Widely available when it has one. */
function currentLevel({ baseline, newlySince, widelyOn }: Feature): string {
    if (false === baseline) {
        return 'Baseline Limited availability';
    }

    return `Baseline Newly available since ${newlySince}, Widely available on ${widelyOn}`;
}

/**
 * Groups by feature rather than by file: one feature used ten times is one decision to make, and
 * the same message repeated ten times buries it.
 */
function report(findings: Finding[], scannedFiles: number): void {
    if (!findings.length) {
        console.error(green(`✔ ${plural(scannedFiles, 'file')} within ${requiredLevel(DEFAULT_BASELINE)}.`));

        return;
    }

    const groups = Map.groupBy(findings, ({ feature, scope }) => `${feature.id}\u0000${scope.baseline}`);

    for (const group of groups.values()) {
        const { feature, scope } = group[0];
        const constructs = [...new Set(group.map(({ construct }) => construct))].sort();

        console.error(`${red('✖')} ${bold(feature.name)} ${dim(feature.id)}`);
        console.error(`  ${currentLevel(feature)}`);
        console.error(`  ${dim('used as')} ${constructs.map(displayConstruct).join(', ')}`);
        if (scope.baseline !== DEFAULT_BASELINE) {
            console.error(`  ${dim(`${scope.dir} requires ${requiredLevel(scope.baseline)}`)}`);
        }

        const byFile = Map.groupBy(group, ({ file }) => file);

        for (const [file, inFile] of byFile) {
            const positions = inFile.map(({ line, column }) => `${line}:${column}`).join(', ');
            console.error(`  ${file} ${dim(positions)}`);
        }

        console.error('');
    }

    const files = new Set(findings.map(({ file }) => file)).size;
    console.error(
        `${bold(plural(groups.size, 'feature'))} beyond ${requiredLevel(DEFAULT_BASELINE)}, ${plural(findings.length, 'use')} across ${plural(files, 'file')}, ${scannedFiles} checked.`
    );
    console.error(
        dim(
            `\nDeclare a deliberate use in the package's assets/package.json:\n    "config": { "baselineIgnoreFeatures": ["${findings[0].feature.id}"] }`
        )
    );
}

/**
 * @param offsets the offset each line starts at, as returned by {@link lineOffsets}.
 * @returns a one-based line and column, the shape every editor expects.
 */
function positionAt(offsets: number[], offset: number): { line: number; column: number } {
    let line = 0;
    while (line + 1 < offsets.length && offsets[line + 1] <= offset) {
        line++;
    }

    return { line: line + 1, column: offset - offsets[line] + 1 };
}

/** Offset each line starts at, computed once per file so positions stay a lookup. */
function lineOffsets(source: string): number[] {
    const offsets = [0];
    for (let i = 0; i < source.length; i++) {
        if ('\n' === source[i]) {
            offsets.push(i + 1);
        }
    }

    return offsets;
}

/**
 * Names the file binds itself. Without scope analysis a parameter named `escape` reads as the
 * global `escape()`, so any locally bound name is left alone wherever it appears.
 */
function boundNames(program: any): Set<string> {
    const names = new Set<string>();

    // Every shape a binding can take, spelled out: `Visitor` only traverses from a program root,
    // and a binding pattern is a small closed grammar anyway.
    const bind = (node: any) => {
        switch (node?.type) {
            case 'Identifier':
                names.add(node.name);
                break;
            case 'ObjectPattern':
                node.properties.forEach((property: any) => bind(property.value ?? property.argument));
                break;
            case 'ArrayPattern':
                node.elements.forEach(bind);
                break;
            case 'AssignmentPattern':
                bind(node.left);
                break;
            case 'RestElement':
                bind(node.argument);
                break;
        }
    };

    const bindFunction = (node: any) => {
        bind(node.id);
        node.params.forEach(bind);
    };

    new Visitor({
        ArrowFunctionExpression: bindFunction,
        CatchClause: (node: any) => bind(node.param),
        ClassDeclaration: (node: any) => bind(node.id),
        ClassExpression: (node: any) => bind(node.id),
        FunctionDeclaration: bindFunction,
        FunctionExpression: bindFunction,
        ImportDefaultSpecifier: (node: any) => bind(node.local),
        ImportNamespaceSpecifier: (node: any) => bind(node.local),
        ImportSpecifier: (node: any) => bind(node.local),
        PropertyDefinition: (node: any) => bind(node.key),
        VariableDeclarator: (node: any) => bind(node.id),
    }).visit(program);

    return names;
}

/**
 * Spots globals, their static members and the members of the interfaces reachable from a global.
 * A method called on a value the parser cannot resolve, `el.checkVisibility()` for instance, needs
 * type information and goes unseen.
 */
function checkScript(file: string, scope: BaselineScope): Finding[] {
    const source = readFileSync(file, 'utf8');
    const { program } = parseSync(file, source);
    const offsets = lineOffsets(source);
    const shadowed = boundNames(program);
    const findings: Finding[] = [];

    const add = (construct: string, start: number) => {
        const feature = JS_FEATURES.get(construct);
        if (!feature || shadowed.has(construct.split('.')[0])) {
            return;
        }

        if (!isAllowed(feature, scope.baseline) && !scope.ignoreFeatures.includes(feature.id)) {
            findings.push({ file, ...positionAt(offsets, start), construct, feature, scope });
        }
    };

    new Visitor({
        NewExpression: (node: any) => 'Identifier' === node.callee?.type && add(node.callee.name, node.callee.start),
        CallExpression: (node: any) => 'Identifier' === node.callee?.type && add(node.callee.name, node.callee.start),
        MemberExpression: (node: any) => {
            if ('Identifier' !== node.object?.type || node.computed) {
                return;
            }

            // `Notification.permission` and `Notification` carry the same feature, so the member
            // alone is enough whenever it names a construct of its own.
            const member = `${node.object.name}.${node.property.name}`;
            add(JS_FEATURES.has(member) ? member : node.object.name, node.object.start);
        },
    }).visit(program);

    return findings;
}

/**
 * Spots properties, selectors and at-rules. Values are only spotted on the properties Lightning
 * CSS passes through as custom, since it normalizes away the source identifiers of the rest.
 */
function checkStyle(file: string, scope: BaselineScope): Finding[] {
    const source = readFileSync(file, 'utf8');
    const findings: Finding[] = [];

    // Lightning CSS locates rules but not the declarations inside them, and it visits a rule right
    // before its own declarations, so the enclosing rule is the finest position available.
    let ruleLine = 0;
    let ruleColumn = 1;

    const add = (construct: string) => {
        const feature = CSS_FEATURES.get(construct);
        if (!feature || isAllowed(feature, scope.baseline) || scope.ignoreFeatures.includes(feature.id)) {
            return;
        }

        findings.push({ file, line: ruleLine + 1, column: ruleColumn, construct, feature, scope });
    };

    transform({
        filename: file,
        code: Buffer.from(source),
        visitor: {
            Declaration(declaration: any) {
                const isCustom = 'custom' === declaration.property;
                const property = isCustom ? declaration.value.name : declaration.property;
                add(`property:${property}`);

                // Lightning CSS normalizes the values of the properties it models, so only the
                // ones it passes through as custom still carry their source identifiers.
                if (isCustom) {
                    for (const token of declaration.value.value) {
                        if ('token' === token.type && 'ident' === token.value?.type) {
                            add(`property:${property}:${token.value.value}`);
                        }
                    }
                }
            },
            Rule(rule: any) {
                ruleLine = rule.value?.loc?.line ?? ruleLine;
                ruleColumn = rule.value?.loc?.column ?? ruleColumn;
                add(`at-rule:${rule.type}`);
            },
            Selector(selector: any) {
                for (const part of selector) {
                    if ('pseudo-class' === part.type || 'pseudo-element' === part.type) {
                        add(`selector:${part.kind}`);
                    }
                }

                return selector;
            },
        },
    });

    return findings;
}

const scopes = declaredScopes();
const assets = globSync(ASSET_GLOBS);
const scripts = assets.filter((file) => !file.endsWith('.css'));
const styles = assets.filter((file) => file.endsWith('.css'));

if (!scripts.length || !styles.length) {
    console.error(`Nothing to check: ${scripts.length} script(s) and ${styles.length} stylesheet(s) matched.`);
    process.exit(2);
}

const findings = [
    ...scripts.flatMap((file) => checkScript(file, scopeOf(file, scopes))),
    ...styles.flatMap((file) => checkStyle(file, scopeOf(file, scopes))),
];

report(findings, scripts.length + styles.length);
process.exit(findings.length ? 1 : 0);
