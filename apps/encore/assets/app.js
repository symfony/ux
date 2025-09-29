/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
import { registerVueControllerComponents } from '@symfony/ux-vue';
import { registerSvelteControllerComponents } from '@symfony/ux-svelte';
import { registerReactControllerComponents } from '@symfony/ux-react';
import './bootstrap.js';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import { THIS_FIELD_IS_MISSING, trans } from './translator';

registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
registerSvelteControllerComponents(require.context('./svelte/controllers', true, /\.svelte$/));
registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));

document.addEventListener('DOMContentLoaded', () => {
    console.log(trans(THIS_FIELD_IS_MISSING));
})
