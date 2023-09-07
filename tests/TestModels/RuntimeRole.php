<?php

namespace Webrek\Permission\Tests\TestModels;

class RuntimeRole extends \Spatie\Permission\Models\Role
{
    protected $visible = [
        'id',
        'name',
    ];
}
