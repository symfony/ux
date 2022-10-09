<script>
    import { fade } from "svelte/transition"
    export let name = "without props";
</script>

<div transition:fade={{ duration: 100 }}>
    <div>Hello {name}</div>
</div>