<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command;

use Jobcloud\Avro\Validator\Command\Formatter\JsonFormatter;
use Jobcloud\Avro\Validator\Command\Formatter\PrettyFormatter;
use Jobcloud\Avro\Validator\RecordRegistry;
use Jobcloud\Avro\Validator\Validator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ValidateCommand extends Command
{
    private const ARG_PAYLOAD = 'payload';

    private const ARG_SCHEMA = 'schema';

    private const ARG_NAMESPACE = 'namespace';

    private const OPTION_FORMAT = 'format';

    private const FORMAT_PRETTY = 'pretty';

    private const FORMAT_JSON = 'json';

    private const SUPPORTED_FORMATS = [self::FORMAT_PRETTY, self::FORMAT_JSON];

    protected function configure(): void
    {
        parent::configure();

        $this
            ->setName('validate')
            ->setDescription('Validates a payload against a schema')
            ->addArgument(self::ARG_SCHEMA, InputArgument::REQUIRED, 'Path to the schema file')
            ->addArgument(self::ARG_NAMESPACE, InputArgument::REQUIRED, 'Schema namespace')
            ->addArgument(self::ARG_PAYLOAD, InputArgument::OPTIONAL, 'Path to the payload file')
            ->addOption(
                self::OPTION_FORMAT,
                'f',
                InputOption::VALUE_REQUIRED,
                'Output format of the result',
                self::FORMAT_PRETTY
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $schemaFilePath = $input->getArgument(self::ARG_SCHEMA);
        $schemaNamespace = $input->getArgument(self::ARG_NAMESPACE);

        $readStreams = [STDIN];
        $writeStreams = [];
        $exceptStreams = [];
        $hasPayloadFromStdin = 1 === stream_select($readStreams, $writeStreams, $exceptStreams, 0);
        $hasPayloadFromFile = null !== $input->getArgument(self::ARG_PAYLOAD);

        if ($hasPayloadFromStdin && $hasPayloadFromFile) {
            $output->writeln('<error>You cannot provide payload through both, stdin and as argument.</error>');
            return 2;
        }

        if (!$hasPayloadFromStdin && !$hasPayloadFromFile) {
            $output->writeln('No payload provided. Provide it either over stdin or as argument.');
            return 3;
        }

        $payloadFilePath = null;

        if ($hasPayloadFromStdin) {
            $payloadFilePath = 'php://stdin';
        } elseif ($hasPayloadFromFile) {
            $payloadFilePath = $input->getArgument(self::ARG_PAYLOAD);
        }

        $recordRegistry = RecordRegistry::fromSchema(file_get_contents($schemaFilePath));
        $validator = new Validator($recordRegistry);

        $outputFormat = $input->getOption(self::OPTION_FORMAT);

        if (self::FORMAT_PRETTY === $outputFormat) {
            $formatter = new PrettyFormatter($output, $schemaNamespace, $schemaFilePath, $payloadFilePath);
        } elseif (self::FORMAT_JSON === $outputFormat) {
            $formatter = new JsonFormatter($output);
        } else {
            $output->writeln(sprintf(
                '<error>Unsupported format: %s. Supported formats are: %s.</error>',
                $outputFormat,
                implode(', ', self::SUPPORTED_FORMATS)
            ));
            return 4;
        }

        $result = $validator->validate(file_get_contents($payloadFilePath), $schemaNamespace);

        if (0 === count($result)) {
            $formatter->formatSuccess($result);

            return 0;
        }

        $formatter->formatFail($result);

        return 1;
    }
}
