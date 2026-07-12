import 'dart:typed_data';

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../app/providers.dart';
import '../core/network/api_error.dart';
import 'widgets.dart';

class SecureAttachmentScreen extends ConsumerStatefulWidget {
  const SecureAttachmentScreen({required this.apiPath, required this.fileName, required this.mimeType, super.key});
  final String apiPath;
  final String fileName;
  final String? mimeType;
  @override ConsumerState<SecureAttachmentScreen> createState() => _SecureAttachmentState();
}

class _SecureAttachmentState extends ConsumerState<SecureAttachmentScreen> {
  Uint8List? bytes; Object? error; double? progress;
  @override void initState(){super.initState();_load();}
  Future<void> _load() async {setState((){error=null;progress=0;});try{final data=await ref.read(apiClientProvider).download(widget.apiPath,onProgress:(a,b){if(mounted&&b>0)setState(()=>progress=a/b);});if(mounted)setState(()=>bytes=Uint8List.fromList(data));}catch(e){if(mounted)setState(()=>error=e);}}
  @override Widget build(BuildContext context)=>Scaffold(appBar:AppBar(title:Text(widget.fileName)),body:error!=null?ErrorPanel(ApiError.messageFor(error),onRetry:_load):bytes==null?Center(child:CircularProgressIndicator(value:progress)):widget.mimeType?.startsWith('image/')??false?InteractiveViewer(child:Center(child:Image.memory(bytes!,gaplessPlayback:true,errorBuilder:(_,__,___)=>const EmptyPanel('This image could not be displayed.')))):const EmptyPanel('The attachment was downloaded securely. PDF handoff requires platform file support and is deferred from this foundation.'));
}
