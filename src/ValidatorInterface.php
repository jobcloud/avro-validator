<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

interface ValidatorInterface
{
    /**
     * @return array<array<mixed>>
     */
    public function validate(string $payload, string $recordType): array;
}
