class Consultation {
  final int id;
  final int assignedUserId;
  final String? assignedUserName;
  final String? assignedUserStatus;
  final String? assignedUserTimeAvailability;
  final int? waypointId;
  final String? waypointName;
  final int ticketNumber;
  final String? displayTicketNumber;
  final String purposeCode;
  final String? purposeOtherText;
  final String status;
  final DateTime createdAt;

  const Consultation({
    required this.id,
    required this.assignedUserId,
    this.assignedUserName,
    this.assignedUserStatus,
    this.assignedUserTimeAvailability,
    required this.waypointId,
    required this.waypointName,
    required this.ticketNumber,
    required this.displayTicketNumber,
    required this.purposeCode,
    required this.purposeOtherText,
    required this.status,
    required this.createdAt,
  });

  factory Consultation.fromJson(Map<String, dynamic> json) {
    final assignedUser = json['assigned_user'] as Map<String, dynamic>?;

    return Consultation(
      id: (json['id'] as num).toInt(),
      assignedUserId: (json['assigned_user_id'] as num?)?.toInt() ?? 0,
      assignedUserName: (json['assigned_user_name'] as String?) ?? assignedUser?['name'] as String?,
      assignedUserStatus: (json['assigned_user_status'] as String?) ?? assignedUser?['availability_status'] as String?,
      assignedUserTimeAvailability: (json['assigned_user_time_availability'] as String?) ?? assignedUser?['time_availability'] as String?,
      waypointId: (json['waypoint_id'] as num?)?.toInt(),
      waypointName: (json['waypoint_name'] as String?) ?? (json['waypoint'] as Map<String, dynamic>?)?['name'] as String?,
      ticketNumber: (json['ticket_number'] as num?)?.toInt() ?? 0,
      displayTicketNumber: json['display_ticket_number'] as String?,
      purposeCode: (json['purpose_code'] as String?) ?? '',
      purposeOtherText: json['purpose_other_text'] as String?,
      status: (json['status'] as String?) ?? '',
      createdAt: DateTime.tryParse(json['created_at']?.toString() ?? '') ?? DateTime.now(),
    );
  }

  String get ticketNumberPadded => ticketNumber.toString().padLeft(3, '0');

  String? get displayTicketNumberPadded {
    final v = (displayTicketNumber ?? '').trim();
    if (v.isEmpty) return null;
    return v;
  }

  static int ticketOffsetForRole(String role) {
    if (role == 'principal') return 100;
    if (role == 'guidance_counselor') return 200;
    return 0;
  }

  String displayTicketNumberForRole(String role) {
    final server = displayTicketNumberPadded;
    if (server != null) return server;
    final n = ticketNumber + ticketOffsetForRole(role);
    return n.toString().padLeft(3, '0');
  }
}
