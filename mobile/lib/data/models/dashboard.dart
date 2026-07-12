class DashboardSummary {
  const DashboardSummary(this.values);
  factory DashboardSummary.fromJson(Map<String,dynamic> json)=>DashboardSummary(json.map((k,v)=>MapEntry(k,(v as num).toInt())));
  final Map<String,int> values;
  int operator [](String key)=>values[key]??0;
}
