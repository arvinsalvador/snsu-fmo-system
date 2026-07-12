class AssetSummary {
  const AssetSummary({required this.uuid,required this.code,required this.name,required this.status,this.category,this.location,this.nextDueDate});
  factory AssetSummary.fromJson(Map<String,dynamic> j){final loc=j['location'];return AssetSummary(uuid:j['uuid'] as String,code:(j['asset_code']??j['asset_tag']??'').toString(),name:j['name'] as String? ?? '',status:j['status'] as String? ?? '',category:j['category'] is Map?(j['category'] as Map)['name']?.toString():j['category']?.toString(),location:loc is Map?[loc['building'],loc['floor'],loc['room'],loc['exact']].whereType<String>().join(' / '):j['location']?.toString(),nextDueDate:(j['maintenance'] as Map?)?['next_due_date'] == null?null:DateTime.parse((j['maintenance'] as Map)['next_due_date'] as String));}
  final String uuid,code,name,status;final String? category,location;final DateTime? nextDueDate;
}
