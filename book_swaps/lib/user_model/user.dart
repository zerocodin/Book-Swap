class UserData {
  int id;
  String name;
  String email;
  String password_hash;
  int is_verified;

  UserData(
      this.id,
      this.name,
      this.email,
      this.password_hash,
      this.is_verified
      );

  factory UserData.fromJson(Map<String, dynamic> json) => UserData(
      // int.parse(json['id']),
      json['id'] is String ? int.parse(json['id']) : json['id'],
      json['name'],
      json['email'],
      json['password_hash'],
      json['is_verified'] is String ? int.parse(json['is_verified']) : json['is_verified'],
  );

  Map<String, dynamic> toJson() => {
    'id': id,
    'name': name,
    'email': email,
    'password_hash': password_hash,
    'is_verified': is_verified,
  };
}