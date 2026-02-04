<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command\Formatter;

use Symfony\Component\Console\Output\OutputInterface;

final readonly class PrettyFormatter implements FormatterInterface
{
    public function __construct(
        private OutputInterface $output,
        private string $schemaNamespace
    ) {
    }

    /**
     * @param array<array<mixed>> $result
     */
    #[\Override]
    public function formatSuccess(array $result): void
    {
        $this->output->writeln(sprintf(
            'Validation of payload was successful against schema with namespace <info>%s</info>.',
            $this->schemaNamespace
        ));
    }

    /**
     * @param array<array<mixed>> $result
     */
    #[\Override]
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
            $errorValue = $error['value'] ?? null;

            if (null !== $errorValue) {
                $this->output->writeln(sprintf(
                    '   Value: <comment>%s</comment>',
                    $this->formatValue($errorValue)
                ));
            }
        }
    }

    private function formatValue(mixed $value): string
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
