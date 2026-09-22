{{-- 回覧・決裁 選択画面 --}}
@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.yuyucirculation.yuyucirculation_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")
@if ($plugin_buckets->isEmpty())
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle"></i>
        回覧・決裁がまだありません。「新規作成」から作成してください。
    </div>
@else
    <form action="{{url('/')}}/redirect/plugin/yuyucirculation/changeBuckets/{{$page->id}}/{{$frame_id}}#frame-{{$frame->id}}" method="POST">
        {{csrf_field()}}
        <input type="hidden" name="redirect_path" value="{{url('/')}}/plugin/yuyucirculation/listBuckets/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
        <div class="table-responsive">
            <table class="table table-hover {{$frame->getSettingTableClass()}}">
                <thead><tr><th></th><th>回覧・決裁名</th><th>作成日</th></tr></thead>
                <tbody>
                @foreach($plugin_buckets as $plugin_bucket)
                    <tr @if ($plugin_bucket->bucket_id == $frame->bucket_id) class="cc-active-tr" @endif>
                        <td><input type="radio" name="select_bucket" value="{{$plugin_bucket->bucket_id}}" @if ($plugin_bucket->bucket_id == $frame->bucket_id) checked @endif></td>
                        <td>{{$plugin_bucket->name}}</td>
                        <td>{{$plugin_bucket->created_at ? $plugin_bucket->created_at->format('Y/m/d H:i') : ''}}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @include('plugins.common.user_paginate', ['posts' => $plugin_buckets, 'frame' => $frame, 'aria_label_name' => '回覧・決裁選択', 'class' => 'form-group'])
        <div class="text-center">
            <button type="button" class="btn btn-secondary mr-2" onclick="location.href='{{URL::to($page->permanent_link)}}#frame-{{$frame->id}}'">キャンセル</button>
            <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> 表示変更</button>
        </div>
    </form>
@endif
@endsection
