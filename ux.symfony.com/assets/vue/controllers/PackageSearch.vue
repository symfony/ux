<template>
    <div>
        <input
            v-model="search"
            class="form-control"
            type="search"
            placeholder="This search is built in Vue!"
        />

        <div class="mt-3">
            <PackageList :packages="filteredPackages" />
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import PackageList from "../components/PackageList";

const props = defineProps({
    packages: Array,
});

const search = ref('');

const filteredPackages = computed(() => {
    return props.packages.filter(
        uxPackage => uxPackage.humanName.toLowerCase().includes(search.value.toLowerCase())
    );
});
</script>
