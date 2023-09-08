import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    switch() {
        let currentTheme = localStorage.getItem('user-theme');
        if (!currentTheme) {
            currentTheme = document.documentElement.getAttribute('data-bs-theme');
        }

        const theme = currentTheme === 'dark' ? 'light' : 'dark';
        localStorage.setItem('user-theme', theme);
        document.documentElement.setAttribute('data-bs-theme', theme);
    }
}
