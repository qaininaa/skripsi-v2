/**
 * Alpine.js component for the Report Template create/edit form.
 *
 * Registered via Alpine.data() — scoped per component instance.
 * Each x-data="reportTemplateForm(...)" call gets its own isolated state.
 *
 * @param {Array<{name: string}>}                   initialMediums    - Pre-populated medium rows (for edit)
 * @param {Array<{label: string, min_day: number}>} initialIncubators - Pre-populated incubator rows (for edit)
 */
export function reportTemplateForm(initialMediums = [], initialIncubators = []) {
    return {
        mediums: initialMediums,
        incubators: initialIncubators,

        addMedium() {
            this.mediums.push({ name: "" });
        },

        removeMedium(index) {
            this.mediums.splice(index, 1);
        },

        addIncubator() {
            this.incubators.push({ label: "", min_day: "" });
        },

        removeIncubator(index) {
            this.incubators.splice(index, 1);
        },

        /**
         * Append " °C" to the incubator label on blur.
         * Strips any existing suffix first to prevent duplication on re-edit.
         *
         * @param {number} index - Index of the incubator row
         */
        formatIncubatorLabel(index) {
            const raw = this.incubators[index].label.replace(/\s*°C$/i, "").trim();
            if (raw !== "") {
                this.incubators[index].label = raw + " °C";
            }
        },
    };
}
