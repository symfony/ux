<script>
    import PackageList from '../components/PackageList.js';

    export let packages = [];
    let search = '';

    $: filteredPackages = packages.filter(
        uxPackage => uxPackage.humanName.toLowerCase().includes(search.toLowerCase())
    );
</script>

<div>
    <input
        bind:value={search}
        class='form-control'
        type='search'
        placeholder='This search is built in Svelte!'
    />
    <div class='mt-3'>
        <PackageList packages={filteredPackages} />
    </div>
</div>
