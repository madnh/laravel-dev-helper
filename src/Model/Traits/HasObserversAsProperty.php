<?php


namespace MaDnh\LaravelDevHelper\Model\Traits;

/**
 * Trait HasObserversAsProperty
 * Add observers class to model by add hasObservers property, instead of define in boot method
 */
trait HasObserversAsProperty
{
    public static function bootHasObserversAsProperty()
    {
        if (property_exists(static::class, 'hasObservers')) {
            $observers = (array)static::$hasObservers;

            foreach ($observers as $observerClass) {
                static::observe($observerClass);
            }
        }
    }
}