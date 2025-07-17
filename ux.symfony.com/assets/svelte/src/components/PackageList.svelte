<script lang='ts'>
    import { fade } from 'svelte/transition';
    import { flip } from 'svelte/animate';

    export let packages = [];
    let transitionDuration = 200;
</script>

{#if packages.length === 0}
    No packages found
{:else}
    <div class="PackageList">
        {#each packages as uxPackage(uxPackage.name)}
            <div class="PackageListItem"
                animate:flip={{duration: transitionDuration}} in:fade={{duration:transitionDuration}}
            >
                <div class="PackageListItem__icon" style="--gradient: {uxPackage.gradient};">
                    <img src="{uxPackage.imageUrl}" alt="Image for the {uxPackage.humanName} UX package" />
                </div>
                <h3 class="PackageListItem__label">
                    <a href="{uxPackage.url}">{uxPackage.humanName}</a>
                </h3>
            </div>
        {/each}
    </div>
{/if}
