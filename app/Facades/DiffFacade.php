<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class DiffFacade
 * The Diff Facade
 * @package App\Facades
 */
class DiffFacade extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'diff';
    }

}