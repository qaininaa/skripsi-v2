/**
 * Alpine.js global store for the shared delete confirmation modal.
 *
 * Used by the x-buttons.delete Blade component to trigger a modal
 * with dynamic title, description, warning, and form action.
 *
 * Usage: $store.deleteModal.open({ action, title, description, warning })
 */
export const deleteModalStore = {
    isOpen: false,
    action: "#",
    title: "Hapus Item",
    description: "Yakin ingin menghapus data ini?",
    warning: "Tindakan ini tidak dapat dibatalkan.",
    confirmLabel: "Ya, Hapus",
    cancelLabel: "Batal",

    open(config = {}) {
        this.action       = config.action       ?? "#";
        this.title        = config.title        ?? "Hapus Item";
        this.description  = config.description  ?? "Yakin ingin menghapus data ini?";
        this.warning      = config.warning      ?? "Tindakan ini tidak dapat dibatalkan.";
        this.confirmLabel = config.confirmLabel ?? "Ya, Hapus";
        this.cancelLabel  = config.cancelLabel  ?? "Batal";
        this.isOpen       = true;
    },

    close() {
        this.isOpen = false;
    },
};
