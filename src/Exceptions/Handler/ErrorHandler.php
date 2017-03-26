<?php

namespace Core\Exceptions\Handler;

class ErrorHandler
{

    /** @var bool True if error occurred at least once */
    protected static $isErrorOccurred = false;

    /** Return true if error occurred at least once, false otherwise
     * @return bool
     */
    public static function isErrorOccurred() : bool
    {
        return self::$isErrorOccurred;
    }

    public static function catchException(\Throwable $e) : bool
    {
        # Clean (erase) the output buffer and turn off output buffering
        while (ob_get_level()) ob_end_clean();

        self::prepareEngineHighlight();
        $style = self::getExceptionsHtmlStyle();

        $html  = '<div class="php-exception-content">';
        $html .= '<p class="php-exception-message">'.$e->getMessage().'</p>';

        $traceList  = '<ul class="php-exception-trace" id="php-exception-stack-trace-list">';
        $traceBlock = '<div class="php-exception-blocks">';

        foreach($e->getTrace() as $key => $trace){
            if(!isset($trace['file'])) $trace['file'] = '';
            if(!isset($trace['line'])) $trace['line'] = 0;
            if(!isset($trace['class'])) $trace['class'] = '';
            if(!isset($trace['type'])) $trace['type'] = '';
            $listLabel = $trace['class'].$trace['type'].$trace['function'];
            $traceList .= '<li class="php-exception-trace-row" trace-id="'.$key.'">'.$listLabel.'</li>';
            $traceBlock .= '<div class="php-exception-trace-block" id="trace-id-'.$key.'" style="display: none;">';
            $traceBlock .= '<p>Function parameters</p>';
            $class = $trace['class'].$trace['type'].$trace['function'].'('.self::parseFunctionArgs($trace['args']).')';
            $traceBlock .= '<div class="php-code-sample"><code>'.$class.'</code></div>';
            $traceBlock .= '<p>Source code</p>';
            $traceBlock .= '<div class="php-code-sample">'.self::getCodeSample($trace['file'], $trace['line']).'</div>';
            $traceBlock .= '</div>';
        }
        $traceList .= '<li class="php-exception-trace-row env-block" trace-id="'.($key + 1).'"><i>Environment variables</i></li>';
        $traceList .= '<li class="php-exception-trace-row env-block" trace-id="'.($key + 2).'"><i>Loaded PHP extensions</i></li>';
        $traceList .= '<li class="php-exception-trace-row env-block" trace-id="'.($key + 3).'"><i>Included files</i></li>';
        $traceBlock .= '<div class="php-exception-trace-block" id="trace-id-'.($key + 1).'" style="display: none;">';
        if(isset($_GET) && count($_GET)) $traceBlock .= '<p>$_GET</p><div class="php-code-sample"><code>'.self::varDumpToString($_GET).'</code></div>';
        if(isset($_POST) && count($_POST)) $traceBlock .= '<p>$_POST</p><div class="php-code-sample"><code>'.self::varDumpToString($_POST).'</code></div>';
        if(isset($_COOKIE) && count($_COOKIE)) $traceBlock .= '<p>$_COOKIE</p><div class="php-code-sample"><code>'.self::varDumpToString($_COOKIE).'</code></div>';
        if(isset($_SESSION) && count($_SESSION)) $traceBlock .= '<p>$_SESSION</p><div class="php-code-sample"><code>'.self::varDumpToString($_SESSION).'</code></div>';
        if(isset($_REQUEST) && count($_REQUEST)) $traceBlock .= '<p>$_REQUEST</p><div class="php-code-sample"><code>'.self::varDumpToString($_REQUEST).'</code></div>';
        if(isset($_SERVER) && count($_SERVER)) $traceBlock .= '<p>$_SERVER</p><div class="php-code-sample"><code>'.self::varDumpToString($_SERVER).'</code></div>';
        $traceBlock .= '</div>';

        $loadedExtensions = '<ul>';
        foreach(get_loaded_extensions() as $extension){
            //$extensionFuncs = get_extension_funcs(strtolower($extension));
            $loadedExtensions .= '<li>'.$extension.str_repeat('&nbsp;', 15 - strlen($extension))." (".phpversion(strtolower($extension)).')';
            //if(is_array($extensionFuncs)) $loadedExtensions .= ' - '.implode(', ', $extensionFuncs);
            $loadedExtensions .= '</li>';
        }
        $loadedExtensions .= '</ul>';

        $loadedFiles = '<ul>';
        foreach(get_included_files() as $file){
            $loadedFiles .= '<li>'.$file;
            $loadedFiles .= '</li>';
        }
        $loadedFiles .= '</ul>';

        $traceBlock .= '<div class="php-exception-trace-block" id="trace-id-'.($key + 2).'" style="display: none;">';
        $traceBlock .= '<p>PHP Extensions loaded</p><div class="php-code-sample"><code>'.$loadedExtensions.'</code></div>';
        $traceBlock .= '</div>';

        $traceBlock .= '<div class="php-exception-trace-block" id="trace-id-'.($key + 3).'" style="display: none;">';
        $traceBlock .= '<p>List of included files</p><div class="php-code-sample"><code>'.$loadedFiles.'</code></div>';
        $traceBlock .= '</div>';

        $traceBlock .= '</div>';
        $html .= $traceList.'</ul>'.$traceBlock;
        $html .= '</div>';

        $html .= self::getExceptionsHtmlScripts();
        self::outputWithHtmlWrapper($style.$html, "Exception");

        return true;
    }

