<?php

namespace App\Services;

use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class DuplicateDetectionService extends BaseService
{
    /**
     * Find potential duplicates for given lead data.
     */
    public function findDuplicates(array $data, ?int $excludeId = null): array
    {
        $phone = $this->normalizePhone($data['phone'] ?? '');
        $email = $this->normalizeEmail($data['email'] ?? '');
        $name = trim($data['name'] ?? '');
        $altPhone = $this->normalizePhone($data['alternate_phone'] ?? '');

        if (empty($phone) && empty($email) && empty($name)) {
            return [];
        }

        // 1. Fetch potential matches (Broad filter)
        $query = Lead::query();
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($phone, $email, $name, $altPhone) {
            if ($phone) {
                $q->orWhere('phone', 'like', "%{$phone}%")
                  ->orWhere('alternate_phone', 'like', "%{$phone}%");
            }
            if ($email) {
                $q->orWhere('email', 'like', "%{$email}%");
            }
            if ($altPhone) {
                $q->orWhere('phone', 'like', "%{$altPhone}%")
                  ->orWhere('alternate_phone', 'like', "%{$altPhone}%");
            }
            if ($name && strlen($name) > 3) {
                $q->orWhere('name', 'like', "%{$name}%");
            }
        });

        $potentialMatches = $query->limit(20)->get();
        $results = [];

        foreach ($potentialMatches as $existing) {
            $scoreData = $this->calculateScore($data, $existing);
            if ($scoreData['score'] >= 30) {
                $results[] = array_merge([
                    'lead' => $existing->load(['assignedTo', 'interestedCourse']),
                ], $scoreData);
            }
        }

        // Sort by score descending
        usort($results, fn($a, $b) => $b['score'] <=> $a['score']);

        return $results;
    }

    /**
     * Batch check for duplicates (optimized for bulk import)
     */
    public function checkBatch(array $leadsData): array
    {
        $results = [];
        foreach ($leadsData as $index => $data) {
            $dupes = $this->findDuplicates($data);
            $results[$index] = [
                'duplicates' => $dupes,
                'action' => !empty($dupes) && $dupes[0]['score'] >= 80 ? 'skip' : (count($dupes) > 0 ? 'warn' : 'proceed')
            ];
        }
        return $results;
    }

    /**
     * Merge two leads
     */
    public function mergeLeads(Lead $primary, Lead $secondary, array $fieldOverrides = []): Lead
    {
        return $this->transactional(function () use ($primary, $secondary, $fieldOverrides) {
            // 1. Update fields on primary
            $updateData = [];
            foreach ($fieldOverrides as $field => $source) {
                if ($source === 'secondary') {
                    $updateData[$field] = $secondary->$field;
                }
            }

            // Also keep best of certain fields if not overridden
            if (!isset($fieldOverrides['email']) && empty($primary->email) && !empty($secondary->email)) {
                $updateData['email'] = $secondary->email;
            }
            if (!isset($fieldOverrides['alternate_phone']) && empty($primary->alternate_phone) && !empty($secondary->alternate_phone)) {
                $updateData['alternate_phone'] = $secondary->alternate_phone;
            }

            if (!empty($updateData)) {
                $primary->update($updateData);
            }

            // 2. Transfer relationships
            $secondary->callLogs()->update(['lead_id' => $primary->id]);
            $secondary->enrollments()->update(['lead_id' => $primary->id]);

            // 3. Log the merge
            $this->logActivity('lead.merged', $primary, [
                'merged_lead_id' => $secondary->id,
                'merged_lead_name' => $secondary->name
            ]);

            // 4. Delete secondary
            $secondary->delete();

            return $primary;
        });
    }

    /**
     * Calculate score between incoming and existing lead
     */
    private function calculateScore(array $incoming, Lead $existing): array
    {
        $score = 0;
        $matchedFields = [];

        $incPhone = $this->normalizePhone($incoming['phone'] ?? '');
        $extPhone = $this->normalizePhone($existing->phone ?? '');
        $incEmail = $this->normalizeEmail($incoming['email'] ?? '');
        $extEmail = $this->normalizeEmail($existing->email ?? '');
        $incAlt = $this->normalizePhone($incoming['alternate_phone'] ?? '');
        $extAlt = $this->normalizePhone($existing->alternate_phone ?? '');
        $incName = strtolower(trim($incoming['name'] ?? ''));
        $extName = strtolower(trim($existing->name ?? ''));

        // Phone matches
        if ($incPhone && $extPhone && $incPhone === $extPhone) {
            $score += 50; $matchedFields[] = 'phone';
        } elseif ($incPhone && $extAlt && $incPhone === $extAlt) {
            $score += 40; $matchedFields[] = 'phone (cross)';
        } elseif ($incAlt && $extPhone && $incAlt === $extPhone) {
            $score += 40; $matchedFields[] = 'alternate_phone (cross)';
        }

        // Email match
        if ($incEmail && $extEmail && $incEmail === $extEmail) {
            $score += 30; $matchedFields[] = 'email';
        }

        // Name match
        if ($incName && $extName) {
            if ($incName === $extName) {
                $score += 25; $matchedFields[] = 'name';
            } else {
                similar_text($incName, $extName, $percent);
                if ($percent > 85) {
                    $score += 20; $matchedFields[] = 'name (fuzzy)';
                }
            }
        }

        // City match
        if (!empty($incoming['city']) && !empty($existing->city) && strtolower($incoming['city']) === strtolower($existing->city)) {
            $score += 5;
        }

        $tier = 'possible';
        if ($score >= 80) $tier = 'definite';
        elseif ($score >= 50) $tier = 'probable';

        return [
            'score' => min(100, $score),
            'tier' => $tier,
            'matched_fields' => $matchedFields
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        // Remove Indian prefix combinations
        if (str_starts_with($phone, '91')) $phone = substr($phone, 2);
        if (str_starts_with($phone, '0')) $phone = substr($phone, 1);
        return $phone;
    }

    private function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }
}
