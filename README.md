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

## Command
There's a command making use of the validator shipped with this package:

```
$ bin/avro-validator validate

Description:
  Validates a payload against a schema

Usage:
  validate [options] [--] <schema> <namespace> [<payload>]

Arguments:
  schema                Path to the schema file
  namespace             Schema namespace
  payload               Path to the payload file

Options:
  -f, --format=FORMAT   Output format of the result [default: "pretty"]
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
```

