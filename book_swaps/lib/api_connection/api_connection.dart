class API {
  // For testing on the same machine
  static const hostConnect = 'http://localhost/book_swap_api';
  // static const hostConnect = 'http://192.168.0.249/book_swap_api';

  static const hostConnectUser = '$hostConnect/users';
  static const hostConnectUserUpdate = '$hostConnect/profile_section';
  static const hostConnectEmail = '$hostConnect/email_update';
  static const hostConnectPosts = '$hostConnect/posts';

  static const validateEmail = "$hostConnectUser/validate_email.php";
  static const signUp = "$hostConnectUser/register.php";
  static const logIn = "$hostConnectUser/login.php";
  static const verifyOtp = "$hostConnectUser/verify_otp.php";
  static const resendOtp = "$hostConnectUser/resend_otp.php";
  static const forgetPassword = "$hostConnectUser/forget_password.php";
  static const resetPassword = "$hostConnectUser/reset_password.php";
  static const deleteUnverifiedUser = "$hostConnectUser/delete_unverified_user.php";

  static const updateProfile = "$hostConnectUserUpdate/profile_update.php";
  static const uploadProfileImage = "$hostConnectUserUpdate/upload_profile_image.php";
  static const getProfile = "$hostConnectUserUpdate/get_profile.php";
  static const changePassword = "$hostConnectUserUpdate/change_password.php";

  static const updateEmail = "$hostConnectEmail/update_email.php";
  static const verifyEmailOtp = "$hostConnectEmail/verify_email_otp.php";
  static const resendEmailOtp = "$hostConnectEmail/resend_email_otp.php";

  static const createPost = "$hostConnectPosts/create_post.php";
  static const uploadPostImage = "$hostConnectPosts/upload_post_image.php";
  static const getPosts = "$hostConnectPosts/get_posts.php";
  static const getUserPosts = '$hostConnectPosts/get_user_posts.php';
  static const deletePost = '$hostConnectPosts/delete_post.php';
  static const updatePostStatus = '$hostConnectPosts/update_post_status.php';
}