class WorkOrder {
  const WorkOrder({required this.uuid,required this.number,required this.title,required this.status,required this.priority,required this.updatedAt,this.description,this.location});
  factory WorkOrder.fromJson(Map<String,dynamic> j)=>WorkOrder(uuid:j['uuid'] as String,number:j['work_order_number'] as String? ?? '',title:j['title'] as String? ?? '',description:j['description'] as String?,status:(j['status'] as Map?)?['name']?.toString()??j['approval_status']?.toString()??'',priority:(j['priority'] as Map?)?['name']?.toString()??'',location:_location(j),updatedAt:DateTime.parse(j['updated_at'] as String).toUtc());
  final String uuid,number,title,status,priority;final String? description,location;final DateTime updatedAt;
  static String? _location(Map<String,dynamic> j){final parts=[(j['building'] as Map?)?['name'],(j['floor'] as Map?)?['floor_name'],(j['room'] as Map?)?['room_code']].whereType<String>();return parts.isEmpty?null:parts.join(' / ');}
}
