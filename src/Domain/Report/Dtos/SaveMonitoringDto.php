<?php

namespace Domain\Report\Dtos;

/**
 * Carries the analyst's monitoring form input from the
 * "Pemantauan Ruangan" page into the service layer.
 */
class SaveMonitoringDto
{
    /** Section 2 — Identitas Instrumen rows, keyed by tool_name. */
    public array $instruments;

    /** Section 3 — Identitas Medium rows, keyed by entry id. */
    public array $mediums;

    /**
     * Section 4 — Inkubator rows, keyed by incubator id.
     * Each value: [
     *   'no_id' => string|null,
     *   'calibration_date' => string|null,
     *   'due_date_calibration' => string|null,
     *   'entries' => [ medium_type => [date_in, time_in, date_out, time_out] ]
     * ]
     */
    public array $incubators;

    public function __construct(array $data)
    {
        $this->instruments = $data['instruments'] ?? [];
        $this->mediums     = $data['mediums']     ?? [];
        $this->incubators  = $data['incubators']  ?? [];
    }
}
