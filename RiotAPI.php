<?php
/**
 * Created by PhpStorm.
 * User: Joshu
 * Date: 18/03/2018
 * Time: 10:34
 */

// TODO: Get Profile Icons and create a method for getting the URL

namespace ProjectOrange;

class RiotAPI
{

    // TODO: Create const for each server

    // All current Riot API endpoints.
    const API_URL_PLATFORM_3 = "https://{platform}.api.riotgames.com/lol/platform/v3/";
    const API_URL_SPECTATOR_3 = 'https://{platform}.api.riotgames.com/lol/spectator/v3/';
    const API_URL_STATIC_3 = 'https://{platform}.api.riotgames.com/lol/static-data/v3/';
    const API_URL_MATCH_3 = 'https://{platform}.api.riotgames.com/lol/match/v3/';
    const API_URL_LEAGUE_3 = 'https://{platform}.api.riotgames.com/lol/league/v3/';
    const API_URL_SUMMONER_3 = 'https://{platform}.api.riotgames.com/lol/summoner/v3/';

    // Default asset location - modify as required
    const ICON_PATH = 'assets/images/champion_icons/';
    const LOADING_PATH = 'assets/images/champion_loading/';
    const SPLASH_PATH = 'assets/images/champion_splash/';

    // Riot's Extensions on these files
    const ICON_EXT = '.png';
    const LOADING_EXT = '.jpg';
    const SPLASH_EXT = '.jpg';

    // Default error pages - modify as required
    const ERROR_BASE_PATH   = 'error/';
    const ERROR_FILE_EXT    = '.php';

    // Cache Times
    const HALF_HOUR = 1800;
    const FULL_HOUR = 3600;

    // API Rate Limits - modify as required
    const SHORT_LIMIT       = 500;
    const SHORT_INTERVAL    = 10;
    const LONG_LIMIT        = 30000;
    const LONG_INTERVAL     = 60;
    const SHORT_BUFFER      = 5;
    const LONG_BUFFER       = 15;

    // API Key - modify as required
    const API_KEY = 'RGAPI-99CF8F9C-C715-4A6E-81B8-8BE3C2BC84B4';

    private $server;

    protected $db;

    public $response_code;

    /**
     * RiotAPI constructor.
     * @param $server
     * @param DB $db
     */
    protected function __construct(string $server, DB $db){
        $this->server = $server;
        $this->db = $db;
    }

    /**
     * @param string $url
     * @param RateLimit|null $rateLimit
     * @return array|mixed
     */
    protected function queryRiot(string $url, RateLimit $rateLimit = null){

        if($rateLimit instanceof RateLimit){
            $rateLimit->run();
        }

        // Call the API and return the result
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'X-Riot-Token: '. self::API_KEY
        ));

        $result = json_decode(curl_exec($ch), true);

        $this->response_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if($this->response_code !== 200){
            // TODO: Some error handling
            return ['response_code' => $this->response_code, 'url' => $url];
        }

        return $result;
    }

    /**
     * @param  int $code
     * @return bool|string
     */
    protected function error(int $code){
        $file = self::ERROR_BASE_PATH . $code . self::ERROR_FILE_EXT;

        if(file_exists($file)){
            return file_get_contents($file);
        }
        return false;
    }

    /**
     * @param string $class_link
     * @param string $method
     * @param string $value
     * @param array  $params
     * @return string
     */
    protected function formatURL(string $class_link, string $method, string $value, $params = array()){
        // URL should look like this - {https://}{$SERVER}{$CLASSLINK}{$METHODLINK}{$VALUE}?{$GETPARAMS}

        $params_str_arr = [];
        foreach($params as $key => $val){
            $params_str_arr[] = "{$key}={$val}";
        }
        $params_string = implode('&', $params_str_arr);
        if($params_string)
        {
            $params_string = "?{$params_string}";
        }
        return "https://{$this->server}.{$class_link}{$method}{$value}{$params_string}";
    }


    /**
     * @param DB $db
     * @param $id
     * @return array
     */
    public static function getChampionData(DB $db, $id){
        $stmt = "select * from champion_data where champion_id = :champion_id";
        return $db->query($stmt, ['champion_id' => $id]);
    }

    /**
     * @param DB $db
     * @param $id
     * @param string $type
     * @return bool|string
     */
    public static function getChampionImage(DB $db, $id, $type = "icon"){
        $path = "https://{$_SERVER['SERVER_NAME']}/";

        switch($type){
            case 'icon':
                $path .= self::ICON_PATH;
                $type = self::ICON_EXT;
                break;
            case 'loading':
                $path .= self::LOADING_PATH;
                $type = self::LOADING_EXT;
                break;
            case 'splash':
                $path .= self::SPLASH_PATH;
                $type = self::SPLASH_EXT;
                break;
            default:
                return false;
                break;
        }

        $champion = RiotAPI::getChampionData($db, $id);

        if(!isset($champion['key'])){
            return false;
        }

        $key = $champion['key'];

        $path .= "{$key}{$type}";

        return $path;

    }

}