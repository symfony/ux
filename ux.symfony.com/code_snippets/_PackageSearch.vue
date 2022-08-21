<template>
    <div>
        <input v-model="search" />

        <div class="row">
            <a v-for="uxPackage in filteredPackages" :href="uxPackage.url">
                <img :src="uxPackage.imageUrl" />
                <h4>{{ uxPackage.humanName }}</h4>
            </a>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    packages: Array,
});

const search = ref('');

const filteredPackages = computed(() => {
    return props.packages.filter(
        uxPackage => uxPackage.humanName.includes(search.value)
    );
});
</script>
