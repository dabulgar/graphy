<?php

namespace Davos\Graphy\Tests\Unit\ValueObjects;

use Davos\Graphy\ValueObjects\Duration;
use Davos\Graphy\ValueObjects\Exceptions\DurationFormatException;
use PHPUnit\Framework\TestCase;

class DurationTest extends TestCase
{
    public function testWithFloatingPointOnly()
    {
        $duration = '0.5';

        $this->expectException(DurationFormatException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
                $duration,
            )
        );

        $data = new Duration($duration);
    }

    public function testWithFloatingPointAndDuration()
    {
        $duration = '0.5d';

        $this->expectException(DurationFormatException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
                $duration,
            )
        );

        $data = new Duration($duration);
    }

    public function testWithEmptyString()
    {
        $duration = '';

        $this->expectException(DurationFormatException::class);
        $this->expectExceptionMessage(
            sprintf(
                'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
                $duration,
            )
        );

        $data = new Duration($duration);
    }

    public function testAcceptWithPureNumber()
    {
        $duration = '1';

        $data = new Duration($duration);

        $this->assertSame(1, $data->getDurationInSeconds());
    }

    public function testAcceptWithPureNumberMoreThanOne()
    {
        $duration = '6565';

        $data = new Duration($duration);

        $this->assertSame(6565, $data->getDurationInSeconds());
    }

    public function testAcceptWithSeconds()
    {
        $duration = '1s';

        $data = new Duration($duration);

        $this->assertSame(1, $data->getDurationInSeconds());
    }

    public function testAcceptWithSecondsMoreNumbers()
    {
        $duration = '4322s';

        $data = new Duration($duration);

        $this->assertSame(4322, $data->getDurationInSeconds());
    }

    public function testAcceptWithMinutes()
    {
        $duration = '1m';

        $data = new Duration($duration);

        $this->assertSame(60, $data->getDurationInSeconds());
    }

    public function testAcceptWithMinutesMoreNumbers()
    {
        $duration = '23m';

        $data = new Duration($duration);

        $this->assertSame(1380, $data->getDurationInSeconds());
    }

    public function testAcceptWithHours()
    {
        $duration = '1h';

        $data = new Duration($duration);

        $this->assertSame(3600, $data->getDurationInSeconds());
    }

    public function testAcceptWithHoursMoreNumbers()
    {
        $duration = '91h';

        $data = new Duration($duration);

        $this->assertSame(327600, $data->getDurationInSeconds());
    }

    public function testAcceptWithDays()
    {
        $duration = '1d';

        $data = new Duration($duration);

        $this->assertSame(86400, $data->getDurationInSeconds());
    }

    public function testAcceptWithDaysMoreNumbers()
    {
        $duration = '12d';

        $data = new Duration($duration);

        $this->assertSame(1036800, $data->getDurationInSeconds());
    }

    public function testAcceptWithWeeks()
    {
        $duration = '1w';

        $data = new Duration($duration);

        $this->assertSame(604800, $data->getDurationInSeconds());
    }

    public function testAcceptWithWeeksMoreNumbers()
    {
        $duration = '88w';

        $data = new Duration($duration);

        $this->assertSame(53222400, $data->getDurationInSeconds());
    }

    public function testAcceptWithMonths()
    {
        $duration = '1M';

        $data = new Duration($duration);

        $this->assertSame(2678400, $data->getDurationInSeconds());
    }

    public function testAcceptWithMonthsMoreNumbers()
    {
        $duration = '422M';

        $data = new Duration($duration);

        $this->assertSame(1130284800, $data->getDurationInSeconds());
    }

    public function testAcceptWithYears()
    {
        $duration = '1y';

        $data = new Duration($duration);

        $this->assertSame(31622400, $data->getDurationInSeconds());
    }

    public function testAcceptWithYearsMoreNumbers()
    {
        $duration = '92y';

        $data = new Duration($duration);

        $this->assertSame(2909260800, $data->getDurationInSeconds());
    }
}
