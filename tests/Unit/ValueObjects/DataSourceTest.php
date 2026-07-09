<?php

namespace Davos\Graphy\Tests\Unit\ValueObjects;

use Davos\Graphy\ValueObjects\DataSource;
use Davos\Graphy\ValueObjects\Exceptions\DataSourceDefinitionException;
use Davos\Graphy\ValueObjects\Exceptions\DurationFormatException;
use PHPUnit\Framework\TestCase;

class DataSourceTest extends TestCase
{
	public function testThrowsExceptionWhenNameContainsInvalidCharacters()
	{
		$definition = 'DS:output-traffic:GAUGE:300:0:24000';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid data source name in "%s". Expected pattern: [%s]',
				$definition,
				'a-zA-Z0-9_'
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenTypeIsInvalid()
	{
		$definition = 'DS:output_traffic:GAGE:300:0:24000';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid data source type in "%s". Allowed types: %s',
				$definition,
				implode(', ', ['GAUGE', 'COUNTER', 'DERIVE', 'DCOUNTER', 'DDERIVE', 'ABSOLUTE', 'COMPUTE'])
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenHeartbeatIsInvalid()
	{
		$definition = 'DS:output_traffic:GAUGE:a:0:24000';
		
		$this->expectException(DurationFormatException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
				'a',
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenHeartbeatIsInvalidString()
	{
		$definition = 'DS:output_traffic:GAUGE:5sm:0:24000';
		
		$this->expectException(DurationFormatException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid duration "%s". Expected number optionally followed by one of: s, m, h, d, w, M, y',
				'5sm',
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenMinIsInvalid()
	{
		$definition = 'DS:output_traffic:GAUGE:300:Y:24000';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid minimum value in "%s". Expected numeric or "U"',
				$definition,
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenMaxIsInvalid()
	{
		$definition = 'DS:output_traffic:GAUGE:300:0:Y';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid maximum value in "%s". Expected numeric or "U"',
				$definition,
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenDefinitionIsEmpty()
	{
		$definition = '';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(sprintf(
			'Invalid data source name in "%s". Expected pattern: [%s]',
			$definition,
			'a-zA-Z0-9_'
		));
		
		$data = new DataSource($definition);
	}
	
	public function testThrowsExceptionWhenDataSourceIsEmpty()
	{
		$definition = 'DS:output_traffic:';
		
		$this->expectException(DataSourceDefinitionException::class);
		$this->expectExceptionMessage(
			sprintf(
				'Invalid data source type in "%s". Allowed types: %s',
				$definition,
				implode(', ', DataSource::VALID_TYPES)
			)
		);
		
		$data = new DataSource($definition);
	}
	
	public function testAcceptsFullDefinition()
	{
		$definition = 'DS:output_traffic:GAUGE:300:0:24000';
		
		$data = new DataSource($definition);
		
		$this->assertSame('output_traffic', $data->getName());
		$this->assertSame('GAUGE', $data->getType());
		$this->assertSame(300, $data->getHeartbeat());
		$this->assertSame('0', $data->getMin());
		$this->assertSame('24000', $data->getMax());
		
		$this->assertSame($definition, $data->getDefinition());
	}
	
	public function testAcceptsDefinitionWithoutDsPrefixButWithLeadingColon()
	{
		$definition = ':output_traffic:GAUGE:5s:U:U';
		
		$data = new DataSource($definition);
		
		$this->assertSame('output_traffic', $data->getName());
		$this->assertSame('GAUGE', $data->getType());
		$this->assertSame(5, $data->getHeartbeat());
		$this->assertSame('U', $data->getMin());
		$this->assertSame('U', $data->getMax());
		
		$this->assertSame("DS:output_traffic:GAUGE:5:U:U", $data->getDefinition());
	}
	
	public function testAcceptsDefinitionWithoutDsPrefixAndWithoutLeadingColon()
	{
		$definition = 'output_traffic:GAUGE:1M:-273:-100';
		
		$data = new DataSource($definition);
		
		$this->assertSame('output_traffic', $data->getName());
		$this->assertSame('GAUGE', $data->getType());
		$this->assertSame(2678400, $data->getHeartbeat());
		$this->assertSame('-273', $data->getMin());
		$this->assertSame('-100', $data->getMax());
		
		$this->assertSame('DS:output_traffic:GAUGE:2678400:-273:-100', $data->getDefinition());
	}
	
	public function testAcceptsComputeDefinition()
	{
		$definition = 'AvgReqDur:COMPUTE:Duration,Requests,0,EQ,1,Requests';
		
		$data = new DataSource($definition);
		
		$this->assertSame('AvgReqDur', $data->getName());
		$this->assertSame('COMPUTE', $data->getType());
		$this->assertSame('Duration,Requests,0,EQ,1,Requests', $data->getExpression());
		$this->assertNull($data->getHeartbeat());
		$this->assertNull($data->getMin());
		$this->assertNull($data->getMax());
		
		$this->assertSame(sprintf("DS:%s", $definition), $data->getDefinition());
	}
}
