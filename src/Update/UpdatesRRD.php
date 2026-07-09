<?php

namespace Davos\Graphy\Update;

use Davos\Graphy\ValueObjects\Flag;

trait UpdatesRRD
{
    /**
     * @param string $file
     * @param array $data
     * @param array $flags
     * @return bool
     */
    public function update(string $file, array $data, array $flags = []): bool
    {
        $defaultFlags = [
            new Flag(UpdateOptions::TEMPLATE, true)
        ];

        $options = new UpdateOptions(
            $this->model->getDataSources(),
            $data,
            $defaultFlags,
            $flags,
        );

        return $this->getManager()->update($file, $options->getOptions());
    }
}