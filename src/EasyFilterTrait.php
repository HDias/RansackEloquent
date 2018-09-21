<?php 

namespace nylmarcos\Searchable;

/**
 * Trait EasyFilterTrait
 * @package nylmarcos\EasyFiltersEloquent
 */
trait EasyFilterTrait
{
    public function eq($column, $value)
    {
        return $this->where($column, $value);
    }

    public function matches($column, $value)
    {
        return $this->whereRaw("cast({$column} as TEXT) ILIKE '%$value%'");
    }
}
