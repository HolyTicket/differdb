<?php
namespace App\Services;

use App\Models\Connection;
use App\Services\BaseService;
use App\Models\Deploy;
use App\Models\Change;

use Auth;

/**
 * Class DependencyService: checks the generated changes and alters them when needed
 * @package App\Services
 */
class DependencyService extends BaseService
{
    /**
     * Check if all dependencies are selected.
     * @param $changes
     * @return bool Return true if dependencies are OK, otherwise false
     */
    public function check($changes) {
        $changes = Change::whereIn('id', $changes)->orderBy('sort', 'asc')->get();

        foreach($changes as $change) {
            if($change->dependency != null) {
                $dependent_on = json_decode($change->dependency, true);
                foreach($dependent_on as $dependent_on_id) {
                    if(!$changes->contains('id', $dependent_on_id)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }
    /**
     * Check the changes and alter some if needed
     * @param $deploy_id
     */
    public static function create($deploy_id) {
        $changes = Change::where('deploy_id', $deploy_id)->get();

        foreach($changes as $change) {
            // When a column is added as AUTO_INCREMENT, the corresponding primary index is always added and disabled in the selection
            if($change->type == 'column_added' && strpos($change->sql, 'AUTO_INCREMENT')) {
                // Disable the corresponding index
                $c = Change::where(['deploy_id' => $deploy_id, 'entity' => 'index', 'type' => 'index_added', 'name' => 'PRIMARY'])->first();
                $c->disable = 1;
                $c->save();
            }
            // When a column is altered as AUTO_INCREMENT, the corresponding primary index is always added and disabled in the selection
            else if($change->type == 'attribute_altered' && $change->name == 'auto_increment') {
                $parent = $change->parent()->first();
                $parent_id = $parent->parent_id;
                $c = Change::where(['deploy_id' => $deploy_id, 'entity' => 'index', 'type' => 'index_added', 'name' => 'PRIMARY', 'parent_id' => $parent_id])->first();
                if($c) {
                    $c->disable = 1;
                    $c->save();
                }
            }
            else if($change->type == 'option_altered' && $change->name == 'engine' && $change->entity == 'option') {
                $parent = $change->parent()->first();
                $parent_id = $parent->id;

                $c = Change::where(['deploy_id' => $deploy_id, 'entity' => 'option', 'type' => 'option_altered', 'name' => 'row_format', 'parent_id' => $parent_id])->first();
                if($c) {
                    $dependent_id = $c->id;
                    $change->dependency = json_encode([$dependent_id]);
                    $change->saveOrFail();
                }
            }
            else if($change->matches(['entity' => 'index', 'type' => 'index_added']) || $change->matches(['entity' => 'attribute_index', 'type' => 'attribute_altered'])) {
                $columns_affected = $change->additional_info['columns'];
                $parent_table = $change->getParentTable();
                $parent_id = $parent_table->id;

                foreach($columns_affected as $column_name) {
                    $c = Change::where(['deploy_id' => $deploy_id, 'entity' => 'column', 'type' => 'column_renamed', 'name' => $column_name, 'parent_id' => $parent_id])->first();
                   if($c) {
                        $change->addDependency($c->id);
                        $change->saveOrFail();
                    }
                }
            }
        }

    }
}