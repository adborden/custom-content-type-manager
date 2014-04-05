<?php
/**
 * Let there be logs!  This class handles logging messages to a file.
 * Should be instatianted with some dependency injection:
 *
 *      new Log(new File(), 'error_log');
 *      Log::warning('This might hurt!', __FILE__, __LINE__);
 *
 * Verbosity is controlled by the Log::$level variable so that:
 *
 *      0 : off (no errors are logged)
 *      1 : only errors are logged
 *      2 : errors and warnings are logged
 *      3 : errors, warnings, and info messages are logged
 *      4 : everything is logged.
 * 
 * The file where info is written is controlled by the Log::target variable.
 * If this is false, logging does not occur.  If it is boolean true,
 * messages are sent to the PHP system log.
 *
 * @package CCTM
 */
namespace CCTM;

class Log {
    
    public static $level = 1;
    public static $target = null;
    public static $format = '[%s @ %s %s ] %s';
    public static $Filesystem;
    public static $log_function;

    /**
     * Dependency injection used here to make this more testable.
     *
     * @param object $Filesystem used to write files.
     * @param string $log_function optional. Default: 'error_log'
     */
    public function __construct(object $Filesystem, $log_function='error_log') {
        self::$Filesystem = (method_exists($Filesystem, 'append')) ? $Filesystem : false;
        self::$log_function = (function_exists($log_function)) ? $log_function : false;
    }

    /**
     * Handles the interface with the $Filesystem
     *
     * @param string $msg
     * @param integer $level
     */
    private static function _send($msg, $level) {
        if ($level <= self::$level) {
            if (self::$target == true && self::$log_function) {
                return $log_function($msg);
            }
            elseif ($Filesystem) {
                return $Filesystem::append(self::$target, $msg);
            }
        }
    }
    
    /**
     * Log an error message.
     *
     * @param string $msg the info to be logged.
     * @param string $file where the error originated (optional)
     * @param integer $line number where the error originated (optional)
     */
	public static function error($msg, $file='unknown', $line='?') {
        return self::_send(sprintf(self::$format, strtoupper(__FUNCTION__), $file, $line, $msg), 1); 
    }
    
    /**
     * Log a debug message.
     *
     * @param string $msg the info to be logged.
     * @param string $file where the error originated (optional)
     * @param integer $line number where the error originated (optional)
     */
	public static function debug($msg, $file='unknown', $line='?') {
        return self::_send(sprintf(self::$format, strtoupper(__FUNCTION__), $file, $line, $msg), 4); 
    }

    /**
     * Log an info message.
     *
     * @param string $msg the info to be logged.
     * @param string $file where the error originated (optional)
     * @param integer $line number where the error originated (optional)
     */    
	public static function info($msg, $file='unknown', $line='?') {
        return self::_send(sprintf(self::$format, strtoupper(__FUNCTION__), $file, $line, $msg), 3); 
    }    
    
    /**
     * Log a warning message.
     *
     * @param string $msg the info to be logged.
     * @param string $file where the error originated (optional)
     * @param integer $line number where the error originated (optional)
     */    
	public static function warning($msg, $file='unknown', $line='?') {
        return self::_send(sprintf(self::$format, strtoupper(__FUNCTION__), $file, $line, $msg), 2); 
    }
        
}