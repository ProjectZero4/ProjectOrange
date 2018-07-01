<?php

namespace ProjectOrange;

/**
 * Class Champion
 * @package ProjectOrange
 */
class Champion extends RiotAPI{

    /**
     * @var string
     */
    protected $class_link = "api.riotgames.com/lol/platform/v3/champions/";

    /**
     * @var string
     */
    protected $table = 'champions';

    /**
     * @var DB $db
     */
    protected $db;

    /**
     * @param int $id
     * @return array
     */
    public function champions(int $id = null){

        if($id)
        {
            $cache = $this->checkCache($this->table, ['id' => $id], self::HALF_HOUR);

            if($cache !== false && $cache !== 0){
                return $cache;
            }
            $where = ['id' => $id];

        }

        $url = $this->formatURL((string)$id);

        $result = $this->queryRiot($url);


        if($id && isset($cache)){
            $this->insertUpdateCache($cache, $result, ['id' => $id]);
        }else{
            foreach($result['champions'] as $row)
            {
                $cache = $this->checkCache($this->table, ['id' => $row['id']],self::FULL_HOUR);
                $this->insertUpdateCache($cache, $row, ['id' => $row['id']]);
            }
            $where = [];
        }

        return $this->db->row($this->table, [], $where);
    }

}