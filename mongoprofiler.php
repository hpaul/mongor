<?php namespace Mongor;

use Laravel\View;
use Laravel\File;
use Laravel\Event;
use Laravel\Config;
use Laravel\Request;
use Laravel\Database;

class MongoProfiler extends \Laravel\Profiling\Profiler
{

    /**
     * Add a performed MongoDB query to the Profiler.
     *
     * @param     string     $sql
     * @param     array      $bindings
     * @param     float      $time
     *
     * @return     void
     */
    public static function mongo_query($db, $sql, $bindings, $time)
    {
        $sql = 'Command:' . $sql .', params [';

        $sql = $sql . implode(', ', MongoProfiler::format_parameters($bindings));

        $sql .= '] (DB ' . $db . ')';

        static::$data['queries'][] = array($sql, $time);
    }

    protected static function format_parameters($bindings)
    {
        $statements = array();

        foreach ($bindings as $key => $binding) {

            if(isset($binding) && !empty($binding)) {

                $statement = $key . ':';

                if (is_array($binding)) {
                    $statements = array_merge($statements, MongoProfiler::format_parameters($binding));
                }
                else {
                    $statement .= htmlspecialchars($binding);
                }

                $statements[] = $statement;
            }
        }

        return $statements;
    }

    /**
     * Attach the Profiler's event listeners.
     *
     * @return void
     */
    public static function attach()
    {
        \Laravel\Profiling\Profiler::attach();

        Event::listen('laravel.mongoquery', function ($db, $sql, $bindings, $time) {
            MongoProfiler::mongo_query($db, $sql, $bindings, $time);
        });
    }


}