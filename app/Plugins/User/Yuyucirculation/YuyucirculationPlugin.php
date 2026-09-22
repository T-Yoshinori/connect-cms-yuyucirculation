<?php

namespace App\Plugins\User\Yuyucirculation;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

use App\Models\Common\Buckets;
use App\Models\Common\Frame;
use App\Models\Common\Group;
use App\Models\Core\Section;
use App\Models\User\YuyuCirculation\YuyuCirculation;
use App\Models\User\YuyuCirculation\YuyuCirculationDocument;
use App\Models\User\YuyuCirculation\YuyuCirculationHistory;
use App\Models\User\YuyuCirculation\YuyuCirculationFile;
use App\Models\User\YuyuCirculation\YuyuCirculationStep;
use App\Models\User\YuyuCirculation\YuyuCirculationFrame;
use App\Models\User\YuyuCirculation\YuyuCirculationTemplate;
use App\Models\User\YuyuCirculation\YuyuCirculationTemplateStep;
use App\Models\User\YuyuCirculation\YuyuCirculationTemplateTarget;
use App\Models\User\YuyuCirculation\YuyuCirculationTarget;
use App\Models\User\YuyuCirculation\YuyuCirculationQuestion;
use App\Models\User\YuyuCirculation\YuyuCirculationChoice;
use App\Models\User\YuyuCirculation\YuyuCirculationAnswer;
use App\Models\User\YuyuCirculation\YuyuCirculationNotification;
use App\Plugins\User\Yuyucirculation\Services\NotificationService;
use App\Plugins\User\Yuyucirculation\Services\WorkflowRouteService;
use App\Plugins\User\UserPluginBase;
use App\User;

/**
 * 回覧・決裁プラグイン
 *
 * @plugin_title 回覧・決裁
 * @plugin_desc 回覧、申請、多段階承認、決裁後回覧を管理するプラグインです。
 */
class YuyucirculationPlugin extends UserPluginBase
{
    /**
     * 標準関数以外で画面から呼び出す関数。
     */
    public function getPublicFunctions()
    {
        return [
            'get' => ['templateList', 'templateCreate', 'templateEdit', 'documentCreate', 'documentShow', 'documentEdit', 'documentFileDownload', 'circulationCreate'],
            'post' => ['templateSave', 'documentSubmit', 'documentCancel', 'documentApprove', 'documentReturn', 'documentResubmit', 'documentConfirm', 'circulationSubmit', 'circulationAnswer'],
        ];
    }

    /**
     * 権限定義。
     */
    public function declareRole()
    {
        return [
            'templateList' => ['role_arrangement'],
            'templateCreate' => ['role_arrangement'],
            'templateEdit' => ['role_arrangement'],
            'templateSave' => ['role_arrangement'],
            'documentCreate' => ['role_guest'],
            'documentShow' => ['role_guest'],
            'documentSubmit' => ['role_guest'],
            'documentCancel' => ['role_guest'],
            'documentApprove' => ['role_guest'],
            'documentReturn' => ['role_guest'],
            'documentEdit' => ['role_guest'],
            'documentResubmit' => ['role_guest'],
            'documentFileDownload' => ['role_guest'],
            'documentConfirm' => ['role_guest'],
            'circulationCreate' => ['role_guest'],
            'circulationSubmit' => ['role_guest'],
            'circulationAnswer' => ['role_guest'],
        ];
    }

    /**
     * プラグインのフレーム情報。
     */
    private function getPluginFrame($frame_id)
    {
        return YuyuCirculationFrame::firstOrNew(['frame_id' => $frame_id]);
    }

    /**
     * プラグインのバケツ情報。
     */
    private function getPluginBucket($bucket_id)
    {
        return YuyuCirculation::firstOrNew(['bucket_id' => $bucket_id]);
    }

