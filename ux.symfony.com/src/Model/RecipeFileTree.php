<?php

namespace App\Model;

class RecipeFileTree
{
    private array $files = [];

    public function __construct()
    {
        $this
            ->addDirectory('assets')
            ->addFile('assets/bootstrap.js', 'Starts Stimulus & registers all files in <code>controllers/</code> as Stimulus controllers.')
            ->addFile('assets/app.js', 'Your main JavaScript file. It\'s job is to import and load all other files.')
            ->addFile('assets/controllers.json', 'Configures 3rd-party Stimulus controllers. This file is automatically updated when you install a UX package.')
            ->addDirectory('assets/controllers', 'The home of your custom Stimulus controllers!')
            ->addFile('assets/controllers/hello_controller.js', 'An example controller. Add it to any element with <code class="text-nowrap">{{ stimulus_controller(\'hello\') }}</code>')
            ->addDirectory('assets/styles')
            ->addFile('assets/styles/app.css', 'Your main CSS file')
            ->addFile('package.json', 'Holds your node dependencies, most importantly Stimulus & Webpack Encore.')
            ->addFile('webpack.config.js', 'Configuration file for Webpack Encore: the tool that processes and combines all of your CSS and JS files.')
        ;
    }

    private function addFile(string $filename, string $description = null): self
    {
        $this->files[$filename] = ['type' => 'file', 'description' => $description];

        return $this;
    }

    private function addDirectory(string $path, string $description = null): self
    {
        $this->files[$path] = ['type' => 'directory', 'description' => $description];

        return $this;
    }

    public function getFiles(): array
    {
        return $this->buildFileTree('');
    }

    public function buildFileTree(string $targetDirectory): array
    {
        $files = [];
        foreach ($this->files as $path => $info) {
            // skip files that are not even at the correct level
            if (substr_count($targetDirectory, '/') !== substr_count($path, '/')) {
                continue;
            }

            // skip files that are not in the target directory
            if ($targetDirectory && !str_starts_with($path, $targetDirectory)) {
                continue;
            }

            // skip yourself
            if ($targetDirectory && rtrim($path, '/') === $targetDirectory) {
                continue;
            }

            $isDirectory = 'directory' === $info['type'];

            $files[$path] = [
                'filename' => $targetDirectory ? substr($path, \strlen($targetDirectory)) : $path,
                'isDirectory' => $isDirectory,
                'description' => $info['description'],
            ];

            if ($isDirectory) {
                $files[$path]['files'] = $this->buildFileTree($path.'/');
            }
        }

        return $files;
    }
}
