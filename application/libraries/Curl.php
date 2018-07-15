<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Curl
{
    private $options;

    public function __construct($options = array())
    {
        $this->options = array_merge(array(
            'debug'      => false,
            'http_port'  => '',
            'user_agent' => 'PHP-curl-client (Marares)',
            'timeout'    => 20,
            'curlopts'   => null,
            'verifyssl'  => true,
        ), $options);
    }

    /**
    * Send a request to the server, receive a response
    *
    * @param  string   $apiPath       Request API path
    * @param  array    $parameters    Parameters
    * @param  string   $httpMethod    HTTP method to use
    *
    * @return string   HTTP response
    */
    public function request($url, array $parameters = array(), $httpMethod = 'GET', array $options = array(), $cookejarPath='', $setcookie = true)
    {
        $options = array_merge($this->options, $options);

        $curlOptions = array();
        $headers = array();

        if ('POST' === $httpMethod || 'JSON' === $httpMethod) {
            $curlOptions += array(
                CURLOPT_POST  => true,
            );
        }
        elseif ('PUT' === $httpMethod) {
            $curlOptions += array(
                CURLOPT_POST  => true, // This is so cURL doesn't strip CURLOPT_POSTFIELDS
                CURLOPT_CUSTOMREQUEST => 'PUT',
            );
        }
        elseif ('DELETE' === $httpMethod) {
            $curlOptions += array(
                CURLOPT_CUSTOMREQUEST => 'DELETE',
            );
        }

        if (!empty($parameters))
        {
            if('GET' === $httpMethod)
            {
                $queryString = utf8_encode($this->buildQuery($parameters));
                $url .= '?' . $queryString;
            } elseif ('POST' === $httpMethod) {
                $curlOptions += array(
                    CURLOPT_POSTFIELDS  => is_array($parameters) ? http_build_query($parameters) : $parameters,
                );
            } else {
                $curlOptions += array(
                    CURLOPT_POSTFIELDS  => json_encode($parameters)
                );
                $headers[] = 'Content-Type: application/json';
            }
        } else {
            $headers[] = 'Content-Length: 0';
        }

        $headers[] = 'Accept-Language:zh-cn';
        $headers[] = 'Expect:';

        $this->debug('send '.$httpMethod.' request: '.$url);

        $curlOptions += array(
            CURLOPT_URL             => $url,
            CURLOPT_PORT            => $options['http_port'],
            CURLOPT_USERAGENT       => $options['user_agent'],
            CURLOPT_RETURNTRANSFER  => true,
            CURLOPT_TIMEOUT         => $options['timeout'],
            CURLOPT_HTTPHEADER      => $headers,
            CURLOPT_SSL_VERIFYPEER  => $options['verifyssl'],
        );
        
        if($cookejarPath) {
            if($setcookie) {
                $curlOptions += array(
                    CURLOPT_COOKIEJAR       => $cookejarPath,//loading;
                    CURLOPT_HEADER          => 1,
                );
            } else {
                $curlOptions += array(
                    CURLOPT_COOKIEFILE       => $cookejarPath,//loading;
                );
            }
        }

        if (ini_get('open_basedir') == '' && ini_get('safe_mode') != 'On') {
            $curlOptions[CURLOPT_FOLLOWLOCATION] = true;
        }

        if (is_array($options['curlopts'])) {
            $curlOptions += $options['curlopts'];
        }

        if (isset($options['proxy'])) {
            $curlOptions[CURLOPT_PROXY] = $options['proxy'];
        }

        $response = $this->doCurlCall($curlOptions);

        return $response;
    }

    /**
     * Get a JSON response and transform it to a PHP array
     *
     * @return  array   the response
     */
    protected function decodeResponse($response)
    {
        // "false" means a failed curl request
        if (false === $response['response']) {
            $this->debug(print_r($response, true));
            return false;
        }
        return parent::decodeResponse($response);
    }

    protected function doCurlCall(array $curlOptions)
    {
        $curl = curl_init();

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $headers = curl_getinfo($curl);
        $errorNumber = curl_errno($curl);
        $errorMessage = curl_error($curl);

        curl_close($curl);

        return compact('response', 'headers', 'errorNumber', 'errorMessage');
    }

    protected function buildQuery($parameters)
    {
        return http_build_query($parameters, '', '&');
    }

    protected function debug($message)
    {
        if($this->options['debug'])
        {
            print $message."\n";
        }
    }
}
