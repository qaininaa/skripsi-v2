/**
 * Alpine.js global store for the "Konfirmasi Simpan" + "Simpan & Serahkan"
 * password modal used by the analyst monitoring/reading form.
 *
 * Two variants:
 *   - kind: "draft"     → simple confirmation, single submit button.
 *   - kind: "finalize"  → analyst chooses between draft or finalize, then submits.
 *
 * Usage:
 *   $store.saveConfirmModal.open({
 *     formId: 'monitoring-form',
 *     kind: 'finalize',           // or 'draft'
 *     draftAction: 'draft',
 *     finalizeAction: 'finalize_monitoring',
 *     draftLabel: 'Simpan Monitoring',
 *     finalizeLabel: 'Selesaikan Monitoring & Mulai Pembacaan',
 *     draftDescription: '...',
 *     finalizeDescription: '...',
 *     title: 'Simpan & Serahkan Laporan',
 *   })
 */
export const saveConfirmModalStore = {
    isOpen: false,
    formId: "",
    kind: "draft",
    title: "Konfirmasi Simpan",
    selectedAction: "draft",

    draftAction: "draft",
    finalizeAction: "finalize_monitoring",
    draftLabel: "Simpan Draft",
    finalizeLabel: "Selesaikan",
    draftDescription: "",
    finalizeDescription: "",

    username: "",
    password: "",
    showPassword: false,
    submitLabel: "Simpan",
    usernameError: "",
    passwordError: "",

    open(config = {}) {
        this.formId              = config.formId              ?? "";
        this.kind                = config.kind                ?? "draft";
        this.title               = config.title               ?? "Konfirmasi Simpan";
        this.draftAction         = config.draftAction         ?? "draft";
        this.finalizeAction      = config.finalizeAction      ?? "finalize_monitoring";
        this.draftLabel          = config.draftLabel          ?? "Simpan Draft";
        this.finalizeLabel       = config.finalizeLabel       ?? "Selesaikan";
        this.draftDescription    = config.draftDescription    ?? "";
        this.finalizeDescription = config.finalizeDescription ?? "";
        this.submitLabel         = config.submitLabel         ?? "Simpan";

        this.selectedAction = this.draftAction;
        this.username       = config.username ?? "";
        this.password       = "";
        this.showPassword   = false;
        this.usernameError  = config.usernameError ?? "";
        this.passwordError  = config.passwordError ?? "";
        this.isOpen         = true;
    },

    close() {
        this.isOpen = false;
    },

    submit() {
        const form = document.getElementById(this.formId);
        if (!form) return;

        // Clear any prior auth errors when re-submitting.
        this.usernameError = "";
        this.passwordError = "";

        // Inject (or update) hidden inputs for action / username / password.
        this.setHidden(form, "action",   this.selectedAction);
        this.setHidden(form, "username", this.username);
        this.setHidden(form, "password", this.password);

        form.submit();
    },

    clearUsernameError() {
        this.usernameError = "";
    },

    clearPasswordError() {
        this.passwordError = "";
    },

    setHidden(form, name, value) {
        let el = form.querySelector(`input[name="${name}"][data-confirm="1"]`);
        if (!el) {
            el = document.createElement("input");
            el.type = "hidden";
            el.name = name;
            el.dataset.confirm = "1";
            form.appendChild(el);
        }
        el.value = value;
    },
};
