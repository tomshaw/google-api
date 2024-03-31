<?php

namespace TomShaw\GoogleApi\Models;

use Illuminate\Database\Eloquent\Model;

class GoogleToken extends Model
{
    protected $table = 'google_tokens';

    public $timestamps = false;

    protected $fillable = ['user_id', 'access_token', 'refresh_token', 'expires_in', 'scope', 'token_type', 'created'];
}
