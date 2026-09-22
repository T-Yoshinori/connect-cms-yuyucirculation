{{-- 差戻し申請 修正・再申請 --}}
@extends('core.cms_frame_base')

@section("plugin_contents_$frame->id")
<div class="card">
    <div class="card-header font-weight-bold">差戻し申請の修正・再申請</div>
    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{$error}}</li>@endforeach</ul></div>
        @endif
        <form method="POST" enctype="multipart/form-data" action="{{url('/')}}/redirect/plugin/yuyucirculation/documentResubmit/{{$page->id}}/{{$frame_id}}/{{$document->id}}">
            @csrf
            <div class="form-group">
                <label>件名 <span class="badge badge-danger">必須</span></label>
                <input type="text" class="form-control" name="title" value="{{old('title', $document->title)}}" maxlength="255" required>
            </div>
            <div class="form-group">
                <label>内容</label>
                <textarea class="form-control" name="body" rows="8">{{old('body', $document->body)}}</textarea>
            </div>
            @if($document->files->isNotEmpty())
            <div class="form-group">
                <label>現在の添付ファイル</label>
                @foreach($document->files as $file)
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="remove_files[]" value="{{$file->id}}" id="remove-file-{{$file->id}}">
                        <label class="form-check-label" for="remove-file-{{$file->id}}">{{$file->original_name}} <span class="text-muted">（チェックすると再申請時に削除）</span></label>
                    </div>
                @endforeach
            </div>
            @endif
            <div class="form-group">
                <label>添付ファイルを追加</label>
                <input type="file" class="form-control-file" name="attachments[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                <small class="form-text text-muted">PDF、Word、Excel、JPEG、PNG。1ファイル10MBまで、最大5ファイル。</small>
            </div>
            <div class="text-center">
                <a class="btn btn-secondary mr-2" href="{{url('/')}}/plugin/yuyucirculation/documentShow/{{$page->id}}/{{$frame_id}}/{{$document->id}}">戻る</a>
                <button type="submit" class="btn btn-primary" onclick="return confirm('修正した内容で再申請します。よろしいですか？');">再申請する</button>
            </div>
        </form>
    </div>
</div>
@endsection
