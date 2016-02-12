<?php

namespace ChrisHemmings\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class DigitalOceanResourceOwner implements ResourceOwnerInterface
{
    /**
     * Raw response
     *
     * @var
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param $response
     */
    public function __construct($response)
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['account']['uuid'] ?: null;
    }

    /**
     * Return all of the details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response['account'];
    }

    /**
     * Get email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['account']['email'] ?: null;
    }

    /**
     * Get droplet limit
     *
     * @return string|null
     */
    public function getDropletLimit()
    {
        return $this->response['account']['droplet_limit'] ?: null;
    }

    /**
     * Get floating ip limit
     *
     * @return string|null
     */
    public function getFloatingIpLimit()
    {
        return $this->response['account']['floating_ip_limit'] ?: null;
    }

    /**
     * Get email verified status
     *
     * @return string|null
     */
    public function getEmailVerified()
    {
        return $this->response['account']['email_verified'] ?: null;
    }

    /**
     * Get account status
     *
     * @return string|null
     */
    public function getStatus()
    {
        return $this->response['account']['status'] ?: null;
    }

    /**
     * Get account status message
     *
     * @return string|null
     */
    public function getStatusMessage()
    {
        return $this->response['account']['status_message'] ?: null;
    }
}
