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

    /**
     * @throws RecordRegistryException
     */
    public static function fromSchema(string $schema): self
    {
        try {
            $decoded = json_decode($schema, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new RecordRegistryException(
                sprintf('Failed to decode schema: %s', $e->getMessage()),
            );
        }

        if (!is_array($decoded)) {
            throw new RecordRegistryException(
                sprintf('Schema must be a JSON object or array, %s given.', get_debug_type($decoded))
            );
        }

        return new self(isset($decoded[0]) && is_array($decoded[0]) ? $decoded : [$decoded]);
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
