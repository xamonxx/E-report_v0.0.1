<?php

namespace App\Services;

use App\Jobs\ProcessConsultationImportJob;
use App\Models\Account;
use App\Models\Consultation;
use App\Models\ConsultationImport;
use App\Models\NeedsCategory;
use App\Models\StatusCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use SplFileObject;
use Throwable;

class ConsultationImportService
{
    private const CHUNK_SIZE = 500;

    public function queue(UploadedFile $file, User $user): ConsultationImport
    {
        $storedPath = $file->store('imports/consultations');

        $import = ConsultationImport::create([
            'user_id' => $user->id,
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'status' => 'queued',
        ]);

        ProcessConsultationImportJob::dispatch($import->id);

        return $import;
    }

    public function process(ConsultationImport $import): void
    {
        $import->refresh();

        if ($import->status === 'completed') {
            return;
        }

        if ($import->status === 'processing' && $import->started_at) {
            return;
        }

        $import->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_preview' => null,
            'total_rows' => 0,
            'success_count' => 0,
            'duplicate_count' => 0,
            'error_count' => 0,
        ]);

        try {
            [$defaultStatus, $defaultCategory] = $this->resolveDefaults();
            $validAccountIds = Account::query()->pluck('id')->all();

            $file = $this->openFile($import->stored_path);
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY);
            $file->setCsvControl(',');

            $rowNumber = 0;
            $chunk = [];
            $seenPhoneKeys = [];
            $seenProfileKeys = [];
            $errors = [];
            $successCount = 0;
            $duplicateCount = 0;
            $errorCount = 0;
            $totalRows = 0;

            foreach ($file as $row) {
                if (!is_array($row) || $row === [null]) {
                    continue;
                }

                $rowNumber++;

                if ($rowNumber === 1) {
                    continue;
                }

                $totalRows++;
                $parsed = $this->parseCsvRow($row, $rowNumber, $import->user, $validAccountIds);

                if (is_string($parsed)) {
                    $errorCount++;
                    $errors[] = $parsed;
                    continue;
                }

                $chunk[] = $parsed;

                if (count($chunk) >= self::CHUNK_SIZE) {
                    [$inserted, $duplicates] = $this->flushChunk(
                        $chunk,
                        $seenPhoneKeys,
                        $seenProfileKeys,
                        $defaultCategory->id,
                        $defaultStatus->id,
                        $import->user_id
                    );

                    $successCount += $inserted;
                    $duplicateCount += $duplicates;
                    $chunk = [];
                }
            }

            if ($chunk !== []) {
                [$inserted, $duplicates] = $this->flushChunk(
                    $chunk,
                    $seenPhoneKeys,
                    $seenProfileKeys,
                    $defaultCategory->id,
                    $defaultStatus->id,
                    $import->user_id
                );

                $successCount += $inserted;
                $duplicateCount += $duplicates;
            }

            $import->update([
                'status' => 'completed',
                'total_rows' => $totalRows,
                'success_count' => $successCount,
                'duplicate_count' => $duplicateCount,
                'error_count' => $errorCount,
                'error_preview' => $this->summarizeErrors($errors),
                'finished_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $import->update([
                'status' => 'failed',
                'error_preview' => $exception->getMessage(),
                'finished_at' => now(),
            ]);

            throw $exception;
        }
    }

    private function resolveDefaults(): array
    {
        $defaultStatus = StatusCategory::query()->orderBy('sort_order')->first();
        $defaultCategory = NeedsCategory::query()->forConsultationOptions()->first() ?? NeedsCategory::query()->orderBy('name')->first();

        if (!$defaultStatus || !$defaultCategory) {
            throw new RuntimeException('Master data Status atau Produk belum tersedia.');
        }

        return [$defaultStatus, $defaultCategory];
    }

    private function openFile(string $storedPath): SplFileObject
    {
        $absolutePath = Storage::path($storedPath);

        if (!is_file($absolutePath)) {
            throw new RuntimeException('File import tidak ditemukan di storage.');
        }

        return new SplFileObject($absolutePath);
    }

    private function flushChunk(
        array $chunk,
        array &$seenPhoneKeys,
        array &$seenProfileKeys,
        int $defaultCategoryId,
        int $defaultStatusId,
        int $createdBy
    ): array {
        $inserted = 0;
        $duplicates = 0;

        foreach ($chunk as $row) {
            $phoneKey = Consultation::buildLeadPhoneKey($row['account_id'], $row['phone']);
            $profileKey = Consultation::buildLeadProfileKey([
                'account_id' => $row['account_id'],
                'client_name' => $row['client_name'],
                'phone' => $row['phone'],
                'province' => $row['province'] ?? null,
                'city' => $row['city'] ?? null,
                'district' => $row['district'] ?? null,
                'address' => $row['address'] ?? null,
                'product_details' => $row['product_details'] ?? null,
                'needs_category_ids' => [$defaultCategoryId],
            ]);

            if (
                isset($seenPhoneKeys[$phoneKey])
                || isset($seenProfileKeys[$profileKey])
                || Consultation::findDuplicateLead([
                    'account_id' => $row['account_id'],
                    'client_name' => $row['client_name'],
                    'phone' => $row['phone'],
                    'province' => $row['province'] ?? null,
                    'city' => $row['city'] ?? null,
                    'district' => $row['district'] ?? null,
                    'address' => $row['address'] ?? null,
                    'product_details' => $row['product_details'] ?? null,
                    'needs_category_ids' => [$defaultCategoryId],
                ])
            ) {
                $duplicates++;
                continue;
            }

            $consultation = DB::transaction(function () use ($createdBy, $defaultCategoryId, $defaultStatusId, $row) {
                $duplicate = Consultation::findDuplicateLead([
                    'account_id' => $row['account_id'],
                    'client_name' => $row['client_name'],
                    'phone' => $row['phone'],
                    'province' => $row['province'] ?? null,
                    'city' => $row['city'] ?? null,
                    'district' => $row['district'] ?? null,
                    'address' => $row['address'] ?? null,
                    'product_details' => $row['product_details'] ?? null,
                    'needs_category_ids' => [$defaultCategoryId],
                ]);

                if ($duplicate) {
                    return null;
                }

                return Consultation::create([
                    'consultation_id' => Consultation::generateConsultationId($row['account_id']),
                    'client_name' => $row['client_name'],
                    'phone' => $row['phone'],
                    'province' => $row['province'] ?? null,
                    'city' => $row['city'] ?? null,
                    'district' => $row['district'] ?? null,
                    'address' => $row['address'] ?? null,
                    'account_id' => $row['account_id'],
                    'needs_category_id' => $defaultCategoryId,
                    'product_details' => $row['product_details'] ?? null,
                    'status_category_id' => $defaultStatusId,
                    'notes' => null,
                    'created_by' => $createdBy,
                    'consultation_date' => now()->toDateString(),
                ]);
            }, 3);

            if (! $consultation) {
                $duplicates++;
                continue;
            }

            if (Consultation::hasNeedsCategoryPivot()) {
                $consultation->needsCategories()->sync([$defaultCategoryId]);
            }

            $seenPhoneKeys[$phoneKey] = true;
            $seenProfileKeys[$profileKey] = true;
            $inserted++;
        }

        return [$inserted, $duplicates];
    }

    private function parseCsvRow(array $row, int $rowNumber, ?User $user, array $validAccountIds): array|string
    {
        if (!$user) {
            return "Baris {$rowNumber}: user import tidak ditemukan.";
        }

        if (count($row) < 2) {
            return "Baris {$rowNumber}: kolom tidak lengkap (minimal 2 kolom).";
        }

        $clientName = preg_replace('/^[=+\-\@\t\r\n]/', '', trim((string) ($row[0] ?? '')));
        $phone = preg_replace('/^[=+\-\@\t\r\n]/', '', trim((string) ($row[1] ?? '')));

        if ($clientName === '' || $phone === '') {
            return "Baris {$rowNumber}: nama klien atau telepon kosong.";
        }

        if ($user->isAdmin()) {
            $accountId = $user->account_id;
        } else {
            $rawAccountId = trim((string) ($row[2] ?? ''));

            if ($rawAccountId !== '' && !in_array((int) $rawAccountId, $validAccountIds, true)) {
                return "Baris {$rowNumber}: Akun ID '{$rawAccountId}' tidak ditemukan di database.";
            }

            $accountId = $rawAccountId !== '' ? (int) $rawAccountId : ($validAccountIds[0] ?? null);
        }

        if (!$accountId) {
            return "Baris {$rowNumber}: Tidak ada akun tersedia.";
        }

        return [
            'client_name' => $clientName,
            'phone' => $phone,
            'province' => null,
            'city' => null,
            'district' => null,
            'address' => null,
            'product_details' => null,
            'account_id' => (int) $accountId,
        ];
    }

    private function summarizeErrors(array $errors): ?string
    {
        if ($errors === []) {
            return null;
        }

        return collect($errors)->take(10)->implode("\n");
    }
}
