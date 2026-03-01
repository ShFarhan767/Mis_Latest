<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientStatusHistory extends Model
{
    protected $fillable = ['client_id','old_status','new_status','reason'];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
