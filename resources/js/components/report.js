const MICROBIAL_PATTERN = /^(<1|tntc|[1-9][0-9]?|1[0-9]{2}|200)$/i;
const POS_INF = Number.POSITIVE_INFINITY;

const parseSupervisorOptions = () => {
    const el = document.getElementById("report-save-supervisor-options");

    if (!el) {
        return [];
    }

    try {
        return JSON.parse(el.textContent || "[]");
    } catch {
        return [];
    }
};

const rehydrateSaveConfirmModal = () => {
    if (!window.Alpine) {
        return;
    }

    const el = document.getElementById("save-confirm-rehydrate");

    if (!el) {
        return;
    }

    const d = el.dataset;
    const store = Alpine.store("saveConfirmModal");

    store.open({
        formId: d.formId,
        kind: d.kind,
        title: d.title,
        draftAction: d.draftAction,
        finalizeAction: d.finalizeAction,
        draftLabel: d.draftLabel,
        finalizeLabel: d.finalizeLabel,
        draftDescription: d.draftDescription,
        finalizeDescription: d.finalizeDescription,
        message: d.message,
        username: d.username,
        usernameError: d.usernameError,
        passwordError: d.passwordError,
        submitLabel: d.submitLabel,
        requiresSupervisor: d.requiresSupervisor === "1",
        supervisorOptions: parseSupervisorOptions(),
        selectedSupervisorId: d.selectedSupervisorId,
        supervisorError: d.supervisorError,
    });

    store.selectedAction = d.selectedAction;
};

const validateMicrobialInput = (el) => {
    const value = (el.value || "").trim();
    const ok = value === "" || MICROBIAL_PATTERN.test(value);

    el.classList.toggle("border-red-500", !ok);
    el.classList.toggle("ring-2", !ok);
    el.classList.toggle("ring-red-100", !ok);

    return ok;
};

const initMicrobialValidation = () => {
    document.querySelectorAll("input[data-microbial]").forEach((el) => {
        validateMicrobialInput(el);
        el.addEventListener("input", () => validateMicrobialInput(el));
        el.addEventListener("blur", () => validateMicrobialInput(el));
    });
};

const parseMicrobialValue = (raw) => {
    if (raw === null || raw === undefined) {
        return null;
    }

    const value = String(raw).trim().toLowerCase();

    if (value === "" || !MICROBIAL_PATTERN.test(value)) {
        return null;
    }

    if (value === "<1") {
        return 0;
    }

    if (value === "tntc") {
        return POS_INF;
    }

    return parseInt(value, 10);
};

const collectReadingsByRow = (sectionEl) => {
    const map = {};
    const inputs = sectionEl.querySelectorAll("[data-reading][data-row-id]");

    inputs.forEach((el) => {
        const rowId = el.dataset.rowId;
        const kind = el.dataset.reading;
        const col = el.dataset.colIndex ?? "0";

        if (!rowId || !kind) {
            return;
        }

        const raw = "value" in el ? el.value : el.textContent;
        const num = parseMicrobialValue(raw);

        if (num === null) {
            return;
        }

        map[rowId] ??= { byCol: {}, totals: [], fungis: [] };
        map[rowId].byCol[col] ??= { total: null, fungi: null };

        if (map[rowId].byCol[col][kind] === null) {
            map[rowId].byCol[col][kind] = num;
            (kind === "total" ? map[rowId].totals : map[rowId].fungis).push(num);
        }
    });

    return map;
};

const renderRowVerdict = (cell, verdict) => {
    if (verdict === "TMS") {
        cell.innerHTML =
            '<span class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-700">TMS</span>';
        return;
    }

    if (verdict === "MS") {
        cell.innerHTML =
            '<span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">MS</span>';
        return;
    }

    cell.innerHTML = '<span class="italic text-gray-300">N/A</span>';
};

