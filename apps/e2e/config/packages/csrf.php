<?php
# Enable stateless CSRF protection for forms and logins/logouts

use App\Kernel;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config) {
    if (Kernel::VERSION_ID >= 70000) {
        // Enable CSRF protection for forms
        $config->form()->csrfProtection()->tokenId('submit');

        // Enable stateless CSRF protection for specific actions
        $config->csrfProtection()
            ->statelessTokenIds(['submit', 'authenticate', 'logout']);
    }
};
