<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\FastMap\Mapping\Field;

use Kiboko\Component\ETL\FastMap\Contracts;
use PhpParser\Node;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class ConstantValueMapper implements
    Contracts\FieldMapperInterface,
    Contracts\CompilableMapperInterface
{
    /** @var mixed */
    private $value;
    /** @var PropertyAccessor */
    private $accessor;

    public function __construct($value)
    {
        $this->value = $value;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function __invoke($input, $output, PropertyPathInterface $outputPath)
    {
        $this->accessor->setValue(
            $output,
            $outputPath,
            $this->value
        );

        return $output;
    }

    public function compile(Node\Expr $outputNode): array
    {
        return [
            new Node\Expr\Assign(
                $outputNode,
                new Node\Scalar\String_($this->value)
            ),
        ];
    }
}
