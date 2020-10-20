<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

interface SchemaRegistryInterface
{
    /**
     * @param string $identifier
     * @return array<mixed>|null
     */
    public function getSchema(string $identifier): ?array;

    public function addSchema(array $record): void;
}
