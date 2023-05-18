<?php

namespace Symfony\UX\TwigComponent\Tests\Fixtures\Component;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent('Alarm', template: 'components/AAlarm.html.twig')]
class Alarm
{
}