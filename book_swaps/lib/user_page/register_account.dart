import 'dart:async';
import 'dart:convert';
import 'package:book_swaps/user_page/login_page.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;
import '../api_connection/api_connection.dart';
import 'otp_verification.dart';

class MyRegister extends StatefulWidget {
  const MyRegister({super.key});

  @override
  State<MyRegister> createState() => _MyRegisterState();
}

class _MyRegisterState extends State<MyRegister> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _isPasswordVisible = false;
  bool _isConfirmPasswordVisible = false;
  bool _requiresOtp = false;
  String _registeredEmail = '';
  bool _isLoading = false;

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
      ),
    );
  }

  //Do register
  registerAndSave() async {
    setState(() => _isLoading = true);
    try {
      Map<String, dynamic> userData = {
        'name': _nameController.text.trim(),
        'email': _emailController.text.trim(),
        'password_hash': _passwordController.text.trim(),
      };

      var res = await http.post(
        Uri.parse(API.signUp),
        body: userData,
      );

      if(res.statusCode == 200){
        var resBody = jsonDecode(res.body);

        // print('Register Response: $resBody');

        bool success = resBody['success'] == true || resBody['success'] == 'true';
        bool requiresOtp = resBody['requires_otp'] == true || resBody['requires_otp'] == 'true';

        if(success) {
          if(requiresOtp) {
            setState(() {
              _requiresOtp = true;
              _registeredEmail = _emailController.text.trim();
              _isLoading = false;
            });
            _showSnackBar('OTP sent to your email');
          } else{
            _showSnackBar('Registered successfully');
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => const MyLogin()),
            );
          }
        } else {
          setState(() => _isLoading = false);
          _showSnackBar('Error: ${resBody['message']}', isError: true);
        }
      }
      else {
        setState(() => _isLoading = false);
        _showSnackBar('Server error: ${res.statusCode}', isError: true);
      }
    } catch(e) {
      setState(() => _isLoading = false);
      print('Error: $e');
      _showSnackBar('Network error: Check your connection', isError: true);
    }
  }

  //delete unverified user when going back
  Future<void> deleteUnverifiedUser() async {
    try {
      var res = await http.post(
        Uri.parse(API.deleteUnverifiedUser),
        body: {'email': _registeredEmail},
      );

      if (res.statusCode == 200) {
        var resBody = jsonDecode(res.body);
        if (resBody['success'] == true) {
          _showSnackBar('Registration cancelled');
        }
      }
    } catch (e) {
      print('Error deleting unverified user: $e');
    }
  }

  // Handle OTP verification success
  void _handleOtpVerificationSuccess() {
    _showSnackBar('Email verified successfully');
    Navigator.pushReplacement(
      context,
      MaterialPageRoute(builder: (context) => const MyLogin()),
    );
  }

  // Handle back from OTP
  void _handleOtpBack() async {
    await deleteUnverifiedUser();
    setState(() => _requiresOtp = false);
  }

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    // print('Register Build - requiresOtp: $_requiresOtp');
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('image_collection/home_page.jpg'),
            fit: BoxFit.cover,
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Text(
                  _requiresOtp ? 'Verify OTP' : 'Fill Up Your Detail\'s',
                  style: const TextStyle(
                    color: Colors.blue,
                    fontWeight: FontWeight.bold,
                    fontSize: 45,
                    shadows: [
                      Shadow(
                        blurRadius: 15,
                        color: Colors.black12,
                        offset: Offset(2, 2),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 30),

                // Show either OTP verification or registration form
                _requiresOtp ?
                OTPVerification(
                  email: _registeredEmail,
                  apiEndpoint: API.verifyOtp,
                  onVerificationSuccess: _handleOtpVerificationSuccess,
                  onBackPressed: _handleOtpBack,
                )
                    : _buildRegistrationForm(),
              ],
            ),
          ),
        ),
      ),
    );
  }

