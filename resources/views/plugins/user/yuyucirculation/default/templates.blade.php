{{-- 決裁テンプレート一覧 --}}
@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.yuyucirculation.yuyucirculation_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")
@include('plugins.common.errors_form_line')

@if (empty($circulation->id))
    <div class="alert alert-warning">
        先に「新規作成」または「回覧・決裁選択」で、このフレームで使用する回覧・決裁を設定してください。
    </div>
@else
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-1">決裁テンプレート</h5>
            <small class="text-muted">申請時に使用する承認ルートと、決裁後の回覧先を管理します。</small>
        </div>
        <a class="btn btn-success" href="{{url('/')}}/plugin/yuyucirculation/templateCreate/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">
            <i class="fas fa-plus"></i> 新規作成
        </a>
    </div>

    @if ($templates->isEmpty())
        <div class="alert alert-info">決裁テンプレートはまだ登録されていません。</div>
    @else
        <div class="table-responsive">
            <table class="table table-hover {{$frame->getSettingTableClass()}}">
                <thead>
                    <tr>
                        <th>テンプレート名</th>
                        <th>コード</th>
                        <th>承認段階</th>
                        <th>決裁後回覧</th>
                        <th>状態</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @foreach($templates as $template)
                    <tr>
                        <td>{{$template->name}}</td>
                        <td><code>{{$template->template_code}}</code></td>
                        <td>{{$template->steps->count()}} 段階</td>
                        <td>{{$template->post_circulation_enabled ? 'あり' : 'なし'}}</td>
                        <td>{{$template->is_active ? '有効' : '無効'}}</td>
                        <td class="text-nowrap text-right">
                            <a class="btn btn-sm btn-primary" href="{{url('/')}}/plugin/yuyucirculation/templateEdit/{{$page->id}}/{{$frame_id}}/{{$template->id}}#frame-{{$frame_id}}">
                                <i class="fas fa-edit"></i> 編集
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if (method_exists($templates, 'links'))
            @include('plugins.common.user_paginate', ['posts' => $templates, 'frame' => $frame, 'aria_label_name' => '決裁テンプレート', 'class' => 'form-group'])
        @endif
    @endif
@endif
@endsection
