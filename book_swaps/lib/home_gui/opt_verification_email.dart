import 'dart:async';
import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../api_connection/api_connection.dart';

class OTPVerification extends StatefulWidget {
  final String email;
  final String apiEndpoint;
  final Map<String, String>? additionalData;
  final VoidCallback onVerificationSuccess;
  final VoidCallback onBackPressed;

  const OTPVerification({
    super.key,
    required this.email,
    required this.apiEndpoint,
    this.additionalData,
    required this.onVerificationSuccess,
    required this.onBackPressed,
  });

  @override
  State<OTPVerification> createState() => _OTPVerificationState();
}

class _OTPVerificationState extends State<OTPVerification> {
  final _otpController = TextEditingController();
  Timer? _otpTimer;
  int _otpSecondsRemaining = 60;
  bool _isLoading = false;

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

// OTP verification
  verifyOtp() async {
    setState(() => _isLoading = true);

    try {
      Map<String, dynamic> body = {
        'user_id': widget.additionalData?['user_id'] ?? '',
        'otp': _otpController.text.trim(),
      };

      //Use jsonEncode
      var res = await http.post(
        Uri.parse(widget.apiEndpoint),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(body), // Encode to JSON
      );

      if(res.statusCode == 200){
        var resBody = jsonDecode(res.body);
        if(resBody['success'] == true){
          widget.onVerificationSuccess();
        } else {
          _showSnackBar('Error: ${resBody['message']}', isError: true);
        }
      } else {
        _showSnackBar('Server error: ${res.statusCode}', isError: true);
      }
    } catch(e) {
      print('Error: $e');
      _showSnackBar('Network error: $e', isError: true);
    } finally {
      setState(() => _isLoading = false);
    }
  }
  // OTP timer count
  void _startOtpTimer() {
    _otpSecondsRemaining = 60;
    _otpTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      setState(() {
        if (_otpSecondsRemaining > 0) {
          _otpSecondsRemaining--;
        } else {
          _otpTimer?.cancel();
        }
      });
    });
  }

// resendOtp method
  resendOtp() async {
    try {
      setState(() {
        _otpSecondsRemaining = 60;
      });

      _otpTimer?.cancel();
      _startOtpTimer();

      // Create JSON body with both user_id and email
      Map<String, dynamic> body = {
        'user_id': widget.additionalData?['user_id'] ?? '',
        'email': widget.email, // Use the email from the widget
      };

      // FIX: Use jsonEncode to convert the body to JSON string
      var res = await http.post(
        Uri.parse(API.resendEmailOtp),
        headers: {'Content-Type': 'application/json'},
        body: jsonEncode(body), // This is the key fix - encode to JSON
      );

      if(res.statusCode == 200){
        var resBody = jsonDecode(res.body);
        if(resBody['success'] == true){
          _showSnackBar('New OTP sent to ${widget.email}');
        } else {
          _showSnackBar('Error: ${resBody['message']}', isError: true);
          _otpTimer?.cancel();
          setState(() => _otpSecondsRemaining = 0);
        }
      } else {
        _showSnackBar('Server error: ${res.statusCode}', isError: true);
      }
    } catch(e) {
      print('Error: $e');
      _showSnackBar('Network error: $e', isError: true);
    }
  }

  @override
  void initState() {
    super.initState();
    _startOtpTimer();
  }

  @override
  void dispose() {
    _otpController.dispose();
    _otpTimer?.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      constraints: const BoxConstraints(maxWidth: 500),
      padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 40),
      decoration: BoxDecoration(
        color: Colors.white.withOpacity(0.6),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 15,
            spreadRadius: 2,
          ),
        ],
      ),
      child: Column(
        children: [
          Text(
            'Enter OTP sent to ${widget.email}',
            style: const TextStyle(color: Colors.black, fontSize: 16),
            textAlign: TextAlign.center,
          ),

          const SizedBox(height: 20),
          TextFormField(
            autofocus: true,
            controller: _otpController,
            keyboardType: TextInputType.number,
            textAlign: TextAlign.center,
            maxLength: 6,
            decoration: InputDecoration(
              hintText: 'Enter 6-digit OTP',
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(25),
              ),
              contentPadding: const EdgeInsets.symmetric(
                horizontal: 20,
                vertical: 15,
              ),
            ),
          ),

          const SizedBox(height: 10),
          Text(
            'OTP expires in: $_otpSecondsRemaining seconds',
            style: TextStyle(
              color: _otpSecondsRemaining < 10 ? Colors.red : Colors.green,
              fontSize: 14,
            ),
          ),
          const SizedBox(height: 20),
          SizedBox(
            width: double.infinity,
            height: 55,
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : ElevatedButton(
              onPressed: _otpSecondsRemaining > 0 ? verifyOtp : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: Colors.greenAccent,
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(25),
                ),
              ),
              child: const Text(
                'Verify OTP',
                style: TextStyle(
                  fontSize: 20,
                  fontWeight: FontWeight.bold,
                  color: Colors.black,
                ),
              ),
            ),
          ),
          const SizedBox(height: 10),
          TextButton(
            onPressed: _otpSecondsRemaining == 0 ? resendOtp : null,
            child: const Text(
              'Resend OTP',
              style: TextStyle(
                fontSize: 16,
                color: Colors.blue,
                decoration: TextDecoration.underline,
              ),
            ),
          ),
          const SizedBox(height: 20),
          TextButton(
            onPressed: widget.onBackPressed,
            child: const Text(
              'Back',
              style: TextStyle(
                fontSize: 16,
                color: Colors.blue,
                decoration: TextDecoration.underline,
              ),
            ),
          ),
        ],
      ),
    );
  }
}