{{-- 通常回覧 新規作成 --}}
@extends('core.cms_frame_base')
@section("plugin_contents_$frame->id")
@php
 $target_rows = old('targets', [[
  'target_type' => 'applicant_section',
  'section_id' => null,
  'group_id' => null,
  'user_id' => null,
 ]]);
@endphp
<div class="card">
 <div class="card-header font-weight-bold">新規回覧</div>
 <div class="card-body">
  @if($errors->any())<div class="alert alert-danger"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>@endif
  <form method="POST" enctype="multipart/form-data" action="{{url('/')}}/redirect/plugin/yuyucirculation/circulationSubmit/{{$page->id}}/{{$frame_id}}">
   @csrf
   <div class="form-group"><label>件名 <span class="badge badge-danger">必須</span></label><input class="form-control" name="title" value="{{old('title')}}" maxlength="255" required></div>
   <div class="form-group"><label>内容</label><textarea class="form-control" name="body" rows="8">{{old('body')}}</textarea></div>
   <div class="form-group">
    <div class="d-flex justify-content-between align-items-center mb-2">
     <label class="mb-0">回覧先 <span class="badge badge-danger">必須</span></label>
     <button type="button" class="btn btn-sm btn-outline-primary" id="add-circulation-target-{{$frame_id}}"><i class="fas fa-plus"></i> 回覧先追加</button>
    </div>
    <div id="circulation-target-list-{{$frame_id}}">
     @foreach($target_rows as $index => $target)
     <div class="circulation-target-row border rounded p-3 mb-2">
      <div class="d-flex justify-content-between align-items-center mb-2">
       <strong class="circulation-target-caption">回覧先 {{$index + 1}}</strong>
       <button type="button" class="btn btn-sm btn-outline-danger remove-circulation-target"><i class="fas fa-times"></i> 削除</button>
      </div>
      <div class="form-row">
       <div class="form-group col-md-4 mb-2">
        <label>種類</label>
        <select class="form-control circulation-target-type" name="targets[{{$index}}][target_type]" required>
         <option value="applicant_section" {{($target['target_type'] ?? '')==='applicant_section'?'selected':''}}>自分と同じ所属</option>
         <option value="fixed_section" {{($target['target_type'] ?? '')==='fixed_section'?'selected':''}}>所属を指定</option>
         <option value="fixed_group" {{($target['target_type'] ?? '')==='fixed_group'?'selected':''}}>グループを指定</option>
         <option value="fixed_user" {{($target['target_type'] ?? '')==='fixed_user'?'selected':''}}>ユーザーを指定</option>
        </select>
       </div>
       <div class="form-group col-md-8 mb-2 circulation-target-specific circulation-target-section">
        <label>所属</label>
        <select class="form-control" name="targets[{{$index}}][section_id]"><option value="">所属を選択</option>@foreach($sections as $section)<option value="{{$section->id}}" {{(string)($target['section_id'] ?? '')===(string)$section->id?'selected':''}}>{{$section->name}}</option>@endforeach</select>
       </div>
       <div class="form-group col-md-8 mb-2 circulation-target-specific circulation-target-group">
        <label>グループ</label>
        <select class="form-control" name="targets[{{$index}}][group_id]"><option value="">グループを選択</option>@foreach($groups as $group)<option value="{{$group->id}}" {{(string)($target['group_id'] ?? '')===(string)$group->id?'selected':''}}>{{$group->name}}</option>@endforeach</select>
       </div>
       <div class="form-group col-md-8 mb-2 circulation-target-specific circulation-target-user">
        <label>ユーザー</label>
        <select class="form-control" name="targets[{{$index}}][user_id]"><option value="">ユーザーを選択</option>@foreach($users as $user)<option value="{{$user->id}}" {{(string)($target['user_id'] ?? '')===(string)$user->id?'selected':''}}>{{$user->name}}</option>@endforeach</select>
       </div>
      </div>
     </div>
     @endforeach
    </div>
    <small class="form-text text-muted">所属・グループ・ユーザーを組み合わせて複数指定できます。同じユーザーは自動的に1人へまとめられます。</small>
   </div>
   <div class="form-group">
    <label>回答方式 <span class="badge badge-danger">必須</span></label>
    <select class="form-control" name="response_type" id="circulation-response-type-{{$frame_id}}" onchange="circulationResponseChanged{{$frame_id}}()" required>
     <option value="confirm" {{old('response_type','confirm')==='confirm'?'selected':''}}>確認のみ</option>
     <option value="choice" {{old('response_type')==='choice'?'selected':''}}>選択肢で回答</option>
     <option value="text" {{old('response_type')==='text'?'selected':''}}>テキストで回答</option>
    </select>
   </div>
   <div class="form-group">
    <label>回答の公開範囲 <span class="badge badge-danger">必須</span></label>
    <div class="form-check"><input class="form-check-input" type="radio" name="response_visibility" id="response-visibility-targets-{{$frame_id}}" value="targets" {{old('response_visibility','targets')==='targets'?'checked':''}}><label class="form-check-label" for="response-visibility-targets-{{$frame_id}}">回覧対象者に公開</label></div>
    <small class="form-text text-muted mb-2">発信者と回覧対象者が、全員の確認・回答状況を閲覧できます。</small>
    <div class="form-check"><input class="form-check-input" type="radio" name="response_visibility" id="response-visibility-sender-{{$frame_id}}" value="sender_only" {{old('response_visibility')==='sender_only'?'checked':''}}><label class="form-check-label" for="response-visibility-sender-{{$frame_id}}">発信者のみ</label></div>
    <small class="form-text text-muted">発信者だけが全員の回答を閲覧できます。回答者は自分の回答だけ確認できます。</small>
   </div>
   <div id="circulation-question-{{$frame_id}}" class="d-none">
    <div class="form-group"><label>回答を求める内容 <span class="badge badge-danger">必須</span></label><textarea class="form-control" name="question_text" rows="3">{{old('question_text')}}</textarea></div>
   </div>
   <div id="circulation-choices-{{$frame_id}}" class="d-none">
    <div class="form-group"><label>選択肢</label>
     @for($i=0;$i<5;$i++)<input type="text" class="form-control mb-2" name="choices[]" value="{{old('choices.'.$i)}}" placeholder="選択肢 {{$i+1}}">@endfor
     <small class="form-text text-muted">2件以上入力してください。空欄は無視されます。</small>
    </div>
   </div>
   <div class="form-group"><label>添付ファイル</label><input type="file" class="form-control-file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"><small class="form-text text-muted">1ファイル10MBまで、最大5ファイル。</small></div>
   <div class="text-center"><a class="btn btn-secondary mr-2" href="{{url('/')}}{{$page->permanent_link}}#frame-{{$frame_id}}">戻る</a><button class="btn btn-primary" type="submit"><i class="fas fa-share-square"></i> 回覧する</button></div>
  </form>
 </div>
