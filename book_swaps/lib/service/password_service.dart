import 'dart:convert';
import 'package:http/http.dart' as http;
import '../api_connection/api_connection.dart';

class PasswordService {
  static Future<Map<String, dynamic>> changePassword({
    required int userId,
    required String currentPassword,
    required String newPassword,
  }) async {
    try {
      final response = await http.post(
        Uri.parse(API.changePassword),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'current_password': currentPassword,
          'new_password': newPassword,
        }),
      );

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': responseData['success'] ?? false,
          'message': responseData['message'] ?? 'Password changed successfully',
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to change password',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e',
      };
    }
  }
}