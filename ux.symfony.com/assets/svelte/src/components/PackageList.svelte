<script lang='ts'>
    import { fade } from 'svelte/transition';
    import { flip } from 'svelte/animate';

    export let packages = [];
    let transitionDuration = 200;
</script>

{#if packages.length === 0}
    <div class='alert alert-info'>Sad trombone... we haven't built any components that
        match this search yet!
    </div>
{:else}
    <div class='row'>
        {#each packages as uxPackage(uxPackage.name)}
            <a
                href='{uxPackage.url}'
                class='col-12 col-md-4 col-lg-3 mb-2'
                animate:flip={{duration: transitionDuration}} in:fade={{duration:transitionDuration}}
            >
                <div class='components-container p-2'>
                    <div class='d-flex'>
                        <div
                            class='live-component-img d-flex justify-content-center align-items-center'
                            style='background: {uxPackage.gradient}'>
                            <img width='17' height='17'
                                 src='{uxPackage.imageUrl}'
                                 alt='`Image for the {uxPackage.humanName} UX package`'
                            />
                        </div>
                        <h4 class='ubuntu-title ps-2 align-self-center'>
                            { uxPackage.humanName }
                        </h4>
                    </div>
                </div>
            </a>
        {/each}
    </div>
{/if}
