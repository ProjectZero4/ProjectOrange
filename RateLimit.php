<?php

namespace ProjectOrange;


class RateLimit
{

    /**
     * @var int
     */
    private $time, $shortTime, $longTime, $s_limit, $s_interval, $s_buffer, $l_limit, $l_interval, $l_buffer;

    /**
     * @var DB
     */
    protected $db;

    /**
     * RateLimit constructor.
     * @param DB $db
     */
    public function __construct(DB $db)
    {
        $this->db = $db;

    }

    /**
     * Run Rate Limit
     * @return bool
     */
    public function run()
    {
        $this->resetTime();

        $this->initRateLimitVars();

        $shortLimit = $this->checkLimit(
            'short',
            $this->s_limit,
            $this->s_interval,
            $this->s_buffer
        );
        $longLimit = $this->checkLimit(
            'long',
            $this->l_limit,
            $this->l_interval,
            $this->l_buffer
        );

        return $this->db->insert(
            'rate_limit',
            [
                'short_limit' => ++$shortLimit,
                'long_limit' => ++$longLimit,
                'last_updated' => time()
            ]
        );
    }

    /**
     * @param array $params
     */
    public function forceRateLimitVars(array $params)
    {
        foreach($params as $k => $v)
        {
            $this->$k = $v;
        }
    }

    /**
     * Init Default Rate Limit Values
     * @return bool
     */
    public function initRateLimitVars()
    {
         $this->s_limit = RiotAPI::SHORT_LIMIT;
         $this->s_interval = RiotAPI::SHORT_INTERVAL;
         $this->s_buffer = RiotAPI::SHORT_BUFFER;
         $this->l_limit = RiotAPI::LONG_LIMIT;
         $this->l_interval = RiotAPI::LONG_INTERVAL;
         $this->l_buffer = RiotAPI::LONG_BUFFER;

         return true;
    }

    /**
     * @return void
     */
    private function resetTime()
    {
        $this->time = time();
        $this->shortTime = floor($this->time / 1000) * 1000;
        $this->longTime = floor($this->time / 100) * 100;
    }

    /**
     * @param string $type
     * @param int $limit
     * @param int $interval
     * @param int $buffer
     * @return int $currentLimit
     */
    private function checkLimit(string $type, int $limit, int $interval, int $buffer)
    {
        $column = "{$type}_limit";

        do{

            $result = $this->getLimit($column, $this->{"{$type}Time"});

            if($result === []){
                $currentLimit = 0;
            }else{
                $currentLimit = (int) $result[$column];
            }


            if($limit < ($currentLimit + $buffer)){
                sleep($interval);
            }

        }while($limit < ($currentLimit + $buffer));

        return $currentLimit;
    }

    /**
     * @param string $column
     * @param int $time
     * @return array
     */
    private function getLimit(string $column, int $time)
    {
        $stmt = "select {$column} from rate_limit where last_updated > :time order by last_updated desc limit 1";

        return $this->db->query($stmt, ['time' => $time]);
    }



}