<?php

namespace spec\Jobcloud\Avro\Validator;

use Jobcloud\Avro\Validator\RecordRegistryInterface;
use Jobcloud\Avro\Validator\Validator;
use PhpSpec\ObjectBehavior;

final class ValidatorSpec extends ObjectBehavior
{
    public function let(RecordRegistryInterface $recordRegistry): void
    {
        $this->beConstructedWith($recordRegistry);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(Validator::class);
    }
}
