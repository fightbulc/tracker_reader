<?php

namespace Tracker;

/**
 * Reader
 * @package Tracker
 * @author Tino Ehrich (tino@bigpun.me)
 */
class Reader
{
    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @var string
     */
    protected $namespace = 'trk';

    /**
     * @var string
     */
    protected $appId;

    /**
     * @param \Redis $redis
     * @param $appId
     */
    public function __construct(\Redis $redis, $appId)
    {
        $this->redis = $redis;
        $this->appId = $appId;
    }

    /**
     * @return \Redis
     */
    private function getRedis()
    {
        return $this->redis;
    }

    /**
     * @return string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * @return string
     */
    private function getAppId()
    {
        return (string)$this->appId;
    }

    /**
     * @param $eventId
     * @param array $name
     *
     * @return string
     */
    private function buildKeyName(array $name, $eventId = null)
    {
        if ($eventId !== null)
        {
            array_unshift($name, $eventId);
        }

        array_unshift($name, $this->getAppId());

        return $this->getNamespace() . '_' . join(':', $name);
    }

    /**
     * @return array
     */
    public function getCapturedEvents()
    {
        $key = $this->buildKeyName(['events']);

        return $this->getRedis()->sMembers($key);
    }

    /**
     * @param $eventId
     *
     * @return array
     */
    public function getEventObjects($eventId)
    {
        $key = $this->buildKeyName(['hashed', 'oid'], $eventId);

        return $this->getRedis()->hGetAll($key);
    }

    /**
     * @param $eventId
     *
     * @return array
     */
    public function getEventEnvs($eventId)
    {
        $key = $this->buildKeyName(['hashed', 'env'], $eventId);

        return $this->getRedis()->hGetAll($key);
    }

    /**
     * @param $eventId
     * @param $interval
     * @param null $date
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    protected function getCounts($eventId, $interval, $date = null, $user = null, $oid = null, $env = null)
    {
        $isBit = false;

        $params = [];

        // set time
        $params[] = $interval . ($date !== null ? ':' . $date : null);

        // set user
        if ($user !== null)
        {
            $params[] = 'user:' . $user;

            if ($user === 'unique')
            {
                $isBit = true;
            }
        }

        // set oid
        if ($oid !== null)
        {
            $params[] = 'oid:' . $oid;
        }

        // set env
        $params[] = 'env:' . ($env !== null ? $env : 'all');

        // add counts
        $params[] = 'counts';

        // render complete key
        $key = $this->buildKeyName($params, $eventId);

        // --------------------------------------

        // get unique counts
        if ($isBit === true)
        {
            return $this->getRedis()->bitCount($key);
        }

        // get total counts
        return (int)$this->getRedis()->get($key);
    }

    /**
     * @param $eventId
     * @param $date // YYYYMMDDHH
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventHourCounts($eventId, $date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'hour', $date, $user, $oid, $env);
    }

    /**
     * @param $date // YYYYMMDD
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppHourCounts($date, $user = null, $oid = null, $env = null)
    {
        return $this->getEventHourCounts('all', $date, $user, $oid, $env);
    }

    /**
     * @param $eventId
     * @param $date // YYYYMMDD
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventDayCounts($eventId, $date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'day', $date, $user, $oid, $env);
    }

    /**
     * @param $date // YYYYMMDD
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppDayCounts($date, $user = null, $oid = null, $env = null)
    {
        return $this->getEventDayCounts('all', $date, $user, $oid, $env);
    }

    /**
     * @param $eventId
     * @param $date // YYYYWW
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventWeekCounts($eventId, $date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'week', $date, $user, $oid, $env);
    }

    /**
     * @param $date // YYYYWW
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppWeekCounts($date, $user = null, $oid = null, $env = null)
    {
        return $this->getEventWeekCounts('all', $date, $user, $oid, $env);
    }

    /**
     * @param $eventId
     * @param $date // YYYYMM
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventMonthCounts($eventId, $date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'month', $date, $user, $oid, $env);
    }

    /**
     * @param $date // YYYYMM
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppMonthCounts($date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts('all', $date, $user, $oid, $env);
    }

    /**
     * @param $eventId
     * @param $date // YYYY
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventYearCounts($eventId, $date, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'year', $date, $user, $oid, $env);
    }

    /**
     * @param $date // YYYY
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppYearCounts($date, $user = null, $oid = null, $env = null)
    {
        return $this->getEventYearCounts('all', $date, $user, $oid, $env);
    }

    /**
     * @param $eventId
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getEventTotalCounts($eventId, $user = null, $oid = null, $env = null)
    {
        return $this->getCounts($eventId, 'total', null, $user, $oid, $env);
    }

    /**
     * @param null $user
     * @param null $oid
     * @param null $env
     *
     * @return int
     */
    public function getAppTotalCounts($user = null, $oid = null, $env = null)
    {
        return $this->getEventTotalCounts('all', $user, $oid, $env);
    }
}