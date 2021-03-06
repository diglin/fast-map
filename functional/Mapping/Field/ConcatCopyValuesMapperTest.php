<?php declare(strict_types=1);

namespace functional\Kiboko\Component\ETL\FastMap\Mapping\Field;

use Kiboko\Component\ETL\FastMap\Compiler;
use Kiboko\Component\ETL\FastMap\Contracts\CompiledMapperInterface;
use Kiboko\Component\ETL\FastMap\Contracts\MapperInterface;
use Kiboko\Component\ETL\FastMap\Mapping\Field\ConcatCopyValuesMapper;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

final class ConcatCopyValuesMapperTest extends TestCase
{
    public function mappingDataProvider()
    {
        yield [
            [
                'person' => 'John Doe'
            ],
            [
                'employee' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ]
            ],
            new PropertyPath('[person]'),
            ' ',
            new PropertyPath('[employee][first_name]'),
            new PropertyPath('[employee][last_name]'),
        ];

        yield [
            [
                'person' => [
                    'name' => 'John Doe',
                ]
            ],
            [
                'employee' => [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                ]
            ],
            new PropertyPath('[person][name]'),
            ' ',
            new PropertyPath('[employee][first_name]'),
            new PropertyPath('[employee][last_name]'),
        ];

        yield [
            [
                'persons' => [
                    [
                        'name' => 'John Doe',
                    ]
                ]
            ],
            [
                'employees' => [
                    [
                        'first_name' => 'John',
                        'last_name' => 'Doe',
                    ]
                ]
            ],
            new PropertyPath('[persons][0][name]'),
            ' ',
            new PropertyPath('[employees][0][first_name]'),
            new PropertyPath('[employees][0][last_name]'),
        ];

        yield [
            [
                'persons' => [
                    [
                        'name' => 'John Doe',
                    ]
                ]
            ],
            [
                'employees' => [
                    (function(): \stdClass {
                        $object = new \stdClass;
                        $object->first_name = 'John';
                        $object->last_name = 'Doe';
                        return $object;
                    })()
                ]
            ],
            new PropertyPath('[persons][0][name]'),
            ' ',
            new PropertyPath('[employees][0].first_name'),
            new PropertyPath('[employees][0].last_name'),
        ];
    }

    /**
     * @dataProvider mappingDataProvider
     */
    public function testDynamicResults(
        $expected,
        $input,
        PropertyPathInterface $outputField,
        string $glue,
        PropertyPathInterface ...$inputFields
    ) {
        /** @var MapperInterface $compiledMapper */
        $staticdMapper = new ConcatCopyValuesMapper($glue, ...$inputFields);

        $this->assertEquals($expected, $staticdMapper($input, [], $outputField));
    }

    /**
     * @dataProvider mappingDataProvider
     */
    public function testCompilationResultsWithSpaghettiStrategy(
        $expected,
        $input,
        PropertyPathInterface $outputField,
        string $glue,
        PropertyPathInterface ...$inputFields
    ) {
        $compiler = new Compiler\Compiler(new Compiler\Strategy\Spaghetti());

        /** @var CompiledMapperInterface $compiledMapper */
        $compiledMapper = $compiler->compile(
            new Compiler\StandardCompilationContext(
                $outputField,
                null,
                null
            ),
            new ConcatCopyValuesMapper($glue, ...$inputFields)
        );

        $this->assertEquals($expected, $compiledMapper($input, []));
    }

    /**
     * @dataProvider mappingDataProvider
     */
    public function testCompilationResultsWithReduceStrategy(
        $expected,
        $input,
        PropertyPathInterface $outputField,
        string $glue,
        PropertyPathInterface ...$inputFields
    ) {
        $compiler = new Compiler\Compiler(new Compiler\Strategy\Reduce());

        /** @var CompiledMapperInterface $compiledMapper */
        $compiledMapper = $compiler->compile(
            new Compiler\StandardCompilationContext(
                $outputField,
                null,
                null
            ),
            new ConcatCopyValuesMapper($glue, ...$inputFields)
        );

        $this->assertEquals($expected, $compiledMapper($input, []));
    }
}