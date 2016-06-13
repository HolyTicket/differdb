<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Change: A database change
 * @package App\Models
 */
class Change extends Eloquent
{
    /**
     * Additional info is JSON
     * @var array
     */
    protected $casts = [
        'additional_info' => 'json'
    ];
    /**
     * Get the underlying children of this change.
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function children()
    {
        return $this->hasMany('App\Models\Change', 'parent_id', 'id');
    }

    /**
     * Get the parent of this change
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo('App\Models\Change', 'parent_id');
    }

    /**
     * Add a change to it's children
     * @param $name
     * @param $type
     * @param $entity
     * @param string $sql
     * @param null $dependency
     * @return Change
     */
    public function addChange($name, $type, $entity, $sql = '', $dependency = null, $additional_info = null) {
        $change = new Change();
        $change->type = $type;
        $change->name = $name;
        $change->entity = $entity;
        $change->sql = $sql;
        if($dependency != null) {
            $dependency = json_encode($dependency);
        }
        $change->dependency = $dependency;
        $change->additional_info = (array) $additional_info;

        $order = config('diff.order');
        $pos = array_search($change->type, $order);

        $change->sort = $pos;

        $this->children()->save($change);
        return $change;
    }

    /**
     * @param $data
     * @return bool
     */
    public function matches($data) {
        foreach($data as $name => $val) {
            if($this->{$name} != $val) return false;
        }
        return true;
    }

    public function addDependency($id) {
        $current = $this->dependency;
        if(!is_array($current)) {
            $current = [];
        }
        $current[] =  $id;
        $this->dependency = json_encode($current);
    }

    public function getParentTable()
    {
        $ancestors = $this->where('id', '=', $this->parent_id)->get();

        while ($ancestors->last() && $ancestors->last()->parent_id !== null)
        {
            $parent = $this->where('id', '=', $ancestors->last()->parent_id)->get();
            $ancestors = $ancestors->merge($parent);
        }

        foreach($ancestors as $an) {
            if($an->entity == 'table') {
                return $an;
            }
        }
        return false;
    }



}