<?php
namespace Backend;

use Phalcon\Crypt;
use Phalcon\Mvc\View;
use Plugins\Tidy;
use SendGrid\Email;

/**
 * Class Exception
 *
 * @package Backend
 */
class Exception extends \Exception
{
}

/**
 * Class Module
 *
 * @package Backend
 */
class Module
{

    public function registerAutoloaders()
    {
        /**
         * Register composer autoloader in modules explicitly where required to be efficient
         */
        //require APPLICATION_VENDOR_DIR . '/autoload.php';

        $loader = new \Phalcon\Loader();

        $loader->registerNamespaces(
            array(
                'Backend\Controllers' => __DIR__.'/controllers/',
                'Backend\Services'    => __DIR__.'/services/',
                'Common\Services'     => __DIR__.'/../../common/services/',
                'Frontend\Services'    => __DIR__.'/../frontend/services/',
                'Frontend\Widgets'    => __DIR__.'/../frontend/widgets/',
                'Entities'            => __DIR__.'/../../common/entities/',
                'Plugins'             => APPLICATION_LIB_DIR.'/Plugins/',
                'Phalcon'             => APPLICATION_LIB_DIR.'/Phalcon/' //override/extend Phalcon
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
                $view->setLayout('admin');
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
                return new \Frontend\Services\Email($config->email);
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

    }

}