    /**
     * 通常画面。初期版ではダッシュボードの骨組みを表示する。
     */
    public function index($request, $page_id, $frame_id)
    {
        $circulation = $this->getPluginBucket($this->getBucketId());

        $sent_documents = collect();
        $received_documents = collect();
        $circulation_documents = collect();
        $processed_documents = collect();
        $notifications = collect();
        $unread_notification_count = 0;
        $plugin_frame = $this->getPluginFrame($frame_id);
        $sent_status_counts = [
            'in_progress' => 0,
            'in_circulation' => 0,
            'completed' => 0,
        ];

        if ($circulation->exists && auth()->check()) {
            // 通知モードはポータル配置を想定し、バケツを横断して本人宛て通知を表示する。
            $notification_query = YuyuCirculationNotification::with('document')
                ->where('user_id', auth()->id());
            $unread_notification_count = (clone $notification_query)->whereNull('read_at')->count();
            $notifications = $notification_query->whereNull('read_at')
                ->orderBy('created_at', 'desc')
                ->limit($plugin_frame->view_count ?: 10)
                ->get();

            // detail_url追加前の通知にも、同じバケツを表示する既存フレームから遷移先を補う。
            $notification_circulation_ids = $notifications->pluck('document.circulation_id')->filter()->unique();
            $notification_source_frames = YuyuCirculationFrame::select(
                    'yuyu_circulation_frames.circulation_id',
                    'frames.id as source_frame_id',
                    'frames.page_id as source_page_id'
                )
                ->join('frames', 'frames.id', '=', 'yuyu_circulation_frames.frame_id')
                ->whereIn('yuyu_circulation_frames.circulation_id', $notification_circulation_ids)
                ->orderBy('frames.id')
                ->get()
                ->unique('circulation_id')
                ->keyBy('circulation_id');

            foreach ($notifications as $notification) {
                if ($notification->detail_url || !$notification->document) continue;
                $source_frame = $notification_source_frames->get($notification->document->circulation_id);
                if (!$source_frame) continue;
                $notification->detail_url = url('/')
                    . "/plugin/yuyucirculation/documentShow/{$source_frame->source_page_id}/{$source_frame->source_frame_id}/{$notification->document_id}"
                    . "#frame-{$source_frame->source_frame_id}";
            }

            $sent_documents = YuyuCirculationDocument::where('circulation_id', $circulation->id)
                ->where('applicant_user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            $sent_status_query = YuyuCirculationDocument::where('circulation_id', $circulation->id)
                ->where('applicant_user_id', auth()->id());
            $sent_status_counts['in_progress'] = (clone $sent_status_query)
                ->where('document_type', 'approval')
                ->whereIn('status', ['submitted', 'in_approval'])
                ->count();
            $sent_status_counts['in_circulation'] = (clone $sent_status_query)
                ->where('status', 'in_circulation')
                ->count();
            $sent_status_counts['completed'] = (clone $sent_status_query)
                ->where('status', 'completed')
                ->count();

            $received_documents = YuyuCirculationDocument::select('yuyu_circulation_documents.*')
                ->join('yuyu_circulation_steps', 'yuyu_circulation_steps.document_id', '=', 'yuyu_circulation_documents.id')
                ->where('yuyu_circulation_documents.circulation_id', $circulation->id)
                ->where('yuyu_circulation_steps.approver_user_id', auth()->id())
                ->where('yuyu_circulation_steps.status', 'pending')
                ->whereIn('yuyu_circulation_documents.status', ['submitted', 'in_approval'])
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('yuyu_circulation_steps as previous_steps')
                        ->whereColumn('previous_steps.document_id', 'yuyu_circulation_steps.document_id')
                        ->whereColumn('previous_steps.step_no', '<', 'yuyu_circulation_steps.step_no')
                        ->whereNotIn('previous_steps.status', ['approved', 'decided', 'skipped']);
                })
                ->orderBy('yuyu_circulation_documents.submitted_at', 'asc')
                ->distinct()
                ->get();

            $circulation_documents = YuyuCirculationDocument::select('yuyu_circulation_documents.*')
                ->join('yuyu_circulation_targets', 'yuyu_circulation_targets.document_id', '=', 'yuyu_circulation_documents.id')
                ->where('yuyu_circulation_documents.circulation_id', $circulation->id)
                ->where('yuyu_circulation_targets.user_id', auth()->id())
                ->where('yuyu_circulation_targets.status', 'waiting')
                ->where('yuyu_circulation_documents.status', 'in_circulation')
                ->orderBy('yuyu_circulation_documents.circulation_started_at', 'asc')
                ->get();

            $processed_target_document_ids = YuyuCirculationTarget::where('user_id', auth()->id())
                ->where('status', 'confirmed')
                ->pluck('document_id');
            $processed_step_document_ids = YuyuCirculationStep::where('approver_user_id', auth()->id())
                ->whereIn('status', ['approved', 'decided', 'returned', 'skipped'])
                ->pluck('document_id');
            $processed_document_ids = $processed_target_document_ids
                ->merge($processed_step_document_ids)
                ->unique()
                ->values();

            if ($processed_document_ids->isNotEmpty()) {
                $processed_documents = YuyuCirculationDocument::where('circulation_id', $circulation->id)
                    ->whereIn('id', $processed_document_ids)
                    ->orderBy('updated_at', 'desc')
                    ->limit(10)
                    ->get();
            }
        }

