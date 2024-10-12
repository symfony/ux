/**
 |  Single-File Components - e.g. PackageSearch.vue -
 |  are supported when using Webpack Encore.
 */
import { ref, computed } from 'vue';
import PackageList from "../components/PackageList.js";

export default {
    components: {
        PackageList
    },
    props: ['packages'],
    setup(props) {
        const search = ref('');

        const filteredPackages = computed(() => {
            return props.packages.filter(
                uxPackage => uxPackage.humanName.toLowerCase().includes(search.value.toLowerCase())
            );
        });

        return {
            search,
            filteredPackages
        }
    },
    template: `
        <div>
            <input
                v-model="search"
                class="form-control"
                type="search"
                placeholder="This search is built in Vue.js!"
            />

            <div class="mt-3">
                <PackageList :packages="filteredPackages" />
            </div>
        </div>
    `
};
