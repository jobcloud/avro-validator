<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command\Formatter;

interface FormatterInterface
{
    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatSuccess(array $result): void;

    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatFail(array $result): void;
}
