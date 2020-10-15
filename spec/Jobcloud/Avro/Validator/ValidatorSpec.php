<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\InvalidSchemaException;
use Jobcloud\Avro\Validator\Exception\MissingSchemaException;
use Jobcloud\Avro\Validator\Exception\UnsupportedTypeException;
use Jobcloud\Avro\Validator\RecordRegistryInterface;
use Jobcloud\Avro\Validator\Validator;
use PhpSpec\ObjectBehavior;

final class ValidatorSpec extends ObjectBehavior
{
    public function let(RecordRegistryInterface $recordRegistry): void
    {
        $this->beConstructedWith($recordRegistry);
    }

    public function it_throws_exception_on_missing_schema(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn(null);

        $this->shouldThrow(MissingSchemaException::class)->during('validate', ['{}', $schemaName]);
    }

    public function it_throws_exception_on_invalid_schema(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([]);

        $this->shouldThrow(InvalidSchemaException::class)->during('validate', ['{}', $schemaName]);
    }

    public function it_detects_missing_field(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
            ],
        ]);

        $payload = [];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$',
                'message' => 'Field "id" is missing in payload',
            ],
        ]);
    }

    public function it_detects_wrong_field_values(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn($this->getSampleSchema());
        $recordRegistry->getRecord('array')->willReturn(null);

        $invalidStringValue = 42;
        $invalidIntValue = 'foo';
        $invalidDoubleValue = 42;
        $invalidArrayValue = 42;
        $invalidNullOrArrayValue = 42;

        $payload = [
            'stringTest' => $invalidStringValue,
            'intTest' => $invalidIntValue,
            'doubleTest' => $invalidDoubleValue,
            'arrayTest' => $invalidArrayValue,
            'multipleTypeTest' => $invalidNullOrArrayValue,
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.stringTest',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => $invalidStringValue,
            ],
            [
                'path' => '$.intTest',
                'message' => 'Field value was expected to be of type "int", but was "string"',
                'value' => $invalidIntValue,
            ],
            [
                'path' => '$.doubleTest',
                'message' => 'Field value was expected to be of type "double", but was "int"',
                'value' => $invalidDoubleValue,
            ],
            [
                'path' => '$.arrayTest',
                'message' => 'Field value was expected to be of type "array", but was "int"',
                'value' => $invalidArrayValue,
            ],
            [
                'path' => '$.multipleTypeTest',
                'message' => 'Field value was expected to be of type "null" or "array", but was "int"',
                'value' => $invalidNullOrArrayValue,
            ],
        ]);
    }

    public function it_validates_array_items(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'arrayTest',
                    'type' => [
                        'type' => 'array',
                        'items' => 'string',
                    ],
                ],
            ],
        ]);

        $invalidArrayItemValue = 42;

        $payload = [
            'arrayTest' => [$invalidArrayItemValue],
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.arrayTest[0]',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => $invalidArrayItemValue,
            ],
        ]);
    }

    public function it_throws_exception_for_unsupported_type_enum(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'enumTest',
                    'type' => 'enum',
                ],
            ],
        ]);

        $payload = [
            'enumTest' => 42,
        ];

        $this->shouldThrow(UnsupportedTypeException::class)->during('validate', [
            $this->encodePayload($payload),
            $schemaName
        ]);
    }

    public function it_validates_sub_schemas(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'subSchema',
                    'type' => 'foo.bar.boo',
                ],
            ],
        ]);
        $recordRegistry->getRecord('foo.bar.boo')->willReturn([
            'type' => 'record',
            'name' => 'boo',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
            ],
        ]);

        $invalidArrayItemValue = 42;

        $payload = [
            'subSchema' => [
                'id' => $invalidArrayItemValue
            ],
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.subSchema.id',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => $invalidArrayItemValue,
            ],
        ]);
    }

    public function it_does_not_report_errors_for_valid_data(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn($this->getSampleSchema());

        $payload = [
            'stringTest' => 'foo',
            'intTest' => 42,
            'doubleTest' => 42.0,
            'arrayTest' => ['foo', 'bar'],
            'multipleTypeTest' => null,
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([]);
    }

    private function getSampleSchema(): array
    {
        return [
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'stringTest',
                    'type' => 'string',
                ],
                [
                    'name' => 'intTest',
                    'type' => 'int',
                ],
                [
                    'name' => 'doubleTest',
                    'type' => 'double',
                ],
                [
                    'name' => 'arrayTest',
                    'type' => [
                        'type' => 'array',
                        'items' => 'string',
                    ],
                ],
                [
                    'name' => 'multipleTypeTest',
                    'type' => ['null', 'array'],
                ],
            ],
        ];
    }

    private function encodePayload(array $payload): string
    {
        return json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION);
    }
}
