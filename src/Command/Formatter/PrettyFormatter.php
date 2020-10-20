<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command\Formatter;

use Symfony\Component\Console\Output\OutputInterface;

final class PrettyFormatter implements FormatterInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var string
     */
    private $schemaNamespace;

    /**
     * @var string
     */
    private $schemaPath;

    /**
     * @var string
     */
    private $payloadPath;

    public function __construct(
        OutputInterface $output,
        string $schemaNamespace,
        string $schemaPath,
        string $payloadPath
    ) {
        $this->output = $output;
        $this->schemaNamespace = $schemaNamespace;
        $this->schemaPath = $schemaPath;
        $this->payloadPath = $payloadPath;
    }

    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatSuccess(array $result): void
    {
        $this->output->writeln(sprintf(
            'Validation of payload was successful against schema with namespace <info>%s</info>.',
            $this->schemaNamespace
        ));
    }

    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatFail(array $result): void
    {
        $this->output->writeln(sprintf(
            'There were <info>%d</info> errors during validation of payload against ' .
            'schema with namespace <info>%s</info>:',
            count($result),
            $this->schemaNamespace
        ));

        foreach ($result as $error) {
            $this->output->writeln('');
            $this->output->writeln(sprintf(' - Field: <info>%s</info>', $error['path']));
            $this->output->writeln(sprintf('   Message: %s', $error['message']));
            $this->output->writeln(sprintf(
                '   Value: <comment>%s</comment>',
                $this->formatValue($error['value'])
            ));
        }
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function formatValue($value): string
    {
        if (is_string($value)) {
            return sprintf('"%s"', $value);
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }
}
