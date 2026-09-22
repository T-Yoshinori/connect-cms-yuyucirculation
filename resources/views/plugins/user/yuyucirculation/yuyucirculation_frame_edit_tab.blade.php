{{-- 回覧・決裁プラグイン 設定タブ --}}
@if ($action == 'editBuckets')
    <li class="nav-item"><span class="nav-link"><span class="active">設定変更</span></span></li>
@else
    <li class="nav-item"><a class="nav-link" href="{{url('/')}}/plugin/yuyucirculation/editBuckets/{{$page->id}}/{{$frame->id}}#frame-{{$frame->id}}">設定変更</a></li>
@endif

@if ($action == 'createBuckets')
    <li class="nav-item"><span class="nav-link"><span class="active">新規作成</span></span></li>
@else
    <li class="nav-item"><a class="nav-link" href="{{url('/')}}/plugin/yuyucirculation/createBuckets/{{$page->id}}/{{$frame->id}}#frame-{{$frame->id}}">新規作成</a></li>
@endif

@if (in_array($action, ['templateList', 'templateCreate', 'templateEdit', 'templateSave']))
    <li class="nav-item"><span class="nav-link"><span class="active">決裁テンプレート</span></span></li>
@else
    <li class="nav-item"><a class="nav-link" href="{{url('/')}}/plugin/yuyucirculation/templateList/{{$page->id}}/{{$frame->id}}#frame-{{$frame->id}}">決裁テンプレート</a></li>
@endif

@if ($action == 'listBuckets')
    <li class="nav-item"><span class="nav-link"><span class="active">回覧・決裁選択</span></span></li>
@else
    <li class="nav-item"><a class="nav-link" href="{{url('/')}}/plugin/yuyucirculation/listBuckets/{{$page->id}}/{{$frame->id}}#frame-{{$frame->id}}">回覧・決裁選択</a></li>
@endif
