{{-- 決裁テンプレート 新規作成・編集 --}}
@extends('core.cms_frame_base_setting')

@section("core.cms_frame_edit_tab_$frame->id")
    @include('plugins.user.yuyucirculation.yuyucirculation_frame_edit_tab')
@endsection

@section("plugin_setting_$frame->id")
@include('plugins.common.errors_form_line')

@if (empty($circulation->id))
    <div class="alert alert-warning">
        先に、このフレームで使用する回覧・決裁を設定してください。
    </div>
@else
@php
    $step_rows = old('steps');
    if (is_null($step_rows)) {
        $step_rows = $template->steps->map(function ($step) {
            return [
                'section_mode' => $step->section_mode,
                'section_id' => $step->section_id,
                'group_id' => $step->group_id,
                'action_type' => $step->action_type,
            ];
        })->values()->toArray();
    }
    if (empty($step_rows)) {
        $step_rows = [[
            'section_mode' => 'applicant_section',
            'section_id' => null,
            'group_id' => null,
            'action_type' => 'decision',
        ]];
    }

    $target_rows = old('targets');
    if (is_null($target_rows)) {
        $target_rows = $template->targets->map(function ($target) {
            return [
                'target_type' => $target->target_type,
                'section_id' => $target->section_id,
                'group_id' => $target->group_id,
                'user_id' => $target->user_id,
            ];
        })->values()->toArray();
    }
    if (empty($target_rows)) {
        $target_rows = [[
            'target_type' => 'applicant_section',
            'section_id' => null,
            'group_id' => null,
            'user_id' => null,
        ]];
    }

    $post_circulation_enabled = old('post_circulation_enabled', $template->exists ? (int)$template->post_circulation_enabled : 0);
    $is_active = old('is_active', $template->exists ? (int)$template->is_active : 1);
@endphp

<div class="mb-3">
    <h5 class="mb-1">{{$template->exists ? '決裁テンプレート編集' : '決裁テンプレート新規作成'}}</h5>
    <small class="text-muted">承認者個人ではなく、所属と役職グループの組み合わせで承認ルートを定義します。</small>
</div>

