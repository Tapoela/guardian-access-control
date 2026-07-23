<?php

/**
 * Check if the currently logged-in user has a specific permission.
 * Uses the user_roles and role_permissions tables dynamically.
 *
 * @param string $permission  The permission name e.g. 'user_management'
 * @return bool
 */
function hasPermission(string $permission): bool
{
    $session = session();

    // Administrator (role_id = 1) always has full access
    if ($session->get('role_id') == 1) {
        return true;
    }

    if (!$session->get('isLoggedIn')) {
        return false;
    }

    $userId = $session->get('user_id');
    if (!$userId) {
        return false;
    }

    $db = \Config\Database::connect();
    $result = $db->table('user_roles')
        ->select('permissions.name')
        ->join('role_permissions', 'role_permissions.role_id = user_roles.role_id')
        ->join('permissions', 'permissions.id = role_permissions.permission_id')
        ->where('user_roles.user_id', $userId)
        ->where('permissions.name', $permission)
        ->get()->getRowArray();

    return !empty($result);
}
