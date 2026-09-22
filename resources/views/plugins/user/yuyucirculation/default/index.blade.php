{{-- 回覧・決裁 ダッシュボード --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")
@if (!isset($frame) || !$frame->bucket_id || empty($circulation->id))
    @can('frames.edit', [[null, null, null, $frame]])
        <div class="card border-danger">
            <div class="card-body text-center">
                フレームの設定画面から、使用する回覧・決裁を選択するか、新規作成してください。
            </div>
        </div>
    @endcan
@else
    @if(($plugin_frame->view_format ?: 'dashboard') === 'notification')
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold"><i class="fas fa-bell mr-1"></i> 回覧・決裁の新着情報</span>
                @if($unread_notification_count > 0)
                    <span class="badge badge-danger">未読 {{$unread_notification_count}}件</span>
                @endif
            </div>
            <div class="list-group list-group-flush">
                @guest
                    <div class="list-group-item text-muted">新着情報を表示するにはログインしてください。</div>
                @else
                    @forelse($notifications as $notification)
                        <a class="list-group-item list-group-item-action @if(is_null($notification->read_at)) font-weight-bold @endif"
                           href="{{$notification->detail_url ?: url('/') . '/plugin/yuyucirculation/documentShow/' . $page->id . '/' . $frame_id . '/' . $notification->document_id . '#frame-' . $frame_id}}">
                            <div class="d-flex justify-content-between align-items-start">
                                <span>
                                    @if(is_null($notification->read_at))<span class="badge badge-danger mr-1">新着</span>@endif
                                    <span class="badge badge-{{[
                                        'approval_requested' => 'warning',
                                        'circulation_received' => 'info',
                                        'returned' => 'danger',
                                        'decision_completed' => 'success',
                                    ][$notification->notification_type] ?? 'secondary'}} mr-1">{{[
                                        'approval_requested' => '承認依頼',
                                        'circulation_received' => '回覧受信',
                                        'returned' => '差戻し',
                                        'decision_completed' => '決裁完了',
                                    ][$notification->notification_type] ?? '通知'}}</span>
                                    {{$notification->title}}
                                </span>
                                <small class="text-muted text-nowrap ml-2">{{$notification->created_at ? $notification->created_at->format('Y/m/d H:i') : ''}}</small>
                            </div>
                            <small class="text-muted d-block mt-1">{{$notification->message}}</small>
                        </a>
                    @empty
                        <div class="list-group-item text-muted">新着情報はありません。</div>
                    @endforelse
                @endguest
            </div>
        </div>
    @else
    <div class="row mb-3">
        <div class="col-md-6 mb-3 mb-md-0">
            <div class="card h-100">
                <div class="card-header font-weight-bold">自分が対応するもの</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-6 border-right">
                            <div class="h4 mb-0">{{$received_documents->count()}}</div>
                            <small>承認待ち</small>
                        </div>
                        <div class="col-6">
                            <div class="h4 mb-0">{{$circulation_documents->count()}}</div>
                            <small>回覧未確認</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header font-weight-bold">自分が申請・発信したもの</div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4 border-right"><div class="h4 mb-0">{{$sent_status_counts['in_progress']}}</div><small>決裁中</small></div>
                        <div class="col-4 border-right"><div class="h4 mb-0">{{$sent_status_counts['in_circulation']}}</div><small>回覧中</small></div>
                        <div class="col-4"><div class="h4 mb-0">{{$sent_status_counts['completed']}}</div><small>完了</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end mb-3">
        @auth
            <a class="btn btn-info mr-2" href="{{url('/')}}/plugin/yuyucirculation/circulationCreate/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}"><i class="fas fa-share-square"></i> 新規回覧</a>
            <a class="btn btn-primary mr-2" href="{{url('/')}}/plugin/yuyucirculation/documentCreate/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
                <i class="fas fa-file-signature"></i> 新規申請
            </a>
        @endauth
        @can('frames.edit', [[null, null, null, $frame]])

            <a class="btn btn-outline-primary" href="{{url('/')}}/plugin/yuyucirculation/templateList/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
                <i class="fas fa-route"></i> 決裁テンプレート管理
            </a>
        @endcan
    </div>

    <div class="card mb-3">
        <div class="card-header font-weight-bold">自分が対応する案件</div>
        <div class="card-body p-0">
            @if ($received_documents->isEmpty() && $circulation_documents->isEmpty())
                <div class="p-3 text-muted">現在、対応が必要な案件はありません。</div>
            @elseif ($received_documents->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>件名</th><th>状態</th><th>申請日時</th></tr></thead>
                        <tbody>
                        @foreach($received_documents as $document)
                            <tr>
                                <td><a href="{{url('/')}}/plugin/yuyucirculation/documentShow/{{$page->id}}/{{$frame_id}}/{{$document->id}}">{{$document->title}}</a></td>
                                <td>{{['submitted' => '承認待ち', 'in_approval' => '承認・決裁中'][$document->status] ?? $document->status}}</td>
                                <td>{{$document->submitted_at ? $document->submitted_at->format('Y/m/d H:i') : ''}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
            @if($circulation_documents->isNotEmpty())
                <div class="border-top">
                    @foreach($circulation_documents as $document)
                        <div class="p-3 border-bottom">
                            <span class="badge badge-info mr-2">{{$document->document_type === 'circulation' ? '回覧' : '決裁後回覧'}}</span>
                            <a href="{{url('/')}}/plugin/yuyucirculation/documentShow/{{$page->id}}/{{$frame_id}}/{{$document->id}}">{{$document->title}}</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header font-weight-bold">自分が申請・発信した案件</div>
        <div class="card-body p-0">
            @if ($sent_documents->isEmpty())
                <div class="p-3 text-muted">まだ申請・発信した案件はありません。</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>件名</th><th>種別</th><th>状態</th><th>作成日</th><th></th></tr></thead>
                        <tbody>
                        @foreach($sent_documents as $document)
                            <tr>
                                <td><a href="{{url('/')}}/plugin/yuyucirculation/documentShow/{{$page->id}}/{{$frame_id}}/{{$document->id}}">{{$document->title}}</a></td>
                                <td>{{[
                                    'approval' => '決裁申請',
                                    'circulation' => '回覧',
                                ][$document->document_type] ?? $document->document_type}}</td>
                                <td>{{[
                                    'draft' => '下書き',
                                    'submitted' => '申請済み',
                                    'in_approval' => '承認・決裁中',
                                    'returned' => '差戻し',
                                    'rejected' => '却下',
                                    'decided' => '決裁済み',
                                    'in_circulation' => '回覧中',
                                    'completed' => '完了',
                                    'cancelled' => '取下げ',
                                ][$document->status] ?? $document->status}}</td>
                                <td>{{$document->created_at ? $document->created_at->format('Y/m/d H:i') : ''}}</td>
                                <td class="text-right text-nowrap">
                                    @if(in_array($document->status, ['submitted', 'in_approval'], true))
                                        <form method="POST" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentCancel/{{$page->id}}/{{$frame_id}}/{{$document->id}}" class="d-inline" onsubmit="return confirm('この申請を取り消します。よろしいですか？');">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-danger">申請取消</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-header font-weight-bold">自分の処理済み履歴</div>
        <div class="card-body p-0">
            @if ($processed_documents->isEmpty())
                <div class="p-3 text-muted">処理済みの案件はありません。</div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>件名</th><th>種別</th><th>現在の状態</th><th>更新日</th></tr></thead>
                        <tbody>
                        @foreach($processed_documents as $document)
                            <tr>
                                <td><a href="{{url('/')}}/plugin/yuyucirculation/documentShow/{{$page->id}}/{{$frame_id}}/{{$document->id}}">{{$document->title}}</a></td>
                                <td>{{[
                                    'approval' => '決裁申請',
                                    'circulation' => '回覧',
                                ][$document->document_type] ?? $document->document_type}}</td>
                                <td>{{[
                                    'submitted' => '申請済み',
                                    'in_approval' => '承認・決裁中',
                                    'returned' => '差戻し',
                                    'decided' => '決裁済み',
                                    'in_circulation' => '回覧中',
                                    'completed' => '完了',
                                    'cancelled' => '取下げ',
                                ][$document->status] ?? $document->status}}</td>
                                <td>{{$document->updated_at ? $document->updated_at->format('Y/m/d H:i') : ''}}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
    @endif
@endif
@endsection
