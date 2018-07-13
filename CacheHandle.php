<?php
/**
 * Created by Josh.
 * Date: 01/07/2018
 * Time: 13:34
 * Last Update: 01/07/2018 - 13:34
 */

namespace ProjectOrange;

/**
 * Class CacheHandle
 * @package ProjectOrange
 */
abstract class CacheHandle
{

    /**
     * @var DB
     */
    protected $db;

    /**
     * @var string
     */
    protected $cache_column = 'last_updated';

    /**
     * @var
     */
    protected $table;

    /**
     * CacheHandle constructor.
     * @param DB $db
     */
    protected function __construct(DB $db)
    {
        $this->db = $db;
    }

    /**
     * @param string $table
     * @param array $params
     * @param int $cache_time
     * @return array|bool|int
     */
    protected function checkCache(string $table, array $params, int $cache_time = 0)
    {
        $result = $this->db->row($table, [], $params);

        if($result === [])
        {
            // Insert Cache
            return false;
        }

        if($cache_time > 0 && time() - ($result[$this->cache_column]/1000) > $cache_time)
        {
            // Update Cache
            return 0;
        }

        return $result;
    }

    /**
     * @param string $table
     * @param array $params
     * @return bool
     */
    protected function insertCache(string $table, array $params)
    {
        $params[$this->cache_column] = time();

        return $this->db->insert($table, $params);
    }

    /**
     * @param string $table
     * @param array $params
     * @param array $where
     * @return bool
     */
    protected function updateCache(string $table, array $params, array $where)
    {
        $params[$this->cache_column] = time();

        return $this->db->update($table, $params, $where);
    }

    /**
     * @param bool|int $cache
     * @param array $result
     * @param array $where
     */
    protected function insertUpdateCache($cache, array $result, array $where)
    {
        if($cache === false){
            $this->insertCache($this->table, $result);
        }else{
            $this->updateCache($this->table, $result, $where);
        }
    }

}