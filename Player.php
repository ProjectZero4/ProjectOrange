<?php
/**
 * Created by Josh.
 * Date: 11/07/2018
 * Time: 20:01
 * Last Update: 11/07/2018 - 20:01
 */

namespace ProjectOrange;


class Player
{

    const MATCHES_TABLE = 'summoner_matches';

    /**
     * @var DB
     */
    protected $db;

    /**
     * @var Summoner
     */
    protected $summoner;

    /**
     * @var Match
     */
    protected $match;

    /**
     * @var League
     */
    protected $league;

    /**
     * @var Spectator
     */
    protected $spectator;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $recentGames;

    /**
     * @var array
     */
    protected $matchHistory;

    /**
     * Player constructor.
     * @param DB $db
     * @param Summoner $summoner
     * @param Match $match
     * @param League $league
     * @param Spectator $spectator
     */
    public function __construct(
        DB $db,
        Summoner $summoner,
        Match $match,
        League $league,
        Spectator $spectator
    )
    {
        $this->db = $db;
        $this->summoner = $summoner;
        $this->match = $match;
        $this->league = $league;
        $this->spectator = $spectator;
    }

    /**
     * @param string $method
     * @param array $args
     * @return mixed
     */
    public function __call(string $method, array $args)
    {
        if (strpos($method, 'get') === 0) {

            $propertyName = lcfirst(str_replace('get', '', $method));

            return $this->data[$propertyName];

        } elseif (strpos($method, 'set') === 0) {

            $propertyName = lcfirst(str_replace('set', '', $method));

            $this->data[$propertyName] = $args;

            return true;
        }

        return false;
    }

    /**
     * @param int|string $value
     */
    public function load($value)
    {
        if (is_integer($value)) {
            $data = $this->summoner->bySummonerId($value);
        } else {
            $data = $this->summoner->bySummonerName($value);
        }

        foreach ($data as $k => $v) {
            $this->data[$k] = $v;
        }
    }

    /**
     * @return array
     */
    public function getMatchHistory()
    {

        if (!isset($this->matchHistory)) {

            if (!isset($this->recentGames)) {
                $this->recentGames = $this->match->matchList($this->getAccountId(), array(), 1);
            }

            $this->matchHistory = array();

            foreach ($this->recentGames as $value) {

                $this->matchHistory[] = $this->match->match($value['gameId']);
            }
        }

        return $this->matchHistory;
    }

    /**
     * @param string $apiKey
     */
    public function setAPIKey(string $apiKey)
    {
        $this->summoner->setAPIKey($apiKey);
        $this->match->setAPIKey($apiKey);
        $this->spectator->setAPIKey($apiKey);
        $this->league->setAPIKey($apiKey);
    }


    public function processMatchHistory(array $matchHistory = array())
    {
        if (empty($matchHistory)) {
            $matchHistory = $this->getMatchHistory();
        }

        foreach ($matchHistory as $match) {

            $summoners = array_flip(json_decode($match['summonerIds']));

            $participantId = $summoners[$this->getId()];

            $player = (json_decode($match['participants'], true))[$participantId];


            $cache = $this->db->row(
                self::MATCHES_TABLE,
                [],
                ['gameId' => $match['gameId'], 'summonerId' => $this->getId()]
            );

            $playerData = $player;

            $playerData = array_merge($playerData,$player['stats'], $player['timeline']);

            unset(
                $playerData['participantId'],
                $playerData['stats'],
                $playerData['timeline'],
                $playerData['highestAchievedSeasonTier']
            );

            $playerData['gameId'] = $match['gameId'];
            $playerData['summonerId'] = $this->getId();
            $playerData['gameCreation'] = $match['gameCreation'];
            $playerData['gameDuration'] = $match['gameDuration'];
            $playerData['last_updated'] = time();


            if (empty($cache)) {
                $this->db->insert(self::MATCHES_TABLE, $playerData);
            }
            
        }

    }


    public function getPlayerData()
    {
        $gameIds = array();

        foreach($this->recentGames as $value)
        {
            $gameIds[] = $value['gameId'];
        }

        return $this->db->select(self::MATCHES_TABLE, [], ['summonerId' => $this->getId(), 'gameId' => $gameIds]);
    }


}