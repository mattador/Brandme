<?php
namespace Frontend\Services;

use Phalcon\DI\Injectable;

abstract class AbstractService extends Injectable
{

    public static function getInstance()
    {
        $instance = get_called_class();
        if (!static::$instance instanceof $instance) {
            static::$instance = new $instance;
        }
        return static::$instance;
    }

    /**
     * @return \Phalcon\Mvc\Model\Manager
     */
    protected function getManager()
    {
        return $this->getDI()->get('models');
    }

    /**
     * @return \Phalcon\Security
     */
    protected function getSecurity()
    {
        return $this->getDI()->get('security');
    }

    /**
     * @return token
     */
    protected function getConfig($index = null)
    {
        $config = $this->getDI()->get('config');
        if (!is_null($index)) {
            return $config[$index];
        }
        return $config;
    }


    /**
     * @param $message
     */
    protected function log($message)
    {
        /** @var \Phalcon\Logger\Adapter\File $log */
        $log = $this->getDI()->get('logger');
        $log->error($message);
    }
}