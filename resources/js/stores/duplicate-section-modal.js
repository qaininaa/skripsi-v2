/**
 * Alpine.js global store for the admin "Duplicate Section" modal.
 *
 * Usage: $store.duplicateSectionModal.open({ action, sectionLabel })
 */
export const duplicateSectionModalStore = {
    isOpen: false,
    action: "#",
    sectionLabel: "",
    reason: "",

    open(config = {}) {
        this.action       = config.action       ?? "#";
        this.sectionLabel = config.sectionLabel ?? "";
        this.reason       = "";
        this.isOpen       = true;
    },

    close() {
        this.isOpen = false;
    },
};
