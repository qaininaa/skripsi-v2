import "./bootstrap";
import Alpine from "alpinejs";
import { deleteModalStore } from "./stores/delete-modal";
import { reportTemplateForm } from "./components/report-template-form";

Alpine.store("deleteModal", deleteModalStore);
Alpine.data("reportTemplateForm", reportTemplateForm);

window.Alpine = Alpine;
Alpine.start();
