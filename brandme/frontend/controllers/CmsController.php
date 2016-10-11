<?php

namespace Frontend\Controllers;

use Phalcon\Mvc\View;

/**
 * Class CmsController
 *
 * @package Frontend\Controllers
 */
class CmsController extends ControllerBase
{

    /**
     * General pseudo-CMS dispatcher, all static HTML content should go here,
     * until we have a better solution in place
     */
    public function indexAction()
    {
        $query = $this->request->getQuery();
        $view = 'index';
        if (isset($query['_url'])) {
            $view = $query['_url'];
        }
        $this->view->partial('/Cms/'.$view);
    }

    /**
     * 404 - Not found
     */
    public function route404Action()
    {
        $this->response->setStatusCode(404, 'Page not found');
    }

    /**
     * 503 - Service unavailable
     */
    public function route503Action()
    {
        $this->response->setStatusCode(503, 'Service unavailable');
    }

}

