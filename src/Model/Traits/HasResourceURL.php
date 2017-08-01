<?php


namespace MaDnh\LaravelDevHelper\Model\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

trait HasResourceURL
{
    /*
    protected $resource_names = ['show', 'edit', 'update', 'destroy'];
    protected $resource_urls = null;
    protected $resource_excepts = [];

    protected $resource_perm_prefix = null;
    protected $resource_names = []; //Maybe ['show', 'edit', 'update', 'destroy']
    protected $resource_perm_only = [];
    protected $resource_perm_excepts = [];
    protected $resource_perm_aliases = [
        'index' => 'list',
        'show' => 'list',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete'
    ];
    */

    protected $resource_urls_cached = null;

    public function getResourceURLAttribute()
    {
        if (is_array($this->resource_urls_cached)) {
            return $this->resource_urls_cached;
        }

        $this->resource_urls_cached = [];
        $resourceTypeName = $this->getResourceTypeName();
        $resourcePrefix = $this->getResourcePrefix();
        $resourceParameters = $this->getResourceParameters();

        $resource_full = (!empty($resourcePrefix) ? ($resourcePrefix . '.') : '') . $resourceTypeName;
        $resource_names = array_except($this->arrayHasKeys($this->getResourceNames()), $this->getExceptResources());
        $resourceRequirePerms = $this->getRequirePermResources();

        $user = \Auth::user();
        foreach ($resource_names as $resource_name => $resource_route_name) {
            try {
                if (array_key_exists($resource_name, $resourceRequirePerms)) {
                    if (!$user || !$this->checkResourcePerm($user, $resourceRequirePerms[$resource_name], $resourceParameters)) {
                        continue;
                    }
                }

                $this->resource_urls_cached[$resource_name] = route($resource_full . '.' . $resource_route_name, $resourceParameters);
            } catch (\Exception $e) {
                if (config('app.debug')) {
                    throw $e;
                } else {
                    logger()->error($e);
                }
            }
        }

        return $this->resource_urls_cached;
    }

    /**
     * Make source array keys is string or item' value
     * Ex: ['a', 'b' => 'B'] => ['a' => 'a', 'b' =>'B']
     *
     * @param $array
     * @return array
     */
    protected function arrayHasKeys($array)
    {
        $result = [];

        foreach ($array as $index => $item) {
            $result[is_int($index) ? $item : $index] = $item;
        }

        return $result;
    }

    /**
     * @param Authorizable $user
     * @param array|string $abilities
     * @param null|Model|array $resourceParameters
     * @return bool
     */
    protected function checkResourcePerm($user, $abilities, $resourceParameters = null)
    {
        foreach ((array)$abilities as $ability) {
            if (\Gate::has($ability) && \Gate::allows($ability)) {
                continue;
            }
            if ($user->cannot($ability, is_null($resourceParameters) ? $this : array_values($resourceParameters))) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array|bool
     */
    protected function getRequirePermResources()
    {
        $resourceNames = $this->getResourceNames();
        $resourcePerms = $this->arrayHasKeys($this->getResourcePerms());
        $permOnly = property_exists($this, 'resource_perm_only') ? (array)$this->resource_perm_only : [];
        $permExcepts = property_exists($this, 'resource_perm_excepts') ? (array)$this->resource_perm_excepts : [];
        $resourceRequirePerms = array_except(
            !empty($permOnly) ? array_only($resourcePerms, $permOnly) : $resourcePerms,
            $permExcepts
        );

        return array_only($resourceRequirePerms, $resourceNames);
    }

    protected function getResourcePrefix()
    {
        if (property_exists($this, 'resource_prefix') && !empty($this->resource_prefix)) {
            return $this->resource_prefix;
        }

        return '';
    }

    protected function getResourceTypeName()
    {
        if (property_exists($this, 'resource') && !empty($this->resource)) {
            $resource = $this->resource;
        } else {
            $resource = strtolower(str_plural(snake_case(class_basename(static::class))));
        }

        return $resource;
    }

    protected function getResourceParameters()
    {
        $baseParameters = [$this->getResourceTypeName() => $this];
        $additionParameters = (array)$this->getAdditionResourceParameters();

        return array_merge($baseParameters, $additionParameters);
    }

    protected function getAdditionResourceParameters()
    {
        return [];
    }

    protected function getResourceNames()
    {
        if (property_exists($this, 'resource_names')) {
            return (array)$this->resource_names;
        }

        return [];
    }


    /**
     * @return array
     */
    protected function getResourcePerms()
    {
        if (property_exists($this, 'resource_perms')) {
            return (array)$this->resource_perms;
        }

        return [];
    }

    protected function getExceptResources()
    {
        if (property_exists($this, 'resource_excepts')) {
            return $this->resource_excepts;
        }

        return [];
    }


}