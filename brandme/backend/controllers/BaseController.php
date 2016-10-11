<?php

namespace Backend\Controllers;

use Phalcon\Session\Bag;

/**
 * Class BaseController for the Admin area
 *
 * @package Backend\Controllers
 */
class BaseController extends \Phalcon\Mvc\Controller
{
    protected $javascripts = [];
    protected $stylesheets = [];

    /**
     * @var Bag
     */
    protected $s;

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
     * Initializes user session data
     */
    protected function initialize()
    {
        if (!$this->isLogged()) {
            $this->destroy('/');
        }
        $this->s = new Bag('user_session');
        $this->s->initialize();
        if (!$this->s->has('account')) {
            $this->destroy('/');
        }
        if ($this->s->get('account')->getIsAdministrator() != 1) {
            //don't let the user think he has hit an important URL, just 404 him
            $this->redirect('/404');
        }
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

    /**
     * @return \Phalcon\Mvc\Model\Manager
     */
    protected function getManager()
    {
        return $this->getDI()->get('models');
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
     * @return \Frontend\Services\Email
     */
    protected function getMail()
    {
        return $this->getDI()->get('email');
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