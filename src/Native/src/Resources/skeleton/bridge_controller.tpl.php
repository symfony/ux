import { BridgeComponent } from "@hotwired/hotwire-native-bridge"

/**
 * Hotwire Native Bridge Controller
 * 
 * This controller connects your web application to the Hotwire Native app
 * by extending the BridgeComponent from hotwire-native-bridge.
 * 
 * For more information, visit: https://native.hotwired.dev/reference/bridge-installation
 */
export default class extends BridgeComponent {
    static component = "<?php echo $name; ?>"
    
    connect() {
        super.connect()
        console.debug("Hotwire Native Bridge connected")
        
        // The bridge is now ready and will handle communication
        // between your web app and the native application
        
        // You can add custom event handlers or additional setup here
    }
    
    disconnect() {
        super.disconnect()
        console.debug("Hotwire Native Bridge disconnected")
        
        // Clean up any resources or event listeners here
    }
}
