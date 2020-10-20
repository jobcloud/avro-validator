<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Example;

use Jobcloud\Avro\Validator\SchemaRegistry;
use Jobcloud\Avro\Validator\Validator;

require_once __DIR__ . '/../vendor/autoload.php';

$recordRegistry = SchemaRegistry::fromSchema(file_get_contents(__DIR__ . '/schema.json'));
$validator = new Validator($recordRegistry);

var_dump($validator->validate(
    file_get_contents(__DIR__ . '/data-wrong.json'),
    'marketplace.ecommerce.entity.order'
));
