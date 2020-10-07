<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

final class Validator implements ValidatorInterface
{

    /**
     * @var RecordRegistryInterface
     */
    private $recordRegistry;

    /**
     * @param RecordRegistryInterface $recordRegistry
     */
    public function __construct(RecordRegistryInterface $recordRegistry)
    {
        $this->recordRegistry = $recordRegistry;
    }

    /**
     * @param string $payload
     * @param string $recordType
     * @return array<array<mixed>>
     * @throws ValidatorException
     */
    public function validate(string $payload, string $recordType): array
    {
        $decodedPayload = json_decode($payload, true);

        if (null === $recordSchema = $this->recordRegistry->getRecord($recordType)) {
            throw new MissingSchemaException(sprintf('Could not find record of type "%s"', $recordType));
        }

        if (!array_key_exists('fields', $recordSchema) || !is_array($recordSchema['fields'])) {
            throw new InvalidSchemaException('Schema does not have any fields defined');
        }

        $validationErrors = [];

        return $this->validateFields($recordSchema['fields'], $decodedPayload, '$', $validationErrors);
    }

    /**
     * @param array<array<mixed>> $schemaFields
     * @param array<mixed> $payload
     * @param string $path
     * @param array<array<mixed>> $validationErrors
     * @return array<array<mixed>>
     */
    private function validateFields(array $schemaFields, array $payload, string $path, array &$validationErrors): array
    {
        foreach ($schemaFields as $rule) {
            $fieldName = $rule['name'];

            if (false === array_key_exists($fieldName, $payload)) {
                $validationErrors[] = [
                    'path' => $path,
                    'message' => sprintf('Field "%s" is missing in payload', $fieldName),
                ];
                continue;
            }

            $types = isset($rule['type']['type']) ? [$rule['type']] : (array) $rule['type'];
            $fieldValue = $payload[$fieldName];
            $currentPath = $path . '.' . $fieldName;

            if (false === $this->checkFieldValueBeOneOf($types, $fieldValue, $currentPath, $validationErrors)) {
                $validationErrors[] = [
                    'path' => $currentPath,
                    'message' => sprintf(
                        'Field value was expected to be of type %s, but was "%s"',
                        $this->formatTypeList($types),
                        gettype($fieldValue)
                    ),
                    'value' => $fieldValue,
                ];
                continue;
            }
        }

        return $validationErrors;
    }

    /**
     * @param array<string> $types
     * @return string
     */
    private function formatTypeList(array $types): string
    {
        $lastEntry = array_pop($types);

        if (0 === count($types)) {
            return sprintf('"%s"', $lastEntry);
        }

        return sprintf('"%s" or "%s"', implode('", "', $types), $lastEntry);
    }

    /**
     * @param array<string> $types
     * @param mixed $fieldValue
     * @param string $currentPath
     * @param array<array<mixed>> $validationErrors
     * @return bool
     */
    private function checkFieldValueBeOneOf(
        array $types,
        $fieldValue,
        string $currentPath,
        array &$validationErrors
    ): bool {
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
                    $this->validateFields(
                        $recordSchema['fields'],
                        $value,
                        sprintf('%s[%s]', $currentPath, $key),
                        $validationErrors
                    );
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
