<!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="/dashboard" class="brand-link">
            <span class="brand-text font-weight-light">Guardian Control</span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">

                    <!-- Dashboard (always accessible) -->
                    <li class="nav-item">
                        <a href="/dashboard" class="nav-link">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <!-- User Management -->
                    <li class="nav-item">
                        <?php if (hasPermission('user_management')): ?>
                            <a href="/admin/users" class="nav-link">
                                <i class="nav-icon fas fa-users"></i>
                                <p>User Management</p>
                            </a>
                        <?php else: ?>
                            <a href="#" class="nav-link nav-link-locked" title="You do not have access to User Management" data-toggle="tooltip" data-placement="right">
                                <i class="nav-icon fas fa-users"></i>
                                <p>User Management <i class="fas fa-lock lock-icon"></i></p>
                            </a>
                        <?php endif; ?>
                    </li>

                    <!-- Settings -->
                    <li class="nav-item has-treeview">
                        <?php if (hasPermission('settings')): ?>
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Settings <i class="right fas fa-angle-left"></i></p>
                            </a>

                            <ul class="nav nav-treeview">

                                <!-- Roles -->
                                <li class="nav-item">
                                    <?php if (hasPermission('role_management')): ?>
                                        <a href="/roles" class="nav-link">
                                            <i class="nav-icon fas fa-user-shield"></i>
                                            <p>Roles</p>
                                        </a>
                                    <?php else: ?>
                                        <a href="#" class="nav-link nav-link-locked" title="You do not have access to Role Management" data-toggle="tooltip" data-placement="right">
                                            <i class="nav-icon fas fa-user-shield"></i>
                                            <p>Roles <i class="fas fa-lock lock-icon"></i></p>
                                        </a>
                                    <?php endif; ?>
                                </li>

                                <li class="nav-item">
                                    <a href="/settings/permissions" class="nav-link">
                                        <i class="nav-icon fas fa-toggle-on"></i>
                                        <p>Role Permissions</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/settings/managePermissions" class="nav-link">
                                        <i class="nav-icon fas fa-key"></i>
                                        <p>Manage Permissions</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/admin/appsettings" class="nav-link">
                                        <i class="nav-icon fas fa-cogs"></i>
                                        <p>App Settings</p>
                                    </a>
                                </li>
                            </ul>
                        <?php else: ?>
                            <a href="#" class="nav-link nav-link-locked" title="You do not have access to Settings" data-toggle="tooltip" data-placement="right">
                                <i class="nav-icon fas fa-cogs"></i>
                                <p>Settings <i class="fas fa-lock lock-icon"></i></p>
                            </a>
                        <?php endif; ?>
                    </li>

                    <!-- Access Control -->
                    <li class="nav-item has-treeview">
                        <?php if (hasPermission('access_control')): ?>
                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-shield-alt"></i>
                                <p>Access Control <i class="right fas fa-angle-left"></i></p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="/access/members" class="nav-link">
                                        <i class="nav-icon fas fa-users"></i>
                                        <p>Members</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/whitelist" class="nav-link">
                                        <i class="nav-icon fas fa-check-circle text-success"></i>
                                        <p>Whitelist</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/blacklist" class="nav-link">
                                        <i class="nav-icon fas fa-ban text-danger"></i>
                                        <p>Blacklist</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/log" class="nav-link">
                                        <i class="nav-icon fas fa-history"></i>
                                        <p>Access Log</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/cameras" class="nav-link">
                                        <i class="nav-icon fas fa-video"></i>
                                        <p>Cameras</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/cameras/events" class="nav-link">
                                        <i class="nav-icon fas fa-car-alt"></i>
                                        <p>ANPR Events</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="/access/boomcontrol" class="nav-link">
                                        <i class="nav-icon fas fa-car-alt"></i>
                                        <p>Boom Control</p>
                                    </a>
                                </li>
                            </ul>
                        <?php else: ?>
                            <a href="#" class="nav-link nav-link-locked" title="You do not have access to Access Control" data-toggle="tooltip" data-placement="right">
                                <i class="nav-icon fas fa-shield-alt"></i>
                                <p>Access Control <i class="fas fa-lock lock-icon"></i></p>
                            </a>
                        <?php endif; ?>
                    </li>

                    <li class="nav-item has-treeview">
                        <a href="#" class="nav-link">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>
                                Reports
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            <li class="nav-item">
                                <a href="/report/overview" class="nav-link">
                                    <i class="nav-icon fas fa-list-alt"></i>
                                    <p>Overview Report</p>
                                </a>
                            </li>
                            <!-- Add more report links here as needed -->
                        </ul>
                    </li>
                    
                    <!-- Hardware -->
                    <li class="nav-item has-treeview">

                        <?php if (hasPermission('hardware')): ?>

                            <a href="#" class="nav-link">
                                <i class="nav-icon fas fa-microchip"></i>
                                <p>
                                    Hardware
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>

                            <ul class="nav nav-treeview">

                                <li class="nav-item">
                                    <a href="/hardware" class="nav-link">
                                        <i class="fas fa-tachometer-alt nav-icon"></i>
                                        <p>Dashboard</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/hardware/devices" class="nav-link">
                                        <i class="fas fa-server nav-icon"></i>
                                        <p>Devices</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/hardware/functions" class="nav-link">
                                        <i class="fas fa-project-diagram nav-icon"></i>
                                        <p>Functions</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/hardware/mapping" class="nav-link">
                                        <i class="fas fa-random nav-icon"></i>
                                        <p>Channel Mapping</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/hardware/diagnostics" class="nav-link">
                                        <i class="fas fa-stethoscope nav-icon"></i>
                                        <p>Diagnostics</p>
                                    </a>
                                </li>

                                <li class="nav-item">
                                    <a href="/hardware/events" class="nav-link">
                                        <i class="fas fa-history nav-icon"></i>
                                        <p>Event Log</p>
                                    </a>
                                </li>

                            </ul>

                        <?php else: ?>

                            <a href="#" class="nav-link nav-link-locked"
                            title="You do not have access to Hardware"
                            data-toggle="tooltip">

                                <i class="nav-icon fas fa-microchip"></i>

                                <p>
                                    Hardware
                                    <i class="fas fa-lock lock-icon"></i>
                                </p>

                            </a>

                        <?php endif; ?>

                    </li>
                    
                    <!-- Add more menu items here -->
                </ul>
            </nav>
        </div>
    </aside>