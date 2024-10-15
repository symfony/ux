<script lang="ts" setup>
// Before Vue 3.4
import { computed } from 'vue';

declare interface Props {
    value1?: string;
    value2?: string;
    value3?: string;
}

declare interface Emits {
    (e: 'update:value1', value: string): unknown;
    (e: 'update:value2', value: string): unknown;
    (e: 'update:value3', value: string): unknown;
}

const props = withDefaults(defineProps<Props>(), {
    value1: '',
    value2: '',
    value3: '',
});
const emit = defineEmits<Emits>();

const useModel = <P extends Props, K extends keyof P, T extends Required<P>[K]>(propName: K) =>
    computed({
        get: (): T => props[propName],
        set: (value: T) => {
            emit(`update:${propName}`, value);
        },
    });

const value1 = useModel('value1');
const value2 = useModel('value2');
const value3 = useModel('value3');

// From Vue 3.4
// const value1 = defineModel('value1');
// const value2 = defineModel('value2');
// const value3 = defineModel('value3');
</script>

<template>
    <input data-testid="field-1" v-model="value1">
    <input data-testid="field-2" v-model="value2">
    <input data-testid="field-3" v-model="value3">
</template>
