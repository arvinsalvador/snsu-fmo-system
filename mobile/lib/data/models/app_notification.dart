class AppNotification {
  const AppNotification({required this.uuid,required this.type,required this.title,required this.isRead,required this.createdAt,this.message,this.route});
  factory AppNotification.fromJson(Map<String,dynamic> j)=>AppNotification(uuid:(j['uuid']??j['id']).toString(),type:j['type'] as String? ?? '',title:j['title'] as String? ?? 'Notification',message:j['message'] as String?,route:j['mobile_route'] as String?,isRead:j['is_read'] as bool? ?? j['read_at']!=null,createdAt:DateTime.parse(j['created_at'] as String).toUtc());
  final String uuid,type,title;final String? message,route;final bool isRead;final DateTime createdAt;
}
