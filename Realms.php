<?php
/**
 * Created by Josh.
 * Date: 01/07/2018
 * Time: 12:41
 * Last Update: 01/07/2018 - 12:41
 */

namespace ProjectOrange;


class Realms extends DataDragon
{

    const NA = 'na1';
    const NA_OLD = 'na';
    const EUW = 'euw1';
    const EUNE = 'eun1';
    const BR = 'br1';
    const JP = 'jp1';
    const KR = 'kr';
    const LAS = 'la1';
    const LAN = 'la2';
    const OCE = 'oc1';
    const TR = 'tr1';
    const RU = 'ru';
    const PBE = 'pbe1';

    const NA_SUB = 'na';
    const EUW_SUB = 'euw';
    const EUNE_SUB = 'eune';
    const BR_SUB = 'br';
    const JP_SUB = 'jp';
    const KR_SUB = 'kr';
    const LAS_SUB = 'las';
    const LAN_SUB = 'lan';
    const OCE_SUB = 'oce';
    const TR_SUB = 'tr';
    const RU_SUB = 'ru';
    const PBE_SUB = 'pbe';

    // To be used for mass cache updates only.
    const ALL = 'all';

    /**
     * @var DB $db
     */
    protected $db;

    /**
     * @var string
     */
    private $dd_url = "https://ddragon.leagueoflegends.com/realms/{realm}.json";

    /**
     * Realms constructor.
     * @param DB $db
     */
    public function __construct(DB $db)
    {
        parent::__construct($db);
    }

    /**
     * @param string $realm
     * @param int $cache_time
     * @return array|bool|int
     */
    public function getRealm(string $realm, int $cache_time = 0)
    {
        $this->table = 'realm_data';

        if($realm === 'all'){
            $where = [];
        }else{
            $where = ['realm' => $realm];
        }

        $cache = $this->checkCache($this->table, $where, $cache_time);


        if($cache !== false && $cache !== 0){
            return $cache;
        }

        $url = $this->formatURL($this->dd_url, $realm);
        $result = $this->queryDD($url);
        $arr_1 = $result['n'];

        unset($result['n']);

        $result = array_merge($arr_1, $result, $where);

        $this->insertUpdateCache($cache, $result, $where);

        return $this->db->row($this->table, [], $where);
    }

    /**
     * @param array $realm
     * @return string
     */
    public static function getRealmBaseURL(array $realm)
    {
        return "{$realm['cdn']}/{$realm['v']}";
    }


}