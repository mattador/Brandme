<?php
namespace Frontend\Services;

/**
 * Implements extremely basic ACL privileges
 *
 * Class SimpleAcl
 * @package Frontend\Services
 */
class SimpleAcl
{

    /**
     * Asserts that namespace == role
     *
     * @param $namespace
     * @param $role
     * @return bool
     */
    public static function assertAccessPrivilege($namespace, $role)
    {
        $namespace = explode('\\', $namespace);
        $namespace = array_filter($namespace, 'strlen');
        foreach ($namespace as $ns) {
            if (strtolower($ns) == $role) {
                return true;
            }
        }
        /**
         * Common controllers shared between account types
         */
        if (strpos(implode('\\', $namespace), 'Frontend\Controllers\Account\Common') === 0) {
            return true;
        }
        return false;
    }
}