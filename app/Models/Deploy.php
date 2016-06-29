<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model as Eloquent;

/**
 * Class Deploy
 * @package App\Models
 */
class Deploy extends Eloquent
{
    /**
     * Get the changes of this deployment
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function changes() {
        return $this->hasMany('App\Models\Change');
    }
}
