<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\RecordRegistryException;

final class RecordRegistry implements RecordRegistryInterface
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
            $this->addRecord($recordType);
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
    public function getRecord(string $identifier): ?array
    {
        if (isset($this->records[$identifier])) {
            return $this->records[$identifier];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $record
     * @throws RecordRegistryException
     */
    public function addRecord(array $record): void
    {
        $this->records[$this->determineRecordIdentifier($record)] = $record;
    }

    /**
     * @param array<string, mixed> $record
     * @return string
     * @throws RecordRegistryException
     */
    private function determineRecordIdentifier(array $record): string
    {
        $identifier = '';

        if (isset($record['namespace'])) {
            $identifier .= sprintf('%s.', $record['namespace']);
        }

        if (!isset($record['name'])) {
            throw new RecordRegistryException('Provided schema does not have a name');
        }

        $identifier .= $record['name'];

        return $identifier;
    }
}
