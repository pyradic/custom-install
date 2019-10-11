<?php

namespace Pyro\CustomInstall\Installer;

use Closure;

class InstallerTask extends \Anomaly\Streams\Platform\Installer\Installer
{
    const TYPE_SEED = 'seed';
    const TYPE_MIGRATE = 'migrate';
    const TYPE_INSTALL = 'install';

    protected $type;

    protected $call;

    /** @var \Anomaly\Streams\Platform\Addon\Addon */
    protected $addon;

    public function __construct($message, Closure $task)
    {
        parent::__construct($message, $task);
    }

    protected function wrapTask(Closure $wrap, Closure $task)
    {
        return function (...$params) use ($wrap, $task) {
            return $wrap($task, $params);
        };
    }

    public static function seed($message, \Closure $closure)
    {
        return with(new static($message, $closure))->setType(self::TYPE_SEED);
    }

    public static function install($message, \Closure $closure)
    {
        return with(new static($message, $closure))->setType(self::TYPE_INSTALL);
    }

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setCall($command, array $parameters = [], $outputBuffer = null)
    {
        $this->call = compact('command', 'parameters', 'outputBuffer');
        return $this;
    }

    public function isType($otherType)
    {
        return $this->type === $otherType;
    }

    public function getCall()
    {
        return $this->call;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getCallString()
    {
        return $this->call;
    }

    public function hasCall()
    {
        return $this->call !== null;
    }

    public function hasType()
    {
        return $this->type !== null;
    }

    public function getAddon()
    {
        return $this->addon;
    }

    public function hasAddon()
    {
        return $this->addon !== null;
    }

    public function setAddon($addon)
    {
        $this->addon = $addon;
        return $this;
    }


}
