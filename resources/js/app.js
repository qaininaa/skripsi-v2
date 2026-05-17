import "./bootstrap";
import Alpine from "alpinejs";
import { deleteModalStore } from "./stores/delete-modal";
import { startReportModalStore } from "./stores/start-report-modal";
import { reportTemplateForm } from "./components/report-template-form";

Alpine.store("deleteModal", deleteModalStore);
Alpine.store("startReportModal", startReportModalStore);
Alpine.data("reportTemplateForm", reportTemplateForm);

window.Alpine = Alpine;
Alpine.start();
