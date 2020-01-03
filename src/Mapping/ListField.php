<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\FastMap\Mapping;

use Kiboko\Component\ETL\FastMap\Compiler\Builder\ExpressionLanguageToPhpParserBuilder;
use Kiboko\Component\ETL\FastMap\Compiler\Builder\PropertyPathBuilder;
use Kiboko\Component\ETL\FastMap\Compiler\Builder\ScopedCodeBuilder;
use Kiboko\Component\ETL\FastMap\Contracts;
use Kiboko\Component\ETL\FastMap\PropertyAccess\EmptyPropertyPath;
use PhpParser\Node;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class ListField implements
    Contracts\FieldScopingInterface,
    Contracts\CompilableInterface
{
    /** @var PropertyPathInterface */
    private $outputPath;
    /** @var ExpressionLanguage */
    private $interpreter;
    /** @var Expression */
    private $inputExpression;
    /** @var Contracts\ArrayMapperInterface */
    private $child;
    /** @var PropertyAccessor */
    private $accessor;

    public function __construct(
        PropertyPathInterface $outputField,
        ExpressionLanguage $interpreter,
        Expression $inputExpression,
        Contracts\ArrayMapperInterface $child
    ) {
        $this->outputPath = $outputField;
        $this->interpreter = $interpreter;
        $this->inputExpression = $inputExpression;
        $this->child = $child;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    public function __invoke($input, $output)
    {
        $input = $this->interpreter->evaluate($this->inputExpression, [
            'input' => $input,
            'output' => $output,
        ]);

        if (!is_iterable($input)) {
            throw new \InvalidArgumentException(strtr(
                'The data at path %path% in first argument should be iterable.',
                [
                    '%path%' => $this->inputExpression,
                ]
            ));
        }

        $collection = $this->accessor->getValue($output, $this->outputPath) ?? [];
        foreach ($input as $item) {
            $collection[] = ($this->child)($item, [], new EmptyPropertyPath());
        }

        $this->accessor->setValue(
            $output,
            $this->outputPath,
            $collection
        );

        return $output;
    }

    public function compile(Node\Expr $outputNode): array
    {
        return [
            new Node\Stmt\Foreach_(
                (new ExpressionLanguageToPhpParserBuilder($this->interpreter, $this->inputExpression))->getNode(),
                new Node\Expr\Variable('item'),
                [
                    'stmts' => [
                        (new ScopedCodeBuilder(
                            new Node\Expr\Variable('item'),
                            (new PropertyPathBuilder($this->path))->getNode(),
                            $this->child->compile($outputNode)
                        ))->getNode(),
                    ]
                ]
            ),
        ];
    }
}