</div>
<script>
function refreshCirculationTarget{{$frame_id}}(row){
 var t=row.querySelector('.circulation-target-type').value;
 row.querySelectorAll('.circulation-target-specific').forEach(function(el){el.classList.add('d-none');});
 if(t==='fixed_section')row.querySelector('.circulation-target-section').classList.remove('d-none');
 if(t==='fixed_group')row.querySelector('.circulation-target-group').classList.remove('d-none');
 if(t==='fixed_user')row.querySelector('.circulation-target-user').classList.remove('d-none');
}
function renumberCirculationTargets{{$frame_id}}(){
 document.querySelectorAll('#circulation-target-list-{{$frame_id}} .circulation-target-row').forEach(function(row,index){
  row.querySelector('.circulation-target-caption').textContent='回覧先 '+(index+1);
  row.querySelectorAll('input,select,textarea').forEach(function(el){
   if(el.name)el.name=el.name.replace(/^targets\[\d+\]/,'targets['+index+']');
  });
 });
}
function circulationResponseChanged{{$frame_id}}(){
 var t=document.getElementById('circulation-response-type-{{$frame_id}}').value;
 document.getElementById('circulation-question-{{$frame_id}}').classList.toggle('d-none',t==='confirm');
 document.getElementById('circulation-choices-{{$frame_id}}').classList.toggle('d-none',t!=='choice');
}
(function(){
 var list=document.getElementById('circulation-target-list-{{$frame_id}}');
 list.querySelectorAll('.circulation-target-row').forEach(function(row){refreshCirculationTarget{{$frame_id}}(row);});
 list.addEventListener('change',function(event){if(event.target.classList.contains('circulation-target-type'))refreshCirculationTarget{{$frame_id}}(event.target.closest('.circulation-target-row'));});
 document.getElementById('add-circulation-target-{{$frame_id}}').addEventListener('click',function(){
  var row=list.querySelector('.circulation-target-row').cloneNode(true);
  row.querySelectorAll('select').forEach(function(el){el.selectedIndex=0;});
  row.querySelector('.circulation-target-type').value='applicant_section';
  list.appendChild(row);
  renumberCirculationTargets{{$frame_id}}();
  refreshCirculationTarget{{$frame_id}}(row);
 });
 list.addEventListener('click',function(event){
  var button=event.target.closest('.remove-circulation-target');
  if(!button)return;
  var rows=list.querySelectorAll('.circulation-target-row');
  if(rows.length<=1)return;
  button.closest('.circulation-target-row').remove();
  renumberCirculationTargets{{$frame_id}}();
 });
 circulationResponseChanged{{$frame_id}}();
})();
</script>
@endsection
