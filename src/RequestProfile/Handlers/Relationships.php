<?php
namespace MaDnh\LaravelDevHelper\RequestProfile\Handlers;


use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use MaDnh\LaravelDevHelper\RequestProfile\Handler;

class Relationships extends Handler
{
    /**
     * Relationship details
     * @var array
     */
    public $relationships = [];

    /**
     * Loadable relationships.
     * Null if all relationships are loadable
     * @var array
     */
    public $loadable = null;

    /**
     * Blacklist of relationships
     * @var array
     */
    public $guarded = [];

    /**
     * Default relationships to load
     * @var array
     */
    public $default_load = [];

    /**
     * Add relationships to blacklist
     * @param string|array $relationships
     * @return $this
     */
    public function guarded($relationships)
    {
        $this->guarded = array_merge($this->guarded, array_flatten(func_get_args()));

        return $this;
    }

    /**
     * Set loadable relationships
     * @param string|array $relationships
     * @return $this
     */
    public function loadable($relationships)
    {
        $this->loadable = array_flatten(func_get_args());

        return $this;
    }

    /**
     * Define relationships
     * @param string|array $relationships
     * @return $this
     * @throws \Exception
     */
    public function relationships($relationships)
    {
        if (func_num_args() === 1) {
            if (is_string($relationships)) {
                $this->relationship($relationships);
            } else if (is_array($relationships)) {
                foreach ($relationships as $name) {
                    $this->relationship($name);
                }
            }
        } else {
            foreach (func_get_args() as $key => $value) {
                if (is_numeric($key)) {
                    $this->relationship($value);
                } else if (!is_array($value) && is_callable($value)) {
                    $this->relationship($key, $value);
                } else if (is_array($value) && !empty($value)) {
                    if (1 < count($value)) {
                        $name = array_shift($value);
                        $detail = array_shift($value);

                        $this->relationship($name, $detail);
                    } else {
                        $this->relationship(head($value));
                    }
                } else {
                    throw new \Exception('Relationship description must be an string of name, or name and constraining in pair, or an array of name and constraining');
                }
            }
        }


        return $this;
    }

    /**
     * Define a relationship detail
     * @param string $relationship
     * @param null|\Closure $detail
     * @return $this
     */
    public function relationship($relationship, $detail = null)
    {
        $this->relationships[$relationship] = $detail;

        return $this;
    }

    /**
     * @return array
     */
    public function getRelationship()
    {
        $withs = array_merge(array_unique($this->default_load), (array)$this->profile->get('includes', []));
        $relationships = array_only($this->relationships, $withs);

        if (!is_null($this->loadable)) {
            $relationships = array_only($relationships, $this->loadable);
        }
        if (!empty($this->guarded)) {
            $relationships = array_except($relationships, $this->guarded);
        }

        return $relationships;
    }

    /**
     *
     * @param EloquentBuilder $eloquent_query
     */
    public function eagerLoading($eloquent_query)
    {
        $relationships = $this->group_relationships();

        if (!empty($relationships['name'])) {
            $eloquent_query->with($relationships['name']);
        }
        if (!empty($relationships['constraining'])) {
            foreach ($relationships['constraining'] as $name => $detail) {
                $eloquent_query->with([$name => $detail]);
            }
        }
    }

    /**
     * @param Model|\Illuminate\Database\Eloquent\Collection $target
     */
    public function lazyEagerLoading($target)
    {
        $relationships = $this->group_relationships();

        if (!empty($relationships['name'])) {
            $target->load($relationships['name']);
        }
        if (!empty($relationships['constraining'])) {
            foreach ($relationships['constraining'] as $relation) {
                $target->load($relation);
            }
        }
    }

    /**
     * @return array
     */
    protected function group_relationships()
    {
        $relations = $this->getRelationship();
        $withs_name = [];
        $withs_constraining = [];

        foreach ($relations as $name => $detail) {
            if (is_null($detail)) {
                $withs_name[] = $name;
            } else {
                $withs_constraining[$name] = $detail;
            }
        }

        return [
            'name' => $withs_name,
            'constraining' => $withs_constraining
        ];
    }

}