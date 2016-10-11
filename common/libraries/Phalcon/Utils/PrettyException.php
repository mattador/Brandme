<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Framework                                                      |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2013 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Andres Gutierrez <andres@phalconphp.com>                      |
  |          Eduar Carvajal <eduar@phalconphp.com>                         |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Utils;

/**
 * Phalcon\PrettyExceptions
 * Prints exception/errors backtraces using a pretty visualization
 */
class PrettyExceptions
{
    /**
     * Reference to the current Phalcon application instance
     */
    protected $_application;

    /**
     * Print the backtrace
     */
    protected $_showBackTrace = true;

    /**
     * Show the application's code
     */
    protected $_showFiles = true;

    /**
     * Show only the related part of the application
     */
    protected $_showFileFragment = false;

    /**
     * Show debug information about the application
     */
    protected $_showApplicationDump = false;

    /**
     * CSS theme
     */
    protected $_theme = 'default';

    /**
     * Pretty Exceptions
     */
    protected $_uri = '//cdn.rawgit.com/ovr/pretty-exceptions/master/';

    /**
     * Flag to control that only one exception/error is show at time
     */
    static protected $_showActive = false;

    /**
     * Constructor
     *
     * @param Phalcon\Mvc\Application $application OPTIONAL To display a dump of the current state of the Phalcon application instance.
     */
    public function __construct($application = null)
    {
        $this->_application =& $application;
    }

    /**
     * Set if the application's files must be opened an showed as part of the backtrace
     *
     * @param boolean $showFiles
     */
    public function showFiles($showFiles)
    {
        $this->_showFiles = $showFiles;
    }

    /**
     * Set if only the file fragment related to the exception must be shown instead of the complete file
     *
     * @param boolean $showFileFragment
     */
    public function showFileFragment($showFileFragment)
    {
        $this->_showFileFragment = $showFileFragment;
    }

    /**
     * Set to display a dump of the Phalcon application instance
     *
     * @param boolean $showApplicationDump
     */
    public function showApplicationDump($showApplicationDump)
    {
        $this->_showApplicationDump = $showApplicationDump;
    }

    /**
     * Change the base uri for css/javascript sources
     *
     * @param string $uri
     */
    public function setBaseUri($uri)
    {
        $this->_uri = $uri;
    }

    /**
     * Change the CSS theme
     *
     * @param string $theme
     */
    public function setTheme($theme)
    {
        $this->_theme = $theme;
    }

    /**
     * Set if the exception/error backtrace must be shown
     *
     * @param boolean $showBackTrace
     */
    public function showBackTrace($showBackTrace)
    {
        $this->_showBackTrace = $showBackTrace;
    }

    /**
     * Returns the css sources
     *
     * @return string
     */
    public function getCssSources()
    {
        return '<link href="'.$this->_uri.'themes/'.$this->_theme.'.css" type="text/css" rel="stylesheet" />';
    }

    /**
     * Returns the javascript sources
     *
     * @return string
     */
    public function getJsSources()
    {
        return '
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.8.3/jquery.min.js"></script>
		<script type="text/javascript" src="'.$this->_uri.'prettify/prettify.js"></script>
		<script type="text/javascript" src="'.$this->_uri.'js/pretty.js"></script>
		<script type="text/javascript" src="'.$this->_uri.'js/jquery.scrollTo-min.js"></script>';
    }

    /**
     * Returns the current framework version
     */
    public function getVersion()
    {
        if (class_exists("\Phalcon\Version")) {
            $version = \Phalcon\Version::get();
        } else {
            $version = "git-master";
        }
        $parts = explode(' ', $version);

        return '<div class="version">
			Phalcon Framework <a target="_new" href="http://docs.phalconphp.com/en/'.$parts[0].'/">'.$version.'</a>
		</div>';
    }

    protected function _escapeString($value)
    {
        $value = str_replace("\n", "\\n", $value);
        $value = htmlentities($value, ENT_COMPAT, 'utf-8');

        return $value;
    }

