<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\RecordRegistryException;

interface RecordRegistryInterface
{
    /**
     * @param string $identifier
     * @return array<mixed>|null
     */
    public function getRecord(string $identifier): ?array;

    /**
     * @param array<string, mixed> $record
     * @throws RecordRegistryException
     */
    public function addRecord(array $record): void;
}
