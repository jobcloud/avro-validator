<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\RecordRegistryException;
use PhpSpec\ObjectBehavior;

final class RecordRegistrySpec extends ObjectBehavior
{
    public function it_adds_record_to_registry_and_returns_it_if_existing(): void
    {
        $record = [
            'type' => 'record',
            'name' => 'baz',
            'namespace' => 'foo.bar',
            'fields' => [
                [
                    'name' => 'id',
                    'type' => 'string',
                ],
            ],
        ];

        $this->beConstructedThrough('fromSchema', [json_encode($record)]);

        $this->getRecord(sprintf('%s.%s', $record['namespace'], $record['name']))->shouldBe($record);
        $this->getRecord('foo')->shouldBe(null);
    }

    public function it_throws_exception_on_malformed_schema(): void
    {
        $this->beConstructedThrough('fromSchema', [json_encode([])]);

        $this->shouldThrow(RecordRegistryException::class)->duringInstantiation();
    }

    public function it_throws_exception_on_invalid_json_string(): void
    {
        $this->beConstructedThrough('fromSchema', ['{invalid_json']);

        $this->shouldThrow(RecordRegistryException::class)->duringInstantiation();
    }

    public function it_throws_exception_on_non_array_json(): void
    {
        $this->beConstructedThrough('fromSchema', ['"string"']);

        $this->shouldThrow(RecordRegistryException::class)->duringInstantiation();
    }

    public function it_handles_array_of_records_schema(): void
    {
        $records = [
            [
                'type' => 'record',
                'name' => 'user',
                'namespace' => 'com.example',
                'fields' => [
                    [
                        'name' => 'id',
                        'type' => 'string',
                    ],
                ],
            ],
            [
                'type' => 'record',
                'name' => 'order',
                'namespace' => 'com.example',
                'fields' => [
                    [
                        'name' => 'orderId',
                        'type' => 'string',
                    ],
                ],
            ],
        ];

        $this->beConstructedThrough('fromSchema', [json_encode($records)]);

        $this->getRecord('com.example.user')->shouldBe($records[0]);
        $this->getRecord('com.example.order')->shouldBe($records[1]);
    }
}
