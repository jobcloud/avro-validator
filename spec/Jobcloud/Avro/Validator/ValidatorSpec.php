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

    public function it_detects_missing_fields(RecordRegistryInterface $recordRegistry): void
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
                [
                    'name' => 'age',
                    'type' => 'int',
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
                'type' => 'missingField',
                'message' => 'Field "id" is missing in payload',
            ],
            [
                'path' => '$',
                'type' => 'missingField',
                'message' => 'Field "age" is missing in payload',
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
        $invalidLongValue = 'long';
        $invalidDoubleValue = 42;
        $invalidArrayValue = 42;
        $invalidNullOrArrayValue = 42;

        $payload = [
            'stringTest' => $invalidStringValue,
            'intTest' => $invalidIntValue,
            'longTest' => $invalidLongValue,
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
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => $invalidStringValue,
            ],
            [
                'path' => '$.intTest',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "int", but was "string"',
                'value' => $invalidIntValue,
            ],
            [
                'path' => '$.longTest',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "long", but was "string"',
                'value' => $invalidLongValue,
            ],
            [
                'path' => '$.doubleTest',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "double", but was "int"',
                'value' => $invalidDoubleValue,
            ],
            [
                'path' => '$.arrayTest',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "array<string>", but was "int"',
                'value' => $invalidArrayValue,
            ],
            [
                'path' => '$.multipleTypeTest',
                'type' => 'wrongType',
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
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => $invalidArrayItemValue,
            ],
        ]);
    }

    public function it_validates_array_items_record(RecordRegistryInterface $recordRegistry): void
    {
        $nestedRecord = [
            'type' => 'record',
            'name' => 'bazoo',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'nestedRecord',
                    'type' => 'string'
                ]
            ]
        ];

        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord('bazoo')->willReturn(null);
        $recordRegistry->addRecord($nestedRecord);
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'arrayTest',
                    'type' => [
                        'type' => 'array',
                        'items' => $nestedRecord,
                    ],
                ],
            ],
        ]);

        $payload = [
            'arrayTest' => [
                [
                    'nestedRecord' => 1
                ]
            ],
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.arrayTest[0].nestedRecord',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "string", but was "int"',
                'value' => 1,
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

    public function it_validates_union_sub_schemas(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'subSchema',
                    'type' => ['wrong.sub.schema', 'foo.bar.boo'],
                ],
            ],
        ]);
        $recordRegistry->getRecord('wrong.sub.schema')->willReturn([
            'type' => 'record',
            'name' => 'schema',
            'namespace' => 'wrong.sub',
            'fields' => [
                [
                    'name' => 'age',
                    'type' => 'int',
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
                'type' => 'wrongType',
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
            'longTest' => 1234567890,
            'doubleTest' => 42.0,
            'arrayTest' => ['foo', 'bar'],
            'multipleTypeTest' => null,
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([]);
    }

    public function it_detects_wrong_int_vs_long_values(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'minLongButIsInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'maxLongButIsInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'minIntPlusOneButIsInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'maxIntPlusOneButIsInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'minInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'maxInt',
                    'type' => 'int',
                ],
                [
                    'name' => 'intInLong',
                    'type' => 'long',
                ],
            ],
        ]);

        $invalidMinLongButIsIntValue = intval(-9223372036854775808);
        $invalidMaxLongButIsIntValue = 9223372036854775807;
        $invalidMinIntPlusOneButIsInt = -2147483649;
        $invalidMaxIntPlusOneButIsInt = 2147483648;
        $minInt = -2147483648;
        $maxInt = 2147483647;

        $payload = [
            'minLongButIsInt' => $invalidMinLongButIsIntValue,
            'maxLongButIsInt' => $invalidMaxLongButIsIntValue,
            'minIntPlusOneButIsInt' => $invalidMinIntPlusOneButIsInt,
            'maxIntPlusOneButIsInt' => $invalidMaxIntPlusOneButIsInt,
            'maxInt' => $minInt,
            'minInt' => $maxInt,
            'intInLong' => 42,
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.minLongButIsInt',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "int", but was "long"',
                'value' => $invalidMinLongButIsIntValue,
            ],
            [
                'path' => '$.maxLongButIsInt',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "int", but was "long"',
                'value' => $invalidMaxLongButIsIntValue,
            ],
            [
                'path' => '$.minIntPlusOneButIsInt',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "int", but was "long"',
                'value' => $invalidMinIntPlusOneButIsInt,
            ],
            [
                'path' => '$.maxIntPlusOneButIsInt',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "int", but was "long"',
                'value' => $invalidMaxIntPlusOneButIsInt,
            ],
        ]);
    }

    public function it_handles_inlined_schemas(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $inlinedSchemaName = 'account';
        $inlinedSchemaName2 = 'foo';

        $inlinedSchema = [
            'type' => 'record',
            'name' => $inlinedSchemaName,
            'fields' => [
                [
                    'name' => 'accountId',
                    'type' => 'string',
                ],
            ],
        ];

        $inlinedSchema2 = [
            'type' => 'record',
            'name' => $inlinedSchemaName2,
            'fields' => [
                [
                    'name' => 'missingField',
                    'type' => 'string',
                ],
            ],
        ];

        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'account1',
                    'type' => $inlinedSchema,
                ],
                [
                    'name' => 'account2',
                    'type' => [
                        $inlinedSchema2,
                        $inlinedSchemaName,
                    ],
                ],
            ],
        ]);
        $recordRegistry->getRecord($inlinedSchemaName)->shouldBeCalled()->willReturn(null, $inlinedSchema);
        $recordRegistry->addRecord($inlinedSchema)->shouldBeCalled();
        $recordRegistry->getRecord($inlinedSchemaName2)->shouldBeCalled()->willReturn(null);
        $recordRegistry->addRecord($inlinedSchema2)->shouldBeCalled();

        $payload = [
            'account1' => [
                'accountId' => 'foobar',
            ],
            'account2' => [
                'accountId' => 'baz',
            ],
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([]);
    }

    public function it_formats_inline_schema_type_correctly(RecordRegistryInterface $recordRegistry): void
    {
        $schemaName = 'foo.bar.baz';
        $inlinedSchemaName = 'account';

        $inlinedSchema = [
            'type' => 'record',
            'name' => $inlinedSchemaName,
            'fields' => [
                [
                    'name' => 'accountId',
                    'type' => 'string',
                ],
            ],
        ];

        $recordRegistry->getRecord($schemaName)->willReturn([
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'account',
                    'type' => $inlinedSchema,
                ],
            ],
        ]);
        $recordRegistry->getRecord($inlinedSchemaName)->shouldBeCalled()->willReturn(null, $inlinedSchema);
        $recordRegistry->addRecord($inlinedSchema)->shouldBeCalled();

        $invalidValue = [
            'invalidField' => 'foobar',
        ];

        $payload = [
            'account' => $invalidValue,
        ];

        $this->validate(
            $this->encodePayload($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.account',
                'type' => 'wrongType',
                'message' => 'Field value was expected to be of type "account", but was "array"',
                'value' => $invalidValue,
            ]
        ]);
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
                    'name' => 'longTest',
                    'type' => 'long',
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
