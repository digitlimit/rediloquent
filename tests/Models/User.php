<?php

namespace Tests\Models;

use Digitlimit\Rediloquent\Model;

class User extends Model
{
    protected string $key = 'users';

    protected array $fillable = [
        'name',
        'email',
    ];
}
