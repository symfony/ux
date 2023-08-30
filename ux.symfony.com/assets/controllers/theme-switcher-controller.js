import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    switch() {
        const theme = localStorage.getItem('user-theme') === 'dark' ? 'light' : 'dark';
        localStorage.setItem('user-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
}
