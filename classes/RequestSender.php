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
    /**
     * @param array  $data
     * @param string $url
     * @return array|bool
     */
    public function sendPostRequest(array $data, string $url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);

        curl_close($ch);

        return $result;
    }
}