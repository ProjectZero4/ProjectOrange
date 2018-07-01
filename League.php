<?php
	/**
	 * Created by Josh Capener.
	 * Date: 18/03/2018
	 * Time: 13:54
     * Last Updated: 26/03/2018 - 21:41
	 */

	// TODO: The challenger and master methods require a delete function as people should move in and out of them.

	namespace ProjectOrange;

    /**
     * Class League
     * @package libraries\RiotAPI
     */
	class League extends RiotAPI{
		
		protected $class_link = "api.riotgames.com/lol/league/v3/";


        /**
         * Update Challenger Table
         * @param $queue
         * @return bool
         */

		public function updateChallenger($queue){

			$table      = "ranked_challenger";
            $queueType  = $this->getQueue($queue);

		    $url        = $this->formatURL($queueType, 'challengerleagues/by-queue/');
            $result     = $this->queryRiot($url);
            $entries    = $result['entries'];

            $this->processEntries($entries, $result, $table);

            return true;
		}

        /**
         * Get all ranked stats from a given League ID.
         *
         * @param $leagueId
         * @return array|bool|string
         */

		public function updateLeague($leagueId){

            $url        = $this->formatURL($leagueId, 'leagues/');
            $result     = $this->queryRiot($url);
            $entries    = $result['entries'];

            $table      = "ranked";

            $this->processEntries($entries, $result, $table);

            return true;
		}

        /**
         * Update Master Table
         * @param $queue
         * @return bool
         */

		public function updateMaster($queue){

            $table      = "ranked_master";
            $queueType  = $this->getQueue($queue);

            $url        = $this->formatURL($queueType, 'masterleagues/by-queue/');
            $result     = $this->queryRiot($url);
            $entries    = $result['entries'];

            $this->processEntries($entries, $result, $table);

            return true;
		}
		
		/**
         * Get a Summoners ranked stats from their Summoner ID. <*>note<*> Although it will only retrive a specific queue, if they're not in the DB or the entries are too old, it will update all queues for that Summoner.
         *
		 * @param int $summoner_id
		 * @param int $queue
		 * @return array|bool
		 */

		public function ranked($summoner_id, $queue = 0){

		    $table = 'ranked';
            $cacheTime = self::HALF_HOUR;

            $queueType = $this->getQueue($queue);


            $cache = $this->checkCache($table, ['playerOrTeamId' => $summoner_id, 'queueType' => $queueType], $cacheTime);

            if($cache !== false && $cache !== 0){
                return $cache;
            }

			$url = $this->formatURL($summoner_id, 'positions/by-summoner/');

			$response = $this->queryRiot($url);

			if($response === [])
            {
                return ['tier' => 'PROVISIONAL', 'rank' => '', 'wins' => 0, 'losses' => 0];
            }

			foreach($response as $key => $val){
			    $cache = $this->checkCache($table, ['playerOrTeamId' => $summoner_id, 'queueType' => $val['queueType']], $cacheTime);
			    if($cache === 0){
                    $this->updateCache($table, $val, ['playerOrTeamId' => $summoner_id, 'queueType' => $queueType]);
                }else{
                    $this->insertCache($table, $val);
			    }
            }
            $params = ['playerOrTeamId' => $summoner_id, 'queueType' => $queueType];

            return $this->db->row($table, $params);
		}

        /**
         * @param $queue
         * @return string
         */
		private function getQueue($queue){
            switch($queue){
                case 0:
                    $queueType = 'RANKED_SOLO_5x5';
                    break;
                case 1:
                    $queueType = 'RANKED_FLEX_SR';
                    break;
                case 2:
                    $queueType = 'RANKED_FLEX_TT';
                    break;
                default:
                    $queueType = 'RANKED_SOLO_5x5';
                    break;
            }
            return $queueType;
        }

        /**
         * @param $entries
         * @param $result
         * @param $table
         */
        private function processEntries($entries, $result, $table){

            $time = time();

            foreach($entries as $val){

                $val['tier']            = $result['tier'];
                $val['queueType']       = $result['queue'];
                $val['leagueName']      = $result['name'];
                $val['last_updated']    = $time;

                $cache = $this->checkCache($table, ['playerOrTeamId' => $val['playerOrTeamId'], 'queueType' => $val['queueType']], self::HALF_HOUR);

                if($cache === 0){
                    $this->updateCache($table, $val, ['playerOrTeamId' => $val['playerOrTeamId'], 'queueType' => $val['queueType']]);
                }else{
                    $this->insertCache($table, $val);
                }

            }

        }
	}