<?php


namespace MaDnh\LaravelDevHelper\Policy;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\Access\Authorizable;

class BasePolicy
{
    use CheckGateBeforeMe;

    protected $check_gates = [];
    protected $throw = true;

    /**
     *
     * @param Model|Authorizable $user
     * @param $ability
     * @return bool|null
     */
    public function before($user, $ability)
    {
        if (!is_null($result = $this->checkGlobalGates())) {
            return $result;
        }

        return $this->checkAbilityGates($ability);
    }
}