    /**
     * @param array ...$var
     * @return string
     */
    protected static function varDumpToString(...$var) : string {
        ob_start();
        var_dump(...$var);
        return ob_get_clean();
    }

    /**
     * @return string
     */
    protected static function phpInfoToString() : string {
        ob_start();
        phpinfo();
        return ob_get_clean();
    }

    protected static function parseFunctionArgs(array $args) : string
    {
        foreach ($args as $pos => $arg){
            $args[$pos] = gettype($arg).' '.var_export($arg, true);
        }

        return implode(', ', $args);
    }

    protected static function prepareEngineHighlight() : void
    {
        ini_set("highlight.comment", "#629755");
        ini_set("highlight.default", "#705c83");
        ini_set("highlight.html", "#808080");
        ini_set("highlight.keyword", "#cc7832");
        ini_set("highlight.string", "#7097d4");
    }

    protected static function getCodeSample(string $filePath, int $lineNumber, int $contextLines = 6) : string
    {
        if($filePath == '') return '';
        $startLine = $lineNumber - $contextLines;
        $endLine = $lineNumber + $contextLines;

        $fileLines = explode('<br />', highlight_file($filePath, true));

        # Match start and end lines
        foreach($fileLines as $line => $content){
            if(strstr($content, '<span') && $line < ($lineNumber - $contextLines)) $startLine = $line;
            if(strstr($content, '</span') && $line > ($lineNumber + $contextLines)){
                $endLine = $line;
                break;
            }
        }

        $fileContent = '';
        foreach($fileLines as $currentLine => $line) {
            if($currentLine < $startLine) continue;
            if($currentLine > $endLine) break;
            if($currentLine == $lineNumber - 1){
                $fileContent .= '<div style="background: #313131;">'.$line."<br></div>";
            } else {
                $fileContent .= $line."<br>";
            }
        }

        return $fileContent;
    }

    /**
     * @param int $errorNumber
     * @return string
     */
    protected static function getErrorType( int $errorNumber ) : string
    {
        switch ($errorNumber) {

            case E_ERROR:
            case E_USER_ERROR:
            case E_RECOVERABLE_ERROR:
                $errorType = 'php-error';
                break;

            case E_WARNING:
            case E_USER_WARNING:
                $errorType = 'php-warning';
                break;

            case E_NOTICE:
            case E_USER_NOTICE:
                $errorType = 'php-notice';
                break;

            case E_DEPRECATED:
            case E_USER_DEPRECATED:
                $errorType = 'php-deprecated';
                break;

            case E_STRICT:
                $errorType = 'php-strict';
                break;

            case E_PARSE:
                $errorType = 'php-parse';
                break;

            case E_CORE_ERROR:
                $errorType = 'php-core-error';
                break;

            case E_CORE_WARNING:
                $errorType = 'php-core-warning';
                break;

            case E_COMPILE_ERROR:
                $errorType = 'php-compile-error';
                break;

            case E_COMPILE_WARNING:
                $errorType = 'php-compile-warning';
                break;

            default:
                $errorType = 'php-unknown-error';
                break;
        }

        return $errorType;
    }

    public static function catchError(int $errorNumber , string $errorString, string $errorFile, int $errorLine ) : bool {

        if(!self::$isErrorOccurred){
            echo self::getErrorsHtmlStyle();
            self::$isErrorOccurred = true;
        }

        $errorType = self::getErrorType($errorNumber);

        $msg = '<p>'.$errorString.' w '.'<link>'.$errorFile.' in line '.$errorLine.'</link></p>';
        self::outputWithSimpleWrapper($msg, $errorType);

        return true;
    }

    /**
     * @return string
     */
    protected static function getErrorsHtmlStyle() : string
    {
        $div = 'div.php-error-content';
        $htmlStyle  = $div.' { font: 11px "Lucida Grande", "Lucida Sans Unicode", "Garuda", "Malayalam", Arial, sans-serif; }';
        $htmlStyle .= $div.' code { font: 11px Lucida Console, Monaco, monospace; }';
        $htmlStyle .= $div.' link { font: 11px Lucida Console, Monaco, monospace; }';
        $htmlStyle .= $div.'.php-warning { color: #e29400 }';
        $htmlStyle .= $div.'.php-notice { color: #3f67ff }';
        $htmlStyle .= $div.'.php-error { color: #e20000 }';
        $htmlStyle .= $div.'.php-parse { color: #e20000 }';
        $htmlStyle .= $div.'.php-deprecated { color: #b700e2 }';
        $htmlStyle .= $div.'.php-strict { color: #b700e2 }';
        $htmlStyle .= $div.'.php-core-error { color: #e20000 }';
        $htmlStyle .= $div.'.php-core-warning { color: #e29400 }';
        $htmlStyle .= $div.'.php-compile-error { color: #e20000 }';
        $htmlStyle .= $div.'.php-compile-warning { color: #e29400 }';

        return '<style>'.$htmlStyle.'</style>';
    }

