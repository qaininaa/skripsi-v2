<?php

namespace Domain\Report\Dtos;

/**
 * Carries the analyst's monitoring form input from the
 * "Pemantauan Ruangan" page into the service layer.
 *
 * Sections payload structure:
 *   sections[{instance_id}][note]
 *   sections[{instance_id}][columns][{idx}][column_label_value]
 *   sections[{instance_id}][columns][{idx}][slots][{label_or_underscore}][time_start]
 *   sections[{instance_id}][columns][{idx}][slots][{label_or_underscore}][time_end]
 */
class SaveMonitoringDto
{
    /** Section 2 — Identitas Instrumen rows, keyed by tool_name. */
    public array $instruments;

    /** Section 3 — Identitas Medium rows, keyed by entry id. */
    public array $mediums;

    /** Section 4 — Inkubator rows, keyed by incubator id. */
    public array $incubators;

    /** Section 5+ — keyed by section_instance id. See class docblock. */
    public array $sections;

    public function __construct(array $data)
    {
        $this->instruments = $data['instruments'] ?? [];
        $this->mediums     = $data['mediums']     ?? [];
        $this->incubators  = $data['incubators']  ?? [];
        $this->sections    = $data['sections']    ?? [];
    }
}
