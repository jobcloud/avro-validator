<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Enum\ValidationError;
use Jobcloud\Avro\Validator\Exception\InvalidSchemaException;
use Jobcloud\Avro\Validator\Exception\MissingSchemaException;
use Jobcloud\Avro\Validator\Exception\RecordRegistryException;
use Jobcloud\Avro\Validator\Exception\UnsupportedTypeException;
use Jobcloud\Avro\Validator\Exception\ValidatorException;

final readonly class Validator implements ValidatorInterface
{
    /**
     * @deprecated Use ValidationError::MISSING_FIELD->value instead. Will be removed in next major version.
     */
    public const string ERROR_TYPE_MISSING_FIELD = 'missingField';

    /**
     * @deprecated Use ValidationError::WRONG_TYPE->value instead. Will be removed in next major version.
     */
    public const string ERROR_TYPE_WRONG_TYPE = 'wrongType';

    /**
     * Lower bound of integer values: -(1 << 31)
     */
    private const int INT_MIN_VALUE = -2147483648;

    /**
     * Upper bound of integer values: (1 << 31) - 1
     */
    private const int INT_MAX_VALUE = 2147483647;

    /**
     * Lower bound of long values: -(1 << 63)
     */
    private const float LONG_MIN_VALUE = -9223372036854775808;

    /**
     * Upper bound of long values: (1 << 63) - 1
     */
    private const int LONG_MAX_VALUE = 9223372036854775807;

    public function __construct(private RecordRegistryInterface $recordRegistry)
    {
    }

    /**
     * @return array<array<mixed>>
     * @throws ValidatorException
     * @throws RecordRegistryException
     */
    #[\Override]
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
     * @param array<array<mixed>> $validationErrors
     * @return array<array<mixed>>
     * @throws UnsupportedTypeException
     * @throws RecordRegistryException
     */
    private function validateFields(array $schemaFields, array $payload, string $path, array &$validationErrors): array
    {
        foreach ($schemaFields as $rule) {
            $fieldName = $rule['name'];

            if (false === array_key_exists($fieldName, $payload) && false == array_key_exists('default', $rule)) {
                $validationErrors[] = [
                    'path' => $path,
                    'type' => ValidationError::MISSING_FIELD->value,
                    'message' => sprintf('Field "%s" is missing in payload', $fieldName),
                ];
                continue;
            }

            $types = isset($rule['type']['type']) ? [$rule['type']] : (array) $rule['type'];
            $fieldValue = array_key_exists($fieldName, $payload) ? $payload[$fieldName] : $rule['default'];
            $currentPath = $path . '.' . $fieldName;

            if (false === $this->checkFieldValueBeOneOf($types, $fieldValue, $currentPath, $validationErrors)) {
                $validationErrors[] = $this->createValidationError(
                    $currentPath,
                    ValidationError::WRONG_TYPE->value,
                    $types,
                    $fieldValue
                );
            }
        }

        return $validationErrors;
    }

    /**
     * @param array<string|array<string, mixed>> $types
     */
    private function formatTypeList(array $types): string
    {
        $normalizedTypes = array_map([$this, 'getTypeAsString'], $types);

        $lastEntry = array_pop($normalizedTypes);

        if (0 === count($normalizedTypes)) {
            return sprintf('"%s"', $lastEntry);
        }

        return sprintf('"%s" or "%s"', implode('", "', $normalizedTypes), $lastEntry);
    }

    /**
     * @param array<string|array<string, mixed>> $types
     * @param array<array<mixed>> $validationErrors
     * @throws UnsupportedTypeException
     * @throws RecordRegistryException
     */
    private function checkFieldValueBeOneOf(
        array $types,
        mixed $fieldValue,
        string $currentPath,
        array &$validationErrors
    ): bool {
        $scalarTypes = [
            'null' => 'is_null',
            'int' => static fn($value): bool => is_int($value)
                && self::INT_MIN_VALUE <= $value && $value <= self::INT_MAX_VALUE,
            'long' => static fn($value): bool => is_int($value)
                && self::LONG_MIN_VALUE <= $value && $value <= self::LONG_MAX_VALUE,
            'string' => 'is_string',
            'boolean' => 'is_bool',
            'float' => 'is_float',
            'double' => 'is_double',
        ];

        foreach ($types as $type) {
            if (is_string($type) && isset($scalarTypes[$type])) {
                if ($scalarTypes[$type]($fieldValue)) {
                    return true;
                }

                continue;
            }

            if (in_array($type, ['enum', 'map', 'bytes'], true)) {
                throw new UnsupportedTypeException(sprintf(
                    'The type "%d" is currently not supported by this validator',
                    $type
                ));
            }

            if (is_array($type)) {
                if ('array' === $type['type'] && is_array($fieldValue)) {
                    $types = (array) $type['items'];

                    if (isset($types['type']) && 'record' === $types['type']) {
                        $types = [$types];
                    }

                    foreach ($fieldValue as $key => $value) {
                        $itemPath = sprintf('%s[%s]', $currentPath, $key);
                        if (false === $this->checkFieldValueBeOneOf($types, $value, $itemPath, $validationErrors)) {
                            $validationErrors[] = $this->createValidationError(
                                $itemPath,
                                ValidationError::WRONG_TYPE->value,
                                $types,
                                $value
                            );
                        }
                    }

                    return true;
                } elseif ('record' === $type['type'] && isset($type['fields'])) {
                    // Inlined schema
                    if (null === $subRecord = $this->recordRegistry->getRecord($type['name'])) {
                        $subRecord = $type;
                        $this->recordRegistry->addRecord($type);
                    }

                    $validationErrorsSub = [];
                    $this->validateFields($subRecord['fields'], $fieldValue, $currentPath, $validationErrorsSub);

                    if ($this->hasOnlyMissingFields($validationErrorsSub)) {
                        continue;
                    }

                    $validationErrors = array_merge($validationErrors, $validationErrorsSub);

                    return true;
                }
            }

            if (is_string($type) && null !== $recordSchema = $this->recordRegistry->getRecord($type)) {
                $validationErrorsSub = [];
                $this->validateFields($recordSchema['fields'], $fieldValue, $currentPath, $validationErrorsSub);

                if ($this->hasOnlyMissingFields($validationErrorsSub)) {
                    continue;
                }

                $validationErrors = array_merge($validationErrors, $validationErrorsSub);

                return true;
            }
        }

        return false;
    }

    /**
     * @param array<array<string, mixed>> $validationErrors
     */
    private function hasOnlyMissingFields(array $validationErrors): bool
    {
        if (0 === count($validationErrors)) {
            return false;
        }

        foreach ($validationErrors as $validationError) {
            if (ValidationError::MISSING_FIELD->value !== $validationError['type']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<string> $types
     * @return array<string, mixed>
     */
    private function createValidationError(string $path, string $errorType, array $types, mixed $value): array
    {
        return [
            'path' => $path,
            'type' => $errorType,
            'message' => sprintf(
                'Field value was expected to be of type %s, but was "%s"',
                $this->formatTypeList($types),
                $this->getType($value)
            ),
            'value' => $value,
        ];
    }

    private function getType(mixed $value): string
    {
        $type = gettype($value);

        if ('integer' === $type) {
            return ($value > self::INT_MAX_VALUE || $value < self::INT_MIN_VALUE) ? 'long' : 'int';
        }

        return $type;
    }

    private function getTypeAsString(mixed $type): string
    {
        if (!is_array($type)) {
            return $type;
        }

        if ('array' === $type['type']) {
            return sprintf('%s<%s>', $type['type'], $this->getTypeAsString($type['items']));
        }

        if ('record' === $type['type']) {
            return $type['name'];
        }

        throw new \InvalidArgumentException('Could not determine name for type');
    }
}
