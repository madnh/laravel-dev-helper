<?php


namespace MaDnh\LaravelDevHelper\Policy;

use Gate;
use MaDnh\LaravelDevHelper\Policy\Exceptions\CheckUndefinedGateAbility;

trait CheckGateBeforeMe
{
    /*
     * Throw exception when a gate is undefined
     * @var bool
     * protected $throw = true;
     */


    /*
     * ability => gate ability
     * us * as global ability
     * @var array
     * protected $check_gates = [];
     */


    protected function checkGlobalGates()
    {
        if (property_exists($this, 'check_gates') && array_key_exists('*', $this->check_gates)) {
            return $this->checkGateAbilities($this->check_gates['*']);
        }
    }

    /**
     * Check gates of an ability
     * @param string $ability
     * @return bool|null
     */
    protected function checkAbilityGates($ability)
    {
        if (!property_exists($this, 'check_gates')) {
            return null;
        }

        $gate_abilities = null;

        if (array_key_exists($ability, $this->check_gates)) {
            $gate_abilities = $this->check_gates[$ability];
        } else if (in_array($ability, $this->check_gates)) {
            $gate_abilities = $ability;
        }

        if (!empty($gate_abilities)) {
            return $this->checkGateAbilities($gate_abilities);
        }
    }

    /**
     * Check gate abilities
     * @param array|string $abilities
     * @return bool|null
     * @throws CheckUndefinedGateAbility
     */
    protected function checkGateAbilities($abilities)
    {
        foreach ((array)$abilities as $gate_ability) {
            if (!Gate::has($gate_ability)) {
                if (property_exists($this, 'throw') && $this->throw) {
                    throw new CheckUndefinedGateAbility('Check gate with an undefined ability: ' . $gate_ability);
                }

                return false;
            }
            if (!Gate::check($gate_ability)) {
                return false;
            }
        }
    }


}