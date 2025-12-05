<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\RecordRegistryException;

final class RecordRegistry implements RecordRegistryInterface
{
    /**
     * @var array<string, array<mixed>>
     */
    private array $records;

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

    public static function fromSchema(string $schema): self
    {
        return new self([json_decode($schema, true)]);
    }

    /**
     * @return array<mixed>|null
     */
    #[\Override]
    public function getRecord(string $identifier): ?array
    {
        return $this->records[$identifier] ?? null;
    }

    /**
     * @param array<string, mixed> $record
     * @throws RecordRegistryException
     */
    #[\Override]
    public function addRecord(array $record): void
    {
        $this->records[$this->determineRecordIdentifier($record)] = $record;
    }

    /**
     * @param array<string, mixed> $record
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

        return $identifier . $record['name'];
    }
}
