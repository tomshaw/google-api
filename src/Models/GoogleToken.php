<?php

namespace TomShaw\GoogleApi\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    public $timestamps = false;

    protected $fillable = ['access_token', 'refresh_token', 'expires_in', 'scope', 'token_type', 'created'];
}
