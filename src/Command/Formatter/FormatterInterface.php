<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command\Formatter;

interface FormatterInterface
{
    public function formatSuccess(array $result): void;

    public function formatFail(array $result): void;
}
