<?php
/**
 * Created by Josh.
 * Date: 01/07/2018
 * Time: 13:30
 * Last Update: 01/07/2018 - 13:30
 */

namespace ProjectOrange;

/**
 * Class DataDragon
 * @package ProjectOrange
 */
abstract class DataDragon extends CacheHandle
{

    const FOUR_WEEKS = 2419200;


    /**
     * @var
     */
    protected $db;

    /**
     * @var
     */
    protected $response_code;

    /**
     * @param string $url
     * @return array|mixed
     */
    protected function queryDD(string $url)
    {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $result = json_decode(curl_exec($ch), true);

        $this->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if($this->response_code !== 200){
            // TODO: Some error handling
            return ['response_code' => $this->response_code, 'url' => $url];
        }

        return $result;
    }

    protected function formatURL(string $url, string $realm)
    {
        return str_replace('{realm}', $realm, $url);
    }


}