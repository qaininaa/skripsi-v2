import "./bootstrap";
import Alpine from "alpinejs";
import { deleteModalStore } from "./stores/delete-modal";
import { startReportModalStore } from "./stores/start-report-modal";
import { saveConfirmModalStore } from "./stores/save-confirm-modal";
import { duplicateSectionModalStore } from "./stores/duplicate-section-modal";
import "./components/report";
import { reportTemplateForm } from "./components/report-template-form";

Alpine.store("deleteModal", deleteModalStore);
Alpine.store("startReportModal", startReportModalStore);
Alpine.store("saveConfirmModal", saveConfirmModalStore);
Alpine.store("duplicateSectionModal", duplicateSectionModalStore);
Alpine.data("reportTemplateForm", reportTemplateForm);

const formatNowTime = () => {
    const now = new Date();
    const hours = String(now.getHours()).padStart(2, "0");
    const minutes = String(now.getMinutes()).padStart(2, "0");

    return `${hours}:${minutes}`;
};

window.qcSetTimeInputNow = (inputId) => {
    const input = document.getElementById(inputId);

    if (!input || input.disabled) {
        return;
    }

    input.value = formatNowTime();
    input.dispatchEvent(new Event("input", { bubbles: true }));
    input.dispatchEvent(new Event("change", { bubbles: true }));
};

window.Alpine = Alpine;
Alpine.start();
