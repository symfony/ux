import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {

    connect() {
        document.body.dataset.iconSize = localStorage.getItem('icon-size') === 'large' ? 'large' : 'small';
    }

    large() {
        document.body.dataset.iconSize = 'large';
        localStorage.setItem('icon-size', 'large');
    }

    small() {
        document.body.dataset.iconSize = 'small';
        localStorage.setItem('icon-size', 'small');
    }
}
