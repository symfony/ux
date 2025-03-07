<?php
declare(strict_types=1);

namespace Symfony\UX\Toolkit\Registry;

enum RegistryItemType: string
{
    case Component = 'component';
    case Example = 'example';
}
