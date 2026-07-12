import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../app/providers.dart';
import '../../core/network/api_error.dart';
import '../../data/models/work_order.dart';
import '../../data/repositories/repositories.dart';
import '../../shared/widgets.dart';

class WorkOrderListScreen extends ConsumerStatefulWidget {
  const WorkOrderListScreen({super.key});
  @override
  ConsumerState<WorkOrderListScreen> createState() => _WorkOrderListState();
}

class _WorkOrderListState extends ConsumerState<WorkOrderListScreen> {
  final search = TextEditingController();
  PageResult<WorkOrder>? result;
  Object? error;
  bool loading = true;
  bool assigned = false;

  @override
  void initState() { super.initState(); _load(); }
  @override
  void dispose() { search.dispose(); super.dispose(); }
  Future<void> _load({int page = 1}) async {
    setState(() { loading = true; error = null; });
    try {
      final value = await ref.read(workOrderRepositoryProvider).list(
          page: page, search: search.text.trim(), assignedToMe: assigned);
      if (mounted) setState(() => result = value);
    } catch (e) { if (mounted) setState(() => error = e); }
    finally { if (mounted) setState(() => loading = false); }
  }

  @override
  Widget build(BuildContext context) {
    final canCreate = ref.watch(authControllerProvider).user?.canCreateWorkOrder ?? false;
    return Scaffold(
      body: RefreshIndicator(
        onRefresh: _load,
        child: CustomScrollView(slivers: [
          SliverToBoxAdapter(child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(children: [
              TextField(controller: search, textInputAction: TextInputAction.search,
                onSubmitted: (_) => _load(), decoration: InputDecoration(
                  labelText: 'Search work orders', prefixIcon: const Icon(Icons.search),
                  suffixIcon: IconButton(tooltip: 'Search', onPressed: _load, icon: const Icon(Icons.arrow_forward)))),
              const SizedBox(height: 8),
              FilterChip(label: const Text('Assigned to me'), selected: assigned,
                onSelected: (v) { setState(() => assigned = v); _load(); }),
            ]),
          )),
          if (loading) const SliverFillRemaining(child: LoadingPanel())
          else if (error != null) SliverFillRemaining(child: ErrorPanel(ApiError.messageFor(error), onRetry: _load))
          else if (result?.items.isEmpty ?? true) const SliverFillRemaining(child: EmptyPanel('No work orders match these filters.'))
          else SliverList.builder(itemCount: result!.items.length, itemBuilder: (context, i) {
            final item = result!.items[i];
            return ListTile(
              leading: const CircleAvatar(child: Icon(Icons.build_outlined)),
              title: Text(item.title),
              subtitle: Text('${item.number}\n${item.location ?? 'Location not specified'} · ${DateFormat.yMMMd().format(item.updatedAt.toLocal())}'),
              isThreeLine: true,
              trailing: Column(mainAxisAlignment: MainAxisAlignment.center, children: [StatusBadge(item.status), Text(item.priority)]),
              onTap: () => context.push('/work-orders/${item.uuid}'),
            );
          }),
          if (!loading && result != null) SliverToBoxAdapter(child: PageControls(page: result!.page, lastPage: result!.lastPage, onPage: (p) => _load(page: p))),
        ]),
      ),
      floatingActionButton: canCreate ? FloatingActionButton.extended(
        onPressed: () => context.push('/work-orders/create').then((_) => _load()),
        icon: const Icon(Icons.add), label: const Text('New request')) : null,
    );
  }
}

class WorkOrderDetailScreen extends ConsumerStatefulWidget {
  const WorkOrderDetailScreen(this.uuid, {super.key});
  final String uuid;
  @override
  ConsumerState<WorkOrderDetailScreen> createState() => _WorkOrderDetailState();
}

class _WorkOrderDetailState extends ConsumerState<WorkOrderDetailScreen> {
  WorkOrder? order;
  List<Map<String, dynamic>> updates = const [];
  Object? error;
  bool busy = true;
  @override
  void initState() { super.initState(); _load(); }
  Future<void> _load() async {
    setState(() { busy = true; error = null; });
    try {
      final repo = ref.read(workOrderRepositoryProvider);
      final values = await Future.wait<Object>([repo.detail(widget.uuid), repo.updates(widget.uuid)]);
      if (mounted) setState(() { order = values[0] as WorkOrder; updates = values[1] as List<Map<String, dynamic>>; });
    } catch (e) { if (mounted) setState(() => error = e); }
    finally { if (mounted) setState(() => busy = false); }
  }

