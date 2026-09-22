{{-- 回覧・決裁 バケツ設定 --}}
@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.yuyucirculation.yuyucirculation_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")
@include('plugins.common.errors_form_line')

@if (empty($circulation->id) && $action != 'createBuckets')
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle"></i>
        選択画面から、使用する回覧・決裁を選択するか、新規作成してください。
    </div>
@else
    <div class="alert alert-info">
        <i class="fas fa-exclamation-circle"></i>
        @if (empty($circulation->id))
            新しい回覧・決裁を登録します。
        @else
            回覧・決裁の基本設定を変更します。
        @endif
    </div>

    @if (empty($circulation->id))
        <form action="{{url('/')}}/redirect/plugin/yuyucirculation/saveBuckets/{{$page->id}}/{{$frame_id}}#frame-{{$frame->id}}" method="POST">
            <input type="hidden" name="redirect_path" value="{{url('/')}}/plugin/yuyucirculation/createBuckets/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
    @else
        <form action="{{url('/')}}/redirect/plugin/yuyucirculation/saveBuckets/{{$page->id}}/{{$frame_id}}/{{$circulation->bucket_id}}#frame-{{$frame->id}}" method="POST">
            <input type="hidden" name="redirect_path" value="{{url('/')}}/plugin/yuyucirculation/editBuckets/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
    @endif
        {{csrf_field()}}
        <div class="form-group row">
            <label class="{{$frame->getSettingLabelClass()}}">回覧・決裁名 <span class="badge badge-danger">必須</span></label>
            <div class="{{$frame->getSettingInputClass()}}">
                <input type="text" name="name" class="form-control" value="{{old('name', $circulation->name)}}">
                @include('plugins.common.errors_inline', ['name' => 'name'])
            </div>
        </div>
        <div class="form-group row">
            <label class="{{$frame->getSettingLabelClass()}}">表示モード <span class="badge badge-danger">必須</span></label>
            <div class="{{$frame->getSettingInputClass()}}">
                <select name="view_format" class="form-control">
                    <option value="dashboard" @if(old('view_format', $plugin_frame->view_format ?: 'dashboard') === 'dashboard') selected @endif>ダッシュボード</option>
                    <option value="notification" @if(old('view_format', $plugin_frame->view_format) === 'notification') selected @endif>新着通知</option>
                </select>
                <small class="form-text text-muted">新着通知は、ログインユーザー宛ての通知をコンパクトに表示します。</small>
                @include('plugins.common.errors_inline', ['name' => 'view_format'])
            </div>
        </div>
        <div class="form-group row">
            <label class="{{$frame->getSettingLabelClass()}}">表示件数 <span class="badge badge-danger">必須</span></label>
            <div class="{{$frame->getSettingInputClass()}}">
                <input type="number" name="view_count" min="1" max="50" class="form-control" value="{{old('view_count', $plugin_frame->view_count ?: 10)}}">
                @include('plugins.common.errors_inline', ['name' => 'view_count'])
            </div>
        </div>
        <div class="form-group row">
            <label class="{{$frame->getSettingLabelClass()}}">メール通知</label>
            <div class="{{$frame->getSettingInputClass()}}">
                <input type="hidden" name="mail_notification_enabled" value="0">
                <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="mail_notification_enabled" value="1" class="custom-control-input" id="mail_notification_enabled_{{$frame_id}}"
                           @if(old('mail_notification_enabled', is_null($circulation->mail_notification_enabled) ? 1 : $circulation->mail_notification_enabled)) checked @endif>
                    <label class="custom-control-label" for="mail_notification_enabled_{{$frame_id}}">メール通知を使用する</label>
                </div>
                <small class="form-text text-muted">無効にしても、新着通知モードのアプリ内通知は保存・表示されます。</small>
                @include('plugins.common.errors_inline', ['name' => 'mail_notification_enabled'])
            </div>
        </div>
        <div class="form-group text-center">
            <button type="button" class="btn btn-secondary mr-2" onclick="location.href='{{URL::to($page->permanent_link)}}#frame-{{$frame->id}}'">
                <i class="fas fa-times"></i> キャンセル
            </button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> 保存</button>
        </div>
    </form>
@endif
@endsection
