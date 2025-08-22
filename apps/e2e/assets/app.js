import { registerVueControllerComponents } from '@symfony/ux-vue';
import { registerSvelteControllerComponents } from '@symfony/ux-svelte';
import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import 'bootstrap/dist/css/bootstrap.min.css';
import './styles/app.css';

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');

registerReactControllerComponents();
registerSvelteControllerComponents();
registerVueControllerComponents();
