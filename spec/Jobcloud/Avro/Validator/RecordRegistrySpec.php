<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\RecordRegistryException;
use Jobcloud\Avro\Validator\RecordRegistry;
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
}
