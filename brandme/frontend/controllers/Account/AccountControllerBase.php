<?php

namespace Frontend\Controllers\Account;

use Frontend\Module;
use Frontend\Controllers\ControllerBase as Base;
use Frontend\Services\Account;
use Frontend\Services\SimpleAcl;
use Phalcon\Exception;
use Phalcon\Filter;
use Phalcon\Session\Bag;

class AccountControllerBase extends Base
{
    /**
     * @var Bag
     */
    protected $s;

    /**
     * @var String
     */
    protected $zone;

    /**
     * Forces session data to reload completely
     */
    protected function refreshSession($url = false)
    {
        $service = new Account();
        $service->initSession($this->s);
        if ($url) {
            $this->redirect($url);
        }
    }

    /**
     * Initializes user session data
     */
    protected function initialize()
    {
        parent::initialize();
        if (!$this->isLogged()) {
            $this->destroy('/');
        }
        if ($this->request->isAjax()) {
            $this->view->disable();
            $this->response->setContentType('application/json');
        }
        $this->s = new Bag('user_session');
        //load account data for remainer of session
        //$this->s->offsetUnset('account');
        $this->s->initialize();
        if (!$this->s->has('account')) {
            $service = new Account();
            $service->initSession($this->s);
        }

        //simple ACL abstraction
        if (!SimpleAcl::assertAccessPrivilege($this->router->getNamespaceName(), $this->s->get('role'))) {
            $this->redirect('/');
        }
        //Set the zone url root segment for "common" controllers
        $this->zone = ['advertiser' => 'anunciante', 'creator' => 'creador'][$this->get('role')];
        //pick the view
        $this->pickView();
        $this->view->setVars(
            array(
                'role'        => $this->get('role'),
                'firstname'   => $this->get('firstname'),
                'lastname'    => $this->get('lastname'),
                'balance'     => $this->get('balance'),
                'banner'      => $this->get('banner'),
                'avatar'      => $this->get('avatar'),
                'action'      => $this->router->getMatchedRoute()->getPattern(),
                'breadcrumbs' => $this->getBreadcrumbs()
            )
        );

    }

    /**
     * Facilitates separating account views of different roles
     */
    protected function pickView()
    {
        $namespace = $this->router->getNamespaceName();
        $controller = $this->router->getControllerName();
        $action = $this->router->getActionName();
        $this->view->pick(str_replace(['Frontend\Controllers\\', '\\'], ['', '/'], $namespace.'/'.$controller.'/'.$action));
        $this->view->setMainView('Layouts/'.$this->getRole());
    }

    /**
     * Sets and returns the user session data
     *
     * @return mixed
     */
    public function get($key = null)
    {
        if (!is_null($key)) {
            return $this->getResourceByKey($key);
        }

        return $this->s;
    }

    /**
     * Returns second tier resource of account object stored in session,
     * with alias shortcuts to entities
     *
     * @param $key
     * @return bool|mixed
     * @throws Exception
     */
    protected function getResourceByKey($key)
    {
        $key = explode('.', $key);
        $resource = false;
        foreach ($key as $path) {
            if (!$resource) {
                if (!$this->s) {
                    throw new Exception('Session is not set.');
                }
                if ($this->s->has($path)) {
                    $resource = $this->s->get($path);
                }
            } else {
                if (isset($resource->$path)) {
                    $resource = $resource->$path;
                } elseif (isset(Account::$alias[$path]) && isset($resource->{Account::$alias[$path]})) {
                    return $resource->{Account::$alias[$path]};
                } else {
                    return false;
                }
            }
        }
        if ($resource) {
            return $resource;
        }

        return false;
    }

    /**
     * Creates breadcrumb mapping of current route
     *
     * @see \Entities\Url
     * @return array
     */
    protected function getBreadcrumbs()
    {
        $router = $this->getDI()->getShared('router');
        if ($router->wasMatched()) {
            return Module::getService('Url')->prepareBreadcrumbs($router->getMatchedRoute(), $router->getRewriteUri());
        }

        return [];
    }
}