    protected function _getArrayDump($argument, $n = 0)
    {
        if ($n < 3 && count($argument) > 0 && count($argument) < 8) {
            $dump = array();
            foreach ($argument as $k => $v) {
                if (is_scalar($v)) {
                    if ($v === '') {
                        $dump[] = $k.' => (empty string)';
                    } else {
                        $dump[] = $k.' => '.$this->_escapeString($v);
                    }
                } else {

                    if (is_array($v)) {
                        $dump[] = $k.' => Array('.$this->_getArrayDump($v, $n + 1).')';
                        continue;
                    }

                    if (is_object($v)) {
                        $dump[] = $k.' => Object('.get_class($v).')';
                        continue;
                    }

                    if (is_null($v)) {
                        $dump[] = $k.' => null';
                        continue;
                    }

                    $dump[] = $k.' => '.$v;
                }
            }

            return join(', ', $dump);
        }

        return count($argument);
    }

    /**
     * Shows a backtrace item
     *
     * @param int   $n
     * @param array $trace
     */
    protected function _showTraceItem($n, $trace)
    {

        echo '<tr><td align="right" valign="top" class="error-number">#', $n, '</td><td>';
        if (isset($trace['class'])) {
            if (preg_match('/^Phalcon/', $trace['class'])) {
                echo '<span class="error-class"><a target="_new" href="http://docs.phalconphp.com/en/latest/api/', str_replace(
                    '\\',
                    '_',
                    $trace['class']
                ), '.html">', $trace['class'], '</a></span>';
            } else {
                $classReflection = new \ReflectionClass($trace['class']);
                if ($classReflection->isInternal()) {
                    echo '<span class="error-class"><a target="_new" href="http://php.net/manual/en/class.', str_replace(
                        '_',
                        '-',
                        strtolower($trace['class'])
                    ), '.php">', $trace['class'], '</a></span>';
                } else {
                    echo '<span class="error-class">', $trace['class'], '</span>';
                }
            }
            echo $trace['type'];
        }

        if (isset($trace['class'])) {
            echo '<span class="error-function">', $trace['function'], '</span>';
        } else {
            if (function_exists($trace['function'])) {
                $functionReflection = new \ReflectionFunction($trace['function']);
                if ($functionReflection->isInternal()) {
                    echo '<span class="error-function"><a target="_new" href="http://php.net/manual/en/function.', str_replace(
                        '_',
                        '-',
                        $trace['function']
                    ), '.php">', $trace['function'], '</a></span>';
                } else {
                    echo '<span class="error-function">', $trace['function'], '</span>';
                }
            } else {
                echo '<span class="error-function">', $trace['function'], '</span>';
            }
        }

        if (isset($trace['args'])) {
            $arguments = array();
            foreach ($trace['args'] as $argument) {
                if (is_scalar($argument)) {

                    if (is_bool($argument)) {
                        if ($argument) {
                            $arguments[] = '<span class="error-parameter">true</span>';
                        } else {
                            $arguments[] = '<span class="error-parameter">null</span>';
                        }
                        continue;
                    }

                    if (is_string($argument)) {
                        $argument = $this->_escapeString($argument);
                    }

                    $arguments[] = '<span class="error-parameter">'.$argument.'</span>';
                } else {
                    if (is_object($argument)) {
                        if (method_exists($argument, 'dump')) {
                            $arguments[] = '<span class="error-parameter">Object('.get_class($argument).': '.$this->_getArrayDump(
                                    $argument->dump()
                                ).')</span>';
                        } else {
                            $arguments[] = '<span class="error-parameter">Object('.get_class($argument).')</span>';
                        }
                    } else {
                        if (is_array($argument)) {
                            $arguments[] = '<span class="error-parameter">Array('.$this->_getArrayDump($argument).')</span>';
                        } else {
                            if (is_null($argument)) {
                                $arguments[] = '<span class="error-parameter">null</span>';
                                continue;
                            }
                        }
                    }
                }
            }
            echo '('.join(', ', $arguments).')';
        }

        if (isset($trace['file'])) {
            echo '<br/><span class="error-file">', $trace['file'], ' (', $trace['line'], ')</span>';
        }

        echo '</td></tr>';

        if ($this->_showFiles) {
            if (isset($trace['file'])) {

                echo '</table>';

                $line = $trace['line'];
                $lines = file($trace['file']);

                if ($this->_showFileFragment) {
                    $numberLines = count($lines);
                    $firstLine = ($line - 7) < 1 ? 1 : $line - 7;
                    $lastLine = ($line + 5 > $numberLines ? $numberLines : $line + 5);
                    echo "<pre class='prettyprint highlight:".$firstLine.":".$line." linenums:".$firstLine."'>";
                } else {
                    $firstLine = 1;
                    $lastLine = count($lines) - 1;
                    echo "<pre class='prettyprint highlight:".$firstLine.":".$line." linenums error-scroll'>";
                }

                for ($i = $firstLine; $i <= $lastLine; ++$i) {

                    if ($this->_showFileFragment) {
                        if ($i == $firstLine) {
                            if (preg_match('#\*\/$#', rtrim($lines[$i - 1]))) {
                                $lines[$i - 1] = str_replace("* /", "  ", $lines[$i - 1]);
                            }
                        }
                    }

                    if ($lines[$i - 1] != PHP_EOL) {
                        $lines[$i - 1] = str_replace("\t", "  ", $lines[$i - 1]);
                        echo htmlentities($lines[$i - 1], ENT_COMPAT, 'UTF-8');
                    } else {
                        echo '&nbsp;'."\n";
                    }
                }
                echo '</pre>';

                echo '<table cellspacing="0">';
            }
        }
    }

