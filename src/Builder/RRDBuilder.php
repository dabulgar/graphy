<?php

namespace Davos\Graphy\Builder;

use Davos\Graphy\Create\CreatesRRD;
use Davos\Graphy\Fetch\FetchesRRD;
use Davos\Graphy\Manager\Factory\ManagerFactory;
use Davos\Graphy\Manager\Manager;
use Davos\Graphy\RRD;
use Davos\Graphy\Update\UpdatesRRD;

class RRDBuilder
{
    use CreatesRRD;
    use UpdatesRRD;
    use FetchesRRD;

    private RRD $model;
    private Manager $manager;

    public function __construct(RRD $model, ?Manager $manager = null)
    {
        $this->model = $model;

        $this->manager = $manager ?? ManagerFactory::make();
    }

    private function getManager(): Manager
    {
        return $this->manager;
    }
}
