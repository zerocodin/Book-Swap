import 'dart:convert';
import 'package:http/http.dart' as http;
import '../api_connection/api_connection.dart';

class PostService {
  static Future<Map<String, dynamic>> createPost(Map<String, dynamic> postData) async {
    try {
      final response = await http.post(
        Uri.parse(API.createPost),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(postData),
      );
      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      print('Post creation error: $e');
      return {'success': false, 'message': 'Network error: $e'};
    }
  }

  static Future<Map<String, dynamic>> uploadPostImage(int postId, String imagePath) async {
    try {
      var request = http.MultipartRequest('POST', Uri.parse(API.uploadPostImage));
      request.fields['post_id'] = postId.toString();
      request.files.add(await http.MultipartFile.fromPath('image', imagePath));

      final response = await request.send();
      final responseData = await response.stream.bytesToString();

      if (response.statusCode == 200) {
        return jsonDecode(responseData);
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      print('Image upload error: $e');
      return {'success': false, 'message': 'Image upload error: $e'};
    }
  }

  static Future<Map<String, dynamic>> getPosts() async {
    try {
      final response = await http.get(Uri.parse(API.getPosts));

      if (response.statusCode == 200) {
        // Check if response body is valid JSON
        if (response.body.trim().startsWith('{') || response.body.trim().startsWith('[')) {
          return jsonDecode(response.body);
        } else {
          return {'success': false, 'message': 'Invalid server response', 'posts': []};
        }
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}', 'posts': []};
      }
    } catch (e) {
      print('Posts loading error: $e');
      return {'success': false, 'message': 'Failed to load posts: $e', 'posts': []};
    }
  }

  static Future<Map<String, dynamic>> getUserPosts(int userId) async {
    try {
      var response = await http.post(
        Uri.parse(API.getUserPosts),
        body: {
          'user_id': userId.toString(),
        },
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  static Future<Map<String, dynamic>> deletePost(int postId, {int? userId}) async {
    try {
      var response = await http.post(
        Uri.parse(API.deletePost),
        body: {
          'post_id': postId.toString(),
          if (userId != null) 'user_id': userId.toString(),
        },
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }

  static Future<Map<String, dynamic>> updatePostStatus(int postId, bool isDonated, {int? userId}) async {
    try {
      var response = await http.post(
        Uri.parse(API.updatePostStatus),
        body: {
          'post_id': postId.toString(),
          'is_donated': isDonated ? '1' : '0',
          if (userId != null) 'user_id': userId.toString(),
        },
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {'success': false, 'message': 'Server error: ${response.statusCode}'};
      }
    } catch (e) {
      return {'success': false, 'message': 'Connection error: $e'};
    }
  }
}