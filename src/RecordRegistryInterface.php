<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

interface RecordRegistryInterface
{
    /**
     * @param string $type
     * @return array<mixed>|null
     */
    public function getRecord(string $type): ?array;
}
