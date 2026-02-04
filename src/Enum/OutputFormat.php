<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Enum;

enum OutputFormat: string
{
    case PRETTY = 'pretty';
    case JSON = 'json';
}
