<?php /** @noinspection PhpHierarchyChecksInspection */

namespace Pyro\CustomInstall\Installer;


use Anomaly\Streams\Platform\Addon\Addon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * @property int[]|Collection    $skip_steps
 * @property int                 $start_from_step
 * @property bool                $ignore_exceptions
 * @property string[]|Collection $skip_install
 * @property string[]|Collection $skip_seed
 * @property string[]|Collection $include
 * @property string[]|Collection $exclude
 */
class InstallerOptions extends Collection
{
    public function __construct($items = [])
    {
        $items = array_replace($this->loadDefaults(), $items);
        foreach ([ 'skip_steps', 'skip_install', 'skip_seed','include','exclude' ] as $k) {
            $items[ $k ] = collect($items[ $k ]);
        }
        parent::__construct($items);
    }

    public function loadDefaults()
    {
        return [
            'skip_steps'        => [],
            'start_from_step'   => 1,
            'ignore_exceptions' => false,
            'skip_install'      => [],
            'skip_seed'         => [],
            'include'           => [],
            'exclude'           => [],
        ];
    }

    public function __get($key)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        return parent::__get($key);
    }

    protected function resolveNamespace($addon)
    {
        return $addon instanceof Addon ? $addon->getNamespace() : $addon;
    }

    public function shouldInstall($addon)
    {
        $namespace = $this->resolveNamespace($addon);
        if($this->include->hasString($namespace)){
            return true;
        }
        if($this->exclude->hasString($namespace)){
            return false;
        }
        if($this->skip_install->hasString($namespace)){
            return false;
        }
        return true;
    }

    public function shouldSeed($addon)
    {
        $namespace = $this->resolveNamespace($addon);
        if($this->skip_seed->hasString($namespace)){
            return false;
        }
        return true;
    }


    public function shouldSkipStep($step)
    {
        return $step < $this->start_from_step || $this->skip_steps->contains($step);
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
        return false === $this->shouldInstall($addon);
//        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
//        return $this->skip_install->contains($addon);
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
        return false === $this->shouldSeed($addon);
//        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
//        return $this->skip_seed->contains($addon);
    }
}
