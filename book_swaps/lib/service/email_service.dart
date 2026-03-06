import 'dart:convert';
import 'package:http/http.dart' as http;
import '../api_connection/api_connection.dart';

class EmailService {
  static Future<Map<String, dynamic>> updateEmail({
    required int userId,
    required String newEmail,
  }) async {
    try {
      final response = await http.post(
        Uri.parse(API.updateEmail),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'new_email': newEmail,
        }),
      );

      // print('Update Email Response Status: ${response.statusCode}');
      // print('Update Email Response Body: ${response.body}');

      if (response.body.trim().isEmpty) {
        return {
          'success': false,
          'message': 'Empty response from server',
        };
      }

      final responseData = jsonDecode(response.body);

      bool success = responseData['success'] == true || responseData['success'] == 'true';

      if (response.statusCode == 200) {
        return {
          'success': success,
          'message': responseData['message'] ?? 'OTP sent successfully',
          'email': responseData['email'] ?? newEmail,
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to send OTP. Status: ${response.statusCode}',
        };
      }
    } catch (e) {
      print('Update Email Error: $e');
      return {
        'success': false,
        'message': 'Network error: $e',
      };
    }
  }
  static Future<Map<String, dynamic>> verifyEmailOtp({
    required int userId,
    required String otp,
  }) async {
    try {
      final response = await http.post(
        Uri.parse(API.verifyEmailOtp),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'otp': otp,
        }),
      );

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': responseData['success'] ?? false,
          'message': responseData['message'] ?? 'Email verified successfully',
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to verify OTP',
        };
      }
    } catch (e) {
      return {
        'success': false,
        'message': 'Network error: $e',
      };
    }
  }

  static Future<Map<String, dynamic>> resendEmailOtp({
    required int userId,
    required String email,
  }) async {
    try {
      final response = await http.post(
        Uri.parse(API.resendEmailOtp),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({
          'user_id': userId,
          'email': email, // Include email
        }),
      );

      final responseData = jsonDecode(response.body);

      if (response.statusCode == 200) {
        return {
          'success': responseData['success'] ?? false,
          'message': responseData['message'] ?? 'OTP resent successfully',
        };
      } else {
        return {
          'success': false,
          'message': responseData['message'] ?? 'Failed to resend OTP',
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