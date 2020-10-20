<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\Exception\SchemaRegistryException;
use Jobcloud\Avro\Validator\SchemaRegistry;
use PhpSpec\ObjectBehavior;

final class SchemaRegistrySpec extends ObjectBehavior
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

        $this->getSchema(sprintf('%s.%s', $record['namespace'], $record['name']))->shouldBe($record);
        $this->getSchema('foo')->shouldBe(null);
    }

    public function it_throws_exception_on_malformed_schema(): void
    {
        $this->beConstructedThrough('fromSchema', [json_encode([])]);

        $this->shouldThrow(SchemaRegistryException::class)->duringInstantiation();
    }
}
