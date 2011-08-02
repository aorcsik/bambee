<?php

class F
{
    public static function implodeArrayKeys(&$array,$sep=".")
    {
        $vals = array();
        foreach ($array as $k => $v)
        {
            if (is_array($v))
            {
                $vals[$k] = empty($v) ? null : "Array";
                $inner_values = self::implodeArrayKeys($v,$sep);
                foreach ($inner_values as $kk => $vv)
                {
                    $key = is_int($k) ? "[".$k."]" : $k;
                    if (is_int($kk))
                    {
                        $key .= "[".$kk."]";
                        $vals[$key] = $vv;
                    }
                    elseif (is_string($kk))
                    {
                        if (preg_match("'^\['",$kk))
                        {
                            $key .= $kk;
                        }
                        else
                        {
                            $key .= $sep.$kk;
                        }
                        $vals[$key] = $vv;
                    }
                }
            }
            else
            {
                $vals[$k] = $v;
            }
        }
        return $vals;
    }

    public static function explodeArrayKeys(&$array,$sep=".")
    {

        $sepx = "[".implode("][",str_split($sep))."]";
        $vals = array();
        foreach ($array as $k => $v)
        {
            if (preg_match("'^(.+?)".$sepx."(.+)$'",$k,$n))
            {
                if (!isset($vals[$n[1]])) $vals[$n[1]] = array();
                $vals[$n[1]][$n[2]] = $v;
            }
            else
            {
                $vals[$k] = $v;
            }
        }
        foreach ($vals as $k => $v)
        {
            if (is_array($v))
            {
                $vals[$k] = self::explodeArrayKeys($v,$sep);
            }
        }
        return $vals;
    }




}
