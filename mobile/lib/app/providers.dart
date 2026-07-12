import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../core/config/app_config.dart';
import '../core/network/api_client.dart';
import '../core/storage/token_storage.dart';
import '../data/models/dashboard.dart';
import '../data/models/system_info.dart';
import '../data/models/user.dart';
import '../data/repositories/repositories.dart';

final configProvider=Provider((_)=>AppConfig.fromEnvironment());
final tokenStorageProvider=Provider<TokenStorage>((_)=>SecureTokenStorage());
final apiClientProvider=Provider<ApiClient>((ref)=>ApiClient(ref.watch(configProvider),ref.watch(tokenStorageProvider),onUnauthorized:()=>ref.read(authControllerProvider.notifier).expired()));
final authRepositoryProvider=Provider((ref)=>AuthRepository(ref.watch(apiClientProvider)));
final systemRepositoryProvider=Provider((ref)=>SystemRepository(ref.watch(apiClientProvider)));
final dashboardRepositoryProvider=Provider((ref)=>DashboardRepository(ref.watch(apiClientProvider)));
final workOrderRepositoryProvider=Provider((ref)=>WorkOrderRepository(ref.watch(apiClientProvider)));
final assetRepositoryProvider=Provider((ref)=>AssetRepository(ref.watch(apiClientProvider)));
final maintenanceRepositoryProvider=Provider((ref)=>MaintenanceRepository(ref.watch(apiClientProvider)));
final notificationRepositoryProvider=Provider((ref)=>NotificationRepository(ref.watch(apiClientProvider)));
final profileRepositoryProvider=Provider((ref)=>ProfileRepository(ref.watch(apiClientProvider)));
final referenceRepositoryProvider=Provider((ref)=>ReferenceRepository(ref.watch(apiClientProvider)));

enum AuthPhase{starting,authenticated,unauthenticated,onlineRequired,maintenance}
class AuthState {const AuthState(this.phase,{this.user,this.systemInfo,this.message});final AuthPhase phase;final AppUser? user;final SystemInfo? systemInfo;final String? message;}
class AuthController extends StateNotifier<AuthState>{AuthController(this.ref):super(const AuthState(AuthPhase.starting)){restore();}final Ref ref;
 Future<void> restore()async{final token=await ref.read(tokenStorageProvider).read();if(token==null){state=const AuthState(AuthPhase.unauthenticated);return;}try{final info=await ref.read(systemRepositoryProvider).info();if(info.maintenanceMode){state=AuthState(AuthPhase.maintenance,systemInfo:info);return;}final user=await ref.read(authRepositoryProvider).currentUser();state=AuthState(AuthPhase.authenticated,user:user,systemInfo:info);}catch(_){if(state.phase!=AuthPhase.unauthenticated)state=const AuthState(AuthPhase.onlineRequired,message:'Server validation is required. Check your connection and retry.');}}
 Future<void> login(String email,String password)async{state=const AuthState(AuthPhase.starting);try{final result=await ref.read(authRepositoryProvider).login(email,password);await ref.read(tokenStorageProvider).write(result.$1);final info=await ref.read(systemRepositoryProvider).info();if(info.maintenanceMode){state=AuthState(AuthPhase.maintenance,systemInfo:info);return;}state=AuthState(AuthPhase.authenticated,user:result.$2,systemInfo:info);}catch(e){await ref.read(tokenStorageProvider).clear();state=const AuthState(AuthPhase.unauthenticated);rethrow;}}
 Future<void> logout({bool all=false})async{try{if(all)await ref.read(authRepositoryProvider).logoutAll();else await ref.read(authRepositoryProvider).logout();}finally{await ref.read(tokenStorageProvider).clear();state=const AuthState(AuthPhase.unauthenticated);}}
 void expired(){ref.read(tokenStorageProvider).clear();state=const AuthState(AuthPhase.unauthenticated,message:'Your session has expired.');}}
final authControllerProvider=StateNotifierProvider<AuthController,AuthState>((ref)=>AuthController(ref));
final dashboardProvider=FutureProvider<DashboardSummary>((ref)=>ref.watch(dashboardRepositoryProvider).load());
final unreadCountProvider=FutureProvider<int>((ref)=>ref.watch(notificationRepositoryProvider).unreadCount());
