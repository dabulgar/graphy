<?php

namespace Davos\Graphy\Fetch\Group;

enum WatermarkLevel
{
    case Started;
    case Inside;
    case HitBoundary;
}