        return $this->view('index', [
            'circulation' => $circulation,
            'sent_documents' => $sent_documents,
            'received_documents' => $received_documents,
            'circulation_documents' => $circulation_documents,
            'processed_documents' => $processed_documents,
            'notifications' => $notifications,
            'unread_notification_count' => $unread_notification_count,
            'sent_status_counts' => $sent_status_counts,
            'plugin_frame' => $plugin_frame,
        ]);
    }

    /**
     * バケツ選択画面。
     */
    public function listBuckets($request, $page_id, $frame_id, $id = null)
    {
        $plugin_buckets = YuyuCirculation::select('yuyu_circulations.*')
            ->leftJoin('frames', function ($join) use ($frame_id) {
                $join->on('yuyu_circulations.bucket_id', '=', 'frames.bucket_id')
                    ->where('frames.id', $frame_id);
            })
            ->orderBy('frames.bucket_id', 'desc')
            ->orderBy('yuyu_circulations.created_at', 'desc')
            ->paginate(10, ['*'], "frame_{$frame_id}_page");

        return $this->view('list_buckets', [
            'plugin_buckets' => $plugin_buckets,
        ]);
    }

    /**
     * バケツ新規作成。
     */
    public function createBuckets($request, $page_id, $frame_id)
    {
        return $this->editBuckets($request, $page_id, $frame_id);
    }

    /**
     * バケツ設定画面。
     */
    public function editBuckets($request, $page_id, $frame_id)
    {
        $bucket_id = $this->action === 'createBuckets' ? null : $this->getBucketId();

        return $this->view('bucket', [
            'circulation' => $this->getPluginBucket($bucket_id),
            'plugin_frame' => $this->getPluginFrame($frame_id),
        ]);
    }

    /**
     * バケツ保存。
     */
    public function saveBuckets($request, $page_id, $frame_id, $bucket_id = null)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'view_format' => ['required', Rule::in(['dashboard', 'notification'])],
            'view_count' => ['required', 'integer', 'min:1', 'max:50'],
            'mail_notification_enabled' => ['nullable', 'boolean'],
        ]);
        $validator->setAttributeNames([
            'name' => '回覧・決裁名',
            'view_format' => '表示モード',
            'view_count' => '表示件数',
            'mail_notification_enabled' => 'メール通知',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $bucket = Buckets::updateOrCreateWithDefaultPostRoles(
            ['id' => $bucket_id],
            ['bucket_name' => $request->name, 'plugin_name' => 'yuyucirculation']
        );

        Frame::find($frame_id)->update(['bucket_id' => $bucket->id]);

        $circulation = $this->getPluginBucket($bucket->id);
        $circulation->bucket_id = $bucket->id;
        $circulation->name = $request->name;
        $circulation->mail_notification_enabled = $request->boolean('mail_notification_enabled');
        $circulation->save();

        YuyuCirculationFrame::updateOrCreate(
            ['frame_id' => $frame_id],
            [
                'circulation_id' => $circulation->id,
                'frame_id' => $frame_id,
                'view_format' => $request->view_format,
                'view_count' => $request->view_count,
            ]
        );

        return new Collection([
            'redirect_path' => url('/') . "/plugin/yuyucirculation/templateList/{$page_id}/{$frame_id}#frame-{$frame_id}",
        ]);
    }


    /**
     * 通常回覧の新規作成画面。
     */
    public function circulationCreate($request, $page_id, $frame_id)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) abort(404);

        return $this->view('circulation_create', [
            'circulation' => $circulation,
            'sections' => Section::orderBy('display_sequence')->orderBy('name')->get(),
            'groups' => Group::orderBy('display_sequence')->orderBy('name')->get(),
            'users' => User::where('status', 0)->orderBy('name')->get(),
        ]);
    }

    /**
     * 通常回覧を発信し、回覧対象者をその時点のユーザーに固定する。
     */
    public function circulationSubmit($request, $page_id, $frame_id)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) abort(404);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'targets' => ['required', 'array', 'min:1', 'max:30'],
            'targets.*.target_type' => ['required', Rule::in(['applicant_section', 'fixed_section', 'fixed_group', 'fixed_user'])],
            'targets.*.section_id' => ['nullable', 'integer'],
            'targets.*.group_id' => ['nullable', 'integer'],
            'targets.*.user_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'response_type' => ['required', Rule::in(['confirm', 'choice', 'text'])],
            'response_visibility' => ['required', Rule::in(['targets', 'sender_only'])],
            'question_text' => ['nullable', 'string', 'max:2000'],
            'choices' => ['nullable', 'array', 'max:20'],
            'choices.*' => ['nullable', 'string', 'max:255'],
        ]);
        $validator->after(function ($validator) use ($request) {
            foreach ((array)$request->input('targets', []) as $index => $target) {
                $target_type = $target['target_type'] ?? null;
                if ($target_type === 'fixed_section' && empty($target['section_id'])) $validator->errors()->add("targets.{$index}.section_id", '回覧先' . ($index + 1) . 'の所属を選択してください。');
                if ($target_type === 'fixed_group' && empty($target['group_id'])) $validator->errors()->add("targets.{$index}.group_id", '回覧先' . ($index + 1) . 'のグループを選択してください。');
                if ($target_type === 'fixed_user' && empty($target['user_id'])) $validator->errors()->add("targets.{$index}.user_id", '回覧先' . ($index + 1) . 'のユーザーを選択してください。');
            }
            if (in_array($request->response_type, ['choice', 'text'], true) && !trim((string)$request->question_text)) $validator->errors()->add('question_text', '回答を求める内容を入力してください。');
            if ($request->response_type === 'choice') {
                $choices = collect((array)$request->input('choices', []))->map(function ($v) { return trim((string)$v); })->filter();
                if ($choices->count() < 2) $validator->errors()->add('choices', '選択肢は2件以上入力してください。');
            }
        });
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        $sender = auth()->user();
        $target_sources = [];
        foreach ((array)$request->input('targets', []) as $target) {
            $target_type = $target['target_type'];
            if ($target_type === 'applicant_section') {
                $section_id = optional($sender->user_section)->section_id;
                if (!$section_id) return back()->withErrors(['targets' => '発信者の所属が設定されていません。'])->withInput();
                $resolved_ids = DB::table('user_sections')->where('section_id', $section_id)->pluck('user_id');
            } elseif ($target_type === 'fixed_section') {
                $resolved_ids = DB::table('user_sections')->where('section_id', $target['section_id'])->pluck('user_id');
            } elseif ($target_type === 'fixed_group') {
                $resolved_ids = DB::table('group_users')->where('group_id', $target['group_id'])->whereNull('deleted_at')->pluck('user_id');
            } else {
                $resolved_ids = collect([$target['user_id']]);
            }

            foreach ($resolved_ids->filter()->unique() as $user_id) {
                if (!isset($target_sources[$user_id])) {
                    $target_sources[$user_id] = $target_type;
                }
            }
        }

        $target_ids = User::whereIn('id', array_keys($target_sources))
            ->where('status', 0)->pluck('id')->unique();
        if ($target_ids->isEmpty()) return back()->withErrors(['targets' => '回覧対象者が見つかりません。'])->withInput();

        $document = DB::transaction(function () use ($request, $circulation, $sender, $target_ids, $target_sources) {
            $document = YuyuCirculationDocument::create([
                'circulation_id' => $circulation->id,
                'template_id' => null,
                'document_type' => 'circulation',
                'title' => $request->title,
                'body' => $request->body,
                'applicant_user_id' => $sender->id,
                'applicant_section_id' => optional($sender->user_section)->section_id,
                'approval_status' => 'none',
                'circulation_status' => 'in_progress',
                'response_visibility' => $request->response_visibility,
                'status' => 'in_circulation',
                'submitted_at' => now(),
                'circulation_started_at' => now(),
            ]);
            foreach ($target_ids as $user_id) {
                YuyuCirculationTarget::create([
                    'document_id' => $document->id,
                    'user_id' => $user_id,
                    'target_source' => $target_sources[$user_id],
                    'status' => 'waiting',
                ]);
            }
            if ($request->response_type !== 'confirm') {
                $question = YuyuCirculationQuestion::create([
                    'document_id' => $document->id,
                    'question_no' => 1,
                    'question_type' => $request->response_type,
                    'question_text' => $request->question_text,
                    'required' => true,
                ]);
                if ($request->response_type === 'choice') {
                    $choice_no = 1;
                    foreach ((array)$request->input('choices', []) as $choice_text) {
                        $choice_text = trim((string)$choice_text);
                        if ($choice_text === '') continue;
                        YuyuCirculationChoice::create(['question_id' => $question->id, 'choice_no' => $choice_no++, 'choice_text' => $choice_text]);
                    }
                }
            }
            YuyuCirculationHistory::create([
                'document_id' => $document->id,
                'user_id' => $sender->id,
                'action' => 'circulated',
                'comment' => '回覧を発信しました。',
                'created_id' => $sender->id,
                'created_name' => $sender->name,
                'created_at' => now(),
            ]);
            return $document;
        });

        $this->saveAttachments($request, $document);
        $this->sendWorkflowNotification(
            $target_ids,
            $document,
            'circulation_received',
            '新しい回覧が届きました。',
            $page_id,
            $frame_id
        );
        return new Collection(['redirect_path' => url('/') . $this->page->permanent_link . "#frame-{$frame_id}"]);
    }

    /**
     * 決裁申請の新規作成画面。
     */
    public function documentCreate($request, $page_id, $frame_id)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $templates = YuyuCirculationTemplate::where('circulation_id', $circulation->id)
            ->where('is_active', true)
            ->whereIn('workflow_type', ['approval', 'decision'])
            ->orderBy('name')
            ->get();

        return $this->view('document_create', [
            'circulation' => $circulation,
            'templates' => $templates,
        ]);
    }

    /**
     * 決裁申請を登録し、テンプレートから実承認STEPを生成する。
     */
    public function documentSubmit($request, $page_id, $frame_id)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'template_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);
        $validator->setAttributeNames([
            'template_id' => '決裁テンプレート',
            'title' => '件名',
            'body' => '内容',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $template = YuyuCirculationTemplate::where('circulation_id', $circulation->id)
            ->where('is_active', true)
            ->find($request->template_id);

        if (!$template) {
            return back()->withErrors(['template_id' => '利用できる決裁テンプレートを選択してください。'])->withInput();
        }

        $applicant = auth()->user();
        $route_service = new WorkflowRouteService();

        try {
            $resolved_steps = $route_service->resolve($template, $applicant);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['route' => $e->getMessage()])->withInput();
        }

        $document = DB::transaction(function () use ($request, $circulation, $template, $applicant, $resolved_steps) {
            $applicant_section_id = optional($applicant->user_section)->section_id;

            $document = YuyuCirculationDocument::create([
                'circulation_id' => $circulation->id,
                'template_id' => $template->id,
                'document_type' => 'approval',
                'title' => $request->title,
                'body' => $request->body,
                'applicant_user_id' => $applicant->id,
                'applicant_section_id' => $applicant_section_id,
                'approval_status' => 'submitted',
                'circulation_status' => 'none',
                'status' => 'submitted',
                'submitted_at' => now(),
            ]);

            foreach ($resolved_steps as $step) {
                YuyuCirculationStep::create(array_merge($step, [
                    'document_id' => $document->id,
                ]));
            }

            return $document;
        });

        $this->saveAttachments($request, $document);

        $first_step = YuyuCirculationStep::where('document_id', $document->id)
            ->where('status', 'pending')
            ->orderBy('step_no')
            ->first();
        if ($first_step) {
            $this->sendWorkflowNotification(
                [$first_step->approver_user_id],
                $document,
                'approval_requested',
                '承認・決裁の依頼が届きました。',
                $page_id,
                $frame_id
            );
        }

        return new Collection([
            'redirect_path' => url('/') . $this->page->permanent_link . "#frame-{$frame_id}",
        ]);
    }

    /**
     * 申請詳細画面。
     * 申請者本人、または承認ルートに含まれる承認者だけが閲覧できる。
     */
    public function documentShow($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $document = YuyuCirculationDocument::with(['steps', 'files', 'histories', 'targets', 'questions.choices', 'answers'])
            ->where('circulation_id', $circulation->id)
            ->findOrFail($document_id);

        $is_applicant = $document->applicant_user_id === auth()->id();
        $is_approver = $document->steps->contains(function ($step) {
            return $step->approver_user_id === auth()->id();
        });

        $is_target = $document->targets->contains(function ($target) {
            return $target->user_id === auth()->id();
        });

        if (!$is_applicant && !$is_approver && !$is_target) {
            abort(403);
        }

        YuyuCirculationNotification::where('user_id', auth()->id())
            ->where('document_id', $document->id)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);

        $user_ids = collect([$document->applicant_user_id])
            ->merge($document->targets->pluck('user_id'))
            ->merge($document->steps->pluck('approver_user_id'))
            ->filter()
            ->unique();

        $users = User::whereIn('id', $user_ids)->get()->keyBy('id');

        $current_step = $document->steps
            ->where('approver_user_id', auth()->id())
            ->where('status', 'pending')
            ->first(function ($step) use ($document) {
                return !$document->steps->contains(function ($previous_step) use ($step) {
                    return $previous_step->step_no < $step->step_no
                        && !in_array($previous_step->status, ['approved', 'decided', 'skipped'], true);
                });
            });

        return $this->view('document_show', [
            'circulation' => $circulation,
            'document' => $document,
            'users' => $users,
            'current_step' => $current_step,
            'current_target' => $document->targets->firstWhere('user_id', auth()->id()),
            'current_answer' => $document->answers->firstWhere('user_id', auth()->id()),
        ]);
    }

    /**
     * 差戻し案件の修正画面。
     */
    public function documentEdit($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        $document = YuyuCirculationDocument::with('files')
            ->where('circulation_id', $circulation->id)
            ->where('applicant_user_id', auth()->id())
            ->findOrFail($document_id);
        if ($document->status !== 'returned') abort(403);

        return $this->view('document_edit', [
            'circulation' => $circulation,
            'document' => $document,
        ]);
    }

    /**
     * 差戻し案件を修正して再申請する。
     * 同一案件の承認ルートをSTEP1から再開し、過去の操作は履歴に残す。
     */
    public function documentResubmit($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        $document = YuyuCirculationDocument::with(['steps', 'files'])
            ->where('circulation_id', $circulation->id)
            ->where('applicant_user_id', auth()->id())
            ->findOrFail($document_id);
        if ($document->status !== 'returned') abort(403);

        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['integer'],
        ]);
        $validator->setAttributeNames(['title' => '件名', 'body' => '内容', 'attachments' => '添付ファイル']);
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::transaction(function () use ($request, $document) {
            $document->title = $request->title;
            $document->body = $request->body;
            $document->approval_status = 'submitted';
            $document->status = 'submitted';
            $document->submitted_at = now();
            $document->decided_at = null;
            $document->save();

            foreach ($document->steps as $step) {
                $step->status = $step->approver_user_id === $document->applicant_user_id ? 'skipped' : 'pending';
                $step->acted_at = null;
                $step->comment = null;
                $step->save();
            }

            if ($request->filled('remove_files')) {
                YuyuCirculationFile::where('document_id', $document->id)
                    ->whereIn('id', (array)$request->input('remove_files'))
                    ->get()->each->delete();
            }

            YuyuCirculationHistory::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'action' => 'resubmitted',
                'comment' => '差戻し内容を修正して再申請しました。',
                'created_id' => auth()->id(),
                'created_name' => auth()->user()->name,
                'created_at' => now(),
            ]);
        });

        $this->saveAttachments($request, $document);

        $first_step = YuyuCirculationStep::where('document_id', $document->id)
            ->where('status', 'pending')
            ->orderBy('step_no')
            ->first();
        if ($first_step) {
            $this->sendWorkflowNotification(
                [$first_step->approver_user_id],
                $document,
                'approval_requested',
                '再申請された承認・決裁の依頼が届きました。',
                $page_id,
                $frame_id
            );
        }

        return new Collection([
            'redirect_path' => url('/') . $this->page->permanent_link . "#frame-{$frame_id}",
        ]);
    }

    /**
     * 案件添付を非公開領域へ保存する。
     */
    private function saveAttachments($request, YuyuCirculationDocument $document)
    {
        foreach ((array)$request->file('attachments', []) as $file) {
            if (!$file || !$file->isValid()) continue;
            $directory = 'yuyucirculation/' . $document->id;
            $stored_name = uniqid('', true) . '.' . $file->getClientOriginalExtension();
            $target = storage_path('app/' . $directory);
            if (!is_dir($target)) mkdir($target, 0775, true);
            $file->move($target, $stored_name);

            YuyuCirculationFile::create([
                'document_id' => $document->id,
                'original_name' => $file->getClientOriginalName(),
                'stored_name' => $stored_name,
                'directory' => $directory,
                'mime_type' => $file->getClientMimeType(),
                'file_size' => filesize($target . '/' . $stored_name),
                'uploaded_user_id' => auth()->id(),
            ]);
        }
    }

    /**
     * 権限確認後に案件添付をダウンロードする。
     */
    public function documentFileDownload($request, $page_id, $frame_id, $file_id = null)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        $file = YuyuCirculationFile::with('document')->findOrFail($file_id);
        $document = $file->document;
        if (!$document || $document->circulation_id !== $circulation->id) abort(404);

        $allowed = $document->applicant_user_id === auth()->id()
            || $document->steps()->where('approver_user_id', auth()->id())->exists()
            || $document->targets()->where('user_id', auth()->id())->exists();
        if (!$allowed) abort(403);

        $path = storage_path('app/' . $file->directory . '/' . $file->stored_name);
        if (!is_file($path)) abort(404);
        return response()->download($path, $file->original_name);
    }

    /**
     * 現在の承認者による承認。
     */
    public function documentApprove($request, $page_id, $frame_id, $document_id = null)
    {
        return $this->processApprovalAction($request, $page_id, $frame_id, $document_id, 'approve');
    }

    /**
     * 現在の承認者による申請者への差戻し。
     */
    public function documentReturn($request, $page_id, $frame_id, $document_id = null)
    {
        return $this->processApprovalAction($request, $page_id, $frame_id, $document_id, 'return');
    }

    /**
     * 承認／差戻しの共通処理。
     */
    private function processApprovalAction($request, $page_id, $frame_id, $document_id, $action)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'comment' => [$action === 'return' ? 'required' : 'nullable', 'string', 'max:2000'],
        ]);
        $validator->setAttributeNames(['comment' => 'コメント']);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $document = YuyuCirculationDocument::with('steps')
            ->where('circulation_id', $circulation->id)
            ->findOrFail($document_id);

        if (!in_array($document->status, ['submitted', 'in_approval'], true)) {
            return back()->withErrors(['approval' => 'この申請は現在処理できません。']);
        }

        $current_step = $document->steps
            ->where('approver_user_id', auth()->id())
            ->where('status', 'pending')
            ->first(function ($step) use ($document) {
                return !$document->steps->contains(function ($previous_step) use ($step) {
                    return $previous_step->step_no < $step->step_no
                        && !in_array($previous_step->status, ['approved', 'decided', 'skipped'], true);
                });
            });

        if (!$current_step) {
            abort(403);
        }

        DB::transaction(function () use ($request, $document, $current_step, $action) {
            if ($action === 'return') {
                $current_step->status = 'returned';
                $current_step->acted_at = now();
                $current_step->comment = $request->comment;
                $current_step->save();

                $document->approval_status = 'returned';
                $document->status = 'returned';
                $document->save();

                YuyuCirculationHistory::create([
                    'document_id' => $document->id,
                    'step_id' => $current_step->id,
                    'user_id' => auth()->id(),
                    'action' => 'returned',
                    'comment' => $request->comment,
                    'created_id' => auth()->id(),
                    'created_name' => auth()->user()->name,
                    'created_at' => now(),
                ]);
                return;
            }

            $current_step->status = $current_step->action_type === 'decision' ? 'decided' : 'approved';
            $current_step->acted_at = now();
            $current_step->comment = $request->comment;
            $current_step->save();

            YuyuCirculationHistory::create([
                'document_id' => $document->id,
                'step_id' => $current_step->id,
                'user_id' => auth()->id(),
                'action' => $current_step->status,
                'comment' => $request->comment,
                'created_id' => auth()->id(),
                'created_name' => auth()->user()->name,
                'created_at' => now(),
            ]);

            $remaining_step = YuyuCirculationStep::where('document_id', $document->id)
                ->where('step_no', '>', $current_step->step_no)
                ->where('status', 'pending')
                ->orderBy('step_no')
                ->first();

            if ($remaining_step) {
                $document->approval_status = 'in_progress';
                $document->status = 'in_approval';
                $document->save();
                return;
            }

            $document->approval_status = 'decided';
            $document->decided_at = now();

            $template = YuyuCirculationTemplate::with('targets')->find($document->template_id);
            if ($template && $template->post_circulation_enabled) {
                $target_users = collect();
                foreach ($template->targets as $target) {
                    if ($target->target_type === 'applicant_section' && $document->applicant_section_id) {
                        $ids = DB::table('user_sections')->where('section_id', $document->applicant_section_id)->pluck('user_id');
                    } elseif ($target->target_type === 'fixed_section' && $target->section_id) {
                        $ids = DB::table('user_sections')->where('section_id', $target->section_id)->pluck('user_id');
                    } elseif ($target->target_type === 'fixed_group' && $target->group_id) {
                        $ids = DB::table('group_users')->where('group_id', $target->group_id)->whereNull('deleted_at')->pluck('user_id');
                    } elseif ($target->target_type === 'fixed_user' && $target->user_id) {
                        $ids = collect([$target->user_id]);
                    } else {
                        $ids = collect();
                    }
                    $target_users = $target_users->merge($ids);
                }

                $target_users = User::whereIn('id', $target_users->filter()->unique())
                    ->where('status', 0)
                    ->pluck('id')
                    ->unique();

                foreach ($target_users as $user_id) {
                    YuyuCirculationTarget::firstOrCreate(
                        ['document_id' => $document->id, 'user_id' => $user_id],
                        ['target_source' => 'post_decision', 'status' => 'waiting']
                    );
                }

                if ($target_users->isNotEmpty()) {
                    $document->circulation_status = 'in_progress';
                    $document->status = 'in_circulation';
                    $document->circulation_started_at = now();
                } else {
                    $document->circulation_status = 'completed';
                    $document->status = 'completed';
                    $document->completed_at = now();
                }
            } else {
                $document->circulation_status = 'none';
                $document->status = 'completed';
                $document->completed_at = now();
            }
            $document->save();
        });

        $document->refresh();
        if ($action === 'return') {
            $this->sendWorkflowNotification(
                [$document->applicant_user_id],
                $document,
                'returned',
                '申請が差し戻されました。',
                $page_id,
                $frame_id
            );
        } elseif ($document->status === 'in_approval') {
            $next_step = YuyuCirculationStep::where('document_id', $document->id)
                ->where('status', 'pending')
                ->orderBy('step_no')
                ->first();
            if ($next_step) {
                $this->sendWorkflowNotification(
                    [$next_step->approver_user_id],
                    $document,
                    'approval_requested',
                    '承認・決裁の依頼が届きました。',
                    $page_id,
                    $frame_id
                );
            }
        } else {
            $this->sendWorkflowNotification(
                [$document->applicant_user_id],
                $document,
                'decision_completed',
                '申請の決裁が完了しました。',
                $page_id,
                $frame_id
            );

            if ($document->status === 'in_circulation') {
                $target_ids = YuyuCirculationTarget::where('document_id', $document->id)
                    ->where('status', 'waiting')
                    ->pluck('user_id');
                $this->sendWorkflowNotification(
                    $target_ids,
                    $document,
                    'circulation_received',
                    '決裁後回覧が届きました。',
                    $page_id,
                    $frame_id
                );
            }
        }

        return new Collection([
            'redirect_path' => url('/') . $this->page->permanent_link . "#frame-{$frame_id}",
        ]);
    }

    /**
     * 通常回覧への選択肢・テキスト回答。
     */
    public function circulationAnswer($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());
        $document = YuyuCirculationDocument::with('questions.choices')
            ->where('circulation_id', $circulation->id)
            ->where('document_type', 'circulation')
            ->where('status', 'in_circulation')
            ->findOrFail($document_id);
        $question = $document->questions->first();
        if (!$question || !in_array($question->question_type, ['choice', 'text'], true)) abort(403);

        $target = YuyuCirculationTarget::where('document_id', $document->id)->where('user_id', auth()->id())->where('status', 'waiting')->firstOrFail();
        $validator = Validator::make($request->all(), ['answer' => ['required', 'string', 'max:4000']]);
        if ($question->question_type === 'choice') {
            $allowed = $question->choices->pluck('choice_text')->all();
            $validator->after(function ($validator) use ($request, $allowed) {
                if (!in_array($request->answer, $allowed, true)) $validator->errors()->add('answer', '選択肢から回答してください。');
            });
        }
        if ($validator->fails()) return back()->withErrors($validator)->withInput();

        DB::transaction(function () use ($document, $question, $target, $request) {
            YuyuCirculationAnswer::updateOrCreate(
                ['question_id' => $question->id, 'user_id' => auth()->id()],
                ['document_id' => $document->id, 'answer' => $request->answer]
            );
            $target->status = 'confirmed'; $target->confirmed_at = now(); $target->save();
            YuyuCirculationHistory::create([
                'document_id' => $document->id, 'user_id' => auth()->id(), 'action' => 'answered',
                'comment' => '回覧に回答しました。', 'created_id' => auth()->id(),
                'created_name' => auth()->user()->name, 'created_at' => now(),
            ]);
            if (!YuyuCirculationTarget::where('document_id', $document->id)->where('status', 'waiting')->exists()) {
                $document->circulation_status = 'completed'; $document->status = 'completed';
                $document->completed_at = now(); $document->save();
            }
        });
        return new Collection(['redirect_path' => url('/') . "/plugin/yuyucirculation/documentShow/{$page_id}/{$frame_id}/{$document_id}#frame-{$frame_id}"]);
    }

    /**
     * 決裁後回覧の確認。
     */
    public function documentConfirm($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) abort(403);
        $circulation = $this->getPluginBucket($this->getBucketId());

        DB::transaction(function () use ($circulation, $document_id) {
            $document = YuyuCirculationDocument::where('circulation_id', $circulation->id)
                ->where('status', 'in_circulation')
                ->lockForUpdate()
                ->findOrFail($document_id);

            $target = YuyuCirculationTarget::where('document_id', $document->id)
                ->where('user_id', auth()->id())
                ->where('status', 'waiting')
                ->lockForUpdate()
                ->firstOrFail();

            $target->status = 'confirmed';
            $target->confirmed_at = now();
            $target->save();

            YuyuCirculationHistory::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'action' => 'confirmed',
                'comment' => '決裁後回覧を確認しました。',
                'created_id' => auth()->id(),
                'created_name' => auth()->user()->name,
                'created_at' => now(),
            ]);

            if (!YuyuCirculationTarget::where('document_id', $document->id)->where('status', 'waiting')->exists()) {
                $document->circulation_status = 'completed';
                $document->status = 'completed';
                $document->completed_at = now();
                $document->save();
            }
        });

        return new Collection([
            'redirect_path' => url('/') . "/plugin/yuyucirculation/documentShow/{$page_id}/{$frame_id}/{$document_id}#frame-{$frame_id}",
        ]);
    }

    /**
     * アプリ内通知とメール通知を同じイベントから送る。
     */
    private function sendWorkflowNotification($user_ids, YuyuCirculationDocument $document, $type, $message, $page_id, $frame_id)
    {
        $detail_url = url('/') . "/plugin/yuyucirculation/documentShow/{$page_id}/{$frame_id}/{$document->id}#frame-{$frame_id}";
        $circulation = YuyuCirculation::find($document->circulation_id);
        $send_mail = !$circulation || $circulation->mail_notification_enabled;
        (new NotificationService())->notify($user_ids, $document, $type, $message, $detail_url, $send_mail);
    }

    /**
     * 申請者本人による申請取消。
     */
    public function documentCancel($request, $page_id, $frame_id, $document_id = null)
    {
        if (!auth()->check()) {
            abort(403);
        }

        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $document = YuyuCirculationDocument::where('circulation_id', $circulation->id)
            ->where('applicant_user_id', auth()->id())
            ->findOrFail($document_id);

        if (!in_array($document->status, ['submitted', 'in_approval'], true)) {
            return back()->withErrors(['cancel' => 'この申請は取り消せません。']);
        }

        DB::transaction(function () use ($document) {
            $document->approval_status = 'cancelled';
            $document->status = 'cancelled';
            $document->save();

            YuyuCirculationStep::where('document_id', $document->id)
                ->whereIn('status', ['pending', 'waiting'])
                ->update([
                    'status' => 'cancelled',
                    'updated_at' => now(),
                ]);

            YuyuCirculationHistory::create([
                'document_id' => $document->id,
                'user_id' => auth()->id(),
                'action' => 'cancelled',
                'comment' => '申請者本人が申請を取り消しました。',
                'created_id' => auth()->id(),
                'created_name' => auth()->user()->name,
                'created_at' => now(),
            ]);
        });

        return new Collection([
            'redirect_path' => url('/') . $this->page->permanent_link . "#frame-{$frame_id}",
        ]);
    }

    /**
     * 決裁テンプレート一覧。
     */
    public function templateList($request, $page_id, $frame_id)
    {
        $circulation = $this->getPluginBucket($this->getBucketId());
        $templates = collect();

        if ($circulation->exists) {
            $templates = YuyuCirculationTemplate::where('circulation_id', $circulation->id)
                ->with(['steps', 'targets'])
                ->orderBy('id', 'desc')
                ->paginate(20, ['*'], "frame_{$frame_id}_page");
        }

        return $this->view('templates', [
            'circulation' => $circulation,
            'templates' => $templates,
        ]);
    }

    /**
     * 決裁テンプレート新規作成。
     */
    public function templateCreate($request, $page_id, $frame_id)
    {
        return $this->templateEdit($request, $page_id, $frame_id);
    }

    /**
     * 決裁テンプレート編集。
     */
    public function templateEdit($request, $page_id, $frame_id, $template_id = null)
    {
        $circulation = $this->getPluginBucket($this->getBucketId());

        if (!$circulation->exists) {
            return $this->view('template_edit', [
                'circulation' => $circulation,
                'template' => new YuyuCirculationTemplate(),
                'sections' => collect(),
                'groups' => collect(),
                'users' => collect(),
            ]);
        }

        $template = YuyuCirculationTemplate::with(['steps', 'targets'])
            ->where('circulation_id', $circulation->id)
            ->findOrNew($template_id);

        return $this->view('template_edit', [
            'circulation' => $circulation,
            'template' => $template,
            'sections' => Section::orderBy('display_sequence')->orderBy('name')->get(),
            'groups' => Group::orderBy('display_sequence')->orderBy('name')->get(),
            'users' => User::orderBy('name')->get(),
        ]);
    }

    /**
     * 決裁テンプレート保存。
     */
    public function templateSave($request, $page_id, $frame_id, $template_id = null)
    {
        $circulation = $this->getPluginBucket($this->getBucketId());
        if (!$circulation->exists) {
            abort(404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'template_code' => [
                'required', 'alpha_dash', 'max:100',
                Rule::unique('yuyu_circulation_templates', 'template_code')
                    ->where(function ($query) use ($circulation) {
                        return $query->where('circulation_id', $circulation->id)->whereNull('deleted_at');
                    })
                    ->ignore($template_id),
            ],
            'workflow_type' => ['required', Rule::in(['approval', 'decision'])],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.section_mode' => ['required', Rule::in(['applicant_section', 'fixed_section', 'none'])],
            'steps.*.section_id' => ['nullable', 'integer'],
            'steps.*.group_id' => ['required', 'integer'],
            'steps.*.action_type' => ['required', Rule::in(['approval', 'decision'])],
            'targets' => ['nullable', 'array'],
            'targets.*.target_type' => ['required_with:targets', Rule::in(['applicant_section', 'fixed_section', 'fixed_group', 'fixed_user'])],
            'targets.*.section_id' => ['nullable', 'integer'],
            'targets.*.group_id' => ['nullable', 'integer'],
            'targets.*.user_id' => ['nullable', 'integer'],
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ((array)$request->input('steps', []) as $index => $step) {
                if (($step['section_mode'] ?? null) === 'fixed_section' && empty($step['section_id'])) {
                    $validator->errors()->add("steps.{$index}.section_id", '固定所属を選択してください。');
                }
            }

            if ($request->boolean('post_circulation_enabled')) {
                $targets = (array)$request->input('targets', []);
                if (empty($targets)) {
                    $validator->errors()->add('targets', '決裁後回覧を行う場合は回覧先を1件以上設定してください。');
                }

                foreach ($targets as $index => $target) {
                    $type = $target['target_type'] ?? null;
                    if ($type === 'fixed_section' && empty($target['section_id'])) {
                        $validator->errors()->add("targets.{$index}.section_id", '所属を選択してください。');
                    } elseif ($type === 'fixed_group' && empty($target['group_id'])) {
                        $validator->errors()->add("targets.{$index}.group_id", 'グループを選択してください。');
                    } elseif ($type === 'fixed_user' && empty($target['user_id'])) {
                        $validator->errors()->add("targets.{$index}.user_id", 'ユーザーを選択してください。');
                    }
                }
            }
        });

        $validator->setAttributeNames([
            'name' => 'テンプレート名',
            'template_code' => 'テンプレートコード',
            'workflow_type' => '種別',
            'steps' => '承認ルート',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        DB::transaction(function () use ($request, $circulation, $template_id) {
            $template = YuyuCirculationTemplate::where('circulation_id', $circulation->id)->findOrNew($template_id);
            $template->circulation_id = $circulation->id;
            $template->name = $request->name;
            $template->template_code = $request->template_code;
            $template->workflow_type = $request->workflow_type;
            $template->post_circulation_enabled = $request->boolean('post_circulation_enabled');
            $template->is_active = $request->boolean('is_active', true);
            $template->save();

            if ($template_id) {
                YuyuCirculationTemplateStep::where('template_id', $template->id)->get()->each->delete();
                YuyuCirculationTemplateTarget::where('template_id', $template->id)->get()->each->delete();
            }

            foreach ($request->input('steps', []) as $index => $step) {
                YuyuCirculationTemplateStep::create([
                    'template_id' => $template->id,
                    'step_no' => $index + 1,
                    'section_mode' => $step['section_mode'],
                    'section_id' => $step['section_mode'] === 'fixed_section' ? ($step['section_id'] ?? null) : null,
                    'group_id' => $step['group_id'],
                    'action_type' => $step['action_type'],
                ]);
            }

            if ($template->post_circulation_enabled) {
                foreach ($request->input('targets', []) as $index => $target) {
                    YuyuCirculationTemplateTarget::create([
                        'template_id' => $template->id,
                        'sort_order' => $index + 1,
                        'target_type' => $target['target_type'],
                        'section_id' => $target['target_type'] === 'fixed_section' ? ($target['section_id'] ?? null) : null,
                        'group_id' => $target['target_type'] === 'fixed_group' ? ($target['group_id'] ?? null) : null,
                        'user_id' => $target['target_type'] === 'fixed_user' ? ($target['user_id'] ?? null) : null,
                    ]);
                }
            }
        });

        return new Collection([
            'redirect_path' => url('/') . "/plugin/yuyucirculation/templateList/{$page_id}/{$frame_id}#frame-{$frame_id}",
        ]);
    }
}
