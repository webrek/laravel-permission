<?php

namespace Webrek\Permission\Tests\TestModels;

use Webrek\Permission\Traits\HasRoles;

class User extends UserWithoutHasRoles
{
    use HasRoles;
}
