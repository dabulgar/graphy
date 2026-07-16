<?php

namespace Davos\Graphy\Tests\Unit\ValueObjects;

use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\Exceptions\DurationFormatException;
use Davos\Graphy\ValueObjects\Exceptions\RoundRobinArchiveDefinitionException;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use PHPUnit\Framework\TestCase;

class RoundRobinArchiveTest extends TestCase
{
    public function testThrowsExceptionWhenDefinitionIsEmpty()
    {
        $definition = '';

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid RRA definition "%s". Expected 4 or 5 parts separated by ":"',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenDefinitionHasWrongPrefix()
    {
        $definition = 'RRa:MAX:0.5:1:1';

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid RRA prefix "%s". Expected "RRA"',
                'RRa'
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenDefinitionHasLessThanFourParts()
    {
        $definition = 'RRA:MAX:0.5';

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid RRA definition "%s". Expected 4 or 5 parts separated by ":"',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenDefinitionHasMoreThanFiveParts()
    {
        $definition = 'RRA:MAX:0.5:1:3:4';

        $this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid RRA definition "%s". Expected 4 or 5 parts separated by ":"',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenDefinitionHasCFIsNotValid()
    {
        $definition = 'RRA:MAXX:0.5:1:3';

        $this->expectException(RoundRobinArchiveDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid CF of RRA "%s". Expected to be in: %s',
                $definition,
                implode(', ', RoundRobinArchive::VALID_CF)
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenXffIsLessThanZero()
    {
        $definition = 'RRA:MAX:-1:1:5';

        $this->expectException(RoundRobinArchiveDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid XFF of RRA "%s". Allowed values between 0 and 1',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenXffIsGreaterThanOne()
    {
        $definition = 'RRA:MAX:1.2:1:5';

        $this->expectException(RoundRobinArchiveDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid XFF of RRA "%s". Allowed values between 0 and 1',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenXffIsString()
    {
        $definition = 'RRA:MAX:a:1:5';

        $this->expectException(RoundRobinArchiveDefinitionException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid XFF of RRA "%s". Allowed values between 0 and 1',
                $definition,
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenStepsAreInvalid()
    {
        $definition = 'RRA:MAX:0.5:1a:5';

        $this->expectException(DurationFormatException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid duration "1a". Expected number optionally followed by one of: s, m, h, d, w, M, y',
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testThrowsExceptionWhenRowsAreInvalid()
    {
        $definition = 'RRA:MAX:0.5:1:5a';

        $this->expectException(DurationFormatException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid duration "5a". Expected number optionally followed by one of: s, m, h, d, w, M, y',
            )
        );

        new RoundRobinArchive($definition, 0);
    }

    public function testAcceptsValidDefinition(): void
    {
        $definition = 'RRA:MAX:0.5:1:5';

        $data = new RoundRobinArchive($definition, 3);

        $this->assertSame('MAX', $data->getCf());
        $this->assertSame(0.5, $data->getXff());
        $this->assertSame(1, $data->getSteps());
        $this->assertSame(5, $data->getRows());
        $this->assertSame(3, $data->getIndex());

        $this->assertSame($definition, $data->getDefinition());

        $this->assertSame(5, $data->getResolutionInSeconds(5));
        $this->assertSame(5, $data->getResolutionInSeconds(new Duration('5s')));

        $this->assertSame(25, $data->getArchiveDurationInSeconds(5));
        $this->assertSame(25, $data->getArchiveDurationInSeconds(new Duration('5s')));
    }

    public function testAcceptsDefinitionWithoutPrefix()
    {
        $definition = 'MAX:0.5:1d:5w';

        $data = new RoundRobinArchive($definition, 0);

        $this->assertSame('MAX', $data->getCf());
        $this->assertSame(0.5, $data->getXff());
        $this->assertSame(86400, $data->getSteps());
        $this->assertSame(3024000, $data->getRows());

        $this->assertSame('RRA:MAX:0.5:86400:3024000', $data->getDefinition());

        $this->assertSame(864000, $data->getResolutionInSeconds(10));
        $this->assertSame(51840000, $data->getResolutionInSeconds(new Duration('10m')));

        $this->assertSame(2612736000000, $data->getArchiveDurationInSeconds(10));
    }

    public function testAcceptsDefinitionWithLeadingColon()
    {
        $definition = ':MAX:0.5:1w:10y';

        $data = new RoundRobinArchive($definition, 0);

        $this->assertSame('MAX', $data->getCf());
        $this->assertSame(0.5, $data->getXff());
        $this->assertSame(604800, $data->getSteps());
        $this->assertSame(316224000, $data->getRows());

        $this->assertSame(4838400, $data->getResolutionInSeconds(8));
        $this->assertSame(1088640000, $data->getResolutionInSeconds(new Duration('30m')));

        $this->assertSame(191252275200000, $data->getArchiveDurationInSeconds(1));
    }

    public function testIndexCanBeChanged(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:1:10', 2);

        $this->assertSame(2, $archive->getIndex());

        $archive->setIndex(5);

        $this->assertSame(5, $archive->getIndex());
    }

    public function testFirstTimestampCanBeSetAndRead(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:1:10', 0);

        $archive->setFirstTimestamp(1700000000);

        $this->assertSame(1700000000, $archive->getFirstTimestamp());
    }

    public function testMatchesReturnsTrueWhenCfAndResolutionMatchUsingIntegerValues(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:60:100', 0);

        $this->assertTrue(
            $archive->matches('AVERAGE', 600, 10)
        );
    }

    public function testMatchesReturnsTrueWhenCfAndResolutionMatchUsingDurationValues(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:60:100', 0);

        $this->assertTrue(
            $archive->matches(
                'AVERAGE',
                new Duration('10m'),
                new Duration('10s')
            )
        );
    }

    public function testMatchesReturnsFalseWhenCfDoesNotMatch(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:60:100', 0);

        $this->assertFalse(
            $archive->matches('MAX', 600, 10)
        );
    }

    public function testMatchesReturnsFalseWhenResolutionDoesNotMatch(): void
    {
        $archive = new RoundRobinArchive('RRA:AVERAGE:0.5:60:100', 0);

        $this->assertFalse(
            $archive->matches('AVERAGE', 300, 10)
        );
    }
}
