<?php

namespace Frontend\Controllers;

use Frontend\Widgets\Translate;
use Phalcon\Session\Bag;
use Phalcon\Tag;

class ControllerBase extends \Phalcon\Mvc\Controller
{

    protected $javascripts = [];
    protected $stylesheets = [];

    /**
     * Build [] of assets to include in HTML
     */
    public function addAssets()
    {
        foreach (func_get_args() as $asset) {
            if (preg_match('/.*\.js$/i', $asset)) {
                $this->javascripts[] = $asset;
            } else {
                $this->stylesheets[] = $asset;
            }
        }
    }

    /**
     * I hi-jacked this event to pass the assets to the view layer
     *
     * @param $dispatcher
     */
    public function afterExecuteRoute($dispatcher)
    {
        $this->view->setVars(
            [
                'js_assets'  => $this->javascripts,
                'css_assets' => $this->stylesheets
            ]
        );
    }

    protected function initialize()
    {
        Tag::setTitle('Brandme');
    }

    /**
     * @return System wide token
     */
    protected function getToken()
    {
        return $this->getDI()->get('token');
    }

    /**
     * @return \Phalcon\Mvc\Model\Manager
     */
    protected function getManager()
    {
        return $this->getDI()->get('models');
    }

    /**
     * @return \Frontend\Services\Email
     */
    protected function getMail()
    {
        return $this->getDI()->get('email');
    }

    /**
     * Flash error message
     *
     * @param $message
     */
    protected function error($message)
    {
        $this->getDI()->get('flash')->error($message);
    }

    /**
     * Flash success message
     *
     * @param $message
     */
    protected function success($message)
    {
        $this->getDI()->get('flash')->success($message);
    }

    /**
     * Flash warning message
     *
     * @param $message
     */
    protected function warning($message)
    {
        $this->getDI()->get('flash')->warning($message);
    }

    /**
     * Flash notice message
     *
     * @param $message
     */
    protected function notice($message)
    {
        $this->getDI()->get('flash')->notice($message);
    }

    /**
     * Replaces the response->redirect() function since it doesn't work correctly
     *
     * @param string $destination
     */
    protected function redirect($destination = '/')
    {
        header('Location: '.$destination);
        exit;
    }

    /**
     * Destroys session and redirects to provided url
     *
     * @param string $destination
     */
    protected function destroy($destination = '/')
    {
        $this->session->destroy();
        header('Location: '.$destination);
        exit;
    }

    /**
     * Returns configuration property or entire configuration object
     *
     * @param null $property
     * @return object
     */
    protected function getConfig($property = null)
    {
        $config = $this->getDI()->get('config');
        if (!is_null($property)) {
            $config = $this->getDI()->get('config')->{$property};
        }

        return $config;
    }

    /**
     * Revises if user is logged in
     *
     * @return bool
     */
    protected function isLogged()
    {
        $bag = new Bag('user_session');
        $bag->initialize();

        return !is_null($bag->get('id'));
    }

    /**
     * Returns true if user is registering
     *
     * @return bool
     */
    protected function isRegistering()
    {
        $registration = new Bag('registration');

        return $registration->count();
    }

    /**
     * Returns current role or false
     *
     * @return bool
     */
    protected function getRole()
    {
        $bag = new Bag('user_session');
        $bag->initialize();
        if ($role = $bag->get('role')) {
            return $role;
        }

        return false;
    }

    /**
     * Shortcut for isset($_POST['index']) && $_POST['index'] == 'value
     *
     * @param $array
     * @param $index
     * @param $value
     * @return bool
     */
    protected function eq($array, $index, $value)
    {
        if (isset($array[$index]) && $array[$index] == $value) {
            return true;
        }

        return false;
    }

    /**
     * Another shortcut
     *
     * @param $array
     * @param $index
     * @return bool
     */
    protected function ok($array, $index)
    {
        return isset($array[$index]) && strlen(trim($array[$index]));
    }

    /**
     * Translation adaptor
     *
     * @todo find out if this causes any slowness, maybe we can cache csv file?
     * @return \Phalcon\Translate\Adapter\Csv
     */
    protected function _($query)
    {
        return Translate::_($query);
    }

    /**
     * Sanitizes input
     *
     * @param mixed $input
     * @param array $filters int, string, email, float
     * @return mixed
     */
    protected function sanitize($input, $filters = ['string', 'trim', 'striptags'])
    {
        /** \Phalcon\Filter */
        if (is_array($input)) {
            foreach ($input as $k => $v) {
                $input[$k] = $this->sanitize($v, $filters);
            }
        } elseif (is_string($input)) {
            $input = $this->filter->sanitize($input, $filters);
        }

        return $input;
    }

    /**
     * Returns sanitized $_POST array or $_POST param
     *
     * @param bool $param
     * @return mixed
     */
    protected function getPost($param = null)
    {
        return $this->sanitize($this->request->getPost($param));
    }

}