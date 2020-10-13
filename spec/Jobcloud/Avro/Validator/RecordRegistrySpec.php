<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\RecordRegistry;
use PhpSpec\ObjectBehavior;

final class RecordRegistrySpec extends ObjectBehavior
{
    public function it_adds_record_to_registry_and_returns_it(): void
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

        $this->beConstructedThrough('fromSchema', [json_encode([$record])]);

        $this->getRecord(sprintf('%s.%s', $record['namespace'], $record['name']))->shouldBe($record);
    }

    public function it_returns_null_for_unknown_record(): void
    {
        $this->beConstructedThrough('fromSchema', [json_encode([])]);

        $this->getRecord('unknown.record')->shouldBe(null);
    }
}
