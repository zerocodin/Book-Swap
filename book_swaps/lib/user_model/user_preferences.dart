import 'dart:convert';
import 'package:book_swaps/user_model/user.dart';
import 'package:shared_preferences/shared_preferences.dart';

class RememberUser{
  static Future<void> saveRememberUser(UserData userinfo) async{
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String userData = jsonEncode(userinfo.toJson());
    await prefs.setString('remember_user', userData);
  }

  //Missing method
  static Future<UserData?> getRememberUser() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    String? userDataString = prefs.getString('remember_user');

    if (userDataString == null) {
      return null;
    }

    try {
      Map<String, dynamic> userMap = jsonDecode(userDataString);
      return UserData.fromJson(userMap);
    } catch (e) {
      return null;
    }
  }

  //Remove user data....Can be remove
  static Future<void> removeRememberUser() async {
    SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.remove('remember_user');
  }
}