class User {
  final int id;
  String name;
  final String email;
  String? studentId;
  String? department;
  String? currentLocation;
  String? permanentLocation;
  String? bio;
  String? profileImage;

  User({
    required this.id,
    required this.name,
    required this.email,
    this.studentId,
    this.department,
    this.currentLocation,
    this.permanentLocation,
    this.bio,
    this.profileImage,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      id: json['id'] is String ? int.parse(json['id']) : json['id'],
      name: json['name'],
      email: json['email'],
      studentId: json['student_id'],
      department: json['department'],
      currentLocation: json['current_location'],
      permanentLocation: json['permanent_location'],
      bio: json['bio'],
      profileImage: json['profile_image'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': id.toString(),
      'name': name,
      'student_id': studentId ?? '',
      'department': department ?? '',
      'current_location': currentLocation ?? '',
      'permanent_location': permanentLocation ?? '',
      'bio': bio ?? '',
      'profile_image': profileImage,
    };
  }
}