    /**
     * @return string
     */
    protected static function getExceptionsHtmlStyle() : string
    {
        $div = 'div.php-exception-content';
        $style  = $div.' { font: 11px "Lucida Grande", "Lucida Sans Unicode", "Garuda", "Malayalam", Arial, sans-serif; }';
        $style .= $div.' code { font: 11px Lucida Console, Monaco, monospace; color: #717171 }';
        $style .= $div.' p.php-exception-message { border: 1px solid #333; padding: 5px; background: #171717; color: #bbb; }';
        $style .= $div.' p.php-exception-message a { color: #5b71e6 }';
        $style .= $div.' .php-exception-blocks { position: absolute; left: 361px; right: 10px; top: 100px; border: 1px solid #333;  }';
        $style .= $div.' .php-exception-blocks .php-exception-trace-block { padding: 10px; min-height: 300px;  }';
        $style .= $div.' .php-exception-blocks .php-code-sample { background: #232525; padding: 10px;  }';
        $style .= $div.' link { font: 11px Lucida Console, Monaco, monospace; }';
        $style .= $div.' ul.php-exception-trace { list-style:none; margin:0; padding:0; display:block; border: 1px solid #333; width: 351px; position: absolute; left: 10px; top: 100px; border-right: none; }';
        $style .= $div.' ul.php-exception-trace li { padding:10px; display:block; cursor: pointer; border-bottom: 1px solid #333; color: #808080; }';
        $style .= $div.' ul.php-exception-trace li.active, '.$div.' ul.php-exception-trace li:hover { color: #efefef; }';
        $style .= $div.' ul.php-exception-trace li.active { border-right: 1px solid #1b1b1b; z-index: 10; position: relative; }';
        $style .= $div.' ul.php-exception-trace li:last-child { border-bottom: none; }';

        return '<style>'.$style.'</style>';
    }

    /**
     * @return string
     */
    protected static function getExceptionsHtmlScripts() : string
    {
        $script = <<<JAVASCRIPT

var traceRows = document.getElementsByClassName("php-exception-trace-row");
var traceDivs = document.getElementsByClassName("php-exception-trace-block");
for(var i = 0; i < traceDivs.length; i++) traceDivs[i].style.display="none";
traceRows[0].className="php-exception-trace-row active";
document.getElementById('trace-id-0').style.display="block";
document.getElementById('php-exception-stack-trace-list').addEventListener('click', function(event) {
    for(var i = 0; i < traceRows.length; i++) traceRows[i].className="php-exception-trace-row";
    event.target.className="php-exception-trace-row active";
    var traceId = event.target.getAttribute("trace-id");
    for(var i = 0; i < traceDivs.length; i++) traceDivs[i].style.display="none";
    document.getElementById('trace-id-'+traceId).style.display="block";
});
document.getElementById('trace-id-0').className="php-exception-trace-block active";
JAVASCRIPT;

        return '<script>'.$script.'</script>';
    }

    /**
     * @param string $htmlContent
     * @param string $errorClass
     */
    protected static function outputWithSimpleWrapper(string $htmlContent, string $errorClass) : void
    {
        $errorContent  = '<div class="php-error-content '.trim($errorClass).'">'.trim($htmlContent).'</div>';
        echo $errorContent;
    }

    /**
     * @param string $htmlContent
     * @param string $htmlTitle
     * @param int $responseCode
     * @return bool
     */
    protected static function outputWithHtmlWrapper(string $htmlContent, string $htmlTitle, int $responseCode = 500) : bool
    {
        $htmlStyle = 'p { color: #a7a7a7 } h2, p { color: #ea981f; }';

        $htmlHeaders  = '<!DOCTYPE html><html><head>';
        $htmlHeaders .= '<meta charset="UTF-8"><title>'.trim($htmlTitle).'</title>';
        $htmlHeaders .= '<style>'.$htmlStyle.'</style></head>';

        $bodyStyle  = 'font: 12px "Century Gothic", "AppleGothic", "Lucida Grande", "Lucida Sans Unicode", "Garuda", "Malayalam", Arial, sans-serif;';
        $bodyStyle .= 'color: #e0e0e0; background: #1b1b1b;';

        $htmlBody  = '<body style=\''.$bodyStyle.'\'>';
        $htmlBody .= '<img style="float: left; margin-right: 5px;" src="data:image/png;base64,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">';
        $htmlBody .= '<h2 style=\'font: 18px "Century Gothic", "AppleGothic", "Lucida Grande", "Lucida Sans Unicode", "Garuda", "Malayalam", Arial, sans-serif;\'>Uncatched exception</h2>';
        $htmlBody .= trim($htmlContent);
        $htmlBody .= '</body>';

        $htmlFooter  = '</html>';

        http_response_code($responseCode);
        //header('HTTP/1.1 '.$responseCode.' Internal Engine Error', true, $responseCode);

        echo $htmlHeaders.$htmlBody.$htmlFooter;

        return true;
    }

}