<?php

namespace EasyFiltersEloquent;

use EloquentFilter\ModelFilter;
use EasyFiltersEloquent\EasyFilterTrait;

class EasyFilter extends ModelFilter
{
    use EasyFilterTrait;

    public function filterInput()
    {
        foreach ($this->input as $key => $val) {

            $data = explode("_", $key);

            if (count($data) == 2) { 
                $column = $data[0];
                $method = $data[1];
                if ($this->methodIsCallable($method)) {
                    $this->$method($column, $val);
                }                
            } else {
                //$method = $this->getFilterMethod($key);

                if ($this->methodIsCallable($key)) {
                    $this->{$key}($val);
                }
            }
        }
    }
}