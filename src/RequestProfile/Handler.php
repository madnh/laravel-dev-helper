<?php
namespace MaDnh\LaravelDevHelper\RequestProfile;


class Handler
{
    /**
     * @var Profile
     */
    public $profile;

    /**
     * Handler constructor.
     * @param Profile $profile
     */
    public function __construct($profile = null)
    {
        $this->profile = $profile;
    }

    /**
     * @param Profile $profile
     * @return $this
     */
    public function useProfile($profile)
    {
        $this->profile = $profile;

        return $this;
    }

    /**
     * @param Profile $profile
     * @return static
     */
    public static function using($profile)
    {
        $instance = new static($profile);

        return $instance;
    }
}