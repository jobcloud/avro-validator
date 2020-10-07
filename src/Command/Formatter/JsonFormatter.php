<?php

declare(strict_types=1);

namespace Jobcloud\Avro\Validator\Command\Formatter;

use Symfony\Component\Console\Output\OutputInterface;

final class JsonFormatter implements FormatterInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
    }

    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatSuccess(array $result): void
    {
        $this->output->writeln($this->encodeResult($result));
    }

    /**
     * @param array<array<mixed>> $result
     * @return void
     */
    public function formatFail(array $result): void
    {
        $this->output->writeln($this->encodeResult($result));
    }

    /**
     * @param array<array<mixed>> $result
     * @return string
     */
    private function encodeResult(array $result): string
    {
        return json_encode($result, JSON_THROW_ON_ERROR);
    }
}
