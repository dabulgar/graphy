<?php

namespace Davos\Graphy\Tests\Unit\Update;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\Update\UpdateOptions;
use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\Flag;
use PHPUnit\Framework\TestCase;

class UpdateOptionsTest extends TestCase
{
    public function testReturnsOptionsForSingleImplicitNowUpdate(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
                'output' => 200,
            ],
            []
        );

        $this->assertSame([
            'N:100:200:U',
        ], $options->getOptions());
    }

    public function testReturnsOptionsForMultipleStructuredBatches(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                [
                    'time' => 'N',
                    'values' => [
                        'input' => 100,
                        'output' => 200,
                    ],
                ],
                [
                    'time' => -5,
                    'values' => [
                        'input' => 150,
                        'errors' => 3,
                    ],
                ],
            ],
            []
        );

        $this->assertSame([
            'N:100:200:U',
            '-5:150:U:3',
        ], $options->getOptions());
    }

    public function testReturnsOptionsForTimeMappedBatches(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'N' => [
                    'input' => 100,
                    'output' => 200,
                ],
                -5 => [
                    'input' => 150,
                    'errors' => 3,
                ],
            ],
            []
        );

        $this->assertSame([
            'N:100:200:U',
            '-5:150:U:3',
        ], $options->getOptions());
    }

    public function testReturnsOptionsWithTemplateFlagGeneratedFromDataSourceOrder(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
                'output' => 200,
            ],
            [],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, true),
            ]
        );

        $this->assertSame([
            UpdateOptions::TEMPLATE,
            'input:output:errors',
            'N:100:200:U',
        ], $options->getOptions());
    }

    public function testReturnsOptionsWithCustomTemplateOrder(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
                'errors' => 5,
            ],
            [],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, 'errors:input'),
            ]
        );

        $this->assertSame([
            UpdateOptions::TEMPLATE,
            'errors:input',
            'N:5:100',
        ], $options->getOptions());
    }

    public function testReturnsOptionsWithDefaultAndCustomFlagsMerged(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [
                $this->createFlag(UpdateOptions::SKIP_PAST_UPDATES, true),
            ],
            [
                $this->createFlag(UpdateOptions::DAEMON, 'unix:/Tmp/rrdcached.sock'),
            ]
        );

        $this->assertSame([
            UpdateOptions::SKIP_PAST_UPDATES,
            UpdateOptions::DAEMON,
            'unix:/Tmp/rrdcached.sock',
            'N:100:U:U',
        ], $options->getOptions());
    }

    public function testReturnsOptionsWhenCustomFlagOverridesDefaultFlag(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, true),
            ],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, false),
            ]
        );

        $this->assertSame([
            'N:100:U:U',
        ], $options->getOptions());
    }

    public function testIgnoresFlagsWithFalseOrNullValue(): void
    {
        $options = new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, false),
                $this->createFlag(UpdateOptions::DAEMON, false),
            ]
        );

        $this->assertSame([
            'N:100:U:U',
        ], $options->getOptions());
    }

    public function testSkipsComputeDataSourcesInGeneratedOrder(): void
    {
        $dataSources = [
            $this->createDataSource('input', 'GAUGE'),
            $this->createDataSource('calc', 'COMPUTE'),
            $this->createDataSource('output', 'GAUGE'),
        ];

        $options = new UpdateOptions(
            $dataSources,
            [
                'input' => 100,
                'output' => 200,
            ],
            [],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, true),
            ]
        );

        $this->assertSame([
            UpdateOptions::TEMPLATE,
            'input:output',
            'N:100:200',
        ], $options->getOptions());
    }

    public function testThrowsExceptionWhenFlagIsNotInstanceOfFlag(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('All flags must be instances of Flag.');

        new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
                'output' => 200,
            ],
            [
                [UpdateOptions::TEMPLATE, true],
            ]
        );
    }

    public function testThrowsExceptionWhenDefaultFlagIsNotFlagInstance(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('All flags must be instances of Flag.');

        new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [
                'not-a-flag',
            ]
        );
    }

    public function testThrowsExceptionWhenCustomFlagIsNotFlagInstance(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('All flags must be instances of Flag.');

        new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [],
            [
                'not-a-flag',
            ]
        );
    }

    public function testThrowsExceptionWhenInvalidFlagIsProvided(): void
    {
        $invalidFlag = '--invalid-flag';

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(sprintf("Invalid %s options: flag '%s' is not allowed.", 'update', $invalidFlag));

        new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [],
            [
                $this->createFlag($invalidFlag, true),
            ]
        );
    }

    public function testThrowsExceptionWhenDataIsEmpty(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage('Update data cannot be empty.');

        new UpdateOptions(
            $this->createDataSources(),
            [],
            []
        );
    }

    public function testThrowsExceptionWhenCustomTemplateContainsInvalidDataSource(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(sprintf("Invalid data source '%s'.", 'unknown'));

        new UpdateOptions(
            $this->createDataSources(),
            [
                'input' => 100,
            ],
            [],
            [
                $this->createFlag(UpdateOptions::TEMPLATE, 'input:unknown'),
            ]
        );
    }

    public function testThrowsExceptionWhenStructuredBatchDoesNotContainTimeAndValues(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(sprintf("Invalid batch format at index %s. Expected ['time' => ..., 'values' => [...]].", 0));

        new UpdateOptions(
            $this->createDataSources(),
            [
                [
                    'time' => 'N',
                ],
            ],
            []
        );
    }

    public function testThrowsExceptionWhenStructuredBatchTimeIsInvalid(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(sprintf("Invalid time at index %s. Expected integer timestamp or string (e.g. 'N'),  got %s.", 0, gettype(1.5)));

        new UpdateOptions(
            $this->createDataSources(),
            [
                [
                    'time' => 1.5,
                    'values' => [
                        'input' => 100,
                    ],
                ],
            ],
            []
        );
    }

    public function testThrowsExceptionWhenStructuredBatchValuesAreInvalid(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(sprintf("Invalid values at index %s. Expected array of data source values.", 0));

        new UpdateOptions(
            $this->createDataSources(),
            [
                [
                    'time' => 'N',
                    'values' => 'invalid',
                ],
            ],
            []
        );
    }

    private function createDataSource(string $name, string $type): DataSource
    {
        return new DataSource("DS:{$name}:{$type}:300:0:24000");
    }

    private function createFlag(string $flag, mixed $value): Flag
    {
        return new Flag($flag, $value);
    }

    /**
     * @return DataSource[]
     */
    private function createDataSources(): array
    {
        return [
            $this->createDataSource('input', 'GAUGE'),
            $this->createDataSource('output', 'GAUGE'),
            $this->createDataSource('errors', 'COUNTER'),
        ];
    }
}