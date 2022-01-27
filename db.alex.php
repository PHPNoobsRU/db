<?php


class DB
{
    public static $QL;

    public static $STATE;

    public static $TOTAL;

    private static $DBNAME;

    private static $DSN;

    private static $LINK;

    private static $Q;

    function __construct($DSN)
    {
        if (!is_array($DSN)) {
            throw new \ErrorException('DB: wrong DSN array');
            return FALSE;
        }
        self::$DSN    = $DSN;
        self::$DBNAME = $DSN['base'];
        self::$Q      = 0;
        self::$QL     = [];
        self::$STATE  = self::_connect();
        return TRUE;
    }

    public static function count($result)
    {
        if (isset($result['count'])) {
            return $result['count'];
        }
        return 0;
    }

    /**
     * @param  $sql         string
     * @param  $data        array
     * @param  $id_field    mixed
     * @param  $id_subfield mixed
     * @return mixed
     */
    public static function fetch_query($sql, $data = [], $id_field = false, $id_subfield = false)
    {
        $out = [];
        $res = self::query($sql, $data);
        if ($res) {
            do {
                if ($result = self::$LINK->store_result()) {
                    if ($result->num_rows > 1) {
                        while ($row = $result->fetch_assoc()) {
                            if ($id_field && $id_field !== true) {
                                if ($id_subfield && $id_subfield !== true) {
                                    if (!isset($out[$row[$id_field]])) {
                                        $out[$row[$id_field]] = [];
                                    }
                                    $out[$row[$id_field]][$row[$id_subfield]] = $row;
                                } else {
                                    $out[$row[$id_field]] = $row;
                                }
                            } else {
                                $out[] = $row;
                            }
                        }
                    } else {
                        if ($result->num_rows > 0) {
                            if ($id_field && $id_field !== true) {
                                $row = $result->fetch_assoc();
                                if ($id_subfield && $id_subfield !== true) {
                                    if (!isset($out[$row[$id_field]])) {
                                        $out[$row[$id_field]] = [];
                                    }
                                    $out[$row[$id_field]][$row[$id_subfield]] = $row;
                                } else {
                                    $out[$row[$id_field]] = $row;
                                }
                            } else {
                                if ($id_field === false) {
                                    $out[] = $result->fetch_assoc();
                                } else {
                                    $out = $result->fetch_assoc();
                                }
                            }
                        } else {
                            $out = false;
                        }
                    }
                    $result->free();
                }
            } while ((self::$LINK->more_results()) ? self::$LINK->next_result() : false);
        }
        return $out;
    }

    public static function key_value($result, $key, $value)
    {
        $out = array();
        if (is_array($result)) {
            foreach ($result as $rv) {
                $out[$rv[$key]] = $rv[$value];
            }
        }
        return $out;
    }

    /**
     * @param  $sql
     * @return mixed
     */
    public static function query($sql, $data = [])
    {
        self::$Q++;
        self::$QL[self::$Q] = [
            'raw'  => $sql,
            'sql'  => self::_parse($sql, $data),
            'data' => $data,
            'time' => microtime(TRUE)
        ];
        $result                     = self::$LINK->multi_query(self::$QL[self::$Q]['sql']);
        self::$QL[self::$Q]['time'] = number_format(microtime(TRUE) - self::$QL[self::$Q]['time'], 6, '.', '');
        return $result;
    }

    public static function query_count($sql, $data = [])
    {
        self::$TOTAL = 0;
        $countsql    = "SELECT COUNT(1) limit_total FROM($sql) sq";
        $res         = self::fetch_query($countsql, $data);
        if ($res) {
            self::$TOTAL = (int) $res[0]['limit_total'];
        }
        return self::$TOTAL;
    }

    private static function _connect()
    {
        self::$LINK = new mysqli(self::$DSN['host'], self::$DSN['user'], self::$DSN['pass'], self::$DSN['base'], (int) self::$DSN['port']);
        if (mysqli_connect_errno() > 0) {
            return false;
        } else {
            if (!isset(self::$DSN['char'])) {
                self::$DSN['char'] = 'UTF8';
            }
            self::query("SET NAMES '{char}';", [
                'char' => self::$DSN['char']
            ]);
            self::query("USE '{base}';", [
                'base' => self::$DBNAME
            ]);
            return true;
        }
    }

    private static function _parse($sql, $data = [])
    {
        return preg_replace_callback('/\{(\w+)?\}/', function ($matches) use ($data) {
            return (array_key_exists($matches[1], $data)) ? $data[$matches[1]] : '';
        }, $sql);
    }
}
