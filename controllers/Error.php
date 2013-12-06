<?php

namespace pew\controllers;

/**
 * Error management class.
 *
 * This is a class for error management, to be used by the framework. It can be
 * extended by the user.
 *
 * @author ifcanduela <ifcanduela@gmail.com>
 * @package pew/controllers
 */
class Error extends \pew\Controller
{
    const NO_ERROR = 0;
    const VIEW_MISSING = 1;
    const LAYOUT_MISSING = 2;
    const CONTROLLER_MISSING = 3;
    const ACTION_MISSING = 4;
    const ELEMENT_MISSING = 5;
    const CONTROLLER_FILE_MISSING = 6;
    const CONTROLLER_CLASS_MISSING = 7;
    const MODEL_FILE_MISSING = 8;
    const MODEL_CLASS_MISSING = 9;
    const LIBRARY_FILE_MISSING = 10;
    const LIBRARY_CLASS_MISSING = 11;
    const ACTION_FORBIDDEN = 12;

    public $layout = 'error';

    /**
     * Error code.
     *
     * Error codes are found in /sys/config.php
     *
     * @var integer
     * @access private
     */
    private $error_code;
    
    /**
     * Error subject.
     * 
     * Information about the subject of the error, it's an array of all the
     * arguments received by the constructor.
     *
     * @var array
     * @access private
     */
    private $subject;
    
    /**
     * Creates an Error object with context-dependant parameters related to the
     * error.
     *
     * Additional parameters may include class name, view name, action name or 
     * file name.
     * 
     * @param $error_code int One of the error constants defined in config.php
     * @param $subject Mixed A series of values referring to the subject of the
     *                       error. the first one usually refers to the 
     *                       controller/model/layout/element, and the second to 
     *                       the action/view/filename.
     *                       
     * @access public
     */
    public function __construct($request)
    {
        parent::__construct($request);

        # If the script is not running on DEBUG, execution is definitely stopped
        if (!$this->pew['debug']) {
            # Render standard error page
            $this->show_404();
        }
    }

    public function _set_error($error_code)
    {
        $this->error_code = $error_code;
    }

    /**
     * Renders the error message template with the information provided,
     *
     * @access private
     */
    private function _render()
    {
        switch ($this->error_code)
        {
            case 404:
                $this->view = '404';
                $this->show_404();
                break;
            # The file for the requested controller is not found in
            # app/controllers
            case self::CONTROLLER_FILE_MISSING:
                $error_title = 'Controller File Missing';
                $controller_name = $this->subject[1];
                $controller_file_name = class_name_to_file_name($this->subject[1]) . '.class.php';
                $folder = CONTROLLERS;
                $error_text = <<<ERROR_TEXT
The file for the requested controller does not exist. Create 
file <strong>$controller_file_name</strong> for 
class <strong>$controller_name</strong> in 
folder <strong>$folder</strong>.
ERROR_TEXT;
                break;
                
            # The class for the requested controller is not defined in the
            # controller file
            case self::CONTROLLER_CLASS_MISSING:
                $error_title = 'Controller Class Missing';
                $controller_name = $this->subject[1];
                $controller_file_name = $this->subject[2];
                $folder = CONTROLLERS;
                $error_text = <<<ERROR_TEXT
The requested controller cannot be found. Create 
class <strong>$controller_name</strong> in
file $folder<strong>$controller_file_name</strong>.
ERROR_TEXT;
                break;
                
            # The class for the requested controller is not defined in the
            # controller file
            case self::ACTION_MISSING:
                $error_title = 'Action Missing';
                $controller_name = get_class($this->subject[1]);
                $action_name = $this->subject[2];
                $controller_file_name = class_name_to_file_name($controller_name) . CONTROLLER_EXT;
                $folder = CONTROLLERS;
                $error_text = <<<ERROR_TEXT
The requested action is not defined. Create method <strong>$action_name</strong> for 
Controller <strong>$controller_name</strong> in 
file $folder<strong>$controller_file_name</strong>.
ERROR_TEXT;
                break;
                
            # The file for the model is not found in app/models
            # This error should not happen: if the file is not found, the base
            # Model class is instanced and a warning is logged
            case self::MODEL_FILE_MISSING:
                $error_title = 'Embarrasing Error';
                $model_name = $this->subject[1];
                $model_file_name = $this->subject[2] . MODEL_EXT;
                $folder = MODELS;
                $error_text = <<<ERROR_TEXT
The model file does not exist. Create 
file <strong>$model_file_name</strong> for 
class <strong>$model_name</strong> in
folder <strong>$folder</strong>.
ERROR_TEXT;
                break;
            
            # The file for the model was found, but the correct Model class is
            # not defined therein
            case self::LAYOUT_MISSING:
                $error_title = 'Layout Missing';
                $layout_file_name = $this->subject[1] . LAYOUT_EXT;
                $folder = VIEWS;
                $error_text = <<<ERROR_TEXT
The layout file could not be found. Create file $layout_file_name in $folder.
ERROR_TEXT;
                break;
            
            # The file for the model was found, but the correct Model class is
            # not defined therein
            case self::VIEW_MISSING:
                $error_title = 'View Missing';
                $view_file_name = $this->subject[2] . VIEW_EXT;
                $folder = VIEWS . $this->subject[1];
                $error_text = <<<ERROR_TEXT
The view file could not be found. Create file <strong>$view_file_name</strong>
in <strong>$folder</strong>.
ERROR_TEXT;
                break;
            
            # The file for the model was found, but the correct Model class is
            # not defined therein
            case self::ELEMENT_MISSING:
                $error_title = 'Element Missing';
                $element_file_name = $this->subject[1] . ELEMENT_EXT;
                $folder = ELEMENTS;
                $error_text = <<<ERROR_TEXT
The element file could not be found. Create file $element_file_name in $folder.
ERROR_TEXT;
                break;
            
            # The file for the model was found, but the correct Model class is
            # not defined therein
            case self::LIBRARY_CLASS_MISSING:
                $error_title = 'Library Missing';
                $library_name = class_name_to_file_name($this->subject[1]) . ELEMENT_EXT;
                $library_name = $this->subject[1];
                $folder = LIBRARIES;
                $error_text = <<<ERROR_TEXT
The requested library cannot be found. Create 
class <strong>$library_name</strong> in
file $folder<strong>$library_file_name</strong>.
ERROR_TEXT;
                break;
            
            # The file for the model was found, but the correct Model class is
            # not defined therein
            case self::LIBRARY_FILE_MISSING:
                $error_title = 'Library Missing';
                $library_file_name = $this->subject[1] . LIBRARY_EXT;
                $library_name = file_name_to_class_name($this->subject[1]);
                $folder = LIBRARIES;
$error_text = <<<ERROR_TEXT
The library file does not exist. Create 
file <strong>$library_file_name</strong> for 
class <strong>$library_name</strong> in
folder <strong>$folder</strong>.
ERROR_TEXT;
                break;
            
            # A fallback error message
            default:
                $error_title = 'Pew Error';
                $error_text = 'That was a mistake.';
                break;
        }
        
        require $this->pew['system_folder'] . '/views/error.layout.php';
        
        exit();
    }
    
    /**
     * This function displays the 404 HTTP error: Document Not Found
     *
     * This shows a picture of a cat.
     *
     * @return void
     */
    public function show_404()
    {
        header("HTTP/1.0 404 Not Found");
        include($this->pew['system_folder'] . '/views/' . $this->url_slug . '/404.php');
        exit(404);
    }
}
