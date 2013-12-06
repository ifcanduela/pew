<?php

namespace pew\controllers;

use \pew\Pew as Pew;

/**
 * The Pages controller can serve static views, useful for help or about pages.
 * 
 * @package pew/controllers
 * @author ifcanduela <ifcanduela@gmail.com>
 */
class Pages extends \pew\Controller
{
    /**
     * The action method of the Pages controller overwrites the same method of
     * the Controller class, to use the action parameter as a view parameter.
     *
     * Instead of this:  /pages/view/my-view-name
     * The url would be: /pages/my-view-name
     * 
     * @access public
     */
    public function _action($action, array $parameters = [])
    {
    	$this->view->title(ucwords(str_replace('_', ' ', $action)));
        $this->view->template($this->url_slug . '/' . $action);

        return [];
    }
}
