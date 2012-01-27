<?php

namespace RedefineLab\FacebookService;

require_once __DIR__ . '/php-sdk/src/facebook.php';

class FacebookService {

    /**
     * @var string The application ID.
     */
    private $appId;

    /**
     * @var string The application secret.
     */
    private $appSecret;

    /**
     * @var \Facebook A Facebook object instance.
     */
    private $fb;

    /**
     * @var string The user id.
     */
    private $userId;

    /**
     * This is the FacebookService class that helps us consume Facebook API.
     * v0.1 - 2012-01-16
     *
     * @author Alessandro Perta
     * @param string $appId The application ID.
     * @param string $appSecret he application secret.
     */
    public function __construct($appId, $appSecret) {
        // Setting app ID and app secret
        $this->appId = $appId;
        $this->appSecret = $appSecret;

        // We do not instanciate the Facebook object right now,
        // since we might not always need it.
    }

    /**
     * Creates an authentication url.
     *
     * @param string $perms A string of permissions separated by commas
     * @param string $redirectUrl Where the user is redirected if he accepts perms.
     * @param string $cancelUrl Where the user is redirected if he denies perms.
     * @return string The authentication url. Do not for get to add a
     * target parent parameter to your a tag
     */
    public function createAuthUrl($perms, $redirectUrl = '', $cancelUrl = '') {
        $url = '//www.facebook.com/dialog/oauth?client_id=' . $this->appId . '&scope=' . $perms;
        $url .= '&redirect_uri=' . urlencode($redirectUrl);
        if ($cancelUrl != '') {
            $url .= '?back=' . urlencode($cancelUrl);
        }
        return $url;
    }

    /**
     * @return \Facebook A Facebook object instance.
     */
    public function getFb() {
        // Singleton-like Facebook object.
        if (!isset($this->fb)) {
            $this->fb = new \Facebook(array('appId' => $this->appId, 'secret' => $this->appSecret));
        }
        return $this->fb;
    }

    public function getProtocol() {
        if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)
                || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
        ) {
            return 'https://';
        }
        return 'http://';
    }

    public function getUser() {
        if (!isset($this->userId)) {
            $this->userId = $this->getFb()->getUser();
        }
        return $this->userId;
    }

    public function getUserCustomFields(array $fields) {
        $fields = implode(', ', $fields);

        $query = "SELECT $fields FROM user WHERE uid = " . $this->getUser();
        $fqlCall = array(
            'method' => 'fql.query',
            'query' => $query,
            'format' => 'json',
            'callback' => ''
        );

        try {
            $fqlResult = $this->getFb()->api($fqlCall);

            if (!array_key_exists(0, $fqlResult)) {
                return false;
            }

            return $fqlResult[0];
        } catch (\Exception $e) {
            return false;
        }
    }

    public function hasPermissions($perms) {
        $query = "SELECT $perms FROM permissions WHERE uid = " . $this->getUser();
        $fqlCall = array(
            'method' => 'fql.query',
            'query' => $query,
            'format' => 'json',
            'callback' => ''
        );

        try {
            $fqlResult = $this->getFb()->api($fqlCall);

            if (!array_key_exists(0, $fqlResult)) {
                return false;
            }

            foreach ($fqlResult[0] as $perm) {
                if ($perm == 0) {
                    return false;
                }
            }
            return true;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }

    public function init() {
        $this->getUser();
    }

    public function isAdmin($admins) {

        if (in_array($this->getUser(), $admins)) {
            return true;
        }

        return false;
    }

    public function redirect($redirectUrl) {
        echo '<script>window.top.location.href = "' . $this->getProtocol() . $redirectUrl . '";</script>';
    }

    public function redirectIfNoPerms($perms, $redirectUrl) {
        if (!$this->userIsConnected() || !$this->hasPermissions($perms)) {
            return $this->redirect($redirectUrl);
        }
    }

    /**
     * Checks if a user is connected.
     *
     * @return boolean
     */
    public function userIsConnected() {
        if ($this->getUser() != '0') {
            return true;
        }
        return false;
    }

    /**
     * Magic method to return original's Facebook method if it does not exist
     * within the FacebookService. Please note that this behaviour is for
     * test purposes only and should not be used in production, unless you
     * want Facebook breaking changes to affect your site.
     *
     * @param string $name
     * @param mixed $arguments
     * @return mixed
     */
    public function __call($name, $arguments) {
        if (!method_exists($this, $name)) {
            if (count($arguments) == 1) {
                return $this->getFb()->$name($arguments[0]);
            }
            return $this->getFb()->$name($arguments);
        }
    }

}