    /**
     * Returns human readable dump of the current Phalcon application instance.
     *
     * @param Phalcon\Mvc\Application $application OPTIONAL To display a dump of the current state of the Phalcon application instance.
     */
    protected function getApplicationDump($application)
    {
        $application = is_null($application) ? $this->_application : $application;

        if (!$this->_showApplicationDump || !($application instanceof \Phalcon\Mvc\Application)) {
            return;
        }

        return '<pre class="prettyprint error-scroll">'.print_r($application, true).'</pre>';
    }

    /**
     * Handles exceptions
     *
     * @param Exception               $e
     * @param Phalcon\Mvc\Application $application OPTIONAL To display a dump of the current state of the Phalcon application instance.
     * @return boolean
     */
    public function handle($e, $application = null)
    {

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (self::$_showActive) {
            echo $e->getMessage();

            return;
        }

        self::$_showActive = true;

        echo '<html><head><title>Exception - ', get_class($e), ': ', $e->getMessage(), '</title>', $this->getCssSources(), '</head><body>';

        echo '<div class="error-main">
			', get_class($e), ': ', $e->getMessage(), '
			<br/><span class="error-file">', $e->getFile(), ' (', $e->getLine(), ')</span>
		</div>';

        if ($this->_showBackTrace) {
            echo '<div class="error-backtrace"><table cellspacing="0">';
            foreach ($e->getTrace() as $n => $trace) {
                $this->_showTraceItem($n, $trace);
            }
            echo '</table></div>';
        }

        echo $this->getApplicationDump($application);

        echo $this->getVersion();

        echo $this->getJsSources().'</body></html>';

        self::$_showActive = false;

        return true;
    }

    /**
     * Handles errors/warnings/notices
     *
     * @param int                     $errorCode
     * @param string                  $errorMessage
     * @param string                  $errorFile
     * @param int                     $errorLine
     * @param Phalcon\Mvc\Application $application OPTIONAL To display a dump of the current state of the Phalcon application instance.
     */
    public function handleError($errorCode, $errorMessage, $errorFile, $errorLine, $application = null)
    {

        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (self::$_showActive) {
            echo $errorMessage;

            return false;
        }

        if (!(error_reporting() & $errorCode)) {
            return false;
        }

        self::$_showActive = true;

        header("Content-type: text/html");

        echo '<html><head><title>Exception - ', $errorMessage, '</title>', $this->getCssSources(), '</head><body>';

        echo '<div class="error-main">
			', $errorMessage, '
			<br/><span class="error-file">', $errorFile, ' (', $errorLine, ')</span>
		</div>';

        if ($this->_showBackTrace) {
            echo '<div class="error-backtrace"><table cellspacing="0">';
            foreach (debug_backtrace() as $n => $trace) {
                if ($n == 0) {
                    continue;
                }
                $this->_showTraceItem($n, $trace);
            }
            echo '</table></div>';
        }

        $this->showApplicationDump($application);

        echo $this->getVersion();

        echo $this->getJsSources().'</body></html>';

        self::$_showActive = false;

        return true;
    }

}
