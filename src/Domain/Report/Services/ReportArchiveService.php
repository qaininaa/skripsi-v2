<?php

namespace Domain\Report\Services;

use Domain\Report\Dtos\GetArchiveReportsFilterDto;
use Domain\Report\Interfaces\ReportRepositoryInterface;
use Domain\Report\Interfaces\SectionInstanceRepositoryInterface;
use Domain\Report\Models\Report;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ReportArchiveService
{
    /**
     * @var array<int, array{slug: string, code: string, subtitle: string, annex_numbers: array<int, int>}>
     */
    private const FOLDERS = [
        [
            'slug' => 'ahu-6-1-1-a',
            'code' => 'AHU 6.1.1 A',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.1 A Filling Line 1',
            'annex_numbers' => [17],
        ],
        [
            'slug' => 'ahu-6-1-1-b',
            'code' => 'AHU 6.1.1 B',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.1 B Filling Line 2',
            'annex_numbers' => [18],
        ],
        [
            'slug' => 'ahu-6-2-1',
            'code' => 'AHU 6.2.1',
            'subtitle' => 'Laporan Pemantauan Ruangan Laboratorium Mikrobiologi HVAC 6.2.1',
            'annex_numbers' => [21, 40],
        ],
        [
            'slug' => 'ahu-6-2-2',
            'code' => 'AHU 6.2.2',
            'subtitle' => 'Laporan Pemantauan Ruangan Laboratorium Mikrobiologi HVAC 6.2.2',
            'annex_numbers' => [22],
        ],
        [
            'slug' => 'ahu-6-2-3',
            'code' => 'AHU 6.2.3',
            'subtitle' => 'Laporan Pemantauan Ruangan Laboratorium Mikrobiologi HVAC 6.2.3',
            'annex_numbers' => [23, 38],
        ],
        [
            'slug' => 'ahu-6-1-5',
            'code' => 'AHU 6.1.5',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.5',
            'annex_numbers' => [24],
        ],
        [
            'slug' => 'ahu-6-1-2-mingguan',
            'code' => 'AHU 6.1.2 Mingguan',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.2 Mingguan',
            'annex_numbers' => [34],
        ],
        [
            'slug' => 'ahu-6-1-2-bulanan',
            'code' => 'AHU 6.1.2 Bulanan',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.2 Bulanan',
            'annex_numbers' => [35],
        ],
        [
            'slug' => 'ahu-6-1-3-monthly-weekly',
            'code' => 'AHU 6.1.3 Monthly and Weekly',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.3 Monthly dan Weekly',
            'annex_numbers' => [36],
        ],
        [
            'slug' => 'ahu-6-1-3-enam-bulan',
            'code' => 'AHU 6.1.3 Setiap 6 Bulan',
            'subtitle' => 'Laporan Pemantauan Ruangan Produksi Injeksi HVAC 6.1.3 Setiap 6 Bulan',
            'annex_numbers' => [37],
        ],
    ];

    public function __construct(
        protected ReportRepositoryInterface $reports,
        protected SectionInstanceRepositoryInterface $sectionInstances,
    ) {}

    /**
     * @return array<int, array{slug: string, code: string, subtitle: string, annex_numbers: array<int, int>, count: int}>
     */
    public function getFoldersWithCounts(): array
    {
        return array_map(function (array $folder) {
            $folder['count'] = $this->reports->countArchivedReports($folder['annex_numbers']);

            return $folder;
        }, self::FOLDERS);
    }

    /**
     * @return array{slug: string, code: string, subtitle: string, annex_numbers: array<int, int>}|null
     */
    public function findFolderBySlug(?string $slug): ?array
    {
        if ($slug === null) {
            return null;
        }

        foreach (self::FOLDERS as $folder) {
            if ($folder['slug'] === $slug) {
                return $folder;
            }
        }

        return null;
    }

    /**
     * @return LengthAwarePaginator|null
     */
    public function getReportsForFolder(GetArchiveReportsFilterDto $dto)
    {
        $folder = $this->findFolderBySlug($dto->folder);
        if ($folder === null) {
            return null;
        }

        return $this->reports->getArchivedReports($dto, $folder['annex_numbers']);
    }

    /**
     * Find one archived report eligible to be shown in Archive.
     */
    public function findArchivedReportById(string $reportId): ?Report
    {
        return $this->reports->findArchivedReportById($reportId);
    }

    /**
     * Build all data needed by the archive detail page.
     *
     * @return array{report: Report, activeFolder: array|null, sectionInstances: mixed, lockMap: array}|null
     */
    public function getArchivedReportDetailData(string $reportId): ?array
    {
        $report = $this->findArchivedReportById($reportId);
        if ($report === null) {
            return null;
        }

        $bundle = $this->sectionInstances->getInstancesForReportWithLocks($report);

        return [
            'report' => $report,
            'activeFolder' => $this->resolveFolderForReport($report),
            'sectionInstances' => $bundle['instances'],
            'lockMap' => $bundle['locks'],
        ];
    }

    /**
     * Resolve folder metadata for a report, using its annex number.
     *
     * @return array{slug: string, code: string, subtitle: string, annex_numbers: array<int, int>}|null
     */
    public function resolveFolderForReport(Report $report): ?array
    {
        $annexNumber = (int) ($report->reportTemplate?->annex_number ?? 0);

        foreach (self::FOLDERS as $folder) {
            if (in_array($annexNumber, $folder['annex_numbers'], true)) {
                return $folder;
            }
        }

        return null;
    }
}
