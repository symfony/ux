import { Popover, Toast, Tooltip } from 'bootstrap';

document.querySelectorAll('[data-bs-toggle="popover"]').forEach((element) => Popover.getOrCreateInstance(element));
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => Tooltip.getOrCreateInstance(element));

const liveToastTrigger = document.querySelector('#live-toast-trigger');
const liveToast = document.querySelector('#live-toast');

if (liveToastTrigger && liveToast) {
    liveToastTrigger.addEventListener('click', () => Toast.getOrCreateInstance(liveToast).show());
}
