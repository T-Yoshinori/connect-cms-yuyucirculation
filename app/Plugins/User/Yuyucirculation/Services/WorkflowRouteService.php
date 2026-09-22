<?php

namespace App\Plugins\User\Yuyucirculation\Services;

use App\Enums\UserStatus;
use App\Models\Core\UserSection;
use App\Models\User\YuyuCirculation\YuyuCirculationTemplate;
use App\Models\User\YuyuCirculation\YuyuCirculationTemplateStep;
use App\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * 決裁テンプレートの Section × Group 条件から実承認者を解決する。
 */
class WorkflowRouteService
{
    /**
     * テンプレートの全STEPを実承認者へ展開する。
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function resolve(YuyuCirculationTemplate $template, User $applicant): Collection
    {
        $applicant_section_id = $this->getApplicantSectionId($applicant);

        return $template->steps()
            ->orderBy('step_no')
            ->get()
            ->map(function (YuyuCirculationTemplateStep $step) use ($applicant, $applicant_section_id) {
                $section_id = $this->resolveSectionId($step, $applicant_section_id);
                $approver = $this->resolveApprover($step, $section_id);

                return [
                    'step_no' => $step->step_no,
                    'action_type' => $step->action_type,
                    'section_id' => $section_id,
                    'group_id' => $step->group_id,
                    'approver_user_id' => $approver->id,
                    'status' => $approver->id === $applicant->id ? 'skipped' : 'pending',
                ];
            });
    }

    /**
     * 申請者の所属を取得する。
     */
    private function getApplicantSectionId(User $applicant): int
    {
        $user_section = UserSection::where('user_id', $applicant->id)->first();

        if (!$user_section) {
            throw new RuntimeException('申請者の所属が設定されていません。');
        }

        return (int)$user_section->section_id;
    }

    /**
     * STEPの所属条件を具体的な section_id に変換する。
     */
    private function resolveSectionId(YuyuCirculationTemplateStep $step, int $applicant_section_id): ?int
    {
        if ($step->section_mode === 'applicant_section') {
            return $applicant_section_id;
        }

        if ($step->section_mode === 'fixed_section') {
            if (empty($step->section_id)) {
                throw new RuntimeException("STEP{$step->step_no}の固定所属が設定されていません。");
            }

            return (int)$step->section_id;
        }

        if ($step->section_mode === 'none') {
            return null;
        }

        throw new RuntimeException("STEP{$step->step_no}の所属条件が不正です。");
    }

    /**
     * Section × Group から利用可能な承認者を一意に特定する。
     */
    private function resolveApprover(YuyuCirculationTemplateStep $step, ?int $section_id): User
    {
        $query = User::query()
            ->select('users.*')
            ->join('group_users', 'group_users.user_id', '=', 'users.id')
            ->where('group_users.group_id', $step->group_id)
            ->whereNull('group_users.deleted_at')
            ->where('users.status', UserStatus::active);

        if (!is_null($section_id)) {
            $query->join('user_sections', 'user_sections.user_id', '=', 'users.id')
                ->where('user_sections.section_id', $section_id);
        }

        $users = $query->distinct()->get();

        if ($users->isEmpty()) {
            throw new RuntimeException("STEP{$step->step_no}の承認者が見つかりません。");
        }

        if ($users->count() > 1) {
            throw new RuntimeException("STEP{$step->step_no}の承認者が複数存在するため、一意に決定できません。");
        }

        return $users->first();
    }
}
