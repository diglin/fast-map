<?php declare(strict_types=1);

namespace Kiboko\Component\ETL\FastMap\Compiler\Strategy;

use Kiboko\Component\ETL\FastMap\Contracts\CompilableMapperInterface;
use Kiboko\Component\ETL\FastMap\Contracts\MapperInterface;
use PhpParser\BuilderFactory;
use PhpParser\Node;
use Prophecy\Argument;

final class Reduce implements StrategyInterface
{
    private function randomIdentifier(): string
    {
        return hash('sha256', random_bytes(1024));
    }

    private function randomMethodName(string $prefix): string
    {
        return $prefix . $this->randomIdentifier();
    }

    public function buildTree(string $namespace, string $className, CompilableMapperInterface ...$mappers): array
    {
        $factory = new BuilderFactory();

        $calls = [];
        $methods = [];
        foreach ($mappers as $mapper) {
            $methodName = $this->randomMethodName('map_');

            $calls[] = new Node\Expr\MethodCall(
                new Node\Expr\Variable('this'),
                $methodName,
                [
                    new Node\Expr\Variable('input'),
                    new Node\Expr\Variable('output'),
                ]
            );
            $methods[] = $this->wrapMapping($methodName, $factory, $mapper->compile());
        }

        return [
            $factory->namespace(rtrim($namespace, '\\'))
//                ->addStmt($factory->use(MapperInterface::class))
                ->addStmt($factory->class($className)
                    ->implement(new Node\Name\FullyQualified(MapperInterface::class))
                    ->makeFinal()
                    ->addStmt($factory->method('__invoke')
                        ->makePublic()
                        ->addParam($factory->param('input'))
                        ->addParam($factory->param('output'))
                        ->addStmt(new Node\Stmt\Return_(
                            new Node\Expr\FuncCall(
                                new Node\Name('array_reduce'),
                                [
                                    new Node\Expr\Array_($calls, [
                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                    ]),
                                    new Node\Expr\Closure([
                                        'params' => [
                                            new Node\Param(new Node\Expr\Variable('current')),
                                            new Node\Param(new Node\Expr\Variable('initial')),
                                        ],
                                        'stmts' => [
                                            new Node\Stmt\Return_(
                                                new Node\Expr\FuncCall(
                                                    new Node\Name('array_merge'),
                                                    [
                                                        new Node\Expr\Variable('initial'),
                                                        new Node\Expr\Variable('current'),
                                                    ]
                                                )
                                            )
                                        ]
                                    ]),
                                    new Node\Expr\Array_([], [
                                        'kind' => Node\Expr\Array_::KIND_SHORT
                                    ]),
                                ]
                            )
                        ))
                    )
                    ->addStmts($methods)
                )
                ->getNode()
        ];
    }

    private function wrapMapping(string $methodName, BuilderFactory $factory, array $statements)
    {
        return $factory->method($methodName)
            ->makeFinal()
            ->makePrivate()
            ->addParam($factory->param('input'))
            ->addParam($factory->param('output'))
            ->addStmts(array_merge(
                $statements,
                [
                    new Node\Stmt\Return_(
                        new Node\Expr\Variable('output')
                    )
                ]
            ));
    }
}