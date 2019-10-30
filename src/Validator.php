<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

final class Validator implements ValidatorInterface
{

    /**
     * @var RecordRegistry
     */
    private $recordRegistry;

    /**
     * @param RecordRegistry $recordRegistry
     */
    public function __construct(RecordRegistry $recordRegistry)
    {
        $this->recordRegistry = $recordRegistry;
    }

    /**
     * @param string $payload
     * @param string $recordType
     * @return array
     */
    public function validate(string $payload, string $recordType): array
    {
        $decodedPayload = json_decode($payload, true);

        if (null === $recordSchema = $this->recordRegistry->getRecord($recordType)) {
            throw new \RuntimeException(sprintf('Could not find record of type %s', $recordType));
        }

        return $this->validateFields($recordSchema['fields'], $decodedPayload, '$', []);
    }

    private function validateFields(array $schemaFields, array $payload, string $path, array $validationErrors): array
    {
        foreach ($schemaFields as $rule) {
            $fieldName = $rule['name'];

            if (false === array_key_exists($fieldName, $payload)) {
                echo 'something is missing';
                $validationErrors[] = [
                    'path' => $path,
                    'message' => sprintf('Field "%s" missing in payload', $fieldName),
                ];
                continue;
            }

            $types = isset($rule['type']['type']) ? [$rule['type']] : (array) $rule['type'];
            $fieldValue = $payload[$fieldName];
            $currentPath = $path . '.' . $fieldName;

            if (false === $this->checkFieldValueBeOneOf($types, $fieldValue, $currentPath, $validationErrors)) {
                $validationErrors[] = [
                    'path' => $currentPath,
                    'message' => sprintf('Field value is not any of: %s', implode(', ', $types)),
                    'value' => $fieldValue,
                ];
                continue;
            }
        }

        return $validationErrors;
    }

    private function checkFieldValueBeOneOf(array $types, $fieldValue, string $currentPath, array $validationErrors): bool
    {
        foreach ($types as $type) {
            if ('null' === $type && null === $fieldValue) {
                return true;
            }

            if ('double' === $type && is_double($fieldValue)) {
                return true;
            }

            if ('int' === $type && is_int($fieldValue)) {
                return true;
            }

            if ('string' === $type && is_string($fieldValue)) {
                return true;
            }

            if (is_array($type) && 'array' === $type['type'] && is_array($fieldValue)) {
                $recordSchema = $this->recordRegistry->getRecord($type['items']);

                foreach ($fieldValue as $key => $value) {
                    $this->validateFields($recordSchema['fields'], $value, $currentPath . '['.$key.']', $validationErrors);
                }
                return true;
            }

            if (is_string($type) && null !== $recordSchema = $this->recordRegistry->getRecord($type)) {
                $this->validateFields($recordSchema['fields'], $fieldValue, $currentPath, $validationErrors);
                return true;
            }
        }

        return false;
    }
}
