<?php

namespace backend\components;

use mdm\admin\components\AccessControl as BaseAccessControl;

class ApiAwareAccessControl extends BaseAccessControl
{
    protected function isActive($action)
    {
        $uniqueId = $action->getUniqueId();

        foreach ($this->allowActions as $route) {
            if (substr($route, -1) === '*') {
                $route = rtrim($route, '*');
                if ($route === '' || strpos($uniqueId, $route) === 0) {
                    return false;
                }
            } else {
                if ($uniqueId === $route) {
                    return false;
                }
            }
        }

        return true;
    }
}
