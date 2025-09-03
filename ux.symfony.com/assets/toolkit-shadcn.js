import './styles/toolkit-shadcn.css';
import { startStimulusApp } from '@symfony/stimulus-bundle';
import AlertDialog from '@symfony/ux-toolkit/kits/shadcn/AlertDialog/assets/controllers/alert_dialog_controller.js';

const app = startStimulusApp();
app.register('alert-dialog', AlertDialog);
