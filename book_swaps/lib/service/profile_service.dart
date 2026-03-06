import 'dart:convert';
import 'package:book_swaps/user_model/user_model.dart';
import 'package:http/http.dart' as http;
import 'package:image_picker/image_picker.dart';
import '../api_connection/api_connection.dart';

class ProfileService {
  // Get user profile with proper error handling
  static Future<Map<String, dynamic>> getProfile(int userId) async {
    try {
      final response = await http.post(
        Uri.parse(API.getProfile),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode({'user_id': userId}),
      );

      if (response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        if (jsonResponse['success'] == true) {
          return {
            'success': true,
            'name': jsonResponse['userData']['name'] ?? '',
            'email': jsonResponse['userData']['email'] ?? '',
            'student_id': jsonResponse['userData']['student_id'] ?? '',
            'department': jsonResponse['userData']['department'] ?? '',
            'current_location': jsonResponse['userData']['current_location'] ?? '',
            'permanent_location': jsonResponse['userData']['permanent_location'] ?? '',
            'bio': jsonResponse['userData']['bio'] ?? '',
            'profile_image': jsonResponse['userData']['profile_image'] ?? '',
          };
        } else {
          return {'success': false, 'message': jsonResponse['message'] ?? 'Failed to load profile'};
        }
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Update profile information
  static Future<Map<String, dynamic>> updateProfile(User user) async {
    try {
      final url = Uri.parse(API.updateProfile);

      // Prepare the data for update
      final Map<String, dynamic> updateData = {
        'user_id': user.id,
        'name': user.name,
        'bio': user.bio ?? '',
        'student_id': user.studentId ?? '',
        'department': user.department ?? '',
        'current_location': user.currentLocation ?? '',
        'permanent_location': user.permanentLocation ?? '',
      };

      final response = await http.post(
        url,
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(updateData),
      );

      if (response.statusCode == 200) {
        final jsonResponse = jsonDecode(response.body);
        return jsonResponse;
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      print('Update Error: $e');
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  // Upload profile image
  static Future<Map<String, dynamic>> uploadProfileImage(int userId, XFile imageFile) async {
    try {
      var request = http.MultipartRequest('POST', Uri.parse(API.uploadProfileImage));

      request.fields['user_id'] = userId.toString();
      request.files.add(await http.MultipartFile.fromPath(
        'profile_image',
        imageFile.path,
      ));

      var response = await request.send();
      var responseData = await response.stream.bytesToString();
      var jsonResponse = jsonDecode(responseData);

      return jsonResponse;
    } catch (e) {
      print('Upload Error: $e');
      return {'success': false, 'message': 'Upload error: $e'};
    }
  }

  // Combined method to update profile with image
  static Future<Map<String, dynamic>> updateProfileWithImage({
    required User user,
    required XFile? imageFile,
  }) async {
    try {
      // First update profile data
      final profileResult = await updateProfile(user);

      if (!profileResult['success']) {
        return profileResult;
      }

      // Then upload image if provided
      if (imageFile != null) {
        final imageResult = await uploadProfileImage(user.id, imageFile);
        if (!imageResult['success']) {
          return imageResult;
        }

        // Return success with image path if available
        return {
          'success': true,
          'message': 'Profile and image updated successfully',
          'image_path': imageResult['image_path'] ?? '',
        };
      }

      return {'success': true, 'message': 'Profile updated successfully'};
    } catch (e) {
      return {'success': false, 'message': 'Error updating profile: $e'};
    }
  }
}