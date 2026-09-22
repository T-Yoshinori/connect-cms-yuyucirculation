{{-- 回覧・決裁 申請詳細 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")
@php
    $status_labels = [
        'draft' => '下書き', 'submitted' => '申請済み', 'in_approval' => '承認・決裁中',
        'returned' => '差戻し', 'rejected' => '却下', 'decided' => '決裁済み',
        'in_circulation' => '回覧中', 'completed' => '完了', 'cancelled' => '取下げ',
    ];
    $step_status_labels = [
        'pending' => '承認待ち', 'approved' => '承認済み', 'decided' => '決裁済み',
        'returned' => '差戻し', 'rejected' => '却下', 'skipped' => '省略',
        'cancelled' => '取消',
    ];
    $action_labels = ['approval' => '承認', 'decision' => '決裁'];
@endphp

<div class="card mb-3">
    <div class="card-header font-weight-bold">申請詳細</div>
    <div class="card-body">
        <dl class="row mb-0">
            <dt class="col-sm-3">件名</dt><dd class="col-sm-9">{{$document->title}}</dd>
            <dt class="col-sm-3">申請者</dt><dd class="col-sm-9">{{optional($users->get($document->applicant_user_id))->name ?? '－'}}</dd>
            <dt class="col-sm-3">申請日時</dt><dd class="col-sm-9">{{$document->submitted_at ? $document->submitted_at->format('Y/m/d H:i') : '－'}}</dd>
            <dt class="col-sm-3">状態</dt><dd class="col-sm-9">{{$status_labels[$document->status] ?? $document->status}}</dd>
            <dt class="col-sm-3">内容</dt>
            <dd class="col-sm-9">{!! nl2br(e($document->body ?? '')) !!}</dd>
        </dl>
        @if($document->files->isNotEmpty())
            <hr>
            <div class="font-weight-bold mb-2">添付ファイル</div>
            @foreach($document->files as $file)
                <div><a href="{{url('/')}}/plugin/yuyucirculation/documentFileDownload/{{$page->id}}/{{$frame_id}}/{{$file->id}}"><i class="fas fa-paperclip"></i> {{$file->original_name}}</a></div>
            @endforeach
        @endif
    </div>
</div>

@if($document->document_type !== 'circulation')
<div class="card mb-3">
    <div class="card-header font-weight-bold">承認・決裁ルート</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>STEP</th><th>処理</th><th>担当者</th><th>状態</th></tr></thead>
                <tbody>
                @foreach($document->steps as $step)
                    <tr>
                        <td>{{$step->step_no}}</td>
                        <td>{{$action_labels[$step->action_type] ?? $step->action_type}}</td>
                        <td>{{optional($users->get($step->approver_user_id))->name ?? '－'}}</td>
                        <td>
                            {{$step_status_labels[$step->status] ?? $step->status}}
                            @if(!empty($step->comment))
                                <div class="small text-muted mt-1">{!! nl2br(e($step->comment)) !!}</div>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($document->histories->isNotEmpty())
<div class="card mb-3">
    <div class="card-header font-weight-bold">処理履歴</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>日時</th><th>処理者</th><th>処理</th><th>コメント</th></tr></thead>
                <tbody>
                @foreach($document->histories as $history)
                    <tr>
                        <td>{{$history->created_at ? $history->created_at->format('Y/m/d H:i') : '－'}}</td>
                        <td>{{$history->created_name ?? '－'}}</td>
                        <td>{{['approved'=>'承認','decided'=>'決裁','returned'=>'差戻し','resubmitted'=>'再申請','cancelled'=>'取下げ','confirmed'=>'回覧確認','answered'=>'回覧回答'][$history->action] ?? $history->action}}</td>
                        <td>{!! nl2br(e($history->comment ?? '')) !!}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@if($document->status === 'in_circulation')
<div class="card mb-3">
    <div class="card-header font-weight-bold">{{$document->document_type === 'circulation' ? '回覧確認' : '決裁後回覧'}}</div>
    <div class="card-body">
        <div class="mb-2">{{$document->document_type === 'circulation' ? '回覧内容を確認してください。全対象者の確認完了後に回覧完了となります。' : '決裁済みです。回覧対象者の確認完了後に案件完了となります。'}}</div>
        <div class="mb-3">確認済み {{$document->targets->where('status', 'confirmed')->count()}} / {{$document->targets->count()}}</div>
        @php $question = $document->questions->first(); @endphp
        @if($current_target && $current_target->status === 'waiting')
            @if($document->document_type === 'circulation' && $question && in_array($question->question_type, ['choice','text'], true))
                <div class="border rounded p-3">
                    <div class="font-weight-bold mb-2">{{$question->question_text}}</div>
                    <form method="POST" action="{{url('/')}}/redirect/plugin/yuyucirculation/circulationAnswer/{{$page->id}}/{{$frame_id}}/{{$document->id}}">
                        @csrf
                        @if($question->question_type === 'choice')
                            @foreach($question->choices as $choice)
                                <div class="form-check mb-2"><input class="form-check-input" type="radio" name="answer" id="choice-{{$choice->id}}" value="{{$choice->choice_text}}" required><label class="form-check-label" for="choice-{{$choice->id}}">{{$choice->choice_text}}</label></div>
                            @endforeach
                        @else
                            <textarea class="form-control mb-3" name="answer" rows="4" maxlength="4000" required>{{old('answer')}}</textarea>
                        @endif
                        <div class="text-center"><button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> 回答する</button></div>
                    </form>
                </div>
            @else
                <form method="POST" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentConfirm/{{$page->id}}/{{$frame_id}}/{{$document->id}}" class="text-center" onsubmit="return confirm('この回覧を確認済みにします。よろしいですか？');">
                    @csrf
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> 確認しました</button>
                </form>
            @endif
        @elseif($current_target && $current_target->status === 'confirmed')
            <div class="alert alert-success mb-0">この回覧は確認済みです。</div>
        @endif
    </div>
</div>
@endif

@php
$responseStatusVisible = $document->document_type === 'circulation'
    && ($document->applicant_user_id === auth()->id()
        || ($document->response_visibility === 'targets' && $document->targets->contains('user_id', auth()->id())));
@endphp
@if($responseStatusVisible)
@php $responseQuestion=$document->questions->first(); $answerMap=$document->answers->keyBy('user_id'); @endphp
<div class="card mb-3">
 <div class="card-header font-weight-bold">{{$responseQuestion ? '回答状況' : '確認状況'}}</div>
 <div class="card-body p-0">
  @if($responseQuestion)<div class="p-3 border-bottom"><strong>{{$responseQuestion->question_text}}</strong></div>@endif
  <div class="table-responsive"><table class="table mb-0"><thead><tr><th>対象者</th><th>状態</th><th>回答</th></tr></thead><tbody>
  @foreach($document->targets as $target)
   @php $answer=$answerMap->get($target->user_id); @endphp
   <tr><td>{{optional($users->get($target->user_id))->name ?? '－'}}</td><td>{{$target->status === 'confirmed' ? ($responseQuestion ? '回答済み' : '確認済み') : ($responseQuestion ? '未回答' : '未確認')}}</td><td>@if($responseQuestion){!! $answer ? nl2br(e($answer->answer)) : '－' !!}@else－@endif</td></tr>
  @endforeach
  </tbody></table></div>
 </div>
</div>
@endif

@if($current_step)
<div class="card mb-3">
    <div class="card-header font-weight-bold">承認・決裁処理</div>
    <div class="card-body">
        @if($errors->any())
            <div class="alert alert-danger">
                @foreach($errors->all() as $error)
                    <div>{{$error}}</div>
                @endforeach
            </div>
        @endif
        <div class="form-group">
            <label for="circulation-comment-{{$frame_id}}">コメント</label>
            <textarea id="circulation-comment-{{$frame_id}}" class="form-control" rows="3" maxlength="2000" placeholder="承認時は任意、差戻し時は必須です。"></textarea>
        </div>
        <div class="d-flex justify-content-center">
            <form method="POST" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentApprove/{{$page->id}}/{{$frame_id}}/{{$document->id}}" class="mr-2" onsubmit="this.querySelector('[name=comment]').value=document.getElementById('circulation-comment-{{$frame_id}}').value; return confirm('この申請を承認します。よろしいですか？');">
                @csrf
                <input type="hidden" name="comment">
                <button type="submit" class="btn btn-primary">{{$current_step->action_type === 'decision' ? '決裁' : '承認'}}</button>
            </form>
            <form method="POST" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentReturn/{{$page->id}}/{{$frame_id}}/{{$document->id}}" onsubmit="this.querySelector('[name=comment]').value=document.getElementById('circulation-comment-{{$frame_id}}').value; if (!this.querySelector('[name=comment]').value.trim()) { alert('差戻し理由を入力してください。'); return false; } return confirm('この申請を申請者へ差し戻します。よろしいですか？');">
                @csrf
                <input type="hidden" name="comment">
                <button type="submit" class="btn btn-outline-danger">差戻し</button>
            </form>
        </div>
    </div>
</div>
@endif

<div class="text-center">
    @if($document->status === 'returned' && $document->applicant_user_id === auth()->id())
        <a href="{{url('/')}}/plugin/yuyucirculation/documentEdit/{{$page->id}}/{{$frame_id}}/{{$document->id}}" class="btn btn-primary mr-2"><i class="fas fa-edit"></i> 修正して再申請</a>
    @endif
    <a href="{{url('/')}}{{$page->permanent_link}}#frame-{{$frame_id}}" class="btn btn-secondary">一覧へ戻る</a>
</div>
@endsection
