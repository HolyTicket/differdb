<?php

namespace App\Http\Controllers;

use App\Models\Virtual\Database;
use Illuminate\Support\Facades\Auth;
use App\Models\Connection;
use Illuminate\Http\Request;

use Diff;
use Dependency;

/**
 * Class DiffController
 *
 * The controller for diffing actions, like selecting connections and starting a diff
 *
 * @package App\Http\Controllers
 */
class DiffController extends Controller
{
    /**
     *
     * Select two connections to compare
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        // Get all saved connections for this user
        $databases = Connection::where('user_id', Auth::id())->lists('name', 'id');

        // Render view and variables
        return view('diff.select_database', compact('databases'));
    }

    /**
     *
     * Perform the diff and show differences
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function load(Request $request)
    {
        // Validate database_one and database_two. Should be filled in, an integer and unique
        $this->validate($request, [
            'database_one' => 'required|integer',
            'database_two' => 'required|integer|different:database_one',
        ]);

        // Get all posted data as an array
        $data = $request->all();

        // Get connection one and two or throw an exception
        $connection_one = Connection::findOrFail($data['database_one']);
        $connection_two = Connection::findOrFail($data['database_two']);

        // Parse the source database. Returns a database object.
        $db_one = new Database($connection_one, 'db_one');
        // Parse the destination database. Returns a database object.
        $db_two = new Database($connection_two, 'db_two');

        // Diff the two databases
        $deployment = $db_one->diff($db_two);

        // Perform a dependency check (e.g. removing indexes being created before column exists)
        Dependency::create($deployment->id);

        // Get parent ID
        if($parent_id = $deployment->changes()->where('parent_id', null)->count()) {
            $parent_id = $deployment->changes()->where('parent_id', null)->first()->id;
        }

        // Create an array which stores the changes by the entity type (table, column, etc.). Used for the table structure tables
        $changes_by_entity = [];

        // Loop through all changes
        foreach($deployment->changes()->get() as $change) {
            // Save changes
            if($change->entity == 'table') {
                $changes_by_entity['table'][$change->name] = $change->type;
            } else if($change->entity == 'column') {
                $changes_by_entity['column'][$change->name][$change->parent->name] = $change->type;
            }
        }

        // Decide which view to render. If there are no changes, show a positive message
        $view = $deployment->changes()->count() ? 'diff.diff' : 'diff.same';

        // Render view and variables
        return view($view, compact('deployment',  'changes_by_entity', 'db_one', 'deployment_id', 'db_two', 'connection_one', 'connection_two', 'parent_id'));
    }
}