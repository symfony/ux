<?php

declare(strict_types=1);

namespace src\Enum;

enum TurboAction: string
{
    case APPEND = 'append';
    case PREPEND = 'prepend';
    case REPLACE = 'replace';
    case UPDATE = 'update';
    case REMOVE = 'remove';
    case BEFORE = 'before';
    case AFTER = 'after';
    case REFRESH = 'refresh';
}