  Future<void> _decision(bool approve) async {
    final input = TextEditingController();
    final accepted = await showDialog<bool>(context: context, builder: (context) => AlertDialog(
      title: Text(approve ? 'Approve work order' : 'Reject work order'),
      content: TextField(controller: input, maxLines: 3, decoration: InputDecoration(labelText: approve ? 'Remarks (optional)' : 'Reason')),
      actions: [TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')), FilledButton(onPressed: () => Navigator.pop(context, true), child: Text(approve ? 'Approve' : 'Reject'))],
    ));
    if (accepted != true || !mounted) return;
    try {
      final repo = ref.read(workOrderRepositoryProvider);
      if (approve) { await repo.approve(widget.uuid, input.text.trim()); }
      else { await repo.reject(widget.uuid, input.text.trim()); }
      await _load();
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(ApiError.messageFor(e)))); }
  }

  @override
  Widget build(BuildContext context) {
    final user = ref.watch(authControllerProvider).user;
    if (busy) return const LoadingPanel();
    if (error != null || order == null) return ErrorPanel(ApiError.messageFor(error), onRetry: _load);
    final value = order!;
    return RefreshIndicator(onRefresh: _load, child: ListView(padding: const EdgeInsets.all(16), children: [
      Text(value.number, style: Theme.of(context).textTheme.labelLarge),
      Text(value.title, style: Theme.of(context).textTheme.headlineSmall),
      const SizedBox(height: 12), Wrap(spacing: 8, children: [StatusBadge(value.status), StatusBadge(value.priority)]),
      const SizedBox(height: 20), DetailSection(title: 'Request', children: [
        Text(value.description ?? 'No description provided.'),
        const SizedBox(height: 8), LabeledValue('Location', value.location ?? 'Not specified'),
        LabeledValue('Last updated', DateFormat.yMMMd().add_jm().format(value.updatedAt.toLocal())),
      ]),
      if (user?.canApproveWorkOrders ?? false) Padding(padding: const EdgeInsets.symmetric(vertical: 12), child: Row(children: [
        Expanded(child: OutlinedButton.icon(onPressed: () => _decision(false), icon: const Icon(Icons.close), label: const Text('Reject'))),
        const SizedBox(width: 12), Expanded(child: FilledButton.icon(onPressed: () => _decision(true), icon: const Icon(Icons.check), label: const Text('Approve'))),
      ])),
      DetailSection(title: 'Progress timeline', children: updates.isEmpty
        ? const [Text('No progress updates yet.')]
        : updates.map((u) => ListTile(contentPadding: EdgeInsets.zero, leading: const Icon(Icons.history), title: Text((u['notes'] ?? u['status'] ?? 'Progress update').toString()), subtitle: Text((u['updated_at'] ?? u['created_at'] ?? '').toString()))).toList()),
    ]));
  }
}

class WorkOrderCreateScreen extends ConsumerStatefulWidget {
  const WorkOrderCreateScreen({super.key});
  @override ConsumerState<WorkOrderCreateScreen> createState() => _WorkOrderCreateState();
}

class _WorkOrderCreateState extends ConsumerState<WorkOrderCreateScreen> {
  final formKey = GlobalKey<FormState>();
  final title = TextEditingController(); final description = TextEditingController();
  Map<String, dynamic>? refs; String? building, floor, room, category, priority; bool saving = false;
  @override void initState() { super.initState(); ref.read(referenceRepositoryProvider).load().then((v) { if (mounted) setState(() => refs = v); }); }
  @override void dispose() { title.dispose(); description.dispose(); super.dispose(); }
  List<Map<String, dynamic>> _items(String key) => (refs?[key] as List? ?? const []).map((e) => Map<String,dynamic>.from(e as Map)).toList();
  String _id(Map<String,dynamic> item) => (item['id'] ?? item['uuid']).toString();
  Future<void> _save() async {
    if (!(formKey.currentState?.validate() ?? false) || [building, category, priority].contains(null)) return;
    setState(() => saving = true);
    try {
      await ref.read(workOrderRepositoryProvider).create({'title':title.text.trim(),'description':description.text.trim(),'building_id':building,'floor_id':floor,'room_id':room,'category_id':category,'priority_id':priority});
      if (mounted) context.pop();
    } catch (e) { if (mounted) ScaffoldMessenger.of(context).showSnackBar(SnackBar(content: Text(ApiError.messageFor(e)))); }
    finally { if (mounted) setState(() => saving = false); }
  }
  @override Widget build(BuildContext context) {
    if (refs == null) return const LoadingPanel();
    final floors = _items('floors').where((e) => building == null || e['building_id'].toString() == building).toList();
    final rooms = _items('rooms').where((e) => floor == null || e['floor_id'].toString() == floor).toList();
    return Scaffold(appBar: AppBar(title: const Text('Create work order')), body: Form(key: formKey, child: ListView(padding: const EdgeInsets.all(16), children: [
      TextFormField(controller:title, decoration:const InputDecoration(labelText:'Title'), validator:(v)=>v==null||v.trim().isEmpty?'Title is required.':null),
      const SizedBox(height:12), TextFormField(controller:description, maxLines:5, decoration:const InputDecoration(labelText:'Description'), validator:(v)=>v==null||v.trim().isEmpty?'Description is required.':null),
      const SizedBox(height:12), _select('Building', _items('buildings'), building, (v)=>setState(() {building=v;floor=null;room=null;})),
      const SizedBox(height:12), _select('Floor', floors, floor, (v)=>setState(() {floor=v;room=null;}), required:false),
      const SizedBox(height:12), _select('Room', rooms, room, (v)=>setState(()=>room=v), required:false),
      const SizedBox(height:12), _select('Category', _items('work_order_categories'), category, (v)=>setState(()=>category=v)),
      const SizedBox(height:12), _select('Priority', _items('priorities'), priority, (v)=>setState(()=>priority=v)),
      const SizedBox(height:20), FilledButton.icon(onPressed:saving?null:_save, icon:saving?const SizedBox.square(dimension:18,child:CircularProgressIndicator(strokeWidth:2)):const Icon(Icons.send), label:const Text('Submit request')),
    ])));
  }
  Widget _select(String label,List<Map<String,dynamic>> items,String? value,ValueChanged<String?> changed,{bool required=true}) => DropdownButtonFormField<String>(value:value,decoration:InputDecoration(labelText:label),items:items.map((e)=>DropdownMenuItem(value:_id(e),child:Text((e['name']??e['floor_name']??e['room_code']).toString()))).toList(),onChanged:changed,validator:(v)=>required&&v==null?'$label is required.':null);
}
