<?php
/**
 * 
 *
 * @package CCTM
 */
namespace CCTM\Controllers;

class Controller {
    
    public static $Log;
    
    /**
     * Dependency injection used here to make this more testable.
     *
     * @param object $Log for logging info
     */
    public function __construct(\Pimple $dependencies) {
        self::$Log = $dependencies['Log'];
    }    
}