/*
 * Welcome to your app's main JavaScript file!
 *
 * We recommend including the built version of this JavaScript file
 * (and its CSS file) in your base layout (base.html.twig).
 */
import { startStimulusApp } from '@symfony/stimulus-bridge';
import { registerVueControllerComponents } from '@symfony/ux-vue';
import { registerReactControllerComponents } from '@symfony/ux-react';

// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.css';
import { trans } from './translator';

registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
registerVueControllerComponents(require.context('./vue/controllers', true, /\.vue$/));

startStimulusApp(require.context('@symfony/stimulus-bridge/lazy-controller-loader!./controllers', true, /\.[jt]sx?$/));

document.addEventListener('DOMContentLoaded', () => {
    console.log(trans('say_hello', { name: 'Fabien' }));
});
