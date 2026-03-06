import 'package:flutter/material.dart';
import '../service/password_service.dart';
import '../user_model/user_preferences.dart';

class ChangePasswordForm extends StatefulWidget {
  final Function() onClose;

  const ChangePasswordForm({
    super.key,
    required this.onClose,
  });

  @override
  State<ChangePasswordForm> createState() => _ChangePasswordFormState();
}

class _ChangePasswordFormState extends State<ChangePasswordForm> {
  final _currentPasswordController = TextEditingController();
  final _newPasswordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isLoading = false;
  bool _obscureCurrentPassword = true;
  bool _obscureNewPassword = true;
  bool _obscureConfirmPassword = true;

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  Future<void> _changePassword() async {
    // Validation - All handled in Flutter
    if (_currentPasswordController.text.isEmpty) {
      _showSnackBar('Please enter current password', isError: true);
      return;
    }

    if (_newPasswordController.text.isEmpty) {
      _showSnackBar('Please enter new password', isError: true);
      return;
    }

    if (_confirmPasswordController.text.isEmpty) {
      _showSnackBar('Please confirm your new password', isError: true);
      return;
    }

    if (_newPasswordController.text.length < 6) {
      _showSnackBar('New password must be at least 6 characters', isError: true);
      return;
    }

    if (_newPasswordController.text != _confirmPasswordController.text) {
      _showSnackBar('New password and confirm password do not match', isError: true);
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      // Get current user ID from shared preferences
      final userInfo = await RememberUser.getRememberUser();
      if (userInfo == null) {
        _showSnackBar('User not found. Please login again.', isError: true);
        return;
      }

      final result = await PasswordService.changePassword(
        userId: userInfo.id,
        currentPassword: _currentPasswordController.text,
        newPassword: _newPasswordController.text,
      );

      if (result['success'] == true) {
        _showSnackBar('Password changed successfully');
        // Clear fields
        _currentPasswordController.clear();
        _newPasswordController.clear();
        _confirmPasswordController.clear();
        // Close the form after successful change
        widget.onClose();
      } else {
        _showSnackBar(result['message'] ?? 'Failed to change password', isError: true);
      }
    } catch (e) {
      _showSnackBar('Error changing password: $e', isError: true);
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _currentPasswordController.dispose();
    _newPasswordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
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
                  'Change Password',
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.close),
                onPressed: _isLoading ? null : widget.onClose,
              ),
            ],
          ),
          const Divider(),
          const SizedBox(height: 20),
          Column(
            children: [
              // Current Password
              TextField(
                controller: _currentPasswordController,
                decoration: InputDecoration(
                  labelText: 'Current Password',
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscureCurrentPassword ? Icons.visibility_off : Icons.visibility,
                    ),
                    onPressed: () {
                      setState(() {
                        _obscureCurrentPassword = !_obscureCurrentPassword;
                      });
                    },
                  ),
                ),
                obscureText: _obscureCurrentPassword,
              ),
              const SizedBox(height: 15),

              // New Password
              TextField(
                controller: _newPasswordController,
                decoration: InputDecoration(
                  labelText: 'New Password',
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscureNewPassword ? Icons.visibility_off : Icons.visibility,
                    ),
                    onPressed: () {
                      setState(() {
                        _obscureNewPassword = !_obscureNewPassword;
                      });
                    },
                  ),
                ),
                obscureText: _obscureNewPassword,
              ),
              const SizedBox(height: 15),

              // Confirm New Password
              TextField(
                controller: _confirmPasswordController,
                decoration: InputDecoration(
                  labelText: 'Confirm New Password',
                  suffixIcon: IconButton(
                    icon: Icon(
                      _obscureConfirmPassword ? Icons.visibility_off : Icons.visibility,
                    ),
                    onPressed: () {
                      setState(() {
                        _obscureConfirmPassword = !_obscureConfirmPassword;
                      });
                    },
                  ),
                ),
                obscureText: _obscureConfirmPassword,
              ),
              const SizedBox(height: 25),

              // Change Password Button
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _changePassword,
                  child: _isLoading
                      ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                      : const Text('Change Password'),
                ),
              ),

              // Password requirements note
              const SizedBox(height: 15),
              const Text(
                'Note: New password must be at least 6 characters long',
                style: TextStyle(
                  fontSize: 12,
                  color: Colors.grey,
                  fontStyle: FontStyle.italic,
                ),
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ],
      ),
    );
  }
}