import 'package:dio/dio.dart';
import 'package:flutter_staff_app/models/appointment_request.dart';
import 'package:flutter_staff_app/models/consultation.dart';
import 'package:flutter_staff_app/models/user_me.dart';

class MobileApi {
  final Dio _dio;

  MobileApi(this._dio);

  Future<({String token, UserMe user})> login({
    required String email,
    required String password,
  }) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/login',
      data: {
        'email': email,
        'password': password,
      },
    );
    final data = res.data ?? <String, dynamic>{};
    final token = (data['token'] as String?) ?? '';
    final user = UserMe.fromJson((data['user'] as Map).cast<String, dynamic>());
    return (token: token, user: user);
  }

  Future<UserMe> me() async {
    final res = await _dio.get<Map<String, dynamic>>('/api/mobile/me');
    return UserMe.fromJson(res.data ?? <String, dynamic>{});
  }

  Future<void> logout() async {
    await _dio.post('/api/mobile/logout');
  }

  Future<String> forgotPassword({required String email}) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/forgot-password',
      data: {
        'email': email,
      },
    );
    return (res.data?['message'] as String?) ??
        'Account information and new password sent to your email.';
  }

  // --- Staff Operations ---
  Future<List<AppointmentRequest>> listAppointments() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/appointments');
    final list = res.data ?? const [];
    return list
        .whereType<Map>()
        .map((e) => AppointmentRequest.fromJson(e.cast<String, dynamic>()))
        .toList();
  }

  Future<AppointmentRequest> acceptAppointment({
    required int id,
    required DateTime scheduledAt,
  }) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/appointments/$id/accept',
      data: {
        'scheduled_at': scheduledAt.toIso8601String(),
      },
    );
    return AppointmentRequest.fromJson(res.data ?? <String, dynamic>{});
  }

  Future<AppointmentRequest> declineAppointment({
    required int id,
  }) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/appointments/$id/decline',
    );
    return AppointmentRequest.fromJson(res.data ?? <String, dynamic>{});
  }

  Future<List<Consultation>> listConsultations() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/consultations');
    final list = res.data ?? const [];
    return list
        .whereType<Map>()
        .map((e) => Consultation.fromJson(e.cast<String, dynamic>()))
        .toList();
  }

  Future<Consultation> startConsultation({required int id}) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/consultations/$id/start',
    );
    return Consultation.fromJson(res.data ?? <String, dynamic>{});
  }

  Future<Consultation> finishConsultation({required int id}) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/consultations/$id/finish',
    );
    return Consultation.fromJson(res.data ?? <String, dynamic>{});
  }

  Future<Map<String, dynamic>> updateAvailability({String? status, String? timeAvailability}) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/profile/availability',
      data: {
        if (status != null) 'availability_status': status,
        if (timeAvailability != null) 'time_availability': timeAvailability,
      },
    );
    return res.data ?? <String, dynamic>{};
  }

  Future<void> changePassword({
    required String currentPassword,
    required String newPassword,
  }) async {
    await _dio.post(
      '/api/mobile/profile/password',
      data: {
        'current_password': currentPassword,
        'new_password': newPassword,
      },
    );
  }

  // --- Admin Operations ---
  Future<Map<String, dynamic>> getAdminDashboard() async {
    final res = await _dio.get<Map<String, dynamic>>('/api/mobile/admin/dashboard');
    return res.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> getAdminWaypoints() async {
    final res = await _dio.get<Map<String, dynamic>>('/api/mobile/admin/waypoints');
    return res.data ?? <String, dynamic>{};
  }

  Future<void> createWaypoint(Map<String, dynamic> data) async {
    await _dio.post('/api/mobile/admin/waypoints', data: data);
  }

  Future<void> updateWaypoint(int id, Map<String, dynamic> data) async {
    await _dio.post('/api/mobile/admin/waypoints/$id', data: data);
  }

  Future<void> deleteWaypoint(int id) async {
    await _dio.delete('/api/mobile/admin/waypoints/$id');
  }

  Future<List<dynamic>> getAdminUsers() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/admin/users');
    return res.data ?? [];
  }

  Future<void> createAdminUser(Map<String, dynamic> data) async {
    await _dio.post('/api/mobile/admin/users', data: data);
  }

  Future<void> updateAdminUser(int id, Map<String, dynamic> data) async {
    await _dio.post('/api/mobile/admin/users/$id', data: data);
  }

  Future<void> deleteAdminUser(int id) async {
    await _dio.delete('/api/mobile/admin/users/$id');
  }

  Future<List<dynamic>> getAdminAppointments() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/admin/appointments');
    return res.data ?? [];
  }

  Future<void> adminAcceptAppointment(int id, DateTime scheduledAt) async {
    await _dio.post(
      '/api/mobile/admin/appointments/$id/accept',
      data: {'scheduled_at': scheduledAt.toIso8601String()},
    );
  }

  Future<void> adminDeclineAppointment(int id) async {
    await _dio.post('/api/mobile/admin/appointments/$id/decline');
  }

  Future<void> deleteAdminAppointment(int id) async {
    await _dio.delete('/api/mobile/admin/appointments/$id');
  }

  Future<List<dynamic>> getAdminConsultations() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/admin/consultations');
    return res.data ?? [];
  }

  Future<void> adminStartConsultation(int id) async {
    await _dio.post('/api/mobile/admin/consultations/$id/start');
  }

  Future<void> adminFinishConsultation(int id) async {
    await _dio.post('/api/mobile/admin/consultations/$id/finish');
  }

  Future<void> deleteAdminConsultation(int id) async {
    await _dio.delete('/api/mobile/admin/consultations/$id');
  }

  Future<List<dynamic>> getAdminFeedbacks() async {
    final res = await _dio.get<List<dynamic>>('/api/mobile/admin/feedbacks');
    return res.data ?? [];
  }

  Future<void> deleteAdminFeedback(int id) async {
    await _dio.delete('/api/mobile/admin/feedbacks/$id');
  }

  Future<void> updateAdminFeedback(int id, {required String rating, String? suggestions}) async {
    await _dio.put(
      '/api/mobile/admin/feedbacks/$id',
      data: {
        'rating': rating,
        'suggestions': suggestions,
      },
    );
  }

  Future<Map<String, dynamic>> getAdminSettings() async {
    final res = await _dio.get<Map<String, dynamic>>('/api/mobile/admin/settings');
    return res.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> updateAdminSettings(Map<String, dynamic> data) async {
    final res = await _dio.post<Map<String, dynamic>>('/api/mobile/admin/settings', data: data);
    return res.data ?? <String, dynamic>{};
  }

  Future<Map<String, dynamic>> testAdminEmail(String email) async {
    final res = await _dio.post<Map<String, dynamic>>(
      '/api/mobile/admin/settings/test-email',
      data: {'email': email},
    );
    return res.data ?? <String, dynamic>{};
  }

  Future<List<dynamic>> getAdminActivityLogs({String? role, String? search}) async {
    final res = await _dio.get<List<dynamic>>(
      '/api/mobile/admin/activity-logs',
      queryParameters: {
        if (role != null && role != 'all') 'role': role,
        if (search != null && search.isNotEmpty) 'search': search,
      },
    );
    return res.data ?? [];
  }
}
