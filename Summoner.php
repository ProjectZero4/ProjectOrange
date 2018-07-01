<?php

namespace ProjectOrange;


class Summoner extends RiotAPI {

    /**
     * @var string
     */
    protected $class_link = "api.riotgames.com/lol/summoner/v3/summoners/";

    /**
     * @var string
     */
    private $table = 'summoners';

    /**
     * Returns Summoner's info via summonerId
     * @param $summoner_id
     * @return array
     */
    public function bySummonerId($summoner_id){

        $cache = $this->checkCache($this->table, ['id' => $summoner_id], self::FULL_HOUR);

        if($cache !== false && $cache !== 0){
            return $cache;
        }

        $url = $this->formatURL($summoner_id);

        $result = $this->queryRiot($url);

        $result['last_updated'] = time();

        $result['name_key'] = $result['name'];

        $this->insertUpdateCache($cache, $result, ['id' => $summoner_id]);

        return $this->db->row($this->table, [], ['id' => $summoner_id]);
    }


    /**
     * @param $name
     * @return array
     */
    public function bySummonerName($name){

        $name = str_replace(' ', '', $name);

        $cache = $this->checkCache($this->table, ['name_key' => $name], self::FULL_HOUR);

        if($cache !== false && $cache !== 0){
            return $cache;
        }

        $method = "by-name/";

        $url = $this->formatURL($name, $method);

        $result = $this->queryRiot($url);

        $result['name_key'] = $result['name'];

        $this->insertUpdateCache($cache, $result, ['name_key' => $name]);

        return $this->db->row($this->table, [], ['name_key' => $name]);


    }

    /**
     * @param $account_id
     * @return array
     */
    public function byAccountId(int $account_id){

        $cache = $this->checkCache($this->table, ['accountId' => $account_id], self::FULL_HOUR);

        if($cache !== false && $cache !== 0){
            return $cache;
        }

        $method = "by-account/";

        $url = $this->formatURL($account_id, $method);
        $result = $this->queryRiot($url);

        $result['last_updated'] = time();
        $result['name_key'] = $result['name'];

        $this->insertUpdateCache($cache, $result, ['accountId' => $account_id]);

        return $this->db->row($this->table, [], ['accountId' => $account_id]);
    }

    /**
     * @param string $icon
     * @return string
     */
    public static function getSummonerIcon(string $icon){

        return "https://{$_SERVER['SERVER_NAME']}/assets/images/profile_icons/{$icon}.png";
    }
}