<?php 
namespace RansackEloquent;

use Exception;
use Illuminate\Database\Eloquent\Relations\Relation;
use ReflectionClass;
use ReflectionMethod;

trait RansackEloquentTrait
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

    public $joins = [];
    public $andWheres = [];
    public $orWheres = [];

    public function formatValue($method, $value)
    {
        if (isset($this->likes[$method])) {
            return $this->likes[$method][0].$value.$this->likes[$method][1];
        }
        return $value;
    }

    public function scopeAddListWhere($query) 
    {
        foreach ($this->andWheres as $item) {
            if (!isset($this->operators[$item['operator']])) {
                continue;
            }

            $query->where(
                $item['column'], 
                $this->operators[$item['operator']], 
                $this->formatValue($item['operator'], $item['value'])
            );
        }

        return $query;
    }

    public function scopeAddOrWhere($query) 
    {
        if (!isset($this->orWheres['columns']) || !isset($this->orWheres['operator'])) {
            return;
        }

        return $query->where(function($q) {
            foreach ($this->orWheres['columns'] as $column) {
                $q->orWhere(
                    $column, 
                    $this->operators[$this->orWheres['operator']], 
                    $this->formatValue($this->orWheres['operator'], $this->orWheres['value'])
                );
            }
        });
        
    }

    public function listOrWhere(array $listOr, $valor) 
    {
        foreach ($listOr as $value) {
            $item = explode("_", $value);
            switch (count($item)) {
                case 1:
                    $this->orWheres['columns'][] = $this->getTable().'.'.$value;

                    break;
                case 2:
                    $this->orWheres['columns'][] = $this->getTable().'.'.$item[0];
                    $this->orWheres['operator'] = $item[1];
                    $this->orWheres['value'] = $valor;

                    break;
                case 3:

                    $relationships = $this->relationships();
                    if (!isset($relationships[$item[0]])) {
                        continue;
                    }
                        
                    $relation = new $relationships[$item[0]]['model'];

                    $this->addJoin($relation);

                    $this->orWheres['columns'][] = $relation->getTable().'.'.$item[1];
                    $this->orWheres['operator'] = $item[2];
                    $this->orWheres['value'] = $valor;
                    

                    break;
            }
        }
    }

    public function addJoin($relation)
    {
        $this->joins[$relation->getTable()] = [
            'first' => $relation->getTable(),
            'firstPrimaryKey' => $relation->getTable().'.'.$relation->primaryKey,
            'second' => $this->getTable().'.'.$this->primaryKey,
        ];
    }

    
    public function scopeFilterr($query, array $dados = [])
    {
        foreach ($dados as $key => $value) {
            $listOr = explode("_or_", $key);
            if (count($listOr) > 1) {
                $this->listOrWhere($listOr, $value);
            } else {
                $operatorColumn = explode("_", $key, 2);
                if (count($operatorColumn) == 2 && isset($this->operators[$operatorColumn[1]])) { 
                    $this->andWheres[] = [
                        'operator' => $operatorColumn[1],
                        'column' => $this->getTable().'.'.$operatorColumn[0],
                        'value' => $value,
                    ];
                } elseif (count(explode("_", $key)) == 3) {
                    $relationship = explode("_", $key);

                    $relationships = $this->relationships();
                    if (!$relationships[$relationship[0]]) {
                        continue;
                    }

                    $relation = new $relationships[$relationship[0]]['model'];

                    $this->addJoin($relation);
                
                    $this->andWheres[] = [
                        'operator' => $relationship[2],
                        'column' => $relation->getTable().'.'.$relationship[1],
                        'value' => $value,
                    ];

                }
            }
        }

        $query->setJoins();
        $query->addListWhere();
        $query->addOrWhere();

        return $query;
    }

    public function scopeSetJoins($query)
    {   
        foreach ($this->joins as $join) {
            $query->join(
                $join['first'], 
                $join['firstPrimaryKey'], 
                '=', 
                $join['second']
            );
        }

        return $query;
    }

    public function relationships()
    {
        try {
            $model = new static;
            $relationships = [];
            foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->class != get_class($model) ||
                    !empty($method->getParameters()) ||
                    $method->getName() == __FUNCTION__) {
                    continue;
                }
                $return = $method->invoke($model);
                if ($return instanceof Relation) {
                    $relationships[$method->getName()] = [
                        'type' => (new ReflectionClass($return))->getShortName(),
                        'model' => (new ReflectionClass($return->getRelated()))->getName()
                    ];
                }
            }
        } catch (Exception $e) {
        }

        return $relationships;
    }
}