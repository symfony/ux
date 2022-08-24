<template>
    <div v-if="packages.length === 0" class="alert alert-info">Sad trombone... we haven't built any components that
        match this search yet!
    </div>

    <div v-else class="row">
        <a
            v-for="uxPackage in packages"
            :href="uxPackage.url"
            class="col-12 col-md-4 col-lg-3 mb-2"
        >
            <div class="components-container p-2">
                <div class="d-flex">
                    <div
                        class="live-component-img d-flex justify-content-center align-items-center"
                        :style="{background: uxPackage.gradient}">
                        <img width="17" height="17"
                             :src="uxPackage.imageUrl"
                             :alt="`Image for the ${uxPackage.humanName} UX package`"
                        />
                    </div>
                    <h4 class="ubuntu-title ps-2 align-self-center">
                        {{ uxPackage.humanName }}
                    </h4>
                </div>
            </div>
        </a>
    </div>
</template>

<script setup>
defineProps({
    packages: Array,
});
</script>
