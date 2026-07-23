import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'config.dart';

class VisitorScreen extends StatefulWidget {
  const VisitorScreen({Key? key}) : super(key: key);

  @override
  State<VisitorScreen> createState() => _VisitorScreenState();
}

class _VisitorScreenState extends State<VisitorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _unitController = TextEditingController();
  
  DateTime? _selectedDate;
  TimeOfDay? _selectedTime;
  int _durationHours = 1;
  bool _loading = false;
  List<Map<String, dynamic>> _visitors = [];
  Map<String, dynamic> _user = {};

  @override
  void initState() {
    super.initState();
    _loadUser();
    _loadVisitors();
  }

  Future<void> _loadUser() async {
    final prefs = await SharedPreferences.getInstance();
    final userJson = prefs.getString('user');
    if (userJson != null) {
      setState(() {
        _user = json.decode(userJson);
      });
    }
  }

  Future<void> _loadVisitors() async {
    final serverIp = await Config.getServerIp();
    final url = Uri.parse('$serverIp/api/visitor/list');

    try {
      final response = await http.post(
        url,
        body: {'user_id': _user['id'].toString()},
      ).timeout(const Duration(seconds: 10));

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          setState(() {
            _visitors = List<Map<String, dynamic>>.from(data['visitors'] ?? []);
          });
        }
      }
    } catch (e) {
      print('DEBUG: Error loading visitors: $e');
    }
  }

  Future<void> _selectDate() async {
    final picked = await showDatePicker(
      context: context,
      initialDate: DateTime.now(),
      firstDate: DateTime.now(),
      lastDate: DateTime.now().add(const Duration(days: 365)),
    );
    if (picked != null) {
      setState(() => _selectedDate = picked);
    }
  }

  Future<void> _selectTime() async {
    final picked = await showTimePicker(
      context: context,
      initialTime: TimeOfDay.now(),
    );
    if (picked != null) {
      setState(() => _selectedTime = picked);
    }
  }

  Future<void> _addVisitor() async {
    if (!_formKey.currentState!.validate()) return;
    if (_selectedDate == null || _selectedTime == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please select check-in date and time')),
      );
      return;
    }

    setState(() => _loading = true);

    final checkInDt = DateTime(
      _selectedDate!.year,
      _selectedDate!.month,
      _selectedDate!.day,
      _selectedTime!.hour,
      _selectedTime!.minute,
    );

    final serverIp = await Config.getServerIp();
    final url = Uri.parse('$serverIp/api/visitor/add');

    try {
      final response = await http.post(
        url,
        body: {
          'user_id': _user['id'].toString(),
          'name': _nameController.text,
          'email': _emailController.text,
          'phone': _phoneController.text,
          'unit_number': _unitController.text,
          'check_in': checkInDt.toIso8601String(),
          'duration_hours': _durationHours.toString(),
        },
      ).timeout(const Duration(seconds: 10));

      setState(() => _loading = false);

      if (response.statusCode == 200) {
        final data = json.decode(response.body);
        if (data['success']) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Visitor added successfully!')),
          );
          _formKey.currentState!.reset();
          _nameController.clear();
          _emailController.clear();
          _phoneController.clear();
          _unitController.clear();
          setState(() {
            _selectedDate = null;
            _selectedTime = null;
            _durationHours = 1;
          });
          _loadVisitors();
        } else {
          ScaffoldMessenger.of(context).showSnackBar(
            SnackBar(content: Text('Error: ${data['message']}')),
          );
        }
      } else {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Server error: ${response.statusCode}')),
        );
      }
    } catch (e) {
      setState(() => _loading = false);
      print('DEBUG: Error adding visitor: $e');
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text('Error: $e')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('Visitor Management'),
      ),
      body: SingleChildScrollView(
        child: Padding(
          padding: const EdgeInsets.all(16.0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Add Visitor Form
              Card(
                elevation: 2,
                child: Padding(
                  padding: const EdgeInsets.all(16.0),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Add New Visitor',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                        const SizedBox(height: 16),
                        TextFormField(
                          controller: _nameController,
                          decoration: const InputDecoration(
                            labelText: 'Visitor Name',
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.person),
                          ),
                          validator: (value) =>
                              value == null || value.isEmpty ? 'Enter name' : null,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _emailController,
                          decoration: const InputDecoration(
                            labelText: 'Email',
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.email),
                          ),
                          keyboardType: TextInputType.emailAddress,
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _phoneController,
                          decoration: const InputDecoration(
                            labelText: 'Phone',
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.phone),
                          ),
                        ),
                        const SizedBox(height: 12),
                        TextFormField(
                          controller: _unitController,
                          decoration: const InputDecoration(
                            labelText: 'Unit Number',
                            border: OutlineInputBorder(),
                            prefixIcon: Icon(Icons.home),
                          ),
                        ),
                        const SizedBox(height: 12),
                        // Date and Time Selection
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton.icon(
                                icon: const Icon(Icons.calendar_today),
                                label: Text(
                                  _selectedDate == null
                                      ? 'Select Date'
                                      : '${_selectedDate!.day}/${_selectedDate!.month}/${_selectedDate!.year}',
                                ),
                                onPressed: _selectDate,
                              ),
                            ),
                            const SizedBox(width: 12),
                            Expanded(
                              child: ElevatedButton.icon(
                                icon: const Icon(Icons.access_time),
                                label: Text(
                                  _selectedTime == null
                                      ? 'Select Time'
                                      : '${_selectedTime!.hour}:${_selectedTime!.minute.toString().padLeft(2, '0')}',
                                ),
                                onPressed: _selectTime,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 12),
                        // Duration Selection
                        Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              'Duration: $_durationHours hour${_durationHours > 1 ? 's' : ''}',
                              style: const TextStyle(fontWeight: FontWeight.bold),
                            ),
                            Slider(
                              value: _durationHours.toDouble(),
                              min: 1,
                              max: 24,
                              divisions: 23,
                              label: '$_durationHours hours',
                              onChanged: (value) {
                                setState(() => _durationHours = value.toInt());
                              },
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: _loading ? null : _addVisitor,
                            child: _loading
                                ? const SizedBox(
                                    height: 20,
                                    width: 20,
                                    child: CircularProgressIndicator(strokeWidth: 2),
                                  )
                                : const Text('Add Visitor'),
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              // Active Visitors List
              const Text(
                'Active Visitors',
                style: TextStyle(
                  fontSize: 18,
                  fontWeight: FontWeight.bold,
                ),
              ),
              const SizedBox(height: 12),
              if (_visitors.isEmpty)
                const Center(
                  child: Text('No active visitors'),
                )
              else
                ListView.builder(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: _visitors.length,
                  itemBuilder: (context, index) {
                    final visitor = _visitors[index];
                    return Card(
                      margin: const EdgeInsets.only(bottom: 12),
                      child: ListTile(
                        leading: const Icon(Icons.person_outline),
                        title: Text(visitor['name'] ?? 'Unknown'),
                        subtitle: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const SizedBox(height: 4),
                            Text('Unit: ${visitor['unit_number'] ?? 'N/A'}'),
                            Text('Duration: ${visitor['duration_hours']} hour(s)'),
                            Text('Check-in: ${visitor['check_in'] ?? 'N/A'}'),
                            Text(
                              'Added by: ${visitor['added_by_username'] ?? 'Unknown'}',
                              style: const TextStyle(
                                fontStyle: FontStyle.italic,
                                color: Colors.grey,
                              ),
                            ),
                          ],
                        ),
                        isThreeLine: true,
                      ),
                    );
                  },
                )
            ],
          ),
        ),
      ),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _unitController.dispose();
    super.dispose();
  }
}