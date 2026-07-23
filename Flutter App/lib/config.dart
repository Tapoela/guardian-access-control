import 'package:shared_preferences/shared_preferences.dart';

class Config {
  static const String defaultServerIp = 'http://192.168.1.2'; 

  static Future<String> getServerIp() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString('server_ip') ?? defaultServerIp;
  }

  static Future<void> setServerIp(String ip) async {
    final prefs = await SharedPreferences.getInstance();
    if (!ip.startsWith('http://') && !ip.startsWith('https://')) {
      ip = 'http://$ip';
    }
    if (ip.endsWith('/')) {
      ip = ip.substring(0, ip.length - 1);
    }
    await prefs.setString('server_ip', ip);
  }
}