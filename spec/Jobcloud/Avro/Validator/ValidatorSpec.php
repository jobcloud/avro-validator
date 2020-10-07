<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\InvalidSchemaException;
use Jobcloud\Avro\Validator\MissingSchemaException;
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
            json_encode($payload),
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
        $recordRegistry->getRecord($schemaName)->willReturn([
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
                    'name' => 'arrayOrNullTest',
                    'type' => ['null', 'array'],
                ],
            ],
        ]);
        $recordRegistry->getRecord('string')->willReturn(null);
        $recordRegistry->getRecord('int')->willReturn(null);
        $recordRegistry->getRecord('double')->willReturn(null);
        $recordRegistry->getRecord('null')->willReturn(null);
        $recordRegistry->getRecord('array')->willReturn(null);

        $invalidStringValue = 42;
        $invalidIntValue = 'foo';
        $invalidDoubleValue = 42;
        $invalidNullOrArrayValue = 42;

        $payload = [
            'stringTest' => $invalidStringValue,
            'intTest' => $invalidIntValue,
            'doubleTest' => $invalidDoubleValue,
            'arrayOrNullTest' => $invalidNullOrArrayValue,
        ];

        $this->validate(
            json_encode($payload),
            $schemaName
        )->shouldBe([
            [
                'path' => '$.stringTest',
                'message' => 'Field value was expected to be of type "string", but was "integer"',
                'value' => $invalidStringValue,
            ],
            [
                'path' => '$.intTest',
                'message' => 'Field value was expected to be of type "int", but was "string"',
                'value' => $invalidIntValue,
            ],
            [
                'path' => '$.doubleTest',
                'message' => 'Field value was expected to be of type "double", but was "integer"',
                'value' => $invalidDoubleValue,
            ],
            [
                'path' => '$.arrayOrNullTest',
                'message' => 'Field value was expected to be of type "null" or "array", but was "integer"',
                'value' => $invalidNullOrArrayValue,
            ],
        ]);
    }
}
