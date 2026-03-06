import 'package:flutter/material.dart';
import 'dart:io';
import 'package:image_picker/image_picker.dart';

import '../api_connection/api_connection.dart';
import '../service/profile_service.dart';
import '../user_model/user_model.dart';

class UpdateProfileForm extends StatefulWidget {
  final User user;
  final Function() onProfileUpdated;
  final Function() onClose;

  const UpdateProfileForm({
    super.key,
    required this.user,
    required this.onProfileUpdated,
    required this.onClose,
  });

  @override
  State<UpdateProfileForm> createState() => _UpdateProfileFormState();
}

class _UpdateProfileFormState extends State<UpdateProfileForm> {
  final ImagePicker _picker = ImagePicker();
  final _nameController = TextEditingController();
  final _bioController = TextEditingController();
  final _studentIdController = TextEditingController();
  final _departmentController = TextEditingController();
  final _currentLocationController = TextEditingController();
  final _permanentLocationController = TextEditingController();

  bool _isLoading = false;
  bool _isLoadingProfile = true;
  File? _selectedNewProfileImage;
  String? _currentProfileImagePath;

  @override
  void initState() {
    super.initState();
    _loadUserProfile();
  }

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  void _loadUserProfile() async {
    setState(() {
      _isLoadingProfile = true;
    });

    var result = await ProfileService.getProfile(widget.user.id);

    setState(() {
      _isLoadingProfile = false;
    });

    if (result['success'] == true) {
      setState(() {
        _nameController.text = result['name'] ?? widget.user.name;
        _bioController.text = result['bio'] ?? '';
        _studentIdController.text = result['student_id'] ?? '';
        _departmentController.text = result['department'] ?? '';
        _currentLocationController.text = result['current_location'] ?? '';
        _permanentLocationController.text = result['permanent_location'] ?? '';
        _currentProfileImagePath = result['profile_image'] ?? '';
      });
    } else {
      _showSnackBar('Failed to load profile: ${result['message']}', isError: true);
    }
  }

  void _updateProfile() async {
    if (_nameController.text.isEmpty) {
      _showSnackBar('Name is required', isError: true);
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Create updated user object
      final updatedUser = User(
        id: widget.user.id,
        name: _nameController.text,
        email: widget.user.email,
        studentId: _studentIdController.text.isEmpty ? null : _studentIdController.text,
        department: _departmentController.text.isEmpty ? null : _departmentController.text,
        currentLocation: _currentLocationController.text.isEmpty ? null : _currentLocationController.text,
        permanentLocation: _permanentLocationController.text.isEmpty ? null : _permanentLocationController.text,
        bio: _bioController.text.isEmpty ? null : _bioController.text,
      );

      XFile? imageFile;
      if (_selectedNewProfileImage != null) {
        imageFile = XFile(_selectedNewProfileImage!.path);
      }

      final result = await ProfileService.updateProfileWithImage(
        user: updatedUser,
        imageFile: imageFile,
      );

      if (result['success'] == true) {
        _showSnackBar(result['message'] ?? 'Profile updated successfully');

        // Update profile image path if new image was uploaded
        if (result['image_path'] != null) {
          _currentProfileImagePath = result['image_path'];
        }

        // Clear selected image
        setState(() {
          _selectedNewProfileImage = null;
        });

        // Call the callback to refresh the parent widget
        widget.onProfileUpdated();
      } else {
        _showSnackBar(result['message'] ?? 'Failed to update profile', isError: true);
      }
    } catch (e) {
      _showSnackBar('Network error: $e', isError: true);
      print('Full error details: $e');
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  Future<void> _pickImage() async {
    try {
      final XFile? image = await _picker.pickImage(
        source: ImageSource.gallery,
        imageQuality: 80,
      );

      if (image != null) {
        setState(() {
          _selectedNewProfileImage = File(image.path);
        });
      }
    } catch (e) {
      _showSnackBar('Error picking image: $e', isError: true);
      print('Image pick error: $e');
    }
  }

  Widget _buildProfileImage() {
    if (_selectedNewProfileImage != null) {
      return CircleAvatar(
        radius: 50,
        backgroundImage: FileImage(_selectedNewProfileImage!),
        child: Align(
          alignment: Alignment.bottomRight,
          child: Container(
            padding: const EdgeInsets.all(4),
            decoration: const BoxDecoration(
              color: Colors.blue,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.camera_alt, size: 20, color: Colors.white),
          ),
        ),
      );
    } else if (_currentProfileImagePath != null && _currentProfileImagePath!.isNotEmpty) {
      return CircleAvatar(
        radius: 50,
        backgroundImage: NetworkImage('${API.hostConnect}/$_currentProfileImagePath'),
        child: Align(
          alignment: Alignment.bottomRight,
          child: Container(
            padding: const EdgeInsets.all(4),
            decoration: const BoxDecoration(
              color: Colors.blue,
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.camera_alt, size: 20, color: Colors.white),
          ),
        ),
      );
    } else {
      return CircleAvatar(
        radius: 50,
        backgroundColor: Colors.grey[300],
        child: Stack(
          children: [
            const Icon(Icons.person, size: 60, color: Colors.grey),
            Align(
              alignment: Alignment.bottomRight,
              child: Container(
                padding: const EdgeInsets.all(4),
                decoration: const BoxDecoration(
                  color: Colors.blue,
                  shape: BoxShape.circle,
                ),
                child: const Icon(Icons.camera_alt, size: 20, color: Colors.white),
              ),
            ),
          ],
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Text(
                  'Update Profile',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: widget.onClose,
              ),
            ],
          ),
          const Divider(),
          const SizedBox(height: 20),

          if (_isLoadingProfile)
            const Center(child: CircularProgressIndicator())
          else
            Column(
              children: [
                GestureDetector(
                  onTap: _pickImage,
                  child: _buildProfileImage(),
                ),
                const SizedBox(height: 20),
                TextField(
                  controller: _nameController,
                  decoration: const InputDecoration(
                    labelText: 'Full Name *',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _bioController,
                  decoration: const InputDecoration(
                    labelText: 'Bio',
                    border: OutlineInputBorder(),
                  ),
                  maxLines: 3,
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _studentIdController,
                  decoration: const InputDecoration(
                    labelText: 'Student ID',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _departmentController,
                  decoration: const InputDecoration(
                    labelText: 'Department',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _currentLocationController,
                  decoration: const InputDecoration(
                    labelText: 'Current Location',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 10),
                TextField(
                  controller: _permanentLocationController,
                  decoration: const InputDecoration(
                    labelText: 'Permanent Location',
                    border: OutlineInputBorder(),
                  ),
                ),
                const SizedBox(height: 20),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _isLoading ? null : _updateProfile,
                    style: ElevatedButton.styleFrom(
                      padding: const EdgeInsets.symmetric(vertical: 15),
                    ),
                    child: _isLoading
                        ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                        : const Text('Update Profile'),
                  ),
                ),
              ],
            ),
        ],
      ),
    );
  }

  @override
  void dispose() {
    _nameController.dispose();
    _bioController.dispose();
    _studentIdController.dispose();
    _departmentController.dispose();
    _currentLocationController.dispose();
    _permanentLocationController.dispose();
    super.dispose();
  }
}