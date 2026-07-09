<?php

namespace Davos\Graphy\Tests\Unit\Create;

use Davos\Graphy\Create\CreateOptions;
use Davos\Graphy\Shared\Exceptions\CommandDefinitionException;
use Davos\Graphy\Update\UpdateOptions;
use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\Flag;
use Davos\Graphy\ValueObjects\RoundRobinArchive;
use PHPUnit\Framework\TestCase;

class CreateOptionsTest extends TestCase
{
	public function testThrowsExceptionWhenInvalidDefaultFlag()
	{
		$this->expectException(CommandDefinitionException::class);
		$this->expectExceptionMessage("All flags must be instances of Flag.");
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			[CreateOptions::START, 'now-2y']
		];
		
		$flags = [
		
		];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testThrowsExceptionWhenInvalidFlag()
	{
		$this->expectException(CommandDefinitionException::class);
        $this->expectExceptionMessage("All flags must be instances of Flag.");
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y')
		];
		
		$flags = [
			[CreateOptions::START, 'now-2y']
		];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testThrowsExceptionWhenStepFlagOmitted()
	{
		$this->expectException(CommandDefinitionException::class);
		$this->expectExceptionMessage(sprintf(
			"Invalid create options: required flag %s is missing.", '--step'
		));
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y')
		];
		
		$flags = [];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testThrowsExceptionWhenStartFlagOmitted()
	{
		$this->expectException(CommandDefinitionException::class);
		$this->expectExceptionMessage(sprintf(
			"Invalid create options: required flag %s is missing.", '--start'
		));
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::STEP, 1800)
		];
		
		$flags = [];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testThrowsExceptionWhenFlagNotAllowed()
	{
		$this->expectException(CommandDefinitionException::class);
		$this->expectExceptionMessage(sprintf(
				"Invalid create options: flag '%s' is not allowed.", '--skip-past-updates'
			)
		);
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y'),
			new Flag(CreateOptions::STEP, '1800'),
			new Flag(UpdateOptions::SKIP_PAST_UPDATES, true),
		];
		
		$flags = [];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testThrowsExceptionWhenRequiredFlagValueIsInvalid()
	{
		$this->expectException(CommandDefinitionException::class);
		$this->expectExceptionMessage(sprintf(
				"Invalid create options: required flag %s is missing.", CreateOptions::STEP
			)
		);
		
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y'),
			new Flag(CreateOptions::STEP, false),
			new Flag(UpdateOptions::SKIP_PAST_UPDATES, true),
		];
		
		$flags = [];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
	}
	
	public function testAcceptBasicDefinition()
	{
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y'),
			new Flag(CreateOptions::STEP, '1800'),
			new Flag(CreateOptions::NO_OVERWRITE),
		];
		
		$flags = [];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
		
		$this->assertSame(
			[CreateOptions::START, 'now-2y', CreateOptions::STEP, '1800', CreateOptions::NO_OVERWRITE,
				'DS:output_traffic:GAUGE:300:0:24000', 'DS:input_traffic:GAUGE:300:0:24000',
				'RRA:MAX:0.5:1:5', 'RRA:AVERAGE:0.5:1:5',
			],
			$createOptions->getOptions())
		;
	}
	
	public function testAcceptBasicDefinitionWhenOverwriteIsOmitted()
	{
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y'),
			new Flag(CreateOptions::STEP, '1800'),
			new Flag(CreateOptions::NO_OVERWRITE),
		];
		
		$flags = [
			new Flag(CreateOptions::NO_OVERWRITE, false),
		];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
		
		$this->assertSame(
			[CreateOptions::START, 'now-2y', CreateOptions::STEP, '1800',
				'DS:output_traffic:GAUGE:300:0:24000', 'DS:input_traffic:GAUGE:300:0:24000',
				'RRA:MAX:0.5:1:5', 'RRA:AVERAGE:0.5:1:5',
			],
			$createOptions->getOptions())
		;
	}
	
	public function testAcceptWithAllFlags()
	{
		list($dataSources, $roundRobinArchives) = $this->generateDSAndRRA();
		
		$defaultFlags = [
			new Flag(CreateOptions::START, 'now-2y'),
			new Flag(CreateOptions::STEP, '1800'),
			new Flag(CreateOptions::NO_OVERWRITE),
		];
		
		$flags = [
			new Flag(CreateOptions::DAEMON, 'unix:123'),
			new Flag(CreateOptions::TEMPLATE, 'template.rrd'),
			new Flag(CreateOptions::FROM_SOURCE, 'data.rrd'),
		];
		
		$createOptions = new CreateOptions($dataSources, $roundRobinArchives, $defaultFlags, $flags);
		
		$this->assertSame(
			[
				CreateOptions::START, 'now-2y',
				CreateOptions::STEP, '1800',
				CreateOptions::NO_OVERWRITE,
				CreateOptions::DAEMON, 'unix:123',
				CreateOptions::TEMPLATE, 'template.rrd',
				CreateOptions::FROM_SOURCE, 'data.rrd',
				'DS:output_traffic:GAUGE:300:0:24000', 'DS:input_traffic:GAUGE:300:0:24000',
				'RRA:MAX:0.5:1:5', 'RRA:AVERAGE:0.5:1:5',
			],
			$createOptions->getOptions()
		);
	}
	
	private function generateDSAndRRA()
	{
		$dataSources = [
			new DataSource('DS:output_traffic:GAUGE:300:0:24000'),
			new DataSource('DS:input_traffic:GAUGE:300:0:24000'),
		];
		
		$roundRobinArchives = [
			new RoundRobinArchive('RRA:MAX:0.5:1:5', 0),
			new RoundRobinArchive('RRA:AVERAGE:0.5:1:5', 0),
		];
		
		return [$dataSources, $roundRobinArchives];
	}
}
