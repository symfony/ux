/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the reprise_entry_script_tags('app') Twig function,
 * which should already be in your base.html.twig.
 */
import { startStimulusApp } from '@symfony/reprise/stimulus';
import { registerVueControllerComponents } from '@symfony/ux-vue';
import { registerReactControllerComponents } from '@symfony/ux-react';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import { trans } from './translator.js';

registerReactControllerComponents(import.meta.glob('./react/controllers/**/*.{jsx,tsx}', { eager: true }));
registerVueControllerComponents(import.meta.glob('./vue/controllers/**/*.vue', { eager: true }));

startStimulusApp();

document.addEventListener('DOMContentLoaded', () => {
    console.log(trans('say_hello', { name: 'Fabien' }));
});
