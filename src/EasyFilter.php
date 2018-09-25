<?php

namespace EasyFiltersEloquent;

use EloquentFilter\ModelFilter;

class EasyFilter extends ModelFilter
{
    public $operators = [
        'eq' => '=', 
        'not_eq' => '!=', 
        'not_cont' => 'NOT ILIKE', 
        'cont' => 'ILIKE',
        'start' => 'ILIKE',
        'end' => 'ILIKE',
    ];

    public $likes = [
        'cont' => ['%', '%'],
        'start' => ['', '%'],
        'end' => ['%', ''],
    ];

    public function formatValue($method, $value)
    {
        if (isset($this->likes[$method])) {
            return $this->likes[$method][0].$value.$this->likes[$method][1];
        }

        return $value;
    }

    public function addWhere($method, $column, $value)
    {
        if (isset($this->operators[$method])) {
            return $this->where($column, $this->operators[$method], $this->formatValue($method, $value));
        }
    }

    public function addOrWhere($columns, $operator, $value) 
    {
        $this->where(function($q) use ($columns, $operator, $value) {
            foreach ($columns as $column) {
                $q->orWhere($column, $this->operators[$operator], $this->formatValue($operator, $value));
            }
        });
    }

    public function listOrWhere(array $listOr) 
    {
        $columnsOr = [];
        foreach ($listOr as $value) {
            $item = explode("_", $value);
            switch (count($item)) {
                case 1:
                    $columnsOr[] = $value;
                    break;
                case 2:
                    $columnsOr[] = $item[0];
                    $this->addOrWhere($columnsOr, $item[1], $value);
                    break;
                case 3:
                     // é um relacionamento
                    break;
            }
        }
    }

    public function addCustom($method, $value) 
    {
        $method = $this->getFilterMethod($method);

        if ($this->methodIsCallable($method)) {
            $this->{$method}($value);
        }
    }

    public function filterInput()
    {
        foreach ($this->input as $key => $value) {
            $listOr = explode("_or_", $key);
            if (count($listOr) > 1) {
                $this->listOrWhere($listOr);
            } else {
                $suf = explode("_", $key, 2);
                if (count($suf) == 2 && isset($this->operators[$suf[1]])) { 
                    $this->addWhere($suf[1], $suf[0], $value);
                } else {
                    $this->addCustom($suf, $value);
                }
            }
        }
    }
}