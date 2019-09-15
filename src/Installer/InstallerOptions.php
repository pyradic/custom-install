<?php /** @noinspection PhpHierarchyChecksInspection */

namespace Pyradic\CustomInstall\Installer;


use Illuminate\Support\Collection;
use Anomaly\Streams\Platform\Addon\Addon;

/**
 * @property int[]|Collection    $skip_steps
 * @property int                 $start_from_step
 * @property bool                $ignore_exceptions
 * @property string[]|Collection $skip_install
 * @property string[]|Collection $skip_seed
 */
class InstallerOptions extends Collection
{
    public function __construct($items = [])
    {
        $items = array_replace($this->loadDefaults(), $items);
        foreach ([ 'skip_steps', 'skip_install', 'skip_seed' ] as $k) {
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
        ];
    }

    public function __get($key)
    {
        if ($this->has($key)) {
            return $this->get($key);
        }
        return parent::__get($key);
    }

    public function shouldSkipStep($step)
    {
        return $step < $this->start_from_step || $this->skip_steps->contains($step);
    }

    /**
     * shouldSkipInstall method
     *
     * @param \Anomaly\Streams\Platform\Addon\Addon|string $addon
     * @return boolean
     */
    public function shouldSkipInstall($addon)
    {
        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
        return $this->skip_install->contains($addon);
    }

    /**
     * shouldSkipInstall method
     *
     * @param \Anomaly\Streams\Platform\Addon\Addon|string $addon
     * @return boolean
     */
    public function shouldSkipSeed($addon)
    {
        $addon = $addon instanceof Addon ? $addon->getNamespace() : $addon;
        return $this->skip_seed->contains($addon);
    }
}
