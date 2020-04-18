<?php

namespace ChrisHemmings\OAuth2\Client\Token;

use League\OAuth2\Client\Token\AccessToken;

class DigitalOceanAccessToken extends AccessToken
{
    /**
     * @var string
     */
    protected $scope;

    /**
     * @var array
     */
    protected $info;

    /**
     * @inheritDoc
     */
    public function __construct(array $options = [])
    {
        if (!empty($options['scope'])) {
            $this->scope = $options['scope'];
        }
        if (!empty($options['info'])) {
            $this->info = $options['info'];
        }
        parent::__construct($options);
    }

    /**
     * @return string[]
     */
    public function getScopes()
    {
        if (empty($this->scope)) {
            return [];
        }
        return array_filter(explode(' ', $this->scope));
    }

    /**
     * @return array|null
     */
    public function getInfo()
    {
        return $this->info;
    }

    /**
     * @param string $scope
     * @return bool
     */
    public function hasScope($scope)
    {
        $scope = trim(strtolower($scope));
        foreach ($this->getScopes() as $candidate) {
            if ($scope === strtolower(trim($candidate))) {
                return true;
            }
        }
        return false;
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        $parameters = parent::jsonSerialize();

        if ($this->scope) {
            $parameters['scope'] = $this->scope;
        }

        if (!empty($this->info)) {
            $parameters['info'] = $this->info;
        }

        return $parameters;
    }
}
