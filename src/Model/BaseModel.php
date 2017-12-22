<?php

namespace MaDnh\LaravelDevHelper\Model;

use Illuminate\Database\Eloquent\Model;
use MaDnh\LaravelDevHelper\Model\Traits\HasObserversAsProperty;
use MaDnh\LaravelDevHelper\Model\Traits\HasResourceURL;

class BaseModel extends Model
{
    use HasObserversAsProperty;
    use HasResourceURL;

    public static $hasObservers = [];

    protected $resource_prefix = null;
    /**
     * resource names
     * or
     * resource name => route name
     * @var array
     */
    protected $resource_names = []; //Maybe ['show', 'edit', 'update', 'destroy']
    protected $resource_urls = null;
    protected $resource_excepts = [];

    /**
     * resource name => permissions to check, string or array
     * @var array
     */
    protected $resource_perms = [
        'index' => 'list',
        'show' => 'detail',
        'create' => 'create',
        'store' => 'create',
        'edit' => 'edit',
        'update' => 'edit',
        'destroy' => 'delete',
        'duplicate' => ['create', 'edit']
    ];
    protected $resource_perm_aliases = [];
    protected $resource_perm_only = [];
    protected $resource_perm_excepts = [];

    /**
     * Auto append resource urls when export model
     * @var bool
     */
    protected $resource_auto_append = true;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if($this->resource_auto_append){
            $this->appends[] = 'resource_url';
        }
    }
}
