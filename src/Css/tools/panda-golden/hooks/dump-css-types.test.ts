import { mkdtempSync, mkdirSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';
import { allCssProperties } from '@pandacss/is-valid-prop';
import ts from 'typescript';
import { test } from 'vitest';

// Panda types each CSS property with csstype's PropertiesFallback<String | Number>: a closed union of keywords, or any string or number.
test('dump css property types', () => {
    const file = join(mkdtempSync(join(tmpdir(), 'css-types-')), 'css-types.ts');
    writeFileSync(
        file,
        `import type { PropertiesFallback } from '${join(process.cwd(), 'packages/types/src/csstype')}'\nexport type Properties = PropertiesFallback<String | Number>\n`
    );

    const program = ts.createProgram([file], { strict: true, noEmit: true, skipLibCheck: true });
    const checker = program.getTypeChecker();
    const alias = program.getSourceFile(file)!.statements.find(ts.isTypeAliasDeclaration)!;

    const types: Record<string, { keywords: string[]; string: boolean; number: boolean }> = {};
    for (const symbol of checker.getPropertiesOfType(checker.getTypeAtLocation(alias.name))) {
        const type = checker.getNonNullableType(checker.getTypeOfSymbol(symbol));
        const described = { keywords: [] as string[], string: false, number: false };
        for (const member of type.isUnion() ? type.types : [type]) {
            const parts = member.isIntersection() ? member.types : [member];
            if (member.isStringLiteral()) described.keywords.push(member.value);
            else if (member.isNumberLiteral()) described.keywords.push(String(member.value));
            else if (parts.some((part) => part.flags & ts.TypeFlags.String || part.symbol?.name === 'String'))
                described.string = true;
            else if (parts.some((part) => part.flags & ts.TypeFlags.Number || part.symbol?.name === 'Number'))
                described.number = true;
        }
        types[symbol.name] = described;
    }

    mkdirSync(dirname(process.env.GOLDEN_CSS_TYPES_OUT as string), { recursive: true });
    writeFileSync(
        process.env.GOLDEN_CSS_TYPES_OUT as string,
        JSON.stringify({ properties: allCssProperties.filter(Boolean), types }, null, 1) + '\n'
    );
});
