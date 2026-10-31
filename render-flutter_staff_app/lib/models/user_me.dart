class UserMe {
  final int id;
  final String name;
  final String email;
  final String role;
  final String availabilityStatus;
  final String? timeAvailability;

  const UserMe({
    required this.id,
    required this.name,
    required this.email,
    required this.role,
    required this.availabilityStatus,
    this.timeAvailability,
  });

  factory UserMe.fromJson(Map<String, dynamic> json) {
    return UserMe(
      id: (json['id'] as num).toInt(),
      name: (json['name'] as String?) ?? '',
      email: (json['email'] as String?) ?? '',
      role: (json['role'] as String?) ?? '',
      availabilityStatus: (json['availability_status'] as String?) ?? 'available',
      timeAvailability: json['time_availability'] as String?,
    );
  }
}

