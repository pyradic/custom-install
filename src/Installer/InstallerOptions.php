<?php

namespace Pyradic\CustomInstall\Installer;


use Illuminate\Support\Collection;
use Anomaly\Streams\Platform\Addon\Addon;

/**
 * @property int[] $skip_steps
 * @property int   $start_from_step
 * @property bool  $ignore_exceptions
 * @property string[]  $skip_install
 * @property string[]  $skip_seed
 */
class InstallerOptions extends Collection
{
    public function __construct($items = [])
    {
        parent::__construct(array_replace($this->loadDefaults(), $items));
    }

    public function loadDefaults()
    {
        return  [
            'skip_steps'        => [],
            'start_from_step'   => 1,
            'ignore_exceptions' => false,
            'skip_install'      => collect(),
            'skip_seed'         => collect(),
        ];
    }

    public function __get($key)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        return parent::__get($key);
    }


    /**
     * shouldSkipInstall method
     *
     * @param \Anomaly\Streams\Platform\Addon\Addon|string $addon
     *
     * @return boolean
     */
    public function shouldSkipInstall($addon)
    {
        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
        return $this->skipInstall->contains($addon);
    }

    /**
     * shouldSkipInstall method
     *
     * @param \Anomaly\Streams\Platform\Addon\Addon|string $addon
     *
     * @return boolean
     */
    public function shouldSkipSeed($addon)
    {
        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
        return $this->skipSeed->contains($addon);
    }

}
