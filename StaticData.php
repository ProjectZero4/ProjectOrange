<?php
/**
 * Created by Josh.
 * Date: 01/07/2018
 * Time: 16:52
 * Last Update: 01/07/2018 - 16:52
 */

namespace ProjectOrange;

/**
 * Class StaticData
 * @package ProjectOrange
 */
class StaticData extends DataDragon
{
    /**
     * @var DB $db
     */
    protected $db;

    /**
     * @var Realms
     */
    protected $realm;

    public function __construct(DB $db, Realms $realm)
    {
        parent::__construct($db);

        $this->realm = $realm;
    }


    /**
     * @param string|int $champion
     * @return array
     */
    public function champion($champion = 'champion')
    {
        $regex = '/^\d+$/';

        if(preg_match($regex, $champion)){
            // Champion Key
            $column = 'key';
        }else{
            $column = 'id';
        }

        $method = 'champion';

        if($champion === 'champion'){
            $where = [];
        }else{
            $where = [$column => $champion];
        }

        $this->table = 'champion_data';

        $cache = $this->checkCache($this->table, $where, self::FOUR_WEEKS);

        if($cache !== false && $cache !== 0){
            return $cache;
        }

        $url = Realms::getRealmBaseURL($this->realm->getRealm(Realms::EUW_SUB)) . "/data/en_US/{$method}.json";

        $result = $this->queryDD($url);

        foreach($result['data'] as $row)
        {
            foreach($row as $k => $v)
            {
                if(is_array($v))
                {
                    $row[$k] = json_encode($v);
                }
            }

            $cache = $this->checkCache($this->table, ['id' => $row['id']], self::FOUR_WEEKS);

            $this->insertUpdateCache($cache, $row, ['id' => $row['id']]);
        }

        return $this->db->row($this->table, [], $where);

    }
}