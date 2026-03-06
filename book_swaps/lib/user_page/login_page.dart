import 'dart:convert';
import 'package:book_swaps/home_gui/home_page.dart';
import 'package:book_swaps/user_page/forget_password.dart';
import 'package:book_swaps/user_page/register_account.dart';
import 'package:flutter/material.dart';
import 'package:http/http.dart' as http;

import '../api_connection/api_connection.dart';
import '../user_model/user.dart';
import '../user_model/user_model.dart';
import '../user_model/user_preferences.dart';

class MyLogin extends StatefulWidget {
  const MyLogin({super.key});

  @override
  State<MyLogin> createState() => _MyLoginState();
}

class _MyLoginState extends State<MyLogin> {
  final _formKey = GlobalKey<FormState>();
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();

  bool _isPasswordVisible = false;
  bool _isLoading = false;

  void _showSnackBar(String message, {bool isError = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message),
        backgroundColor: isError ? Colors.red : Colors.green,
        duration: Duration(seconds: 1),
      ),
    );
  }

  loginUserNow() async{
    if (_formKey.currentState!.validate()) {
      setState(() {
        _isLoading = true;
      });
    }

    try{
      var res = await http.post(
        Uri.parse(API.logIn),
        body: {
          'email' : _emailController.text.trim(),//changed user_email
          'password_hash' : _passwordController.text.trim()//changed user_password
        },
      );

      if(res.statusCode == 200){
          var resBody = jsonDecode(res.body);

          if(resBody['success'] == true){
            _showSnackBar('Login Successfully.');

            UserData userInfo = UserData.fromJson(resBody['userData']);

            await RememberUser.saveRememberUser(userInfo);

            User userHome = _convertUserDataToUser(userInfo);
            Navigator.pushReplacement(
              context,
              MaterialPageRoute(builder: (context) => HomePage(user: userHome,))
            );

          }
          else {
            _showSnackBar('Incorrect email or password\nTry again');
          }
        }

      else {
        _showSnackBar('Server error: ${res.statusCode}', isError: true);
      }
    }catch(e){
      _showSnackBar('Network error: Check your connection',isError: true);
    }finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          image: DecorationImage(
            image: AssetImage('image_collection/background.jpg'),
            fit: BoxFit.cover,
          ),
        ),
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                const Text(
                  'Welcome Back',
                  style: TextStyle(
                    color: Colors.blue,
                    fontWeight: FontWeight.bold,
                    fontSize: 40,
                    shadows: [
                      Shadow(
                        blurRadius: 10,
                        color: Colors.black12,
                        offset: Offset(2, 2),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 30),

                Container(
                  constraints: const BoxConstraints(maxWidth: 500),
                  padding: const EdgeInsets.symmetric(
                    horizontal: 30,
                    vertical: 40,
                  ),
                  decoration: BoxDecoration(
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

                        //Email Input
                        TextFormField(
                          controller: _emailController,
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
                            if (!value.contains('@') && !value.contains('.')) {
                              return 'Please enter a valid email';
                            }
                            return null;
                          },
                        ),
                        const SizedBox(height: 20),

                        //Password Input
                        TextFormField(
                          controller: _passwordController,
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
                        const SizedBox(height: 15),

                        //ForgetOption
                        Align(
                          alignment: Alignment.centerRight,
                          child: TextButton(
                            onPressed: () {
                              Navigator.pushReplacement(
                                context,
                                MaterialPageRoute(builder: (context) => const ForgetPassword()),
                              );
                            },
                            style: ButtonStyle(
                              overlayColor:
                              WidgetStateProperty.all(Colors.transparent),
                              padding: WidgetStateProperty.all(EdgeInsets.zero),
                            ),
                            child: const Text(
                              "Forgot Password?",
                              style: TextStyle(
                                decoration: TextDecoration.underline,
                                decorationColor: Colors.white,
                                fontSize: 16,
                                fontWeight: FontWeight.normal,
                                color: Colors.greenAccent,
                              ),
                            ),
                          ),
                        ),
                        const SizedBox(height: 25),

                        //LogIn Option
                        SizedBox(
                          width: double.infinity,
                          height: 55,

                          child: _isLoading ?
                          const Center(child: CircularProgressIndicator()) : TextButton(
                            onPressed: () {
                              if (_formKey.currentState!.validate()) {
                                loginUserNow();
                              }
                            },
                            style: TextButton.styleFrom(
                              backgroundColor: Colors.greenAccent,
                              shape: RoundedRectangleBorder(
                                borderRadius: BorderRadius.circular(25),
                              ),
                            ),
                            child: const Text(
                              "Sign In",
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

                        //Register Option
                        Row(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Text(
                              "Don't have an account? ",
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
                                  MaterialPageRoute(builder: (context) => const MyRegister()),
                                );
                              },
                              style: ButtonStyle(
                                overlayColor: WidgetStateProperty.all(Colors.transparent),
                                padding: WidgetStateProperty.all(EdgeInsets.zero),
                              ),
                              child: const Text(
                                'Sign Up',
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
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

User _convertUserDataToUser(UserData userData) {
  return User(
    id: userData.id,
    name: userData.name,
    email: userData.email,
    studentId: null,
    department: null,
    currentLocation: null,
    permanentLocation: null,
    bio: null,
    profileImage: null,
  );
}