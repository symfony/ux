import { type CSSProperties, createElement, type ReactElement, type SVGProps } from 'react';
import { mapNodes, resolveIcon, splitAttributes, type UXIconData } from './index';

export type UXIconProps = Omit<SVGProps<SVGSVGElement>, 'dangerouslySetInnerHTML' | 'children'> & {
    icon: UXIconData;
};

const REACT_PROP_NAMES: Record<string, string> = {
    class: 'className',
    for: 'htmlFor',
    tabindex: 'tabIndex',
};

/**
 * Renders a dumped icon, with the same attributes as the "ux_icon()" Twig function.
 *
 *     import { LucideTrash2Icon } from '../var/icons/index.js';
 *
 *     <UXIcon icon={LucideTrash2Icon} className="text-danger" aria-label="Delete" />
 */
export function UXIcon({ icon, ...props }: UXIconProps): ReactElement {
    const [attributes, passthrough] = splitAttributes(props);

    return createElement(
        'svg',
        { ...toReactProps(resolveIcon(icon, attributes)), ...passthrough },
        // Static children given as arguments, so React does not require keys
        ...mapNodes<ReactElement>(icon.children, (tag, attributes, children) =>
            createElement(tag, toReactProps(attributes), ...children)
        )
    );
}

/**
 * Converts HTML attribute names (from the YAML configuration and SVG files) to React prop names.
 *
 * "class" => "className", "stroke-width" => "strokeWidth", "xlink:href" => "xlinkHref", "style" => object, ...
 *
 * As attributes are processed in order, a React-named prop given by the user (e.g. "strokeWidth") overrides the dumped HTML-named one (e.g. "stroke-width").
 */
function toReactProps(attributes: Record<string, string>): Record<string, unknown> {
    const props: Record<string, unknown> = {};
    for (const [name, value] of Object.entries(attributes)) {
        if (name === 'style') {
            props.style = parseStyle(value);
        } else if (name.startsWith('aria-') || name.startsWith('data-')) {
            props[name] = value;
        } else {
            props[REACT_PROP_NAMES[name] ?? camelize(name)] = value;
        }
    }

    return props;
}

function parseStyle(style: string): CSSProperties {
    const properties: Record<string, string> = {};
    for (const declaration of style.split(';')) {
        const index = declaration.indexOf(':');
        if (index === -1) {
            continue;
        }

        const property = declaration.slice(0, index).trim();
        properties[property.startsWith('--') ? property : camelize(property)] = declaration.slice(index + 1).trim();
    }

    return properties;
}

function camelize(name: string): string {
    return name.replace(/[-:]([a-z])/g, (_, letter: string) => letter.toUpperCase());
}
