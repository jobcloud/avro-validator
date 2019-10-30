<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

final class RecordRegistry implements RecordRegistryInterface
{
    /**
     * @var array
     */
    private $records;

    /**
     * @param array $recordTypes
     */
    private function __construct(array $recordTypes)
    {
        $this->records = [];

        foreach ($recordTypes as $recordType) {
            $this->records[$recordType['namespace'] . '.' . $recordType['name']] = $recordType;
        }
    }

    /**
     * @param string $schema
     * @return static
     */
    public static function fromSchema(string $schema): self
    {
        return new self(json_decode($schema, true));
    }

    /**
     * @param string $type
     * @return array|null
     */
    public function getRecord(string $type): ?array
    {
        if (isset($this->records[$type])) {
            return $this->records[$type];
        }

        return null;
    }
}
