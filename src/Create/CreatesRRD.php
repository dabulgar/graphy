<?php

namespace Davos\Graphy\Create;

use Davos\Graphy\ValueObjects\Flag;

trait CreatesRRD
{
    /**
     * @param string $file
     * @param array $flags
     * @return bool
     */
	public function create(string $file, array $flags = []): bool
	{
		$defaultFlags = [
			new Flag(CreateOptions::STEP, $this->model->getStep()->getDurationInSeconds()),
			new Flag(CreateOptions::START, $this->model->getStart()),
			new Flag(CreateOptions::NO_OVERWRITE, true),
		];
		
		$options = new CreateOptions(
			$this->model->getDataSources(),
			$this->model->getRoundRobinArchives(),
			$defaultFlags,
			$flags
		);
		
		return $this->getManager()->create($file, $options->getOptions());
	}
}
