<?php


namespace Jobcloud\Avro\Validator;


interface RecordRegistryInterface
{
    public function getRecord(string $type): ?array;
}
