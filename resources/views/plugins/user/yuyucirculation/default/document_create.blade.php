{{-- 決裁申請 新規作成 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")
<div class="card">
    <div class="card-header font-weight-bold">決裁申請</div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{$error}}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" enctype="multipart/form-data" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentSubmit/{{$page->id}}/{{$frame_id}}">
            @csrf

            <div class="form-group">
                <label for="circulation-template-{{$frame_id}}">決裁テンプレート <span class="badge badge-danger">必須</span></label>
                <select class="form-control" id="circulation-template-{{$frame_id}}" name="template_id" required>
                    <option value="">選択してください</option>
                    @foreach ($templates as $template)
                        <option value="{{$template->id}}" {{(string)old('template_id') === (string)$template->id ? 'selected' : ''}}>
                            {{$template->name}}
                        </option>
                    @endforeach
                </select>
                @if ($templates->isEmpty())
                    <small class="form-text text-danger">利用可能な決裁テンプレートがありません。</small>
                @endif
            </div>

            <div class="form-group">
                <label for="circulation-title-{{$frame_id}}">件名 <span class="badge badge-danger">必須</span></label>
                <input type="text" class="form-control" id="circulation-title-{{$frame_id}}" name="title" value="{{old('title')}}" maxlength="255" required>
            </div>

            <div class="form-group">
                <label for="circulation-body-{{$frame_id}}">内容</label>
                <textarea class="form-control" id="circulation-body-{{$frame_id}}" name="body" rows="8">{{old('body')}}</textarea>
            </div>

            <div class="form-group">
                <label for="circulation-attachments-{{$frame_id}}">添付ファイル</label>
                <input type="file" class="form-control-file" id="circulation-attachments-{{$frame_id}}" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <small class="form-text text-muted">PDF、Word、Excel、JPEG、PNG。1ファイル10MBまで、最大5ファイル。</small>
            </div>

            <div class="text-center">
                <a class="btn btn-secondary mr-2" href="{{url('/')}}/plugin/yuyucirculation/index/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">戻る</a>
                <button type="submit" class="btn btn-primary" @if($templates->isEmpty()) disabled @endif>
                    <i class="fas fa-paper-plane"></i> 申請する
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
