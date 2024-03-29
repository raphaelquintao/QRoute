<?php
/**
 * MIT License
 *
 * Copyright (c) 2021 Raphael Quintão
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

namespace Quintao {
    
    use Error as ErrorAlias;
    use ReflectionFunction;
    
    class QRoute
    {
        public static $VERSION = '1.28';
        private static $req = null;
        private static $func_return = null;
        
        private static $func_handle_return = null;
        private static $func_error_not_found = null;
        private static $func_error_bad_request = null;
        private static $helper_functions = [];
        
        private static $BaseURL = '';
        private static $SubURL = '';
        
        private $match = null;
        private $uri;
        private $params = null;
        private $query = null;
        
        private function __construct($uri, $is_method)
        {
            $this->uri = $uri;
            if ($is_method && self::match_url($uri, self::$req['path'], $out)) {
                $this->match = $out;
            }
        }
        
        private static function get_body_content()
        {
            $content_type = @$_SERVER['CONTENT_TYPE'] ?: '';
            $input = file_get_contents("php://input", "r");
            
            
            if (!empty($_POST)) {
                return $_POST;
            } elseif ($content_type === 'application/json') {
                return json_decode($input, true);
            } elseif ($content_type === 'application/x-www-form-urlencoded') {
                parse_str($input, $tmp);
                return $tmp;
            } elseif (preg_match('/multipart\/form-data;\sboundary=(?<boundary>.*)$/', $content_type, $m)) {
                $blocks = preg_split("/-+{$m['boundary']}-*/", $input, -1, PREG_SPLIT_NO_EMPTY);
                array_pop($blocks);
                
                $data = [];
                foreach ($blocks as $id => $block) {
                    if (empty($block)) {
                        continue;
                    }
                    if (preg_match('/Content-Disposition:\sform-data;\sname=\"(?<name>[^\"]*)\"[\n|\r]+(?<value>[^\n\r].*)?\r?$/s', trim($block), $matches)) {
                        $data[$matches['name']] = $matches['value'];
                    }
                }
                return $data;
            }
            return [];
        }
        
        private static function parse_request()
        {
            self::$req['method'] = $_SERVER['REQUEST_METHOD'];
            self::$req['params'] = array();
            self::$req['query'] = $_GET;
            self::$req['path'] = '';
            
            $req_uri = urldecode($_SERVER['REQUEST_URI']);
            
            switch (self::$req['method']) {
                case 'GET':
                case 'PATCH':
                case 'DELETE':
                case 'OPTIONS':
                    self::$req['params'] = $_GET;
                    break;
                case 'POST':
                case 'PUT':
                    self::$req['params'] = self::get_body_content();
                    break;
            }
            
            if ($_GET) {
                $req_uri = strstr($req_uri, '?', true);
            }
            
            $req_uri = rtrim($req_uri, "/ ");
            
            self::$req['path'] = $req_uri;
        }
        
        private static function match_url($url_pattern, $request_url, &$out = null)
        {
//            static $COMPILE_REGEX = /** @lang PhpRegExpCommentMode */
//                '@(?<=/){(?<var>[a-z0-9_]+):?(?<reg>(?:\[[\w\d\-\\\./]+\](?:\+|\*|{(?:[0-9]+|[0-9]+,[0-9]*)})?)+|)}(?=\s*(?:/|$))@ix';

//            static $COMPILE_REGEX = /** @lang PhpRegExp */
//                '@(?<=/){(?<var>[a-z0-9_]+):?(?<reg>(?:(?:\[{0,1}[\w\d\-\\\./]+\]{0,1}(?:\+|\*|{(?:[0-9]+|[0-9]+,[0-9]*)})?)+|(?:\({0,1}[\w\d\-\\\./\[\]]+\){0,1}(?:\+|\*|{(?:[0-9]+|[0-9]+,[0-9]*)})?)+)+|)}(?=\s*(?:/|$))@ix';
            
            static $COMPILE_REGEX = /** @lang PhpRegExp */
                '@{(?<var>[a-z0-9_]+):?(?<reg>(?:(?:\[?[\w\d\-\\\./]+\]?(?:\+|\*|{(?:[0-9]+|[0-9]+,[0-9]*)})?)+|(?:\(?[\w\d\-\\\./\[\]]+\)?(?:\+|\*|{(?:[0-9]+|[0-9]+,[0-9]*)})?)+)+|)}@ix';


//            qDebug('URL_PATTERN: ', $url_pattern);
//            qDebug('Request Url: ', $request_url, "\n");
            
            $url_pattern = rtrim($url_pattern, "/ \t\n\r\0\x0B");
            $request_url = rtrim($request_url, "/ \t\n\r\0\x0B");
            
            $params_temp = array();
            $func_replace = function ($matches) use (&$params_temp) {
                if (empty($matches[2])) {
                    $matches[2] = '[\w]+';
                }
                $params_temp[] = [
                    'name' => $matches['var'],
                    'regex' => $matches[2]
                ];
                return "(?<$matches[1]>$matches[2])";
            };
            
            $compiled_regex = preg_replace_callback($COMPILE_REGEX, $func_replace, $url_pattern);

//            qDebug('Parsed: ', $params_temp);
//            qDebug('Compiled: ', $compiled_regex);
            
            if ($out === null) {
                $out = array();
            }
            if (preg_match('@^' . $compiled_regex . '$@i', $request_url, $temp)) {
                foreach ($params_temp as &$p) {
                    $out[$p['name']] = $temp[$p['name']];
                }
                return true;
            }
            
            return false;
        }
        
        private static function add_method($uri, $method)
        {
            if (self::$req === null) {
                self::parse_request();
            }
            
            $is_method = (self::$req['method'] === $method);
            return new QRoute(self::$BaseURL . self::$SubURL . $uri, $is_method);
        }
        
        /**
         * Register Helper function.
         * @param string $key
         * @param callable $callback
         */
        static function HREGISTER($key, $callback)
        {
            self::$helper_functions[$key] = $callback;
        }
        
        /**
         *  Call Helper function.
         * @param $key
         * @param mixed ...$params
         */
        static function HCALL($key, ...$params)
        {
            if (!isset(self::$helper_functions[$key])) {
                exit("HELPER FUNCTION NOT FOUND: $key");
            }
            $func = self::$helper_functions[$key];
            
            @call_user_func_array($func, $params);
        }
        
        
        static function BaseURL($url)
        {
            self::$BaseURL = rtrim($url, " \t\n\r\0\x0B/");
        }
        
        static function SubURL($url)
        {
            self::$SubURL = rtrim($url, " \t\n\r\0\x0B/");
        }
        
        static function HandleReturn($callback)
        {
            self::$func_handle_return = $callback;
        }
        
        static function NotFound(\Closure $callback)
        {
            self::$func_error_not_found = $callback;
        }
        
        static function BadRequest(\Closure $callback)
        {
            self::$func_error_bad_request = $callback;
        }
        
        
        static function GET($uri)
        {
            return self::add_method($uri, 'GET');
        }
        
        static function POST($uri)
        {
            return self::add_method($uri, 'POST');
        }
        
        static function PUT($uri)
        {
            return self::add_method($uri, 'PUT');
        }
        
        static function PATCH($uri)
        {
            return self::add_method($uri, 'PATCH');
        }
        
        static function DELETE($uri)
        {
            return self::add_method($uri, 'DELETE');
        }
        
        static function OPTIONS($uri)
        {
            return self::add_method($uri, 'OPTIONS');
        }
        
        
        /**
         * POST(body) Params.
         * @param array|null $required
         * @param array|null $optional
         * @param bool $showEmptyOptionals
         * @return $this
         */
        public function setParams(array $required = null, $optional = null, $showEmptyOptionals = true)
        {
            
            if ($this->match !== null) {
                $missing_params = [
                    'body' => []
                ];
                if (!empty($required)) {
                    foreach ($required as $p_req_name) {
                        if (isset(self::$req['params'][$p_req_name])) {
                            $this->params[$p_req_name] = self::$req['params'][$p_req_name];
                        } else {
                            $missing_params['body'][] = $p_req_name;
                            $this->match = null;
                            // if (self::$func_error_bad_request !== null) {
                            //     self::$func_return = call_user_func(self::$func_error_bad_request);
                            // }
                            // return $this;
                        }
                    }
                    if(sizeof($missing_params['body']) > 0){
                        if (self::$func_error_bad_request !== null) {
                            self::$func_return = call_user_func(self::$func_error_bad_request, $missing_params);
                        }
                        return $this;
                    }
                }
                if (!empty($optional)) {
                    foreach ($optional as $p_opt_name) {
                        if (isset(self::$req['params'][$p_opt_name])) {
                            $this->params[$p_opt_name] = self::$req['params'][$p_opt_name];
                        } else {
                            if ($showEmptyOptionals) {
                                $this->params[$p_opt_name] = null;
                            }
                        }
                    }
                }
            }
            
            return $this;
        }
        
        /**
         * GET(url) Params.
         * <br>/info?<b>user</b>=Jack&<b>code</b>=27
         * @param array|null $required
         * @param array|null $optional
         * @param bool $showEmptyOptionals
         * @return $this
         */
        public function setQuery($required = null, $optional = null, $showEmptyOptionals = true)
        {
            if ($this->match !== null) {
                $missing_params = [
                    'query' => []
                ];
                if (!empty($required)) {
                    foreach ($required as $p_req_name) {
                        if (isset(self::$req['query'][$p_req_name])) {
                            $this->query[$p_req_name] = self::$req['query'][$p_req_name];
                        } else {
                            $missing_params['query'][] = $p_req_name;
                            $this->match = null;
                            // if (self::$func_error_bad_request !== null) {
                            //     self::$func_return = call_user_func(self::$func_error_bad_request);
                            // }
                            // return $this;
                        }
                    }
                    if(sizeof($missing_params['query']) > 0){
                        if (self::$func_error_bad_request !== null) {
                            self::$func_return = call_user_func(self::$func_error_bad_request, $missing_params);
                        }
                        return $this;
                    }
                }
                if (!empty($optional)) {
                    foreach ($optional as $p_opt_name) {
                        if (isset(self::$req['query'][$p_opt_name])) {
                            $this->query[$p_opt_name] = self::$req['query'][$p_opt_name];
                        } else {
                            if ($showEmptyOptionals) {
                                $this->query[$p_opt_name] = null;
                            }
                        }
                    }
                }
            }
            
            return $this;
        }
        
        
        /**
         * Set callback function
         *
         * Urls Params go first matched by name, body parameters go next and query params are the last. <br><br>
         *
         * Example: function ($url_param1, $url_param2, $body_params, $query_params)
         * @param callable $callback
         */
        function setCallback(callable $callback)
        {
            if ($this->match !== null) {
                $url_params_count = count($this->match);
                try {
                    $f = new ReflectionFunction($callback);
                    
                    $params_count = $f->getNumberOfParameters();
                    
                    $params = $f->getParameters();
                    
                    foreach ($params as $param){
                        $name = $param->getName();
                        if(!array_key_exists($name, $this->match)){
                            $this->match[$name] = null;
                        }
                    }
                    array_splice($params, 0, $url_params_count);
                    
                    $index = 0;
                    if ($this->params !== null) {
                        if (array_key_exists($index, $params)) {
                            $this->match[$params[$index]->getName()] = $this->params;
                            $index++;
                        }
                    }
                    if ($this->query !== null) {
                        if (array_key_exists($index, $params)) {
                            $this->match[$params[$index]->getName()] = $this->query;
                        }
                    }
                    
                } catch (\Exception $e) {
                    qdd($e);
                }
                
                self::$func_return = call_user_func_array($callback, $this->match);
                
                if (self::$func_return === null) {
                    self::$func_return = '';
                }
                
                self::finish();
                
            }
            
        }
        
        static function finish()
        {
            if (self::$func_handle_return === null) {
                self::$func_handle_return = function ($content) {
                    print_r($content);
                };
            }
            
            if (self::$func_return !== null) {
                call_user_func(self::$func_handle_return, self::$func_return);
                exit();
            }
            
            if (self::$func_error_not_found !== null) {
                call_user_func(self::$func_handle_return, call_user_func(self::$func_error_not_found));
            }
        }
        
        
        /**
         * Send a raw HTTP header
         * @param array $headers
         */
        static function HEADERS($headers = array())
        {
            foreach ($headers as $name => $value) {
                header("{$name}: {$value}");
            }
        }
        
        /**
         * Set the HTTP response code
         * @param int $code The optional response_code will set the response code.
         */
        static function STATUS($code = 200)
        {
            http_response_code($code);
        }
        
        /**
         * Optional way to get: GET(url) params.
         * <br>/info?<b>user</b>=Jack&<b>code</b>=27
         * @param $var
         * @param null $default
         * @return null
         */
        static function InputGET($var, $default = null)
        {
            if (!isset($_GET[$var]) || empty($_GET[$var])) {
                return $default;
            } else {
                return @$_GET[$var] ?: $default;
            }
        }
        
        /**
         * Optional way to get: POST(body) params.
         * @param $var
         * @param null $default
         * @return null
         */
        static function InputPOST($var, $default = null)
        {
            $post = self::get_body_content();
            if (empty($post)) {
                return $default;
            } else {
                return @$post[$var] ?: $default;
            }
        }
        
    }
    
    function qdd(...$values)
    {
        header("Content-Type: application/json");
        foreach ($values as $value) {
            echo $value;
            echo "\n";
        }
        exit();
    }
    
    function qDebug($msg, ...$values)
    {
        $str = "$msg ";
        foreach ($values as $value) {
            $str .= print_r($value, 1);
            $str = rtrim($str, "\n");
            $str .= print_r("\n", 1);
        }
        $str = rtrim($str, "\n");
        $str .= print_r("\n", 1);
        
        
        if (php_sapi_name() === 'cli') {
            echo $str;
        } else {
            echo "<pre>{$str}</pre>";
        }
    }
}