<?php
/**
 * Created by Josh.
 * Date: 27/03/2018
 * Time: 12:57
 * Last Update: 27/03/2018 - 18:31
 */

namespace ProjectOrange;


class Match extends RiotAPI {

    protected $class_link = 'api.riotgames.com/lol/match/v3/';

    /**
     * @var DB $db
     */
    protected $db;

    /**
     * @var
     */
    protected $table;

    /**
     * Get match data via a given match/game ID
     * @param $match_id
     * @return array|bool
     */
    public function match($match_id){

        $table = 'matches';

        $cache = $this->checkCache($table, ['gameId' => $match_id], -1);

        if($cache !== false){
            return $cache;
        }

        $url = $this->formatURL($match_id, 'matches/');

        $result = $this->queryRiot($url);

        foreach($result['participantIdentities'] as $val){
            $result['summonerIds'][] = isset($val['player']['summonerId']) ? $val['player']['summonerId'] : 1;
            $result['accountIds'][] = isset($val['player']['accountId']) ? $val['player']['accountId'] : 1;
        }


        $this->insertCache($table, $result);

        return $this->db->row($table, [], ['gameId' => $match_id]);
    }

    /**
     * Get match list of a given Account ID TODO: Currently only works correctly for current season.
     * @param int $accountId
     * @param array $params
     * @param int $recent
     * @return array|bool
     */
    public function matchList(int $accountId, array $params = [], int $recent = 0){

        $this->table = 'match_lists';

        if($recent === 1) {
            $params['beginIndex'] = 0;
            $params['endIndex'] = 20;
        }

        $url = $this->formatURL('', "matchlists/by-account/{$accountId}/", $params);

        $result = $this->queryRiot($url);
        if(!isset($result['matches'])) {
            var_dump($url);
        }

        foreach($result['matches'] as $match) {
            $cache = $this->db->row($this->table, [], ['gameId' => $match['gameId'], 'accountId' => $accountId]);

            $match['accountId'] = $accountId;

            if ($cache === []) {
                $this->insertCache($this->table, $match);
            }
        }

        $extra = "order by `timestamp` desc";

        if($recent === 1){
            $extra .= " limit 0,20";
        }else{
            if(isset($params['beginIndex'], $params['endIndex'])){
                $lower = intval($params['beginIndex']);
                $upper = intval($params['endIndex']);
            }else{
                $lower = 0;
                $upper = 10;
            }


            $extra .= " limit {$lower},{$upper}";
        }

        $where = [
            'accountId' => $accountId,
            'queue' => isset($params['queue']) ? $params['queue'] : null,
            'champion' => isset($params['champion']) ? $params['champion'] : null
        ];

        foreach($where as $key => $val){
	        if($val === null){
		        unset($where[$key]);
	        }
        }

        return $this->db->select($this->table, [], $where, $extra);

    }

    public function timeline($match_id){

        // TODO: Need to figure out a smart way of storing the JSON responce as it's frame intervals and the number can vary dependant on game length

    }
    // TODO: Look into tournament code end points. Requires a sepecific API key from riot.



    /*
     * Personal Functions not to be included in the library by default
     */

    public function mostlyPlays($mostly_plays){
        return (array_keys($mostly_plays, max($mostly_plays)))[0];
    }
    public function leastPlays($mostly_plays){
        return (array_keys($mostly_plays, min($mostly_plays)))[0];
    }
    public function mostlyPlaysLane($mostly_plays){
        $key = (array_keys($mostly_plays, max($mostly_plays)))[0];

        $stmt = "select name from lanes where `key` = :key";
        $result = $this->db->query($stmt, array('key' => $key));
        if($result === []){
            return $key;
        }
        return $result['name'];
    }
    public function lastGame($time_val){
        $dateLastGame = date('d/m/Y', floor($time_val/1000));
        if($dateLastGame === date('d/m/Y')){
            return date('H:i', floor($time_val/1000));
        }else{
            return date('d/m/Y', floor($time_val/1000));
        }
    }




}