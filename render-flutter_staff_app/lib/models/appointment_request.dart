class AppointmentRequest {
  final int id;
  final int targetUserId;
  final String? targetUserName;
  final String? targetUserStatus;
  final String? targetUserTimeAvailability;
  final int? waypointId;
  final String? waypointName;
  final String requesterType;
  final String requesterName;
  final String? gradeLevel;
  final String requesterEmail;
  final String purposeCode;
  final String? purposeOtherText;
  final String status;
  final DateTime? scheduledAt;
  final DateTime createdAt;

  const AppointmentRequest({
    required this.id,
    required this.targetUserId,
    this.targetUserName,
    this.targetUserStatus,
    this.targetUserTimeAvailability,
    required this.waypointId,
    required this.waypointName,
    required this.requesterType,
    required this.requesterName,
    required this.gradeLevel,
    required this.requesterEmail,
    required this.purposeCode,
    required this.purposeOtherText,
    required this.status,
    required this.scheduledAt,
    required this.createdAt,
  });

  factory AppointmentRequest.fromJson(Map<String, dynamic> json) {
    DateTime? dtOrNull(dynamic v) {
      if (v == null) return null;
      if (v is String && v.isNotEmpty) return DateTime.tryParse(v);
      return null;
    }

    final targetUser = json['target_user'] as Map<String, dynamic>?;

    return AppointmentRequest(
      id: (json['id'] as num).toInt(),
      targetUserId: (json['target_user_id'] as num?)?.toInt() ?? 0,
      targetUserName: (json['target_user_name'] as String?) ?? targetUser?['name'] as String?,
      targetUserStatus: (json['target_user_status'] as String?) ?? targetUser?['availability_status'] as String?,
      targetUserTimeAvailability: (json['target_user_time_availability'] as String?) ?? targetUser?['time_availability'] as String?,
      waypointId: (json['waypoint_id'] as num?)?.toInt(),
      waypointName: (json['waypoint_name'] as String?) ?? (json['waypoint'] as Map<String, dynamic>?)?['name'] as String?,
      requesterType: (json['requester_type'] as String?) ?? '',
      requesterName: (json['requester_name'] as String?) ?? '',
      gradeLevel: json['grade_level'] as String?,
      requesterEmail: (json['requester_email'] as String?) ?? '',
      purposeCode: (json['purpose_code'] as String?) ?? '',
      purposeOtherText: json['purpose_other_text'] as String?,
      status: (json['status'] as String?) ?? '',
      scheduledAt: dtOrNull(json['scheduled_at']),
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.now(),
    );
  }
}
