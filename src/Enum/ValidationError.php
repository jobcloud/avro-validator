<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Enum;

enum ValidationError: string
{
    case MISSING_FIELD = 'missingField';
    case WRONG_TYPE = 'wrongType';
}
