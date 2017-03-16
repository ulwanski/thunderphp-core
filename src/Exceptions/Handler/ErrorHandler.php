<?php

namespace Core\Exceptions;


class ErrorHandler
{
    public static function catchException(\Throwable $e) {
        error_reporting(0);

        $path = self::getTemplatePath();
        $template = file_get_contents($path.'/exception.html');

        $trace_html = '<table class="trace">';
        foreach($e->getTrace() as $trace){

            $args = $trace['args'];
            $fn_args = array();
            if(isset($args[0])){
                foreach($args[0] as $name => $value){
                    $fn_args[] = '<span title="'.var_export($value, true).'">$'.$name.'</span>';
                }
            }

            $html = '<td><span class="trace_class">'.$trace['class'].'</span>'.$trace['type'].'<span class="trace_function">'.$trace['function'].'('.implode(', ', $fn_args).')</span></td>';
            $html .= '<td><span class="error_file">'.$trace['file'].'</span>:<span class="error_line">'.$trace['line'].'</span></td>';

            $trace_html .= '<tr>'.$html.'</tr>';
        }
        $trace_html .= '</table>';

        $environment = '';
        foreach($_SERVER as $name => $value){
            $environment .= '<span class="var_name">'.$name.'</span> = <span class="var_value">'.var_export($value, true).'</span><br>';
        }

        $dataArray = array (
            '{$error_message}'  => $e->getMessage(),
            '{$error_code}'     => $e->getCode(),
            '{$error_file}'     => $e->getFile(),
            '{$error_line}'     => $e->getLine(),
            '{$error_trace}'    => $trace_html,
            '{$environment}'    => $environment,
        );

        $template = str_replace(array_keys($dataArray), $dataArray, $template);

        echo trim($template);
    }

    public static function catchError($errno , $errstr, $errfile, $errline, $errcontext) {
        $msg = ''.$errstr.'<span style="color: #666;"> w <i>'.$errfile.'</i> linia '.$errline.'</span>';
        //echo '<p style="font: 13px Arial;">'.$msg.'</p>';
        return false;
    }

    private static function getTemplatePath(){
        $path = realpath(__DIR__ . '/../Exceptions/templates');
        return $path;
    }

}