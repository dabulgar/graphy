<?php

namespace Davos\Graphy\Tests\Unit\Fetch;

use Davos\Graphy\Fetch\FetchOptions;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Flag;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use PHPUnit\Framework\TestCase;

class FetchOptionsTest extends TestCase
{
    public function testBuildsOptionsWithRequiredFlags(): void
    {
        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
        ];

        $options = new FetchOptions('AVERAGE', $defaultFlags);

        $this->assertSame([
            'AVERAGE',
            FetchOptions::RESOLUTION,
            '60',
            FetchOptions::START,
            'end-1h',
        ], $options->getOptions());
    }

    public function testUserFlagsOverrideDefaultFlags(): void
    {
        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
            new Flag(FetchOptions::END, 'now'),
        ];

        $flags = [
            new Flag(FetchOptions::RESOLUTION, 300),
            new Flag(FetchOptions::START, 'end-24h'),
        ];

        $options = new FetchOptions('AVERAGE', $defaultFlags, $flags);

        $this->assertSame([
            'AVERAGE',
            FetchOptions::RESOLUTION,
            '300',
            FetchOptions::START,
            'end-24h',
            FetchOptions::END,
            'now',
        ], $options->getOptions());
    }

    public function testIncludesBooleanTrueFlagWithoutValue(): void
    {
        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
            new Flag(FetchOptions::ALIGN_START, true),
        ];

        $options = new FetchOptions('AVERAGE', $defaultFlags);

        $this->assertSame([
            'AVERAGE',
            FetchOptions::RESOLUTION,
            '60',
            FetchOptions::START,
            'end-1h',
            FetchOptions::ALIGN_START,
        ], $options->getOptions());
    }

    public function testSkipsFalseFlags(): void
    {
        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
            new Flag(FetchOptions::ALIGN_START, false),
            new Flag(FetchOptions::DAEMON, false),
        ];

        $options = new FetchOptions('AVERAGE', $defaultFlags);

        $this->assertSame([
            'AVERAGE',
            FetchOptions::RESOLUTION,
            '60',
            FetchOptions::START,
            'end-1h',
        ], $options->getOptions());
    }

    public function testNumericStartValueIsAccepted(): void
    {
        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 1700000000),
        ];

        $options = new FetchOptions('AVERAGE', $defaultFlags);

        $this->assertSame([
            'AVERAGE',
            FetchOptions::RESOLUTION,
            '60',
            FetchOptions::START,
            '1700000000',
        ], $options->getOptions());
    }

    public function testThrowsExceptionWhenConsolidationFunctionIsInvalid(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::invalidConsolidationFunction(
                'fetch',
                'INVALID',
                RoundRobinArchive::VALID_CF
            )->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
        ];

        new FetchOptions('INVALID', $defaultFlags);
    }

    public function testThrowsExceptionWhenResolutionFlagIsMissing(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::missingRequiredFlag('fetch', FetchOptions::RESOLUTION)->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::START, 'end-1h'),
        ];

        new FetchOptions('AVERAGE', $defaultFlags);
    }

    public function testThrowsExceptionWhenStartFlagIsMissing(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::missingRequiredFlag('fetch', FetchOptions::START)->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
        ];

        new FetchOptions('AVERAGE', $defaultFlags);
    }

    public function testThrowsExceptionWhenDefaultFlagIsNotFlagInstance(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::invalidFlagInstance()->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            'invalid-flag',
        ];

        new FetchOptions('AVERAGE', $defaultFlags);
    }

    public function testThrowsExceptionWhenUserFlagIsNotFlagInstance(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::invalidFlagInstance()->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
        ];

        $flags = [
            'invalid-flag',
        ];

        new FetchOptions('AVERAGE', $defaultFlags, $flags);
    }

    public function testThrowsExceptionWhenFlagIsNotAllowed(): void
    {
        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            CommandDefinitionException::flagNotAllowed('fetch', '--invalid')->getMessage()
        );

        $defaultFlags = [
            new Flag(FetchOptions::RESOLUTION, 60),
            new Flag(FetchOptions::START, 'end-1h'),
            new Flag('--invalid', 'value'),
        ];

        new FetchOptions('AVERAGE', $defaultFlags);
    }
}