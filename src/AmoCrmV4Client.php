<?php

class AmoCrmV4Client
{
    var $curl = null;
    var $subDomain = "";

    var $client_id = "";
    var $client_secret = "";
    var $code = "";
    var $redirect_uri = "";

    var $access_token = "";

    var $token_file = "TOKEN.txt";

    function __construct($subDomain, $client_id, $client_secret, $code, $redirect_uri)
    {
        $this->subDomain = $subDomain;
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
        $this->code = $code;
        $this->redirect_uri = $redirect_uri;
        
        if(file_exists($this->token_file)) {
            $expires_in = json_decode(file_get_contents("TOKEN.txt"))->{'expires_in'};
            if($expires_in < time()) {
                $this->access_token = json_decode(file_get_contents("TOKEN.txt"))->{'access_token'};
                $this->GetToken(true);
            }
            else
                $this->access_token = json_decode(file_get_contents("TOKEN.txt"))->{'access_token'};
        }
        else
            $this->GetToken();
    }

    function GetToken($refresh = false){
        $link = 'https://' . $this->subDomain . '.amocrm.ru/oauth2/access_token';

        if($refresh)
        {
            $data = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => json_decode(file_get_contents("TOKEN.txt"))->{'refresh_token'},
                'redirect_uri' => $this->redirect_uri
            ];
        } else {
            $data = [
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $this->code,
                'redirect_uri' => $this->redirect_uri
            ];
        }

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            echo $out;
            die('Error: ' . $e->getMessage() . PHP_EOL . 'Code: ' . $e->getCode());
        }

        $response = json_decode($out, true);

        $this->access_token = $response['access_token'];

        $token = [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'token_type' => $response['token_type'],
            'expires_in' => time() + $response['expires_in']
        ];

        file_put_contents("TOKEN.txt", json_encode($token));
    }

    function CurlRequest($link, $method, $PostFields = [])
    {
        $headers = [
            'Authorization: Bearer ' . $this->access_token,
            'Content-Type: application/json'
        ];

        $curl = curl_init();
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        if ($method == "POST" || $method == 'PATCH') {
            curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($PostFields));
        }
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST,$method);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int) $code;
        $errors = array(
            301 => 'Moved permanently',
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        );

        try
        {
            if ($code != 200 && $code != 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undescribed error', $code);
            }

        } catch (Exception $E) {
            $this->Error('Error: ' . $E->getMessage() . PHP_EOL . 'Code: ' . $E->getCode() . $link);
        }


        return $out;
    }

    function GETRequestApi($service, $params = [])
    {
        $result = '';
        try {
            $url = "";
            if ($params !== []) {
                $params = ToGetArray($params);
                $url = 'https://' . $this->subDomain . '.amocrm.ru/api/v4/' . $service . '?' . $params;
            } else
                $url = 'https://' . $this->subDomain . '.amocrm.ru/api/v4/' . $service;

            $result = json_decode($this->CurlRequest($url, 'GET'), true);

            usleep(250000);

        } catch (ErrorException $e) {
            $this->Error($e);
        }

        return $result;
    }

    function POSTRequestApi($service, $params = [], $method = "POST")
    {   
        $result = '';
        try {
            $url = 'https://' . $this->subDomain . '.amocrm.ru/api/v4/' . $service;

            $result = json_decode($this->CurlRequest($url, $method, $params), true);

            usleep(250000);

        } catch (ErrorException $e) {
            $this->Error($e);
        }

        return $result;
    }

    function GETAll($entity, $custom_params = null){
        $array = [];
        $i = 1;

        if ($entity == 'leads') {
            $with = 'contacts';
        } else if ($entity == 'contacts') {
            $with = 'leads';
        } else {
            $with = 'leads,contacts';
        }

        $params = [
            'limit' => 250,
            'with' => $with
        ];

        do {
            if ($custom_params != null){
                foreach ($custom_params as $key => $param){
                    $params[$key] = $param;
                }
            }
            $params['page'] = $i;
            $array_temp = $this->GETRequestApi($entity, $params)['_embedded'][$entity];
            if ($array_temp == null)
                break;
            foreach ($array_temp as $elem) {
                array_push($array, $elem);
            }
            $i++;
        } while ($array_temp != null);

        return $array;
    }

    function Error($e){
        file_put_contents("ERROR_LOG.txt", $e);
    }
}

function ToGetArray($array){
    $result = "";

    foreach ($array as $key => $value)
    {
        $result .= $key . "=" . $value . '&';
    }

    return substr($result,0,-1);
}