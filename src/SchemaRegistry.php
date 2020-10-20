<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\SchemaRegistryException;

final class SchemaRegistry implements SchemaRegistryInterface
{
    /**
     * @var array<string, array<mixed>>
     */
    private $records;

    /**
     * @param array<array<mixed>> $recordTypes
     */
    private function __construct(array $recordTypes)
    {
        $this->records = [];

        foreach ($recordTypes as $recordType) {
            $this->addSchema($recordType);
        }
    }

    /**
     * @param string $schema
     * @return self
     */
    public static function fromSchema(string $schema): self
    {
        return new self([json_decode($schema, true)]);
    }

    /**
     * @param string $identifier
     * @return array<mixed>|null
     */
    public function getSchema(string $identifier): ?array
    {
        if (isset($this->records[$identifier])) {
            return $this->records[$identifier];
        }

        return null;
    }

    /**
     * @param array $record
     * @throws SchemaRegistryException
     */
    public function addSchema(array $record): void
    {
        $this->records[$this->determineRecordIdentifier($record)] = $record;
    }

    private function determineRecordIdentifier(array $record): string
    {
        $identifier = '';

        if (isset($record['namespace'])) {
            $identifier .= sprintf('%s.', $record['namespace']);
        }

        if (!isset($record['name'])) {
            throw new SchemaRegistryException('Provided schema does not have a name');
        }

        $identifier .= $record['name'];

        return $identifier;
    }
}
