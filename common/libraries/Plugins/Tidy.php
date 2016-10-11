<?php
namespace Plugins;

class Tidy
{
    public static function afterRender($event, \Phalcon\Mvc\View $view)
    {
        $tidyConfig = array(
            'clean'               => false,
            'show-body-only'      => true,
            'wrap'                => 0,
            'hide-comments'       => true,
            'tidy-mark'           => false,
            'drop-empty-elements' => 'i',
            'indent'              => true,
            'indent-spaces'       => 2,
            //'sort-attributes'     => 'alpha',
            'vertical-space'      => false,
            'output-xhtml'        => false,
            'wrap-attributes'     => false,
            'break-before-br'     => false,
            'char-encoding'       => 'utf8',
            'input-encoding'      => 'utf8',
            'output-encoding'     => 'utf8',
        );
        $tidy = \tidy_parse_string($view->getContent(), $tidyConfig, 'UTF8');
        $tidy->cleanRepair();
        $view->setContent((string)$tidy);
    }

}