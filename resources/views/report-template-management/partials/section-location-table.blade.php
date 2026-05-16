{{--
    Partial: section-location-table.blade.php
    Renders the list of locations assigned to a section.

    Required variables:
      $reportTemplate — ReportTemplate model
      $section        — Section model (with loaded locations.room)
--}}
<div class="mt-2 overflow-x-auto">
    <table class="min-w-full text-xs">
        <thead>
            <tr class="border-b border-gray-200">
                <th class="py-2 pr-4 text-left font-semibold text-gray-500">Ruangan</th>
                <th class="py-2 pr-4 text-left font-semibold text-gray-500">Kelas</th>
                <th class="py-2 pr-4 text-left font-semibold text-gray-500">No. Ruangan</th>
                <th class="py-2 pr-4 text-left font-semibold text-gray-500">No. Lokasi</th>
                <th class="py-2 pr-4 text-left font-semibold text-gray-500">Tipe</th>
                <th class="py-2 pr-4 text-right font-semibold text-gray-500">Alert B</th>
                <th class="py-2 pr-4 text-right font-semibold text-gray-500">Action B</th>
                <th class="py-2 pr-4 text-right font-semibold text-gray-500">Alert F</th>
                <th class="py-2 pr-4 text-right font-semibold text-gray-500">Action F</th>
                <th class="py-2 text-center font-semibold text-gray-500">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach ($section->locations as $location)
                <tr>
                    <td class="py-2 pr-4 font-medium text-gray-700">{{ $location->room->name }}</td>
                    <td class="py-2 pr-4">
                        <x-badges.room-class :class="$location->room->class ?? null" />
                    </td>
                    <td class="py-2 pr-4 text-gray-600">{{ $location->room->room_number ?? '-' }}</td>
                    <td class="py-2 pr-4 text-gray-600">{{ $location->loc_number }}</td>
                    <td class="py-2 pr-4"><x-badges.measurement-type :type="$location->measurement_type" /></td>
                    <td class="py-2 pr-4 text-right text-gray-600">{{ $location->alert_limit_total ?? '-' }}</td>
                    <td class="py-2 pr-4 text-right text-gray-600">{{ $location->alert_action_total ?? '-' }}</td>
                    <td class="py-2 pr-4 text-right text-gray-600">{{ $location->alert_limit_fungi ?? '-' }}</td>
                    <td class="py-2 pr-4 text-right text-gray-600">{{ $location->alert_action_fungi ?? '-' }}</td>
                    <td class="py-2 text-center">
                        <x-buttons.delete
                            :action="route('report-templates.sections.locations.remove', [$reportTemplate, $section, $location])"
                            title="Hapus Lokasi"
                            :item-name="$location->loc_number . ' (' . $location->room->name . ')'"
                            warning="Lokasi di section ini akan dihapus."
                            confirm-label="Ya, Hapus"
                            label="Hapus"
                        />
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
