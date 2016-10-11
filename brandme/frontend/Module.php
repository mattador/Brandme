<?php
namespace Frontend;

use Frontend\Services\Email;
use Hybrid\Auth;
use Phalcon\Crypt;
use Phalcon\Mvc\View;
use Plugins\Tidy;

/**
 * Class Exception
 *
 * @package Frontend
 */
class Exception extends \Exception
{
}

/**
 * Class Module
 *
 * @package Frontend
 */
class Module
{

    protected static $services = [];

    /**
     * Helper method to return a single service class
     *
     * @return bool
     */
    public static function getService($service)
    {
        $serviceClass = 'Frontend\Services\\'.str_replace('/', '\\', $service);
        if (!class_exists($serviceClass)) {
            return false;
        }
        if (!isset(self::$services[$serviceClass])) {
            self::$services[$serviceClass] = new $serviceClass;
        }

        return self::$services[$serviceClass];
    }

    public function registerAutoloaders()
    {

        /**
         * Register composer autoloader in modules explicitly where required to be efficient
         */
        //require APPLICATION_VENDOR_DIR . '/autoload.php';

        $loader = new \Phalcon\Loader();

        $loader->registerNamespaces(
            array(
                'Frontend\Controllers' => __DIR__.'/controllers/',
                'Frontend\Services'    => __DIR__.'/services/',
                'Frontend\Widgets'     => __DIR__.'/widgets/',
                'Common\Services'      => __DIR__.'/../../common/services/',
                'Entities'             => __DIR__.'/../../common/entities/',
                'Hybrid'               => APPLICATION_LIB_DIR.'/Hybrid/',
                'Plugins'              => APPLICATION_LIB_DIR.'/Plugins/',
                'Phalcon'              => APPLICATION_LIB_DIR.'/Phalcon/' //override/extend Phalcon
                //'Sendgrid' => APPLICATION_LIB_DIR . '/Sendgrid/', //Just load this with a require since it's only used in one place
            )
        );
        $loader->register();
    }

    public function registerServices(\Phalcon\DI\FactoryDefault $di)
    {
        $config = $di->get('config');

        /**
         * Setting up the view component
         */
        $di->set(
            'view',
            function () {

                $view = new \Phalcon\Mvc\View();
                $view->setViewsDir(__DIR__.'/views/');
                $view->setLayoutsDir('Layouts/');
                $view->setLayout('site');
                $view->setRenderLevel(View::LEVEL_MAIN_LAYOUT);

                // Create an event manager
                $eventsManager = new \Phalcon\Events\Manager();
                // Attach a listener for type 'view'
                $eventsManager->attach(
                    'view',
                    function ($event, \Phalcon\Mvc\View $view) {
                        if ($event->getType() == 'beforeRenderView') {
                            Tidy::afterRender($event, $view);
                        }
                    }
                );
                $view->setEventsManager($eventsManager);

                $view->registerEngines(
                    array(
                        ".volt" => function ($view, $di) {
                            $volt = new \Phalcon\Mvc\View\Engine\Volt($view, $di);
                            $volt->setOptions(
                                array(
                                    "compiledPath"      => __DIR__."/../../var/volt/",
                                    "compiledExtension" => ".php"
                                )
                            );
                            //add translation function
                            $volt->getCompiler()
                                ->addFunction(
                                    'is404',
                                    function ($resolvedArgs) {
                                        return '\Frontend\Widgets\File::is404('.$resolvedArgs.')';
                                    }
                                )
                                ->addFunction(
                                    '_',
                                    function ($resolvedArgs) {
                                        return '\Frontend\Widgets\Translate::_('.$resolvedArgs.')';
                                    }
                                )
                                ->addFunction(
                                    'currency',
                                    function ($resolvedArgs) {
                                        return '\Frontend\Widgets\Currency::format('.$resolvedArgs.')';
                                    }
                                );

                            return $volt;
                        }
                    )
                );

                return $view;
            }
        );

        //Included mainly for cookie encryption
        $di->set(
            'crypt',
            function () use ($config) {
                $crypt = new Crypt();
                $crypt->setMode(MCRYPT_MODE_CFB);
                $crypt->setKey($config->token);

                return $crypt;
            }
        );

        $di->set(
            'token',
            function () use ($config) {
                return $config->token;
            }
        );

        $di->set(
            'email',
            function () use ($config) {
                return new Email($config->email);
            }
        );

        $di->set(
            'models',
            function () {
                return new \Phalcon\Mvc\Model\Manager();
            }
        );

        $di->set(
            'security',
            function () {
                $security = new \Phalcon\Security();
                //Set the password hashing factor to 12 rounds
                $security->setWorkFactor(12);

                return $security;
            },
            true
        );

        $di->set(
            'flash',
            function () {
                $flash = new \Phalcon\Flash\Session();
                $flash->setCssClasses(
                    [
                        'success' => 'alert alert-success',
                        'error'   => 'alert alert-danger',
                        'warning' => 'alert alert-warning',
                        'notice'  => 'alert alert-info'
                    ]
                );

                //$flash->setAutomaticHtml(false); //We render the HTML ourselves so that we can group them like normal messages
                return $flash;
            }
        );

        $di->set(
            'hybrid',
            function () {
                return new Auth(__DIR__.'/../../common/config/hybrid.php');
            }
        );
    }

}