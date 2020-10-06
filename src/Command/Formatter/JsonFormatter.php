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

    public function formatSuccess(array $result): void
    {
        $this->output->writeln(json_encode($result));
    }

    public function formatFail(array $result): void
    {
        $this->output->writeln(json_encode($result));
    }
}
