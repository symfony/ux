import './styles/toolkit-shadcn.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import AlertDialog from '@symfony/ux-toolkit/kits/shadcn/alert-dialog/assets/controllers/alert_dialog_controller.js';
import Dialog from '@symfony/ux-toolkit/kits/shadcn/dialog/assets/controllers/dialog_controller.js';

const app = startStimulusApp();
app.register('alert-dialog', AlertDialog);
app.register('dialog', Dialog);
