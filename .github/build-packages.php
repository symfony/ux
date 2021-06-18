<?php

$dir = __DIR__.'/../src/LiveComponent';
$flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

$json = ltrim(file_get_contents($dir.'/composer.json'));
if (null === $package = json_decode($json)) {
    passthru("composer validate $dir/composer.json");
    exit(1);
}

$package->repositories[] = [
    'type' => 'path',
    'url' => '../TwigComponent',
];

$json = preg_replace('/\n    "repositories": \[\n.*?\n    \],/s', '', $json);
$json = rtrim(json_encode(['repositories' => $package->repositories], $flags), "\n}").','.substr($json, 1);
$json = preg_replace('/"symfony\/ux-twig-component": "(\^[\d]+\.[\d]+)"/s', '"symfony/ux-twig-component": "@dev"', $json);
file_put_contents($dir.'/composer.json', $json);

