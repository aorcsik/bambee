<?php

require_once("F.class.php");

class BambeeTemplate extends F
{
    private $compiled = "";
    private $conditions = array();
    
    private $template = "";
    private $vars = array();
    
    private $bambee;
    
    public function __construct($template_string,$bambee)
    {
        $this->bambee = $bambee;
        $this->compiled = preg_replace("'[{][*].*?[*][}]'s","",$template_string);
        do
        {
            $end = $this->find_conditions();
        }
        while (false === $end);
    }
    
    private function find_conditions($offset=-1,$tag=null)
    {
        // if ($offset == -1) echo "Compiling!";
        $pos = false;
        // We search for the block tags below
        $block_tags = array("if","foreach");
        foreach ($block_tags as $tagname)
        {
            $new_pos = strpos($this->compiled,"{".$tagname." ",$offset+1);
            // We have a match for the starting tag and the position is smaller than the other
            if (false != $new_pos && (false === $pos || $new_pos < $pos))
            {
                $pos = $new_pos;
                $searchtag = $tagname;
            }
        }
        // If we find a $tag starting tag -> return false
        if ($tag != "foreach" && false !== $pos)
        {
            // echo "<blockquote>Found starting ".$searchtag." tag!<br />";
            do
            {
                // We search for another after it, or a $tag ending
                $end = $this->find_conditions($pos,$searchtag);
                // $end will be:
                //  -  true if ending found, 
                //  -  false if another starting tag is found (handled recursively)
            }
            while (false === $end);
            // all the way until we find an ending... or nothing
            return false;
        }
        // If we find an ending tag -> return true
        elseif (false !== ($pos = strpos($this->compiled,"{/".$tag,$offset+1)))
        {
            // echo "Found ending ".$tag." tag!</blockquote>";
            
            $condition_start = $offset;
            $condition_end = $pos + strlen($tag) + 3;
            $condition_length = $condition_end - $condition_start;
            
            // Isolate the whole condition
            $c = substr($this->compiled,$condition_start,$condition_length);
            // store it in an array of conditions
            // $this->conditions[] = preg_replace("'[\n\r]'s","",$c);
            $this->conditions[] = $c;
            $cid = count($this->conditions) - 1;
            // and replace it with a condition reference {condition:#} in the live template
            $this->compiled = substr($this->compiled,0,$condition_start)."{condition:".$cid."}".substr($this->compiled,$condition_end);
            return true;
        }
        // If we find nothing -> throw Exception!
        elseif ($offset > -1)
        {
            throw new BambeeTemplateException("Bird! Missing condition ending!");
        }
        else
        {
            return true;
        }
    }

    private function resolve_condition($condition_id)
    {
        if (!empty($this->conditions[$condition_id]))
        {
            /* Matches:
             *  0 initial match
             *  1 condition start tags (if, elseif, else)
             *  2 condition
             *  3 condition branch resolution
             *  - ignored match (condition look-ahead)
             */
            if (preg_match_all("'[{](foreachelse|foreach)([^}]*)[}](.*?)(?=[{](?:foreachelse|/foreach))'s",$this->conditions[$condition_id],$n))
            {
                return $this->resolve_foreach($n);
            }
            elseif (preg_match_all("'[{](if|elseif|else)([^}]*)[}](.*?)(?=[{](?:elseif|else|/if))'s",$this->conditions[$condition_id],$n))
            {
                return $this->resolve_if($n);
            }
            else
            {
                return "";
            }
        }
    }
    
    private function resolve_foreach($n)
    {
        foreach ($n[1] as $k => $v)
        {
            if ($v == "foreach")
            {
                $props = trim($n[2][$k]);
                if (preg_match("'from=[$]([^\s]+)'",$props,$m))
                {
                    $from = $this->getvareval($m[1]);
                    if (is_array($from) && !empty($from))
                    {
                        if (preg_match("/item=(['\"])([^'\"\s]+)\\1/",$props,$m))
                        {
                            $item = $m[2];
                        }
                        else throw new BambeeTemplateException("Bird! Missing property: item!");
                        
                        if (preg_match("/key=(['\"])([^'\"\s]+)\\1/",$props,$m))
                        {
                            $key = $m[2];
                        }
                        $res = "";
                        foreach ($from as $fk => $fv)
                        {
                            /* Here comes the actual foreach resolving */
                            $this->bambee->assign($key,$fk);
                            $this->bambee->assign($item,$fv);
                            $res .= $this->bambee->fetch($n[3][$k],true);
                        }
                        return $res;
                    }
                }
                else throw new BambeeTemplateException("Bird! Missing property: from!");
            }
            elseif ($v == "foreachelse")
            {
                return $n[3][$k];
            }
        }
        return "";
    }
    
    private function resolve_if($n)
    {
        foreach ($n[1] as $k => $v)
        {
            if ($v == "if" || $v == "elseif")
            {
                $cond = trim($n[2][$k]);
                if (!empty($n[2][$k]))
                {
                    if (preg_match("'^(!?)[$]([^\s]+)$'",$cond,$m))
                    {
                        $var = $this->getvareval($m[2]);
                        if (($m[1] == "!" &&  empty($var))
                         || ($m[1] != "!" && !empty($var)))
                        {
                            return $n[3][$k];
                        }
                    }
                    else throw new BambeeTemplateException("Bird! Incompatible condition!");
                }
                else throw new BambeeTemplateException("Bird! Missing condition!");
            }
            elseif ($v == "else")
            {
                return $n[3][$k];
            }
        }
        return "";
    }
    
    public function getvareval($tplvar)
    {
        $var = null;
        $eval = "['".preg_replace("'[.]'","']['",$tplvar)."']";  // .   -> ']['
        $eval = preg_replace("/([^\]])\[/","\\1'][",$eval);      // a[  -> a'][
        $eval = '$this->vars'.preg_replace("/\]'\]/","]",$eval); // ]'] -> ]
        $eval = '$var = isset('.$eval.') ? '.$eval.' : null;';
        eval($eval);
        return $var;
    }
    
    public function resolve(&$vars)
    {
        $this->vars = $vars;
        $this->template = $this->compiled;
        
        while (preg_match("'[{]condition:([0-9]+)[}]'",$this->template,$n))
        {
            $resolution = $this->resolve_condition($n[1],$this->template);
            $this->template = str_replace($n[0],$resolution,$this->template);
        }
        
        if (preg_match_all("'[{][$](.*?)[}]'",$this->template,$n))
        {
            foreach ($n[0] as $k => $v)
            {
                $var = $this->getvareval($n[1][$k]);
                $this->template = str_replace($v,$var,$this->template);
            }
        }
        
        $this->template = preg_replace("'[{].*?[}]'","",$this->template);
        
        return $this->template;
    }
}

class BambeeTemplateException extends Exception
{
    
}