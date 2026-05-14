import "./bootstrap";
import Alpine from "alpinejs";

window.Alpine = Alpine;

Alpine.store("deleteModal", {
    isOpen: false,
    action: "#",
    title: "Hapus Item",
    description: "Yakin ingin menghapus data ini?",
    warning: "Tindakan ini tidak dapat dibatalkan.",
    confirmLabel: "Ya, Hapus",
    cancelLabel: "Batal",
    open(config = {}) {
        this.action = config.action ?? "#";
        this.title = config.title ?? "Hapus Item";
        this.description =
            config.description ?? "Yakin ingin menghapus data ini?";
        this.warning = config.warning ?? "Tindakan ini tidak dapat dibatalkan.";
        this.confirmLabel = config.confirmLabel ?? "Ya, Hapus";
        this.cancelLabel = config.cancelLabel ?? "Batal";
        this.isOpen = true;
    },
    close() {
        this.isOpen = false;
    },
});

Alpine.start();