// Build registration form UI
  Widget _buildRegistrationForm() {
    return Container(
      constraints: const BoxConstraints(maxWidth: 500),
      padding: const EdgeInsets.symmetric(horizontal: 30, vertical: 40),
      decoration: BoxDecoration(
        // color: Colors.white.withOpacity(0.9),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.1),
            blurRadius: 15,
            spreadRadius: 2,
          ),
        ],
      ),
      child: Form(
        key: _formKey,
        child: Column(
          children: [
            // User Name
            TextFormField(
              controller: _nameController,
              textInputAction: TextInputAction.next,
              decoration: InputDecoration(
                fillColor: Colors.grey.shade100,
                filled: true,
                hintText: 'Enter your full name',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Name can\'t be empty';
                }
                final invalidCharacters = RegExp(r'[@!#\$%^&*(),?":{}|<>0-9]');
                if (invalidCharacters.hasMatch(value)) {
                  return 'Name cannot contain numbers or special characters';
                }
                return null;
              },
            ),
            const SizedBox(height: 20),

            // Email Section
            TextFormField(
              controller: _emailController,
              textInputAction: TextInputAction.next,
              decoration: InputDecoration(
                fillColor: Colors.grey.shade100,
                filled: true,
                hintText: 'Enter your email',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your email';
                }
                final emailRegex = RegExp(r'^[\w-\.]+@([\w-]+\.)+[\w-]{2,4}$');
                if (!emailRegex.hasMatch(value)) {
                  return 'Please enter a valid email address';
                }
                return null;
              },
            ),

            const SizedBox(height: 20),

            // Password
            TextFormField(
              controller: _passwordController,
              textInputAction: TextInputAction.next,
              obscureText: !_isPasswordVisible,
              decoration: InputDecoration(
                fillColor: Colors.grey.shade100,
                filled: true,
                hintText: 'Enter your password',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
                suffixIcon: IconButton(
                  icon: Icon(
                    _isPasswordVisible ? Icons.visibility : Icons.visibility_off,
                    color: Colors.grey,
                  ),
                  onPressed: () {
                    setState(() {
                      _isPasswordVisible = !_isPasswordVisible;
                    });
                  },
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Please enter your password';
                }
                if (value.length < 6) {
                  return 'Password must be at least 6 characters';
                }
                return null;
              },
            ),
            const SizedBox(height: 20),

            // Confirm Password
            TextFormField(
              controller: _confirmPasswordController,
              textInputAction: TextInputAction.done,
              obscureText: !_isConfirmPasswordVisible,
              decoration: InputDecoration(
                fillColor: Colors.grey.shade100,
                filled: true,
                hintText: 'Confirm your password',
                border: OutlineInputBorder(
                  borderRadius: BorderRadius.circular(25),
                  borderSide: BorderSide.none,
                ),
                contentPadding: const EdgeInsets.symmetric(
                  horizontal: 20,
                  vertical: 15,
                ),
                suffixIcon: IconButton(
                  icon: Icon(
                    _isConfirmPasswordVisible ? Icons.visibility : Icons.visibility_off,
                    color: Colors.grey,
                  ),
                  onPressed: () {
                    setState(() {
                      _isConfirmPasswordVisible = !_isConfirmPasswordVisible;
                    });
                  },
                ),
              ),
              validator: (value) {
                if (value == null || value.isEmpty) {
                  return 'Enter your password';
                }
                if (value != _passwordController.text) {
                  return 'Passwords do not match';
                }
                return null;
              },
            ),
            const SizedBox(height: 25),

            // Register Button
            SizedBox(
              width: double.infinity,
              height: 55,
              child: _isLoading ? const Center(child: CircularProgressIndicator())
                  : TextButton(
                onPressed: () {
                  if (_formKey.currentState!.validate()) {
                    registerAndSave();
                  }
                },
                style: TextButton.styleFrom(
                  backgroundColor: Colors.greenAccent,
                  shape: RoundedRectangleBorder(
                    borderRadius: BorderRadius.circular(25),
                  ),
                ),
                child: const Text(
                  "Register Now",
                  style: TextStyle(
                    fontSize: 20,
                    fontWeight: FontWeight.bold,
                    color: Colors.black,
                  ),
                ),
              ),
            ),

            const SizedBox(height: 25),
            const Divider(),
            const SizedBox(height: 15),

            // SignIn Option
            Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text(
                  "Have an account already? ",
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.normal,
                    color: Colors.white,
                  ),
                ),
                TextButton(
                  onPressed: () {
                    Navigator.pushReplacement(
                      context,
                      MaterialPageRoute(builder: (context) => const MyLogin()),
                    );
                  },
                  style: ButtonStyle(
                    overlayColor: WidgetStateProperty.all(Colors.transparent),
                    padding: WidgetStateProperty.all(EdgeInsets.zero),
                  ),
                  child: const Text(
                    'Sign In',
                    style: TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: Colors.greenAccent,
                      decoration: TextDecoration.underline,
                      decorationColor: Colors.white,
                    ),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}