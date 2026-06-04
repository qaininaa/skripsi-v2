@extends('layouts.app')

@section('title', 'Pengisian Laporan')
@section('page-title', 'Pengisian Laporan')

@section('content')
    <datalist id="microbial-count-options">
        <option value="&lt;1"></option>
        <option value="TNTC"></option>
    </datalist>

    @php
        use Domain\Report\Models\Incubator;
        use Domain\Report\Models\IncubatorEntry;
        use Domain\Report\Models\InstrumentEntry;
        use Domain\Report\Models\Analyst;
        use Domain\Report\Models\MediumEntry;
        use Domain\Report\Models\Report;
        use Domain\Report\Models\ReportApproval;
        use Domain\Report\Services\MonitoringService;

        $template = $report->reportTemplate;
        $hasSwab  = $template?->hasSwab() ?? false;

        // Section 2: Identitas Instrumen — Air Sampler is the only tracked instrument.
        $instrumentEntries = $report->instrumentEntries
            ->sortBy(fn ($i) => $i->tool_name === 'Swab Kit' ? 1 : 0)
            ->values();

        if ($instrumentEntries->isEmpty()) {
            $instrumentEntries = collect([
                new InstrumentEntry(['tool_name' => 'Air Sampler']),
            ]);
        }

        // Section 3: Identitas Medium — fall back to MediumTemplate rows.
        $mediumEntries = $report->mediumEntries
            ->sortBy(fn ($m) => $m->is_swab ? 1 : 0)
            ->values();

        if ($mediumEntries->isEmpty() && $template) {
            $mediumEntries = $template->mediumTemplates->map(function ($mt) {
                $entry          = new MediumEntry();
                $entry->id      = 'preview-' . $mt->id;
                $entry->name    = $mt->name;
                $entry->is_swab = str_contains(strtolower($mt->name), 'swab');
                return $entry;
            })->sortBy(fn ($m) => $m->is_swab ? 1 : 0)->values();
        }

        // Section 4: Inkubasi — build preview Incubators from IncubatorTemplate.
        $incubators = $report->incubators;

        if ($incubators->isEmpty() && $template) {
            $incubators = $template->incubatorTemplates->map(function ($it) use ($hasSwab) {
                $incubator             = new Incubator();
                $incubator->id         = 'preview-' . $it->id;
                $incubator->setRelation('template', $it);

                $entries = collect();
                $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                    $e->id          = 'preview-mon-' . $it->id;
                    $e->medium_type = 'monitoring';
                }));
                if ($hasSwab) {
                    $entries->push(tap(new IncubatorEntry(), function ($e) use ($it) {
                        $e->id          = 'preview-swab-' . $it->id;
                        $e->medium_type = 'swab';
                    }));
                }
                $incubator->setRelation('entries', $entries);

                return $incubator;
            });
        }

        // Phase-driven submit handlers.
        $phase ??= $report->isReadingPhase() ? 'reading' : 'monitoring';

        $formAction = $phase === 'reading'
            ? route('report.save-reading', $report)
            : route('report.save-monitoring', $report);

        // Inline "Simpan Draft" button keeps the lock and stays on this page.
        $draftAction        = 'draft';

        // Modal "Simpan & Serahkan" → first option releases the lock to others.
        $releaseAction      = 'release';
        $releaseLabel       = $phase === 'reading'
            ? 'Simpan Pembacaan'
            : 'Simpan Monitoring';
        $releaseDescription = $phase === 'reading'
            ? 'Draft tersimpan, analis lain bisa melanjutkan pembacaan.'
            : 'Draft tersimpan, analis lain bisa melanjutkan monitoring.';

        // Modal second option — finalize the phase.
        $finalizeAction      = $phase === 'reading' ? 'finalize_reading' : 'finalize_monitoring';
        $finalizeLabel       = $phase === 'reading'
            ? 'Selesaikan Pembacaan & Kirim Review'
            : 'Selesaikan Monitoring & Mulai Pembacaan';
        $finalizeDescription = $phase === 'reading'
            ? 'Data pembacaan sudah lengkap, kirim ke supervisor untuk review.'
            : 'Data monitoring sudah lengkap, lanjut ke tahap baca.';

        $isAdmin = optional(auth()->user())->role === 'admin';
        $previewOnly = $previewOnly ?? false;
        $supervisorOptions = collect($supervisors ?? [])
            ->map(fn ($supervisor) => [
                'id' => (string) $supervisor->id,
                'name' => $supervisor->name,
            ])
            ->values();

        $currentUserId = (string) (auth()->id() ?? '');
        $returnedApproval = $report->approvals
            ->where('status', ReportApproval::STATUS_RETURNED)
            ->filter(fn ($approval) => (string) $approval->returned_to_user_id === $currentUserId)
            ->sortByDesc(fn ($approval) => $approval->updated_at?->getTimestamp() ?? 0)
            ->first();
        $isRevisionForMe = $returnedApproval !== null;

        $hasMonitoringRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_MONITORING
                && (string) $analyst->user_id === $currentUserId
        );
        $hasReadingRole = $report->analysts->contains(
            fn ($analyst) => $analyst->type === Analyst::TYPE_READING
                && (string) $analyst->user_id === $currentUserId
        );
        $isDualRoleRevision = $isRevisionForMe
            && $hasMonitoringRole
            && $hasReadingRole;

        $isMonitoringRevisionMode = ! $readonly
            && $phase === 'monitoring'
            && $isRevisionForMe
            && $hasMonitoringRole;
        $isReadingRevisionSendOnlyMode = ! $readonly
            && $isRevisionForMe
            && $phase === 'reading';

        $toReadingAction = MonitoringService::ACTION_TO_READING;
        $sendToSupervisorAction = MonitoringService::ACTION_FINALIZE_TO_REVIEW;

        if ($isReadingRevisionSendOnlyMode) {
            $finalizeLabel = 'Kirim ke Supervisor Langsung';
            $finalizeDescription = 'Kirim hasil revisi pembacaan langsung ke supervisor.';
        }
    @endphp

    {{-- Header --}}
    <div class="mb-4 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('report.index') }}"
               class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-700">
                <span class="text-xl leading-none font-light">&lt;</span><span>Kembali</span>
            </a>
            <div>
                @if ($template)
                    <div class="flex items-center gap-2 text-base font-semibold text-gray-800">
                        <span class="font-semibold text-gray-700">Annex {{ $template->annex_number }}</span>
                        <span class="text-gray-400">—</span>
                        <span class="font-semibold text-gray-700">{{ $template->name }}</span>
                        <x-badges.report-status :status="$report->status" />
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-500">
                    {{ $report->product_name }} · Batch {{ $report->batch_number }} · {{ $report->created_at->translatedFormat('d M Y') }}
                </div>
            </div>
        </div>

        @unless ($readonly)
            <div class="flex items-center gap-2">
                @if ($isReadingRevisionSendOnlyMode)
                    <button
                        type="button"
                        @click.prevent="
                            $store.saveConfirmModal.open({
                                formId: 'report-form',
                                kind: 'draft',
                                title: 'Konfirmasi Kirim Laporan',
                                draftAction: @js($finalizeAction),
                                selectedAction: @js($finalizeAction),
                                submitLabel: 'Kirim Laporan',
                                message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                                requiresSupervisor: true,
                                supervisorOptions: @js($supervisorOptions),
                            });
                        "
                        class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
                    >
                        Kirim ke Supervisor
                    </button>
                @elseif ($isMonitoringRevisionMode)
                    @if ($isDualRoleRevision)
                        <button
                            type="button"
                            @click.prevent="
                                $store.saveConfirmModal.open({
                                    formId: 'report-form',
                                    kind: 'draft',
                                    title: 'Konfirmasi Lanjut ke Pembacaan',
                                    draftAction: @js($toReadingAction),
                                    selectedAction: @js($toReadingAction),
                                    submitLabel: 'Lanjut ke Pembacaan',
                                });
                            "
                            class="inline-flex items-center rounded-xl bg-amber-500 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-amber-600 cursor-pointer"
                        >
                            Lanjut ke Pembacaan
                        </button>
                    @endif
                    <button
                        type="button"
                        @click.prevent="
                            $store.saveConfirmModal.open({
                                formId: 'report-form',
                                kind: 'draft',
                                title: 'Konfirmasi Kirim Laporan',
                                draftAction: @js($sendToSupervisorAction),
                                selectedAction: @js($sendToSupervisorAction),
                                submitLabel: 'Kirim Laporan',
                                message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                                requiresSupervisor: true,
                                supervisorOptions: @js($supervisorOptions),
                            });
                        "
                        class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
                    >
                        Kirim ke Supervisor
                    </button>
                @else
                    <x-buttons.save-draft
                        :form-id="'report-form'"
                        :draft-action="$draftAction"
                    />
                    @if ($phase === 'reading')
                        <button
                            type="button"
                            @click.prevent="
                                $store.saveConfirmModal.open({
                                    formId: 'report-form',
                                    kind: 'draft',
                                    title: 'Simpan & Serahkan Laporan',
                                    draftAction: @js($releaseAction),
                                    selectedAction: @js($releaseAction),
                                    submitLabel: 'Simpan Pembacaan',
                                    message: @js($releaseDescription),
                                });
                            "
                            class="inline-flex items-center rounded-xl bg-sky-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-sky-700 cursor-pointer"
                        >
                            Simpan & Serahkan
                        </button>
                        <button
                            type="button"
                            @click.prevent="
                                $store.saveConfirmModal.open({
                                    formId: 'report-form',
                                    kind: 'draft',
                                    title: 'Konfirmasi Kirim Laporan',
                                    draftAction: @js($finalizeAction),
                                    selectedAction: @js($finalizeAction),
                                    submitLabel: 'Kirim Laporan',
                                    message: 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.',
                                    requiresSupervisor: true,
                                    supervisorOptions: @js($supervisorOptions),
                                });
                            "
                            class="inline-flex items-center rounded-xl bg-blue-600 px-6 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700 cursor-pointer"
                        >
                            Kirim ke Supervisor
                        </button>
                    @else
                        <x-buttons.save-submit
                            :form-id="'report-form'"
                            :draft-action="$releaseAction"
                            :finalize-action="$finalizeAction"
                            :draft-label="$releaseLabel"
                            :finalize-label="$finalizeLabel"
                            :draft-description="$releaseDescription"
                            :finalize-description="$finalizeDescription"
                        />
                    @endif
                @endif
                {{--
                <button
                    type="button"
                    @click.prevent="$store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'draft',
                        title: 'Konfirmasi Simpan Draft',
                        draftAction: @js($draftAction),
                        submitLabel: 'Simpan',
                    })"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    <span>Simpan Draft</span>
                </button>
                <button
                    type="button"
                    @click.prevent="$store.saveConfirmModal.open({
                        formId: 'report-form',
                        kind: 'finalize',
                        title: 'Simpan & Serahkan Laporan',
                        draftAction: @js($releaseAction),
                        finalizeAction: @js($finalizeAction),
                        draftLabel: @js($releaseLabel),
                        finalizeLabel: @js($finalizeLabel),
                        draftDescription: @js($releaseDescription),
                        finalizeDescription: @js($finalizeDescription),
                    })"
                    class="inline-flex items-center gap-2 rounded-lg bg-sky-500 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-sky-600"
                >
                    <span>Simpan & Serahkan</span>
                </button>
                --}}
            </div>
        @endunless
    </div>

    <x-messages.success-message />
    <x-messages.error-message />
    <x-messages.validation-errors :except="['username', 'password', 'supervisor_id']" />

    @if ($isRevisionForMe && $returnedApproval !== null)
        <div class="mb-5 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4">
            <div class=" font-semibold text-amber-900">Laporan dikembalikan untuk direvisi</div>
            @if (! empty($returnedApproval->notes))
                <p class="mt-1 text-amber-700 text-sm">“{{ $returnedApproval->notes }}”</p>
            @endif
            <p class="mt-1 text-sm text-amber-700">— {{ $returnedApproval->user?->name ?? $returnedApproval->role_label }}</p>
        </div>
    @endif

    <form
        id="report-form"
        action="{{ $formAction }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @method('PUT')

        <x-report-form.section-room-monitoring :report="$report" :readonly="$readonly" :preview-only="$previewOnly" />

        <x-report-form.section-instrument-identity
            :instrument-entries="$instrumentEntries"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-medium-identity
            :medium-entries="$mediumEntries"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        <x-report-form.section-incubation
            :incubators="$incubators"
            :has-swab="$hasSwab"
            :readonly="$readonly || $phase === 'reading'"
            :lock-map="$lockMap ?? []"
        />

        @foreach ($sectionInstances as $instance)
            <x-report-form.section-instance
                :instance="$instance"
                :report="$report"
                :phase="$phase"
                :readonly="$readonly"
                :is-admin="false"
                :lock-map="$lockMap ?? []"
            />
        @endforeach
    </form>

    @push('scripts')
        @php
            $usernameError = $errors->first('username');
            $passwordError = $errors->first('password');
            $supervisorError = $errors->first('supervisor_id');
            $hasAuthError  = $usernameError !== '' || $passwordError !== '' || $supervisorError !== '';
            $oldUsername   = old('username', '');
            $oldAction     = old('action', $isReadingRevisionSendOnlyMode ? $finalizeAction : $draftAction);
            $isRevisionQuickAction = in_array($oldAction, [$toReadingAction, $sendToSupervisorAction], true);
            $requiresSupervisor = ($phase === 'reading' && $oldAction === $finalizeAction)
                || $oldAction === $sendToSupervisorAction;
            $modalMessage = '';
            if ($requiresSupervisor) {
                $modalKind = 'draft';
                $modalTitle = 'Konfirmasi Kirim Laporan';
                $effectiveDraftAction = $oldAction;
                $effectiveSubmitLabel = 'Kirim Laporan';
                $modalMessage = 'Setelah dikirim, data tidak dapat diubah lagi. Masukkan username dan password Anda untuk melanjutkan.';
            } elseif ($phase === 'reading' && $oldAction === $releaseAction) {
                $modalKind = 'draft';
                $modalTitle = 'Simpan & Serahkan Laporan';
                $effectiveDraftAction = $releaseAction;
                $effectiveSubmitLabel = 'Simpan Pembacaan';
                $modalMessage = $releaseDescription;
            } else {
                $modalKind     = $isRevisionQuickAction
                    ? 'draft'
                    : (old('action') === $finalizeAction || old('action') === $releaseAction ? 'finalize' : 'draft');
                $modalTitle    = $isRevisionQuickAction
                    ? ($oldAction === $toReadingAction ? 'Konfirmasi Lanjut ke Pembacaan' : 'Konfirmasi Kirim ke Supervisor')
                    : ($modalKind === 'finalize' ? 'Simpan & Serahkan Laporan' : 'Konfirmasi Simpan Draft');
                $effectiveDraftAction = $isRevisionQuickAction
                    ? $oldAction
                    : ($modalKind === 'finalize' ? $releaseAction : $draftAction);
                $effectiveSubmitLabel = $oldAction === $toReadingAction
                    ? 'Lanjut ke Pembacaan'
                    : ($oldAction === $sendToSupervisorAction ? 'Kirim ke Supervisor' : 'Simpan');
            }
            $oldSupervisorId = old('supervisor_id', '');
        @endphp

        @if ($hasAuthError)
            <div
                id="save-confirm-rehydrate"
                data-form-id="report-form"
                data-kind="{{ $modalKind }}"
                data-title="{{ $modalTitle }}"
                data-draft-action="{{ $effectiveDraftAction }}"
                data-finalize-action="{{ $finalizeAction }}"
                data-draft-label="{{ $releaseLabel }}"
                data-finalize-label="{{ $finalizeLabel }}"
                data-draft-description="{{ $releaseDescription }}"
                data-finalize-description="{{ $finalizeDescription }}"
                data-message="{{ $modalMessage }}"
                data-username="{{ $oldUsername }}"
                data-username-error="{{ $usernameError }}"
                data-password-error="{{ $passwordError }}"
                data-supervisor-error="{{ $supervisorError }}"
                data-selected-supervisor-id="{{ $oldSupervisorId }}"
                data-requires-supervisor="{{ $requiresSupervisor ? '1' : '0' }}"
                data-selected-action="{{ $oldAction }}"
                data-submit-label="{{ $effectiveSubmitLabel }}"
                hidden
            ></div>
            <script>
                document.addEventListener('alpine:initialized', () => {
                    if (! window.Alpine) return;
                    const el = document.getElementById('save-confirm-rehydrate');
                    if (! el) return;
                    const d = el.dataset;
                    const store = Alpine.store('saveConfirmModal');
                    store.open({
                        formId:              d.formId,
                        kind:                d.kind,
                        title:               d.title,
                        draftAction:         d.draftAction,
                        finalizeAction:      d.finalizeAction,
                        draftLabel:          d.draftLabel,
                        finalizeLabel:       d.finalizeLabel,
                        draftDescription:    d.draftDescription,
                        finalizeDescription: d.finalizeDescription,
                        message:             d.message,
                        username:            d.username,
                        usernameError:       d.usernameError,
                        passwordError:       d.passwordError,
                        submitLabel:         d.submitLabel,
                        requiresSupervisor:  d.requiresSupervisor === '1',
                        supervisorOptions:   @js($supervisorOptions),
                        selectedSupervisorId: d.selectedSupervisorId,
                        supervisorError:     d.supervisorError,
                    });
                    // Re-select the previously chosen action.
                    store.selectedAction = d.selectedAction;
                });
            </script>
        @endif

        <script>
            // Live border-red feedback for microbial value inputs.
            // Allowed: empty, "<1", "tntc" (case-insensitive), positive int 1..200.
            (function () {
                const PATTERN = /^(<1|tntc|[1-9][0-9]?|1[0-9]{2}|200)$/i;
                const inputs = document.querySelectorAll('input[data-microbial]');
                const validate = (el) => {
                    const v = (el.value || '').trim();
                    const ok = v === '' || PATTERN.test(v);
                    el.classList.toggle('border-red-500', !ok);
                    el.classList.toggle('ring-2',          !ok);
                    el.classList.toggle('ring-red-100',    !ok);
                    return ok;
                };
                inputs.forEach((el) => {
                    validate(el);
                    el.addEventListener('input', () => validate(el));
                    el.addEventListener('blur',  () => validate(el));
                });
            })();

            // Live MS/TMS conclusion compute on every reading input change.
            //
            // Parsing:
            //   ""      → null (skip)
            //   "<1"    → 0
            //   "tntc"  → +Infinity
            //   pos int → that number (no leading zero, "0" rejected)
            //
            // Per row aggregation:
            //   Per (row, col) we have a B and an F. T = B+F (Infinity if either is TNTC).
            //   A row is TMS when ANY column's B ≥ alert_action_total OR F ≥ alert_action_fungi.
            //   No readings → null. Otherwise MS.
            //
            // Per section:
            //   Any row TMS → section TMS. All MS → MS. All null → "Belum ada data".
            (function () {
                const PATTERN = /^(<1|tntc|[1-9][0-9]?|1[0-9]{2}|200)$/i;
                const POS_INF = Number.POSITIVE_INFINITY;

                const parseVal = (raw) => {
                    if (raw === null || raw === undefined) return null;
                    const v = String(raw).trim().toLowerCase();
                    if (v === '') return null;
                    if (!PATTERN.test(v)) return null;
                    if (v === '<1') return 0;
                    if (v === 'tntc') return POS_INF;
                    return parseInt(v, 10);
                };

                /**
                 * Read all reading inputs/spans in the section, indexed by row+col+kind.
                 * Different entries may share (row, col, kind) when sub_columns A/B exist;
                 * we keep the first non-null per (row, col, kind).
                 */
                const collectByRow = (sectionEl) => {
                    const map = {}; // rowId → { byCol: { col → {total, fungi} }, totals: [], fungis: [] }
                    const inputs = sectionEl.querySelectorAll('[data-reading][data-row-id]');
                    inputs.forEach((el) => {
                        const rowId = el.dataset.rowId;
                        const kind  = el.dataset.reading;
                        const col   = el.dataset.colIndex ?? '0';
                        if (! rowId || ! kind) return;

                        const raw = ('value' in el) ? el.value : el.textContent;
                        const num = parseVal(raw);
                        if (num === null) return;

                        if (! map[rowId]) {
                            map[rowId] = { byCol: {}, totals: [], fungis: [] };
                        }
                        if (! map[rowId].byCol[col]) {
                            map[rowId].byCol[col] = { total: null, fungi: null };
                        }
                        // Keep the first non-null value for the (row, col, kind) cell.
                        if (map[rowId].byCol[col][kind] === null) {
                            map[rowId].byCol[col][kind] = num;
                            (kind === 'total' ? map[rowId].totals : map[rowId].fungis).push(num);
                        }
                    });
                    return map;
                };

                const renderRowVerdict = (cell, verdict) => {
                    if (verdict === 'TMS') {
                        cell.innerHTML = '<span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">TMS</span>';
                    } else if (verdict === 'MS') {
                        cell.innerHTML = '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">MS</span>';
                    } else {
                        cell.innerHTML = '<span class="italic text-gray-300">N/A</span>';
                    }
                };

                const renderSectionVerdict = (cell, verdict) => {
                    // Strip everything after the leading "Kesimpulan:" label.
                    Array.from(cell.children).forEach((child, idx) => {
                        if (idx === 0) return;
                        child.remove();
                    });
                    const badge = document.createElement('span');
                    if (verdict === 'TMS') {
                        badge.className = 'rounded-full bg-red-100 px-3 py-0.5 text-xs font-semibold text-red-700';
                        badge.textContent = 'TMS';
                    } else if (verdict === 'MS') {
                        badge.className = 'rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-700';
                        badge.textContent = 'MS';
                    } else {
                        badge.className = 'text-xs italic text-gray-400';
                        badge.textContent = 'Belum ada data';
                    }
                    cell.appendChild(badge);
                };

                const renderTotalCells = (sectionEl, rowMap) => {
                    Object.entries(rowMap).forEach(([rowId, data]) => {
                        Object.entries(data.byCol).forEach(([col, vals]) => {
                            const cell = sectionEl.querySelector(
                                `[data-total-cell][data-row-id="${CSS.escape(rowId)}"][data-col-index="${CSS.escape(col)}"]`
                            );
                            if (! cell) return;

                            cell.textContent = formatTotal(vals.total, vals.fungi);
                        });
                    });
                };

                /**
                 * Display rule for T (Total = B + F):
                 *   - both null              → "N/A"
                 *   - any TNTC (+Infinity)   → "TNTC"
                 *   - both "<1" (0)          → "<1"
                 *   - one "<1" (0), other n  → n
                 *   - both numeric           → sum
                 */
                const formatTotal = (b, f) => {
                    if (b === null && f === null) return 'N/A';
                    if (b === POS_INF || f === POS_INF) return 'TNTC';
                    const bn = b ?? 0;
                    const fn = f ?? 0;
                    if (bn === 0 && fn === 0) {
                        // Either explicit "<1" + "<1", or one filled with "<1" and the
                        // other still empty. In either case we treat the row as below
                        // the limit of detection.
                        return '<1';
                    }
                    return String(bn + fn);
                };

                const recomputeSection = (sectionEl) => {
                    const rowMap = collectByRow(sectionEl);
                    const rows = sectionEl.querySelectorAll('[data-conclusion-row]');
                    let anyTms = false;
                    let anyAssessed = false;

                    rows.forEach((rowEl) => {
                        const rowId = rowEl.dataset.rowId;
                        const actionTotal = rowEl.dataset.actionTotal === '' ? null : Number(rowEl.dataset.actionTotal);
                        const actionFungi = rowEl.dataset.actionFungi === '' ? null : Number(rowEl.dataset.actionFungi);

                        const data = rowMap[rowId];
                        const hasReading = data && (data.totals.length > 0 || data.fungis.length > 0);

                        let verdict = null;
                        if (hasReading) {
                            anyAssessed = true;
                            const breachT = (actionTotal !== null) && data.totals.some((n) => n >= actionTotal);
                            const breachF = (actionFungi !== null) && data.fungis.some((n) => n >= actionFungi);
                            verdict = (breachT || breachF) ? 'TMS' : 'MS';
                            if (verdict === 'TMS') anyTms = true;
                        }

                        const cell = sectionEl.querySelector(
                            `[data-row-conclusion-cell][data-row-id="${CSS.escape(rowId)}"]`
                        );
                        if (cell) renderRowVerdict(cell, verdict);
                    });

                    renderTotalCells(sectionEl, rowMap);

                    const sectionVerdict = anyAssessed ? (anyTms ? 'TMS' : 'MS') : null;
                    const sectionCell = sectionEl.querySelector('[data-section-conclusion-cell]');
                    if (sectionCell) renderSectionVerdict(sectionCell, sectionVerdict);
                };

                const recomputeAll = () => {
                    document.querySelectorAll('[data-conclusion-section]').forEach(recomputeSection);
                };

                // Initial paint to align UI with whatever is preloaded.
                recomputeAll();

                // React to every reading input change.
                document.querySelectorAll('input[data-reading]').forEach((el) => {
                    el.addEventListener('input', () => {
                        const sectionEl = el.closest('[data-conclusion-section]');
                        if (sectionEl) recomputeSection(sectionEl);
                    });
                });
            })();
        </script>
    @endpush
@endsection
