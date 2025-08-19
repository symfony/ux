<template>
    <div>Hello {{ name }}!</div>
</template>

<script setup>
    defineProps({
        name: String
    });
</script>
