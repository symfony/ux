<?php
# Enable stateless CSRF protection for forms and logins/logouts

use App\Kernel;

if (Kernel::VERSION_ID >= 80000) {
    return Symfony\Component\DependencyInjection\Loader\Configurator\App::config([
        'framework' => [
            'form' => [
                'csrf_protection' => [
                    'token_id' => 'submit',
                ],
            ],
            'csrf_protection' => [
                'stateless_token_ids' => ['submit', 'authenticate', 'logout']
            ]
        ]
    ]);
} else if (Kernel::VERSION_ID >= 70000) {
    return static function (Symfony\Config\FrameworkConfig $config) {
        // Enable CSRF protection for forms
        $config->form()->csrfProtection()->tokenId('submit');

        // Enable stateless CSRF protection for specific actions
        $config->csrfProtection()
            ->statelessTokenIds(['submit', 'authenticate', 'logout']);
    };
}
