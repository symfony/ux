/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the reprise_entry_script_tags('app') Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import { startStimulusApp } from '@symfony/reprise/stimulus';
import { registerReactControllerComponents } from '@symfony/ux-react';
import { registerVueControllerComponents } from '@symfony/ux-vue';

registerReactControllerComponents(import.meta.glob('./react/controllers/**/*.{jsx,tsx}', { eager: true }));
registerVueControllerComponents(import.meta.glob('./vue/controllers/**/*.vue', { eager: true }));

startStimulusApp();
