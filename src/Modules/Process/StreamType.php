<?php

declare(strict_types=1);

namespace Jsadaa\PhpCoreLibrary\Modules\Process;

enum StreamType
{
    case PIPE;
    case FILE;
    case RESOURCE;
    case INHERIT;
    case NULL;
}
