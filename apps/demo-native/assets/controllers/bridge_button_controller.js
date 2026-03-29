import { BridgeComponent } from '@hotwired/hotwire-native-bridge';

/*
 * The following line makes this controller "lazy": it won't be downloaded until needed
 * See https://github.com/symfony/stimulus-bridge#lazy-controllers
 */
/* stimulusFetch: 'lazy' */
export default class extends BridgeComponent {
    static component = 'button';

    connect() {
        super.connect();
        const title = this.bridgeElement.bridgeAttribute('title');

        this.send('connect', { title }, () => {
            this.bridgeElement.click();
        });
    }

    disconnect() {
        super.disconnect();
        this.send('disconnect');
    }
}
