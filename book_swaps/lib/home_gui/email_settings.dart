import 'package:flutter/material.dart';
import '../api_connection/api_connection.dart';
import '../service/email_service.dart';
import '../user_model/user_preferences.dart';

import 'opt_verification_email.dart' show OTPVerification;

class EmailSettingsForm extends StatefulWidget {
  final Function() onClose;

  const EmailSettingsForm({
    super.key,
    required this.onClose,
  });

  @override
  State<EmailSettingsForm> createState() => _EmailSettingsFormState();
}

class _EmailSettingsFormState extends State<EmailSettingsForm> {
  final _emailController = TextEditingController();
  bool _isLoading = false;
  bool _showOtpVerification = false;
  String _pendingEmail = '';

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
        duration: const Duration(seconds: 3),
      ),
    );
  }

  Future<void> _updateEmail() async {

    if (_emailController.text.isEmpty) {
      _showSnackBar('Please enter email address', isError: true);
      return;
    }

    if (!_emailController.text.contains('@') || !_emailController.text.contains('.')) {
      _showSnackBar('Please enter a valid email address', isError: true);
      return;
    }

    setState(() {
      _isLoading = true;
    });

    try {
      final userInfo = await RememberUser.getRememberUser();
      if (userInfo == null) {
        _showSnackBar('User not found. Please login again.', isError: true);
        return;
      }


      final result = await EmailService.updateEmail(
        userId: userInfo.id,
        newEmail: _emailController.text.trim(),
      );

      // print('EmailService result: $result'); // Debug

      if (result['success'] == true || result['success'] == 'true') {
        setState(() {
          _showOtpVerification = true;
          _pendingEmail = result['email'] ?? _emailController.text.trim();
        });
        _showSnackBar('OTP sent to your new email address');
      } else {
        _showSnackBar(result['message'] ?? 'Failed to send OTP', isError: true);
      }
    } catch (e) {
      _showSnackBar('Error updating email: $e', isError: true);
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }
  void _handleOtpVerificationSuccess() {
    _showSnackBar('Email updated successfully!');
    setState(() {
      _showOtpVerification = false;
      _emailController.clear();
      _pendingEmail = '';
    });
    widget.onClose();
  }

  void _handleOtpBackPressed() {
    setState(() {
      _showOtpVerification = false;
      _pendingEmail = '';
    });
  }

  @override
  void dispose() {
    _emailController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_showOtpVerification) {
      return _buildOtpVerification();
    }

    return _buildEmailForm();
  }

  Widget _buildEmailForm() {
    return SingleChildScrollView(
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Expanded(
                child: Text(
                  'Update Email Address',
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
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'Enter new email :',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w500,
                ),
              ),
              const SizedBox(height: 10),
              TextField(
                controller: _emailController,
                decoration: const InputDecoration(
                  hintText: 'example@gmail.com',
                ),
                keyboardType: TextInputType.emailAddress,
              ),
              const SizedBox(height: 15),
              const Text(
                '⚠️ Important: Enter the correct email or you might lose access to your account',
                style: TextStyle(
                  color: Colors.black,
                  fontWeight: FontWeight.bold,
                  fontSize: 12,
                ),
              ),
              const SizedBox(height: 25),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: _isLoading ? null : _updateEmail,
                  style: ElevatedButton.styleFrom(
                    padding: const EdgeInsets.symmetric(vertical: 15),
                  ),
                  child: _isLoading
                      ? const SizedBox(
                    height: 20,
                    width: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                    ),
                  )
                      : const Text(
                    'Verify Email',
                    style: TextStyle(fontSize: 16),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildOtpVerification() {
    return FutureBuilder(
      future: RememberUser.getRememberUser(),
      builder: (context, snapshot) {
        if (snapshot.connectionState == ConnectionState.waiting) {
          return const Center(child: CircularProgressIndicator());
        }

        if (snapshot.hasError || snapshot.data == null) {
          return Center(
            child: Column(
              children: [
                const Text('Error loading user data'),
                ElevatedButton(
                  onPressed: _handleOtpBackPressed,
                  child: const Text('Back'),
                ),
              ],
            ),
          );
        }

        final userInfo = snapshot.data!;

        return OTPVerification(
          email: _pendingEmail,
          apiEndpoint: API.verifyEmailOtp,
          additionalData: {'user_id': userInfo.id.toString()},
          onVerificationSuccess: _handleOtpVerificationSuccess,
          onBackPressed: _handleOtpBackPressed,
        );
      },
    );
  }
}