<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\FastMap\Contracts;

interface FieldScopingInterface
{
    public function __invoke($input, $output);
}