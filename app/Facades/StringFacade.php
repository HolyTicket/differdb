<?php
namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class StringFacade
 * Exotic string functions
 * @package App\Facades
 */
class StringFacade extends Facade {

    /**
     * @return string
     */
    protected static function getFacadeAccessor() {
        return 'stringHelper';
    }
}