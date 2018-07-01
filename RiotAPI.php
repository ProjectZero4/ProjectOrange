<?php

// TODO: Get Profile Icons and create a method for getting the URL

namespace ProjectOrange;

abstract class RiotAPI extends CacheHandle
{

    // TODO: Create const for each server

    // All current Riot API endpoints.
    const API_URL_PLATFORM_3 = "https://{platform}.api.riotgames.com/lol/platform/v3/";
    const API_URL_SPECTATOR_3 = 'https://{platform}.api.riotgames.com/lol/spectator/v3/';
    const API_URL_STATIC_3 = 'https://{platform}.api.riotgames.com/lol/static-data/v3/';
    const API_URL_MATCH_3 = 'https://{platform}.api.riotgames.com/lol/match/v3/';
    const API_URL_LEAGUE_3 = 'https://{platform}.api.riotgames.com/lol/league/v3/';

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
    protected $api_key = '<API_KEY>';

    private $server;

    protected $cache_column = 'last_updated';

    protected $db;

    protected $class_link;

    protected $rate_limit;

    public $response_code;

    protected $table;

    /**
     * RiotAPI constructor.
     * @param $server
     * @param DB $db
     * @param RateLimit $rate_limit
     */
    public function __construct(string $server, DB $db, RateLimit $rate_limit = null){

        parent::__construct($db);

        $this->server = $server;
        $this->rate_limit = $rate_limit;
    }

    /**
     * @param string $url
     * @return array|false
     */
    protected function queryRiot(string $url){

        if($this->rate_limit instanceof RateLimit){
            $this->rate_limit->run();
        }

        // Call the API and return the result
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Riot-Token: '. $this->api_key]);

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
     * @param string $method
     * @param string $value
     * @param array  $params
     * @return string
     */
    protected function formatURL(string $value, string $method = '', $params = array()){
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

        return "https://{$this->server}.{$this->getClassLink()}{$method}{$value}{$params_string}";
    }


    /**
     * @return string
     */
    protected function getClassLink()
    {
        return $this->class_link;
    }


    /**
     * @param DB $db
     * @param $id
     * @return array
     */
    public static function getChampionData(DB $db, $id){
        $stmt = "select * from champion_data where `key` = :champion_id";
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

    /**
     * Mainly for development purposes
     * @param string $api_key
     */
    public function setAPIKey(string $api_key)
    {
        $this->api_key = $api_key;
    }
}