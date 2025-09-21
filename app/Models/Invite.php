<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invite extends Model
{
    protected $fillable = [
        'name',
        'company',
        'number_of_invites',
        'contact',
        'email',
        'table_no',
        'qr_code',
        'scan_status',
    ];
}
