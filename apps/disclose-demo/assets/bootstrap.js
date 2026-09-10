import { Application } from '@hotwired/stimulus';
import DiscloseController from '@symfony/ux-disclose';

const application = Application.start();
application.register('disclose', DiscloseController);
