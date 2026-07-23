import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'package:local_auth/local_auth.dart';
import 'dart:convert';
import 'config.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({Key? key}) : super(key: key);

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey = GlobalKey<FormState>();
  final _ipController = TextEditingController();
  String _username = '';
  String _password = '';
  bool _obscureText = true;
  bool _loading = false;
  final LocalAuthentication _localAuth = LocalAuthentication();
  bool _isBiometricAvailable = false;

  @override
  void initState() {
    super.initState();
    _setSecureKey();
    _checkBiometricAvailability();
    _loadServerIp();
  }

  Future<void> _loadServerIp() async {
    final serverIp = await Config.getServerIp();
    setState(() {
      _ipController.text = serverIp;
    });
  }

  Future<void> _setSecureKey() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('secure_key', 'GuardianControlWp@140586');
    print('DEBUG: Secure key set to GuardianControlWp@140586');
  }

  Future<void> _checkBiometricAvailability() async {
    try {
      bool canCheckBiometrics = await _localAuth.canCheckBiometrics;
      setState(() {
        _isBiometricAvailable = canCheckBiometrics;
      });
      
      if (_isBiometricAvailable) {
        List<BiometricType> availableBiometrics = 
            await _localAuth.getAvailableBiometrics();
        print('DEBUG: Available biometrics: $availableBiometrics');
      }
    } catch (e) {
      print('DEBUG: Biometric check error: $e');
    }
  }

  Future<void> _saveServerIp() async {
    if (_ipController.text.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('IP address cannot be empty')),
      );
      return;
    }

    await Config.setServerIp(_ipController.text);
    if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Server IP updated successfully')),
      );
      Navigator.pop(context);
    }
  }

    void _showIpDialog() {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Server Settings'),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _ipController,
              decoration: const InputDecoration(
                labelText: 'Server IP Address',
                hintText: 'e.g., http://192.168.1.2',
                border: OutlineInputBorder(),
                prefixIcon: Icon(Icons.language),
              ),
            ),
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                ElevatedButton(
                  onPressed: () {
                    _ipController.text = 'http://192.168.1.2';
                  },
                  child: const Text('Local'),
                ),
                ElevatedButton(
                  onPressed: () {
                    _ipController.text = 'http://guardianaccess.ddns.net';
                  },
                  child: const Text('Remote (No-IP)'),
                ),
              ],
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: _saveServerIp,
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  Future<void> _authenticateWithBiometrics() async {
    try {
      bool authenticated = await _localAuth.authenticate(
        localizedReason: 'Authenticate to access Guardian Control',
        options: const AuthenticationOptions(
          stickyAuth: true,
          biometricOnly: true,
        ),
      );

      if (authenticated) {
        print('DEBUG: Biometric authentication successful');
        await _autoBiometricLogin();
      }
    } catch (e) {
      print('DEBUG: Biometric error: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Biometric error: $e')),
      );
    }
  }

  Future<void> _autoBiometricLogin() async {
    final prefs = await SharedPreferences.getInstance();
    final storedEmail = prefs.getString('stored_email');
    final storedPassword = prefs.getString('stored_password');

    print('DEBUG: Checking stored credentials...');
    print('DEBUG: Stored email: $storedEmail');
    print('DEBUG: Stored password exists: ${storedPassword != null}');

    if (storedEmail != null && storedPassword != null) {
      _username = storedEmail;
      _password = storedPassword;
      print('DEBUG: Auto-logging in with stored credentials');
      await _login(isBiometric: true);
    } else {
      print('DEBUG: No stored credentials found');
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('No stored credentials found. Please login first.')),
      );
    }
  }

  Future<void> _login({bool isBiometric = false}) async {
    if (!isBiometric && !(_formKey.currentState?.validate() ?? false)) return;
    
    if (!isBiometric) {
      _formKey.currentState?.save();
    }
    
    setState(() => _loading = true);

    final prefs = await SharedPreferences.getInstance();
    final secureKey = prefs.getString('secure_key') ?? '';
    final serverIp = _ipController.text;

    final url = Uri.parse('$serverIp/api/login');
    
    print('DEBUG: Attempting login to $url');
    print('DEBUG: Username: $_username');

    try {
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'username': _username,
          'password': _password,
          'secure_key': secureKey,
        },
      ).timeout(const Duration(seconds: 15), onTimeout: () {
        throw Exception('Connection timeout - server not responding');
      });

      print('DEBUG: Response status: ${response.statusCode}');
      print('DEBUG: Response body: ${response.body}');

      setState(() => _loading = false);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          await prefs.setString('stored_email', _username);
          await prefs.setString('stored_password', _password);
          await prefs.setString('user', json.encode(data['user']));
          
          // Fetch permissions
          final userId = int.parse(data['user']['id'].toString());
          await _fetchUserPermissions(userId);
          
          if (!mounted) return;
          Navigator.pushReplacementNamed(context, '/dashboard');
        } else {
          _showError(data['message'] ?? 'Login failed');
        }
      } else {
        _showError('Server error: ${response.statusCode}');
      }
    } catch (e) {
      setState(() => _loading = false);
      print('DEBUG: Error: $e');
      _showError('Network error: $e');
    }
  }

   Future<void> _fetchUserPermissions(int userId) async {
    final serverIp = _ipController.text;
    final url = Uri.parse('$serverIp/api/permission/getUserPermissions');

    try {
      print('DEBUG: Fetching permissions from: $url with userId: $userId');
      
      final response = await http.post(
        url,
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',  // ← ADD THIS
        },
        body: {'user_id': userId.toString()},
      ).timeout(const Duration(seconds: 10));

      print('DEBUG: Permission response status: ${response.statusCode}');
      print('DEBUG: Permission response body: ${response.body}');

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          final prefs = await SharedPreferences.getInstance();
          await prefs.setString(
            'user_permissions',
            json.encode(data['permissions'] ?? []),
          );
          print('DEBUG: Permissions saved: ${data['permissions']}');
        } else {
          print('DEBUG: Permission fetch failed: ${data['message']}');
        }
      } else {
        print('DEBUG: Permission API error: ${response.statusCode}');
      }
    } catch (e) {
      print('DEBUG: Error fetching permissions: $e');
    }
  }

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Stack(
        fit: StackFit.expand,
        children: [
          Image.asset(
            'assets/images/GC.jpeg',
            fit: BoxFit.cover,
            alignment: Alignment.center,
          ),
          // Settings button top-right
          Positioned(
            top: 40,
            right: 16,
            child: FloatingActionButton.small(
              onPressed: _showIpDialog,
              tooltip: 'Server Settings',
              child: const Icon(Icons.settings),
            ),
          ),
          Center(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(24.0),
              child: Form(
                key: _formKey,
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    TextFormField(
                      decoration: const InputDecoration(
                        labelText: 'Email',
                        border: OutlineInputBorder(),
                        filled: true,
                        fillColor: Colors.white70,
                      ),
                      onSaved: (value) => _username = value ?? '',
                      validator: (value) =>
                          value == null || value.isEmpty ? 'Enter email' : null,
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      decoration: InputDecoration(
                        labelText: 'Password',
                        border: const OutlineInputBorder(),
                        filled: true,
                        fillColor: Colors.white70,
                        suffixIcon: IconButton(
                          icon: Icon(
                            _obscureText ? Icons.visibility : Icons.visibility_off,
                          ),
                          onPressed: () =>
                              setState(() => _obscureText = !_obscureText),
                        ),
                      ),
                      obscureText: _obscureText,
                      onSaved: (value) => _password = value ?? '',
                      validator: (value) =>
                          value == null || value.isEmpty ? 'Enter password' : null,
                    ),
                    const SizedBox(height: 24),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: _loading ? null : _login,
                        child: _loading
                            ? const SizedBox(
                                width: 24,
                                height: 24,
                                child: CircularProgressIndicator(strokeWidth: 2),
                              )
                            : const Text('Login'),
                      ),
                    ),
                    if (_isBiometricAvailable) ...[
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton.icon(
                          onPressed: _loading ? null : _authenticateWithBiometrics,
                          icon: const Icon(Icons.fingerprint),
                          label: const Text('Biometric Login'),
                          style: ElevatedButton.styleFrom(
                            backgroundColor: Colors.green,
                          ),
                        ),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _ipController.dispose();
    super.dispose();
  }
}