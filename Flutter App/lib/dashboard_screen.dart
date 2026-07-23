import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'boom_control_screen.dart';
import 'settings_screen.dart';
import 'visitor_screen.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({Key? key}) : super(key: key);

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  Map<String, dynamic> _user = {};
  List<Map<String, dynamic>> _permissions = [];
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadUserAndPermissions();
  }

  Future<void> _loadUserAndPermissions() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString('user');
    
    if (userJson != null) {
      final user = json.decode(userJson);
      setState(() {
        _user = user;
      });
      
      // Load stored permissions
      final permJson = prefs.getString('user_permissions') ?? '[]';
      final permissions = List<Map<String, dynamic>>.from(json.decode(permJson));
      
      print('DEBUG: User loaded: ${user['username']}');
      print('DEBUG: Permissions from storage: $permissions');
      
      setState(() {
        _permissions = permissions;
        _loading = false;
      });
    } else {
      if (mounted) {
        Navigator.pushReplacementNamed(context, '/login');
      }
    }
  }

  bool _hasPermission(String permissionName) {
    final has = _permissions.any((p) => p['name'] == permissionName);
    print('DEBUG: Checking permission "$permissionName": $has');
    print('DEBUG: Available permissions: ${_permissions.map((p) => p['name']).toList()}');
    return has;
  }

  Future<void> _logout() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove('user');
    await prefs.remove('user_permissions');
    if (mounted) {
      Navigator.pushReplacementNamed(context, '/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    if (_loading) {
      return const Scaffold(
        body: Center(child: CircularProgressIndicator()),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('Guardian Access'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: _logout,
            tooltip: 'Logout',
          ),
        ],
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Welcome, ${_user['username'] ?? 'User'}',
                style: const TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 32),
              const Text(
                'Menu',
                style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              // Boom Control
              if (_hasPermission('boom_control'))
                Column(
                  children: [
                    Card(
                      elevation: 2,
                      child: ListTile(
                        leading: const Icon(Icons.door_sliding, size: 32, color: Colors.blue),
                        title: const Text('Boom Control'),
                        subtitle: const Text('Control boom gates'),
                        trailing: const Icon(Icons.arrow_forward),
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (context) => const BoomControlScreen(),
                            ),
                          );
                        },
                      ),
                    ),
                    const SizedBox(height: 16),
                  ],
                ),
              // Settings
              if (_hasPermission('settings'))
                Card(
                  elevation: 2,
                  child: ListTile(
                    leading: const Icon(Icons.settings, size: 32, color: Colors.grey),
                    title: const Text('Settings'),
                    subtitle: const Text('Configure server and preferences'),
                    trailing: const Icon(Icons.arrow_forward),
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const SettingsScreen(),
                        ),
                      );
                    },
                  ),
                ),

              // Visitor Management
              if (_hasPermission('visitor_management'))
                Card(
                  elevation: 2,
                  child: ListTile(
                    leading: const Icon(Icons.group, size: 32, color: Colors.purple),
                    title: const Text('Visitor Management'),
                    subtitle: const Text('Add and manage visitors'),
                    trailing: const Icon(Icons.arrow_forward),
                    onTap: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (context) => const VisitorScreen(),
                        ),
                      );
                    },
                  ),
                ),
              if (_hasPermission('visitor_management')) const SizedBox(height: 16),

              // Debug: Show if no permissions
              if (_permissions.isEmpty)
                Container(
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.orange.shade100,
                    border: Border.all(color: Colors.orange),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    'No permissions assigned. Contact admin.\nRole ID: ${_user['role_id']}',
                    style: const TextStyle(color: Colors.orange),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}