<?php

require_once("BambeeTemplate.class.php");

class Bambee
{
    private $vars = array();
    private $template_dir;
    private $templates;
    
    public function __construct($template_dir)
    {
        $this->template_dir = $template_dir;
    }
    
    public function assign($key,$value=null)
    {
        if (is_null($value) && is_array($key))
        {
            foreach($key as $k => $v)
            {
                $this->vars[$k] = $v;
            }
        }
        else
        {
            $this->vars[$key] = $value;
        }
    }
    
    public function fetch($template,$source=false)
    {
        if (false !== $source)
        {
            $hash = md5($template);
            if (empty($this->templates[$hash]))
            {
                $this->templates[$hash] = new BambeeTemplate($template,$this);
            }
        }
        else
        {
            $hash = md5($template);
            if (empty($this->templates[$hash]))
            {
                if (file_exists($template))
                {
                    $this->templates[$hash] = new BambeeTemplate(file_get_contents($template),$this);
                }
                elseif (file_exists($this->template_dir.$template))
                {
                    $this->templates[$hash] = new BambeeTemplate(file_get_contents($template),$this);
                }
                else throw new BambeeException("Bird! Template file does not exist!");
            }
        }
        
        return $this->templates[$hash]->resolve($this->vars);
    }
}

class BambeeException extends Exception
{
    
}