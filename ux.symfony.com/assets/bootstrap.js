import { startStimulusApp } from '@symfony/stimulus-bridge';
import Clipboard from 'stimulus-clipboard'

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.[jt]sx?$/
));

app.debug = process.env.NODE_ENV === 'development';

app.register('clipboard', Clipboard);
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);