const renderSectionVerdict = (cell, verdict) => {
    Array.from(cell.children).forEach((child, idx) => {
        if (idx === 0) {
            return;
        }

        child.remove();
    });

    const badge = document.createElement("span");

    if (verdict === "TMS") {
        badge.className =
            "rounded-full bg-red-100 px-3 py-0.5 text-xs font-semibold text-red-700";
        badge.textContent = "TMS";
    } else if (verdict === "MS") {
        badge.className =
            "rounded-full bg-emerald-100 px-3 py-0.5 text-xs font-semibold text-emerald-700";
        badge.textContent = "MS";
    } else {
        badge.className = "text-xs italic text-gray-400";
        badge.textContent = "Belum ada data";
    }

    cell.appendChild(badge);
};

const formatTotal = (bacteri, fungi) => {
    if (bacteri === null && fungi === null) {
        return "N/A";
    }

    if (bacteri === POS_INF || fungi === POS_INF) {
        return "TNTC";
    }

    const bacteriCount = bacteri ?? 0;
    const fungiCount = fungi ?? 0;

    return bacteriCount === 0 && fungiCount === 0
        ? "<1"
        : String(bacteriCount + fungiCount);
};

const renderTotalCells = (sectionEl, rowMap) => {
    Object.entries(rowMap).forEach(([rowId, data]) => {
        Object.entries(data.byCol).forEach(([col, vals]) => {
            const cell = sectionEl.querySelector(
                `[data-total-cell][data-row-id="${CSS.escape(rowId)}"][data-col-index="${CSS.escape(col)}"]`,
            );

            if (cell) {
                cell.textContent = formatTotal(vals.total, vals.fungi);
            }
        });
    });
};

const recomputeSection = (sectionEl) => {
    const rowMap = collectReadingsByRow(sectionEl);
    const rows = sectionEl.querySelectorAll("[data-conclusion-row]");
    let anyTms = false;
    let anyAssessed = false;

    rows.forEach((rowEl) => {
        const rowId = rowEl.dataset.rowId;
        const actionTotal =
            rowEl.dataset.actionTotal === "" ? null : Number(rowEl.dataset.actionTotal);
        const actionFungi =
            rowEl.dataset.actionFungi === "" ? null : Number(rowEl.dataset.actionFungi);
        const data = rowMap[rowId];
        const hasReading = data && (data.totals.length > 0 || data.fungis.length > 0);
        let verdict = null;

        if (hasReading) {
            anyAssessed = true;

            const breachT =
                actionTotal !== null && data.totals.some((count) => count >= actionTotal);
            const breachF =
                actionFungi !== null && data.fungis.some((count) => count >= actionFungi);

            verdict = breachT || breachF ? "TMS" : "MS";
            anyTms = anyTms || verdict === "TMS";
        }

        const cell = sectionEl.querySelector(
            `[data-row-conclusion-cell][data-row-id="${CSS.escape(rowId)}"]`,
        );

        if (cell) {
            renderRowVerdict(cell, verdict);
        }
    });

    renderTotalCells(sectionEl, rowMap);

    const sectionCell = sectionEl.querySelector("[data-section-conclusion-cell]");

    if (sectionCell) {
        renderSectionVerdict(sectionCell, anyAssessed ? (anyTms ? "TMS" : "MS") : null);
    }
};

const initReadingConclusion = () => {
    document.querySelectorAll("[data-conclusion-section]").forEach(recomputeSection);

    document.querySelectorAll("input[data-reading]").forEach((el) => {
        el.addEventListener("input", () => {
            const sectionEl = el.closest("[data-conclusion-section]");

            if (sectionEl) {
                recomputeSection(sectionEl);
            }
        });
    });
};

export const initReportPage = () => {
    if (!document.getElementById("report-form")) {
        return;
    }

    initMicrobialValidation();
    initReadingConclusion();
};

document.addEventListener("DOMContentLoaded", initReportPage);
document.addEventListener("alpine:initialized", rehydrateSaveConfirmModal);
