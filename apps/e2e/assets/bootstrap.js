import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
if (app.debug) {
    window.Stimulus = app;
}
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
