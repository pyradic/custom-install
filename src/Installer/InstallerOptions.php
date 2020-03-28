<?php /** @noinspection PhpHierarchyChecksInspection */

namespace Pyro\CustomInstall\Installer;

use Anomaly\Streams\Platform\Addon\Addon;
use Illuminate\Support\Collection;

/**
 * @property string[]|Collection $call_before
 * @property string[]|Collection $dispatch_before
 * @property string[]|Collection $call_after
 * @property string[]|Collection $dispatch_after
 * @property int[]|Collection    $skip_steps
 * @property int                 $start_from_step
 * @property bool                $ignore_exceptions
 * @property string[]|Collection $skip_install
 * @property string[]|Collection $include_seed
 * @property string[]|Collection $exclude_seed
 * @property boolean             $skip_seeds
 * @property string[]|Collection $include
 * @property string[]|Collection $exclude
 * @property bool                $skip_base_migrations
 * @property bool                $skip_base_seeds
 *
 */
class InstallerOptions extends Collection
{
    public function __construct($items = [])
    {
        $items = array_replace($this->loadDefaults(), $items);
        foreach ([
                     'call_before',
                     'dispatch_before',
                     'call_after',
                     'dispatch_after',
                     'skip_steps',
                     'include',
                     'exclude',
                     'include_seed',
                     'exclude_seed',
                 ] as $k) {
            $items[ $k ] = collect($items[ $k ]);
        }
        parent::__construct($items);
    }

    public function loadDefaults()
    {
        return [
            'call_before'          => [],
            'dispatch_before'      => [],
            'call_after'           => [],
            'dispatch_after'       => [],
            'skip_steps'           => [],
            'start_from_step'      => 1,
            'ignore_exceptions'    => false,
            'include'              => [],
            'exclude'              => [],
            'skip_base_migrations' => false,
            'skip_base_seeds'      => false,
            'skip_seeds'           => false,
            'include_seed'         => [],
            'exclude_seed'         => [],
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
        if ($this->include->hasString($namespace)) {
            return true;
        }
        if ($this->exclude->hasString($namespace)) {
            return false;
        }
        return true;
    }

    public function shouldSeed($addon)
    {
        $namespace = $this->resolveNamespace($addon);
        if ($this->skip_seeds) {
            return false;
        }
        if ($this->include_seed->hasString($namespace)) {
            return true;
        }
        if ($this->exclude_seed->hasString($namespace)) {
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
    }

    public function shouldSkipSeeds()
    {
        return true === $this->skip_seeds;
    }

    public function shouldSkipBaseMigrations()
    {
        return $this->skip_base_migrations;
    }

    public function shouldMigrateBase()
    {
        return false === $this->shouldSkipBaseMigrations();
    }

    public function shouldSkipBaseSeeds()
    {
        return $this->skip_base_seeds;
    }

    public function shouldSeedBase()
    {
        return false === $this->shouldSkipBaseSeeds();
    }

}
