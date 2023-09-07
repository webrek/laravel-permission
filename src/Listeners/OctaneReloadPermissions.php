<?php

namespace Webrek\Permission\Listeners;

use Webrek\Permission\PermissionRegistrar;

class OctaneReloadPermissions
{
    public function handle($event): void
    {
        $event->sandbox->make(PermissionRegistrar::class)->clearPermissionsCollection();
    }
}
