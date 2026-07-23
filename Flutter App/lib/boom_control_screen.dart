import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';
import 'dart:convert';
import 'config.dart';

class BoomControlScreen extends StatefulWidget {
  const BoomControlScreen({Key? key}) : super(key: key);

  @override
  State<BoomControlScreen> createState() => _BoomControlScreenState();
}

class _BoomControlScreenState extends State<BoomControlScreen> {
  List<Map<String, dynamic>> _cameras = [];
  Map<int, String> _boomStates = {}; // Store boom states
  bool _loading = true;

  @override
  void initState() {
    super.initState();
    _loadCameras();
    _loadBoomStates(); // Load saved states
  }

  Future<void> _loadBoomStates() async {
    final prefs = await SharedPreferences.getInstance();
    final statesJson = prefs.getString('boom_states') ?? '{}';
    setState(() {
      _boomStates = Map<int, String>.from(json.decode(statesJson));
    });
  }

  Future<void> _saveBoomState(int cameraId, String state) async {
    final prefs = await SharedPreferences.getInstance();
    _boomStates[cameraId] = state;
    await prefs.setString('boom_states', json.encode(_boomStates));
  }

    Future<void> _loadCameras() async {
    final serverIp = await Config.getServerIp();
    final url = Uri.parse('$serverIp/api/boom/cameras?gate_trigger=1');  // ← Correct endpoint

    try {
      final response = await http.get(url).timeout(const Duration(seconds: 15));

      if (response.statusCode == 403) {
        _showError('You do not have permission to access Boom Control');
        setState(() => _loading = false);
        return;
      }

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _cameras = List<Map<String, dynamic>>.from(data['cameras']);
            _loading = false;
          });
          print('DEBUG: Loaded ${_cameras.length} boom cameras');
          print('DEBUG: Camera IDs: ${_cameras.map((c) => c['id']).toList()}');
        }
      }
    } catch (e) {
      _showError('Error: $e');
      setState(() => _loading = false);
    }
  }

  Future<void> _toggleBoom(dynamic cameraId) async {
  final camId = int.parse(cameraId.toString());
  final currentState = _boomStates[camId] ?? 'closed';
  final newState = currentState == 'closed' ? 'open' : 'closed';

  final serverIp = await Config.getServerIp();
  final url = Uri.parse('$serverIp/api/boom/trigger');

  try {
    final response = await http.post(
      url,
      body: {
        'camera_id': camId.toString(),
        'action': newState,
      },
    ).timeout(const Duration(seconds: 15));

    if (response.statusCode == 200) {
      final data = json.decode(response.body);
      if (data['success']) {
        await _saveBoomState(camId, newState);
        setState(() {
          _boomStates[camId] = newState;
        });
        _showSuccess('Boom ${newState.toUpperCase()}');
      } else {
        _showError(data['message'] ?? 'Failed to toggle boom');
      }
    } else {
      _showError('Server error: ${response.statusCode}');
    }
  } catch (e) {
    _showError('Error: $e');
  }
}

  void _showError(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.red),
    );
  }

  void _showSuccess(String message) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(message), backgroundColor: Colors.green),
    );
  }

 @override
Widget build(BuildContext context) {
  if (_loading) {
    return Scaffold(
      appBar: AppBar(title: const Text('Boom Control')),
      body: const Center(child: CircularProgressIndicator()),
    );
  }

  if (_cameras.isEmpty) {
    return Scaffold(
      appBar: AppBar(title: const Text('Boom Control')),
      body: const Center(
        child: Text('No cameras found'),
      ),
    );
  }

    return Scaffold(
      appBar: AppBar(title: const Text('Boom Control')),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: GridView.builder(
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2,
            childAspectRatio: 1.0,
            mainAxisSpacing: 16,
            crossAxisSpacing: 16,
          ),
          itemCount: _cameras.length,
          itemBuilder: (context, index) {
            final cam = _cameras[index];
            final camId = int.parse(cam['id'].toString()); // Convert to int
            final state = _boomStates[camId] ?? 'closed'; // Use camId not cam['id']
            final isOpen = state == 'open';

            return Card(
              elevation: 4,
              child: InkWell(
                onTap: () => _toggleBoom(camId), // Use camId
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Text(
                      cam['name'],
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.bold,
                      ),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 16),
                    Icon(
                      isOpen ? Icons.check_circle : Icons.cancel,
                      size: 48,
                      color: isOpen ? Colors.green : Colors.red,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      isOpen ? 'OPEN' : 'CLOSED',
                      style: TextStyle(
                        fontSize: 14,
                        fontWeight: FontWeight.bold,
                        color: isOpen ? Colors.green : Colors.red,
                      ),
                    ),
                  ],
                ),
              ),
            );
          },
        ),
      ),
    );
  }
}