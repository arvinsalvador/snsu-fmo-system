import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:mobile_scanner/mobile_scanner.dart';

import '../../app/providers.dart';
import '../../core/network/api_error.dart';
import '../../core/utils/qr_payload_parser.dart';
import '../../data/models/asset.dart';
import '../../data/repositories/repositories.dart';
import '../../shared/widgets.dart';

class AssetListScreen extends ConsumerStatefulWidget { const AssetListScreen({super.key}); @override ConsumerState<AssetListScreen> createState()=>_AssetListState(); }
class _AssetListState extends ConsumerState<AssetListScreen>{
 final search=TextEditingController();PageResult<AssetSummary>? result;Object? error;bool loading=true;
 @override void initState(){super.initState();_load();}@override void dispose(){search.dispose();super.dispose();}
 Future<void> _load({int page=1})async{setState((){loading=true;error=null;});try{final v=await ref.read(assetRepositoryProvider).list(page:page,search:search.text.trim());if(mounted)setState(()=>result=v);}catch(e){if(mounted)setState(()=>error=e);}finally{if(mounted)setState(()=>loading=false);}}
 @override Widget build(BuildContext context)=>Scaffold(body:RefreshIndicator(onRefresh:_load,child:CustomScrollView(slivers:[SliverToBoxAdapter(child:Padding(padding:const EdgeInsets.all(16),child:TextField(controller:search,onSubmitted:(_)=>_load(),decoration:InputDecoration(labelText:'Search assets',prefixIcon:const Icon(Icons.search),suffixIcon:IconButton(tooltip:'Search',onPressed:_load,icon:const Icon(Icons.arrow_forward)))))),if(loading)const SliverFillRemaining(child:LoadingPanel())else if(error!=null)SliverFillRemaining(child:ErrorPanel(ApiError.messageFor(error),onRetry:_load))else if(result?.items.isEmpty??true)const SliverFillRemaining(child:EmptyPanel('No assets found.'))else SliverList.builder(itemCount:result!.items.length,itemBuilder:(c,i){final a=result!.items[i];return ListTile(leading:const CircleAvatar(child:Icon(Icons.precision_manufacturing_outlined)),title:Text(a.name),subtitle:Text('${a.code}\n${a.location??'Location not specified'}'),isThreeLine:true,trailing:StatusBadge(a.status),onTap:()=>context.push('/assets/${a.uuid}'));}),if(!loading&&result!=null)SliverToBoxAdapter(child:PageControls(page:result!.page,lastPage:result!.lastPage,onPage:(p)=>_load(page:p)))])),floatingActionButton:FloatingActionButton.extended(onPressed:()=>context.push('/assets/scan'),icon:const Icon(Icons.qr_code_scanner),label:const Text('Scan')));
}

class AssetDetailScreen extends ConsumerStatefulWidget{const AssetDetailScreen(this.uuid,{super.key});final String uuid;@override ConsumerState<AssetDetailScreen> createState()=>_AssetDetailState();}
class _AssetDetailState extends ConsumerState<AssetDetailScreen>{AssetSummary? asset;Object? error;@override void initState(){super.initState();_load();}Future<void>_load()async{try{final v=await ref.read(assetRepositoryProvider).detail(widget.uuid);if(mounted)setState(()=>asset=v);}catch(e){if(mounted)setState(()=>error=e);}}@override Widget build(BuildContext c){if(error!=null)return ErrorPanel(ApiError.messageFor(error),onRetry:_load);if(asset==null)return const LoadingPanel();final a=asset!;return RefreshIndicator(onRefresh:_load,child:ListView(padding:const EdgeInsets.all(16),children:[Text(a.code,style:Theme.of(c).textTheme.labelLarge),Text(a.name,style:Theme.of(c).textTheme.headlineSmall),const SizedBox(height:12),StatusBadge(a.status),const SizedBox(height:20),DetailSection(title:'Asset details',children:[LabeledValue('Category',a.category??'Not specified'),LabeledValue('Location',a.location??'Not specified'),LabeledValue('Next maintenance',a.nextDueDate==null?'Not scheduled':DateFormat.yMMMd().format(a.nextDueDate!.toLocal()))]),const DetailSection(title:'Maintenance summary',children:[Text('Open Maintenance for full schedule and review history.')])])) ;}}

class AssetScannerScreen extends ConsumerStatefulWidget{const AssetScannerScreen({super.key});@override ConsumerState<AssetScannerScreen>createState()=>_ScannerState();}
class _ScannerState extends ConsumerState<AssetScannerScreen>{final controller=MobileScannerController();bool handling=false;String? message;
 Future<void>_capture(BarcodeCapture capture)async{if(handling)return;final raw=capture.barcodes.firstOrNull?.rawValue;final identifier=QrPayloadParser.assetIdentifier(raw);if(identifier==null){setState(()=>message='This QR code is not a supported SNSU FMO asset code.');return;}setState(()=>handling=true);try{final asset=await ref.read(assetRepositoryProvider).lookup(identifier);if(mounted)context.go('/assets/${asset.uuid}');}catch(e){if(mounted)setState(()=>message=ApiError.messageFor(e));}finally{await Future<void>.delayed(const Duration(seconds:2));if(mounted)setState(()=>handling=false);}}
 @override void dispose(){unawaited(controller.dispose());super.dispose();}
 @override Widget build(BuildContext c)=>Scaffold(appBar:AppBar(title:const Text('Scan asset QR')),body:Column(children:[Expanded(child:MobileScanner(controller:controller,onDetect:_capture,errorBuilder:(c,e,_)=>ErrorPanel(_cameraMessage(e),onRetry:()=>controller.start()))),if(message!=null)MaterialBanner(content:Text(message!),actions:[TextButton(onPressed:()=>setState(()=>message=null),child:const Text('Dismiss'))]),const Padding(padding:EdgeInsets.all(16),child:Text('Place the SNSU FMO asset QR code inside the camera view.',textAlign:TextAlign.center))]));
 String _cameraMessage(MobileScannerException e){final code=e.errorCode.name;return code=='permissionDenied'?'Camera access was denied. Enable camera access in system settings.':code=='unsupported'?'Camera scanning is not supported on this device.':'The camera is unavailable. Check permission and try again.';}}
