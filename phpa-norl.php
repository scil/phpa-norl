#!/usr/bin/php
<?php
/*
    $Id: phpa-norl.php 2010/06/16 $

    Stefan Fischerländer <stefan@fischerlaender.de>, http://www.fischerlaender.net/php/phpa-norl
    original version: David Phillips <david@acz.org>, http://david.acz.org/phpa/
    
    
    2010/07/16 - register_shutdown_function
    2010/07/16 - now using PHP_EOL
    2010/07/16 - replaced deprecated split function
    2007/07/10 - CTRL-D now exits the script instead of entering an infinite loop
    2007/07/08 - initial version of phpa-norl published
    
*/
    register_shutdown_function('__phpa__shutdown');
    
    __phpa__setup();
    __phpa__print_info();

    /*
     * begin edit by Stefan Fischerländer and scil
     */
    @include dirname(__FILE__).'/php-norl_include.php';

    foreach( array(
        "__PHPA_HISTORY_COMMAND" =>  'h' ,     // defines the command name for history manipulation
        "__PHPA_EXIT_COMMAND" =>  'q' ,        // defines the command name to exit the shell
        "__PHPA_MAX_HIST" =>  20 ,             // maximum number of history entries
        "__PHPA_PROMPT" =>  PHP_VERSION.' > ' ,
        
        '__PHPA_HINT' => true ,     //if you type '$_G'  => then click tab and enter  => then you can get hint '$_GET' .
        '__PHPA_HINT_STRICT' => true , //'aBC' is thought to be function =>  'ABC' all are upper to be constant  => others e.g. Abc to be Class ;
        '__PHPA_HINT_ONLYUSER' => false , // only hint user function  => no internal function
        
        '__PHPA_LOG_INHERIT' => 2 ,// 1: restore last session; 2:ask user; 0: ignore last session
        
        ) as $config => $value){
            defined($config) || define($config,$value);
        }
    unset($config, $value);
    
    $__phpa_myhist = array();
    $__phpa_fh = fopen('php://stdin','rb') or die($php_errormsg);
    /*
     * end edit by Stefan Fischerländer and scil
     */
     
    
    // eval should be here, not in class PHPALog , because var scope.
    switch (__PHPA_LOG_INHERIT) {
        case 2:
            $__phpa_line=__phpa__myReadLine($__phpa_fh, '[Enable Last Session History?]'.PHP_EOL.' (Y)/n : ', __PHPA_HINT);
            if($__phpa_line=='n')
                PHPALog::getinstance(false);
            break;
        case 1:
            @eval(PHPALog::getinstance()->hist[0]);
            break;
        default:
            break;
    }

    for (;;)
    {
        /*
         * begin edit by Stefan Fischerländer
         */
        $__phpa_line = __phpa__myReadLine($__phpa_fh, __PHPA_PROMPT,__PHPA_HINT);
        if ($__phpa_line == __PHPA_EXIT_COMMAND)
        {
            echo PHP_EOL;
            break;
        } elseif( $__phpa_line == __PHPA_HISTORY_COMMAND ) {
            __phpa__showHistory($__phpa_myhist);
            continue;
        } elseif( preg_match('/^'.__PHPA_HISTORY_COMMAND.'\s*(\d+)$/', $__phpa_line, $__phpa_result) ) {
            $__phpa_line = $__phpa_myhist[$__phpa_result[1]];
            array_splice($__phpa_myhist, $__phpa_result[1], 1);
            echo __PHPA_PROMPT,$__phpa_line.PHP_EOL;
        }
        if (strlen($__phpa_line) == 0)
            continue;

        # manage history
        array_unshift($__phpa_myhist, $__phpa_line);
        if( count($__phpa_myhist) > __PHPA_MAX_HIST )
            array_pop($__phpa_myhist);
        /*
         * end edit by Stefan Fischerländer
         */

        PHPALog::log($__phpa_line);

        if (__phpa__is_immediate($__phpa_line))
            $__phpa_line = "return ($__phpa_line)";

        ob_start();
        $__phpa_ret = eval("unset(\$__phpa_line); $__phpa_line;");
        if (ob_get_length() == 0)
        {
            if (is_bool($__phpa_ret))
                echo ($__phpa_ret ? "true" : "false");
            else if (is_string($__phpa_ret))
                echo "'" , addcslashes($__phpa_ret, "\0..\37\177..\377")  , "'";
            else if (!is_null($__phpa_ret))
                print_r($__phpa_ret);
        }
        unset($__phpa_ret);
        $__phpa_out = ob_get_contents();
        ob_end_clean();
        if ((strlen($__phpa_out) > 0) && (substr($__phpa_out, -1) != PHP_EOL))
            $__phpa_out .= PHP_EOL;
        echo $__phpa_out;
        unset($__phpa_out);
    }
    fclose($__phpa_fh);


    /**
     *
     * @author Stefan Fischerländer
     * @return STRING input from keyboard, may contain line breaks
     */
    function __phpa__myReadLine($fh, $prompt,$usehint)
    {
        echo $prompt;
        $complete_line = '';
        for(;;) {
            $line = fgets($fh,1024);
            if( !$line && strlen($line)==0 )        # this is true, when CTRL-D is pressed
                die("\nUser pressed CTRL-D. phpa-norl quits.\n");
            $line = rtrim($line," \n\r\0\x0B;");
            if( $usehint && substr($line,-1) == "\t"){
                $hint=PHPAHint::hint(substr($line,0,-1));
                echo '[HINT]',PHP_EOL,"  ",count($hint)>0?implode('  ',$hint):"[no hint]", PHP_EOL;
                unset($hint);
                echo $prompt;
                continue;
            }
            $complete_line .= $line;
            if( substr($line,-1) != '#')
                break;
            else
                $complete_line = substr($complete_line,0,-1).PHP_EOL;
        }
        return $complete_line;
    }

    /**
     *
     * @author Stefan Fischerländer
     * @return STRING input from keyboard, may contain line breaks
     */
    function __phpa__showHistory($myhist)
    {
        echo "History:\n";
        for( $i=count($myhist)-1; $i>=0; $i--) {
            $val = $myhist[$i];
            $prompt = "[$i] => ";
            echo $prompt;
            if( strpos($val, PHP_EOL) > 0 ) {
                echo str_replace("\n",  str_pad("\n", strlen($prompt)+1, ' '), str_replace("\t", '    ', $val));
                echo "\n";
            }
            else
                echo "$val\n";
        }
    }





    class PHPAHint{
        static $onlyuser = __PHPA_HINT_ONLYUSER;
        static $strict = __PHPA_HINT_STRICT;
        static function hint($str){
            /**
                with mark:
                    "\$",    "\$abc" //var or object
                    " abc->", " abc->n" //var or method of an object
                    " abc::", " abc::n" //constant or static method of a class
                    "new ", "new C" //class
                    "\\", "\N" // namespace
                without mark:
                    "abc", "ABC", "Abc" //function ,constant, class 
            */
            if (preg_match('/(?<mark>\$|(?<obj>[a-zA-Z_]\w*)->|(?<class>[a-zA-Z_]\w*)::|\bnew\s+|\\\)?(?<tosearch>[a-zA-Z_]\w*)?$/',$str,$result) ){
                //print_r( $result);
                //this re has two parts, mark and tosearch, all are optionl. so string '3' is matched ,its $result is array(0=>'') . this if is used to skip this situation.
                if(count($result)>1){
                    $tosearch=isset($result['tosearch'])?$result['tosearch']:'';
                    $about='';
                    if(!empty($result['mark'])){
                        if($result['mark']=='$') $type='var';
                        elseif(!empty($result['obj'])) {$type='objField';$about=$result['obj'];}
                        elseif(!empty($result['class'])) {$type='classField';$about=$result['class'];}
                        elseif(substr($result['mark'],0,3)=='new') $type='class';
                        elseif($result['mark']=='\\') $type='ns';
                    }else {
                        if(self::$strict){
                            if(ord($tosearch)>96){
                                $type='fn';
                            }elseif(preg_match('/^[A-Z_]+$/',$tosearch)){
                                $type='const';
                            }else $type='class';
                        }else
                            $type='mix';
                    }
                    return self::hint4($type,$tosearch,$about);
                }
            }
            // always return array
            return array();
            
        }
        static function hint4($type,$tosearch,$about){
            echo '[HINT TYPE] :',$type,PHP_EOL;
            switch (strtolower($type)) {
                case 'var':
                    return self::va($tosearch);
                    break;
                case 'fn':
                    return self::fn($tosearch);
                    break;
                case 'const':
                    return self::cons($tosearch);
                    break;
                case 'mix':
                    return array_merge(self::fn($tosearch),self::cons($tosearch),  self::clas($tosearch));
                    break;
                case 'objField':
                    return self::objField($about,$tosearch);
                    break;
                case 'classField':
                    return self::classField($about,$tosearch);
                    break;
                case 'class':
                    return self::clas($tosearch);
                    break;
                case 'ns':
                    return self::ns($tosearch);
                    break;
                default:
                    # code...
                    break;
            }
        }
        static function va($str){
            if (strlen($str)==0){
                return array_keys($GLOBALS);
            }else{
                return self::filter(array_keys($GLOBALS) ,$str);
            }
        }
        static function fn($str){
            $func = get_defined_functions();
            if(self::$onlyuser) $func=$func["user"];
            else $func=array_merge($func["user"], $func["internal"]);
            if (strlen($str)==0){
                return $func;
            }else{
                return self::filter($func,$str);
            }
        }
        static function cons($str){
            if (strlen($str)==0){
                return array_keys(get_defined_constants());
            }else{
                return self::filter(array_keys(get_defined_constants()),$str);
            }
        }
        static function clas($str){
            if (strlen($str)==0){
                return get_declared_classes();
            }else{
                return self::filter(get_declared_classes(),$str);
            }
        }
        static function objField($obj,$tosearch){
            
        }
        static function classField($class,$tosearch){
            
        }
        private static function filter($array,$beginwith){
            $n=array();
            $len=strlen($beginwith);
            foreach ($array as $value) {
                if(substr($value,0,$len)==$beginwith) $n[]=$value;
            }
            return $n;
        }
    }

    class PHPALog {
        static $singleinstance=null;
        public $hist = array();
        private $f;
        public $logging = true;
        
        static function getinstance($inheric=true,$file=''){
            if(self::$singleinstance) return self::$singleinstance;
            else {
                $me=new PHPALog();
                if(empty($file[0])) $file=dirname(__FILE__).'/phpa-norl_history.txt';
                if($inheric && is_file($file)){
                    $me->hist[]=file_get_contents($file);
                }
                $f=fopen($file,'wb');
                $me->f=$f;
                return self::$singleinstance=$me;
            }
        }
        function write(){
            $f=$this->f;
            if($this->logging) fwrite($f, implode(';'.PHP_EOL, $this->hist).';' );
            fclose($f);
        }
        static function log($line){
            $me=self::getinstance();
            if($me->logging) $me->hist[]=$line;
        }
        static function pause(){
            $me=self::getinstance();
            if($me->logging){
                $me->logging = false;
                fwrite($me->f, implode(';'.PHP_EOL, $me->hist).';' );
                $me->hist=array();
            }
        }
        static function unpause(){
            self::getinstance()->logging = true;
        }
    }

    function __phpa__rl_complete($line, $pos, $cursor)
    {
        $const = array_keys(get_defined_constants());
        $var = array_keys($GLOBALS);

        $func = get_defined_functions();
        $s = "__phpa__";
        foreach ($func["user"] as $i)
            if (substr($i, 0, strlen($s)) != $s)
                $func["internal"][] = $i;
        $func = $func["internal"];

        return array_merge($const, $var, $func);
    }

    function __phpa__is_immediate($line)
    {
        $skip = array("class", "declare", "die", "echo", "exit", "for",
                      "foreach", "function", "global", "if", "include",
                      "include_once", "print", "require", "require_once",
                      "return", "static", "switch", "while");
        $okeq = array("===", "!==", "==", "!=", "<=", ">=");
        $code = "";
        $sq = false;
        $dq = false;
        for ($i = 0; $i < strlen($line); $i++)
        {
            $c = $line{$i};
            if ($c == "'")
                $sq = !$sq;
            else if ($c == '"')
                $dq = !$dq;
            else if (($sq) || ($dq))
            {
                if ($c == "\\")
            $i++;
            }
            else
                $code .= $c;
        }
        $code = str_replace($okeq, "", $code);
        if (strcspn($code, ";{=") != strlen($code))
            return false;
        $kw = preg_split("/[^A-Za-z0-9_]/", $code);
        foreach ($kw as $i)
            if (in_array($i, $skip))
                return false;
        return true;
    }

    function __phpa__print_info()
    {
        $ver = phpversion();
        $sapi = php_sapi_name();
        $date = __phpa__build_date();
        $os = PHP_OS;
        echo "PHP $ver ($sapi) ($date) [$os]\n";
    }

    function __phpa__build_date()
    {
        ob_start();
        phpinfo(INFO_GENERAL);
        $x = ob_get_contents();
        ob_end_clean();
        $x = strip_tags($x);
        $x = explode("\n", $x);
        $len=strlen("Build Date ");
        foreach ($x as $i) {
            if( substr($i,0,$len) == 'Build Date ')
                return trim(substr($i,$len),' =>');
        }
        return "???";
    }

    function __phpa__setup()
    {
        if (version_compare(phpversion(), "5.2.0", "<"))
        {
            echo "PHP 5.2.0 or above is required.\n";
            exit(111);
        }
        error_reporting(E_ALL ^ E_NOTICE);
        ini_set("html_errors", 0);
        while (ob_get_level())
            ob_end_clean();
        ob_implicit_flush(true);
    }


    // This is our __phpa__shutdown function, in
    // here we can do any last operations
    // before the script is complete.
    function __phpa__shutdown() {
        $log=PHPALog::getinstance();
        $log->write();
        
        $error = error_get_last();
        if($error !== NULL){
            echo <<< EEE

[SHUTDOWN]
 File: {$error['file']}
 Line: {$error['line']}
 Message: {$error['message']}
 
EEE;
        }
    }
?>
