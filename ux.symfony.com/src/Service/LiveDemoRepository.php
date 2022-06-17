<?php

namespace App\Service;

use App\Model\LiveDemo;

class LiveDemoRepository
{
    /**
     * @return array<LiveDemo>
     */
    public function findAll(): array
    {
        return [
            new LiveDemo(
                'auto-validating-form',
                name: 'Auto-Validating Form',
                description: 'Create a form that validates each field in-real-time as the user enters data!',
                route: 'app_live_components_demo_auto_validating_form',
            ),
            new LiveDemo(
                'form-collection-type',
                name: 'Embedded CollectionType Form',
                description: 'Create embedded forms with functional "add" and "remove" buttons all in Twig.',
                route: 'app_live_components_demo_form_collection_type',
            ),
            new LiveDemo(
                'dependent-form-fields',
                name: 'Dependent Form Fields',
                description: 'After selecting the first field, automatically reload the options for a second field.',
                route: 'app_live_components_demo_dependent_form_fields',
            ),
            new LiveDemo(
                'voting',
                name: 'Up & Down Voting',
                description: 'Save up & down votes live in pure Twig & PHP',
                route: 'app_live_components_demo_voting',
            ),
        ];
    }

    public function find(string $identifier): LiveDemo
    {
        $demos = $this->findAll();
        foreach ($demos as $demo) {
            if ($demo->getIdentifier() === $identifier) {
                return $demo;
            }
        }

        throw new \InvalidArgumentException(sprintf('Unknown demo "%s"', $identifier));
    }
}
