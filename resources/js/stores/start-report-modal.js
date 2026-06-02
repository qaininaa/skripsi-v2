/**
 * Alpine.js global store for the "Mulai Pengerjaan Laporan" confirmation modal.
 *
 * Usage: $store.startReportModal.open({ action, productName, batchNumber })
 */
export const startReportModalStore = {
    isOpen: false,
    action: "#",
    productName: "",
    batchNumber: "",

    open(config = {}) {
        this.action      = config.action      ?? "#";
        this.productName = config.productName ?? "";
        this.batchNumber = config.batchNumber ?? "";
        this.isOpen      = true;
    },

    close() {
        this.isOpen = false;
    },
};
