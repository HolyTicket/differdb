<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;

use App\Models\Connection;
use App\Models\Deploy;

use Illuminate\Support\Facades\Input;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

use Sync;
use Connect;
use Dependency;

/**
 * Class SyncController
 * Controller that handles the sync actions
 * @package App\Http\Controllers
 */
class SyncController extends Controller
{
    /**
     * Show the SQL queries needed to sync the changes
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function sql(Request $request) {
        // Get all posted data as an array
        $data = $request->all();

        // Get a list of change ID's
        $changes = array_values($data['change']);

        // Check if all dependencies are OK (otherwise incorrect SQL is generated)
        $dependency_check = Dependency::check($changes);

        // Get the SQL
        $sql = Sync::generateSql($changes);

        // Render view and variables
        return view('sync.sql', compact('sql', 'dependency_check'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function execute(Request $request) {
        // Get all posted data as an array
        $data = $request->all();

        // Get the original data posted (the ID's of the changes)
        $data['original_data'] = json_decode($data['original_data'], true);

        // Get a list of change ID's
        $changes = array_values($data['original_data']['change']);

        // Get the SQL
        $sql = Sync::generateSql($changes);

        // Get the destination connection or throw an exception
        $destination_connection = Connection::findOrFail($data['original_data']['database_two']);

        // Create an empty array if additional database list is blank
        if(empty($data['databases'])) {
            $data['databases'] = [];
        }

        // Execute the SQL queries at the destinations
        $results = Sync::executeMysql($sql, $destination_connection, $data['databases']);

        // Render the view
        return view('sync.results', compact('results'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function confirm(Request $request) {
        // Get all posted data as an array
        $data = $request->all();

        // Cast the connection_id to an array
        $destination_connection_id = (int) $data['database_two'];

        // Get the destination connection or throw exception
        $destination_connection = Connection::findOrFail($destination_connection_id);

        // Get the other databases for this connection
        $all_databases = Connect::getOtherDatabases($destination_connection);

        // Render the view
        return view('sync.confirm', compact('all_databases', 'destination_connection', 'data'));
    }

}