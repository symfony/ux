import { defineComponent, h, type PropType, type VNode } from 'vue';
import { mapNodes, resolveIcon, splitAttributes, type UXIconData } from './index';

/**
 * Renders a dumped icon, with the same attributes as the "ux_icon()" Twig function.
 *
 *     <script setup>
 *     import { UXIcon } from '@symfony/ux-icons/vue';
 *     import { LucideTrash2Icon } from '../var/icons/index.js';
 *     </script>
 *
 *     <UXIcon :icon="LucideTrash2Icon" class="text-danger" aria-label="Delete" />
 */
export const UXIcon = defineComponent({
    name: 'UXIcon',
    inheritAttrs: false,
    props: {
        icon: {
            type: Object as PropType<UXIconData>,
            required: true,
        },
    },
    setup(props, { attrs }) {
        return () => {
            const [attributes, passthrough] = splitAttributes(attrs);

            return h(
                'svg',
                { ...resolveIcon(props.icon, attributes), ...passthrough },
                mapNodes<VNode>(props.icon.children, (tag, attributes, children) => h(tag, attributes, children))
            );
        };
    },
});