@if ($template->exists)
<form action="{{url('/')}}/redirect/plugin/yuyucirculation/templateSave/{{$page->id}}/{{$frame_id}}/{{$template->id}}#frame-{{$frame->id}}" method="POST">
@else
<form action="{{url('/')}}/redirect/plugin/yuyucirculation/templateSave/{{$page->id}}/{{$frame_id}}#frame-{{$frame->id}}" method="POST">
@endif
    {{csrf_field()}}
    <input type="hidden" name="redirect_path" value="{{url('/')}}/plugin/yuyucirculation/templateList/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}">

    <div class="card mb-3">
        <div class="card-header font-weight-bold">基本設定</div>
        <div class="card-body">
            <div class="form-group row">
                <label class="{{$frame->getSettingLabelClass()}}">テンプレート名 <span class="badge badge-danger">必須</span></label>
                <div class="{{$frame->getSettingInputClass()}}">
                    <input type="text" name="name" class="form-control" value="{{old('name', $template->name)}}">
                    @include('plugins.common.errors_inline', ['name' => 'name'])
                </div>
            </div>

            <div class="form-group row">
                <label class="{{$frame->getSettingLabelClass()}}">テンプレートコード <span class="badge badge-danger">必須</span></label>
                <div class="{{$frame->getSettingInputClass()}}">
                    <input type="text" name="template_code" class="form-control" value="{{old('template_code', $template->template_code)}}" placeholder="purchase_standard">
                    @include('plugins.common.errors_inline', ['name' => 'template_code'])
                    <small class="form-text text-muted">半角英数字・ハイフン・アンダースコア。申請フォームからテンプレートを識別するために使用します。</small>
                </div>
            </div>

            <div class="form-group row">
                <label class="{{$frame->getSettingLabelClass()}}">種別</label>
                <div class="{{$frame->getSettingInputClass()}}">
                    <select name="workflow_type" class="form-control">
                        <option value="decision" @if(old('workflow_type', $template->workflow_type ?: 'decision') === 'decision') selected @endif>決裁</option>
                        <option value="approval" @if(old('workflow_type', $template->workflow_type) === 'approval') selected @endif>承認</option>
                    </select>
                </div>
            </div>

            <div class="form-group row mb-0">
                <label class="{{$frame->getSettingLabelClass()}}">状態</label>
                <div class="{{$frame->getSettingInputClass()}}">
                    <input type="hidden" name="is_active" value="0">
                    <div class="custom-control custom-checkbox">
                        <input type="checkbox" class="custom-control-input" id="is_active" name="is_active" value="1" @if($is_active) checked @endif>
                        <label class="custom-control-label" for="is_active">このテンプレートを有効にする</label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span class="font-weight-bold">承認ルート</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="add-step"><i class="fas fa-plus"></i> STEP追加</button>
        </div>
        <div class="card-body" id="step-list">
            @foreach($step_rows as $index => $step)
            <div class="step-row border rounded p-3 mb-3">
                <div class="d-flex justify-content-between mb-2">
                    <strong class="step-caption">STEP {{$index + 1}}</strong>
                    <button type="button" class="btn btn-sm btn-outline-danger remove-step"><i class="fas fa-times"></i> 削除</button>
                </div>
                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>所属条件</label>
                        <select name="steps[{{$index}}][section_mode]" class="form-control section-mode">
                            <option value="applicant_section" @if(($step['section_mode'] ?? '') === 'applicant_section') selected @endif>申請者と同じ所属</option>
                            <option value="fixed_section" @if(($step['section_mode'] ?? '') === 'fixed_section') selected @endif>指定所属</option>
                            <option value="none" @if(($step['section_mode'] ?? '') === 'none') selected @endif>所属を問わない</option>
                        </select>
                    </div>
                    <div class="form-group col-md-3 fixed-section-wrap">
                        <label>指定所属</label>
                        <select name="steps[{{$index}}][section_id]" class="form-control">
                            <option value="">選択してください</option>
                            @foreach($sections as $section)
                                <option value="{{$section->id}}" @if((string)($step['section_id'] ?? '') === (string)$section->id) selected @endif>{{$section->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-3">
                        <label>役職グループ <span class="badge badge-danger">必須</span></label>
                        <select name="steps[{{$index}}][group_id]" class="form-control">
                            <option value="">選択してください</option>
                            @foreach($groups as $group)
                                <option value="{{$group->id}}" @if((string)($step['group_id'] ?? '') === (string)$group->id) selected @endif>{{$group->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-2">
                        <label>処理</label>
                        <select name="steps[{{$index}}][action_type]" class="form-control">
                            <option value="approval" @if(($step['action_type'] ?? '') === 'approval') selected @endif>承認</option>
                            <option value="decision" @if(($step['action_type'] ?? '') === 'decision') selected @endif>最終決裁</option>
                        </select>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header font-weight-bold">決裁後回覧</div>
        <div class="card-body">
            <input type="hidden" name="post_circulation_enabled" value="0">
            <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="post_circulation_enabled" name="post_circulation_enabled" value="1" @if($post_circulation_enabled) checked @endif>
                <label class="custom-control-label" for="post_circulation_enabled">最終決裁後に関係職員へ回覧する</label>
            </div>

            <div id="post-circulation-settings">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong>回覧先</strong>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="add-target"><i class="fas fa-plus"></i> 回覧先追加</button>
                </div>
                <div id="target-list">
                    @foreach($target_rows as $index => $target)
                    <div class="target-row border rounded p-3 mb-3">
                        <div class="d-flex justify-content-between mb-2">
                            <strong class="target-caption">回覧先 {{$index + 1}}</strong>
                            <button type="button" class="btn btn-sm btn-outline-danger remove-target"><i class="fas fa-times"></i> 削除</button>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>種類</label>
                                <select name="targets[{{$index}}][target_type]" class="form-control target-type">
                                    <option value="applicant_section" @if(($target['target_type'] ?? '') === 'applicant_section') selected @endif>申請者と同じ所属</option>
                                    <option value="fixed_section" @if(($target['target_type'] ?? '') === 'fixed_section') selected @endif>指定所属</option>
                                    <option value="fixed_group" @if(($target['target_type'] ?? '') === 'fixed_group') selected @endif>指定グループ</option>
                                    <option value="fixed_user" @if(($target['target_type'] ?? '') === 'fixed_user') selected @endif>指定ユーザー</option>
                                </select>
                            </div>
                            <div class="form-group col-md-8 target-specific target-section">
                                <label>所属</label>
                                <select name="targets[{{$index}}][section_id]" class="form-control">
                                    <option value="">選択してください</option>
                                    @foreach($sections as $section)
                                        <option value="{{$section->id}}" @if((string)($target['section_id'] ?? '') === (string)$section->id) selected @endif>{{$section->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-8 target-specific target-group">
                                <label>グループ</label>
                                <select name="targets[{{$index}}][group_id]" class="form-control">
                                    <option value="">選択してください</option>
                                    @foreach($groups as $group)
                                        <option value="{{$group->id}}" @if((string)($target['group_id'] ?? '') === (string)$group->id) selected @endif>{{$group->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group col-md-8 target-specific target-user">
                                <label>ユーザー</label>
                                <select name="targets[{{$index}}][user_id]" class="form-control">
                                    <option value="">選択してください</option>
                                    @foreach($users as $user)
                                        <option value="{{$user->id}}" @if((string)($target['user_id'] ?? '') === (string)$user->id) selected @endif>{{$user->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @if ($errors && $errors->has('targets')) <div class="text-danger">{{$errors->first('targets')}}</div> @endif
        </div>
    </div>

    <div class="form-group text-center">
        <a class="btn btn-secondary mr-2" href="{{url('/')}}/plugin/yuyucirculation/templateList/{{$page->id}}/{{$frame_id}}#frame-{{$frame_id}}"><i class="fas fa-times"></i> キャンセル</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> 保存</button>
    </div>
</form>

<script>
$(function () {
    function renumberRows(listSelector, rowSelector, prefix, caption) {
        $(listSelector).find(rowSelector).each(function (index) {
            $(this).find('input, select, textarea').each(function () {
                var name = $(this).attr('name');
                if (name) {
                    $(this).attr('name', name.replace(new RegExp('^' + prefix + '\\[\\d+\\]'), prefix + '[' + index + ']'));
                }
            });
            $(this).find(caption).text((prefix === 'steps' ? 'STEP ' : '回覧先 ') + (index + 1));
        });
    }

    function refreshStep(row) {
        var mode = row.find('.section-mode').val();
        row.find('.fixed-section-wrap').toggle(mode === 'fixed_section');
    }

    function refreshTarget(row) {
        var type = row.find('.target-type').val();
        row.find('.target-specific').hide();
        if (type === 'fixed_section') row.find('.target-section').show();
        if (type === 'fixed_group') row.find('.target-group').show();
        if (type === 'fixed_user') row.find('.target-user').show();
    }

    function refreshPostCirculation() {
        $('#post-circulation-settings').toggle($('#post_circulation_enabled').is(':checked'));
    }

    $('#step-list .step-row').each(function () { refreshStep($(this)); });
    $('#target-list .target-row').each(function () { refreshTarget($(this)); });
    refreshPostCirculation();

    $(document).on('change', '.section-mode', function () { refreshStep($(this).closest('.step-row')); });
    $(document).on('change', '.target-type', function () { refreshTarget($(this).closest('.target-row')); });
    $('#post_circulation_enabled').on('change', refreshPostCirculation);

    $('#add-step').on('click', function () {
        var row = $('#step-list .step-row').first().clone();
        row.find('select').prop('selectedIndex', 0);
        row.find('.section-mode').val('applicant_section');
        row.find('select[name*="action_type"]').val('approval');
        $('#step-list').append(row);
        renumberRows('#step-list', '.step-row', 'steps', '.step-caption');
        refreshStep(row);
    });

    $(document).on('click', '.remove-step', function () {
        if ($('#step-list .step-row').length <= 1) return;
        $(this).closest('.step-row').remove();
        renumberRows('#step-list', '.step-row', 'steps', '.step-caption');
    });

    $('#add-target').on('click', function () {
        var row = $('#target-list .target-row').first().clone();
        row.find('select').prop('selectedIndex', 0);
        row.find('.target-type').val('applicant_section');
        $('#target-list').append(row);
        renumberRows('#target-list', '.target-row', 'targets', '.target-caption');
        refreshTarget(row);
    });

    $(document).on('click', '.remove-target', function () {
        if ($('#target-list .target-row').length <= 1) return;
        $(this).closest('.target-row').remove();
        renumberRows('#target-list', '.target-row', 'targets', '.target-caption');
    });
});
</script>
@endif
@endsection
