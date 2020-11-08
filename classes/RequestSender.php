<?php
/**
 * Created by PhpStorm.
 * User: jin
 * Date: 3/10/18
 * Time: 2:45 PM
 */

namespace Keios\Apparatus\Classes;

/**
 * Class RequestSender
 * @package Keios\Apparatus\Classes
 */
class RequestSender
{
    private $headers = [];
    /**
     * RequestSender constructor.
     *
     * @param string      $contentType
     * @param string|null $bearerToken
     */
    public function __construct($bearerToken = null, $contentType = 'application/json')
    {
        $this->headers[] = 'Content-Type: '.$contentType;
        if ($bearerToken) {
            $this->headers[] = 'Authorization: Bearer '.$bearerToken;
        }
    }

    /**
     * @param array  $data
     * @param string $url
     * @return array|bool
     */
    public function sendPostRequest(array $data, string $url, bool $asJson = false)
    {
        if($asJson){
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }

    public function sendPutRequest(array $data, $url, bool $asJson = false)
    {
        if($asJson){
            $data = json_encode($data);
        } else {
            $data = http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    /**
     * @param array  $data
     * @param string $url
     * @return array|bool
     */
    public function sendGetRequest(array $data, string $url, $ignoreSsl = false)
    {
        $error = false;
        $ch = curl_init();
        $query = http_build_query($data);
        if ($query) {
            $url .= '?'.$query;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $requestData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if($ignoreSsl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        $content = curl_exec($ch);
        if (curl_errno($ch)) {
            $error = curl_error($ch);
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'code'    => $httpCode,
            'content' => $content,
            'error'   => $error,
        ];
    }
}
