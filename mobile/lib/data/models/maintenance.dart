class MaintenanceSchedule {
  const MaintenanceSchedule({required this.uuid,required this.title,required this.frequency,required this.nextDueDate,this.lastCompletedDate,this.assetCode});
  factory MaintenanceSchedule.fromJson(Map<String,dynamic> j)=>MaintenanceSchedule(uuid:j['uuid']?.toString()??j['id'].toString(),title:j['title'] as String? ?? '',frequency:j['frequency'] as String? ?? '',nextDueDate:DateTime.parse(j['next_due_date'] as String),lastCompletedDate:j['last_completed_date']==null?null:DateTime.parse(j['last_completed_date'] as String),assetCode:(j['asset'] as Map?)?['asset_tag']?.toString());
  final String uuid,title,frequency;final DateTime nextDueDate;final DateTime? lastCompletedDate;final String? assetCode;
}
