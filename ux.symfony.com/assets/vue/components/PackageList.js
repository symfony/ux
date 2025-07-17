export default {
    props: ['packages'],
    template: `
            <div v-if="packages.length === 0">
                No packages found. Sad trombone...
            </div>

            <div v-else class="PackageList">
            
                    <div v-for="package in packages" class="PackageListItem">
                        <div class="PackageListItem__icon" :style="{'--gradient': package.gradient}">
                            <img :src="package.imageUrl" :alt="package.humanName">
                        </div>
                        <h4 class="PackageListItem__label">
                            <a :href="package.url">{{ package.humanName }}</a>
                        </h4>
                    </div>
                    
            </div>
    `
};
