<?php

namespace Jobcloud\Avro\Validator;

interface ValidatorInterface
{
    public function validate(string $payload, string $recordType): array;
}
