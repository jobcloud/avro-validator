# Avro schema validator

## Install

```
composer require jobcloud/avro-validator
```

## Usage

```php
$recordRegistry = RecordRegistry::fromSchema(file_get_contents(__DIR__ . '/schema.json'));
$validator = new Validator($recordRegistry);

var_dump($validator->validate(
    '{"payload": "here comes the payload to validate against the schema"}}',
    'marketplace.ecommerce.entity.order'
));
```
