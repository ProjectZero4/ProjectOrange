<?php

namespace ProjectOrange;

/**
 * Class Spectator
 * @package ProjectOrange
 */
class Spectator extends RiotAPI {

    /**
     * @var string
     */
    protected $class_link = "api.riotgames.com/lol/spectator/v3/";


    /**
     * @param $summoner_id
     * @return array|mixed
     */
    public function liveGame($summoner_id){
        $url = $this->formatURL($summoner_id, 'active-games/by-summoner/');

        return $this->queryRiot($url);
    }

    /**
     * @return array
     */
    public function featuredGames()
    {
        $url = $this->formatURL('', 'featured-games/');

        return $this->queryRiot($url);
    }

}