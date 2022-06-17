import { registerReactControllerComponents } from '@symfony/ux-react';


// any CSS you import will output into a single css file (app.css in this case)
import './styles/app.scss';

// start the Stimulus application
import './bootstrap';

// imported to initialize global plugins
import Dropdown from 'bootstrap/js/dist/dropdown';
import Collapse from 'bootstrap/js/dist/collapse';

// initialize symfony/ux-react
registerReactControllerComponents(require.context('./react/controllers', true, /\.(j|t)sx?$/));
