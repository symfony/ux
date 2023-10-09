import './styles/app.scss';
import { registerReactControllerComponents } from '@symfony/ux-react';
import {registerVueControllerComponents} from "@symfony/ux-vue";
import { registerSvelteControllerComponents } from "@symfony/ux-svelte";

// start the Stimulus application
import './bootstrap.js';

// imported to initialize global plugins
// dropdown, collapse, tab
import * as bootstrap from 'bootstrap';

registerReactControllerComponents();
registerVueControllerComponents();
registerSvelteControllerComponents();
