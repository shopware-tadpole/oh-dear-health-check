<?php declare(strict_types=1);

namespace Tadpole\OhDear\Check;

abstract class AbstractCheck {

    abstract protected function getHealthCheckStruct();
}