<?php
# Enable stateless CSRF protection for forms and logins/logouts

use App\Kernel;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $config) {
    if (Kernel::VERSION_ID >= 70000) {
        // Method "checkHeader" does not exist yet
        // $config->form()->csrfProtection()->checkHeader(true);
    }
};
