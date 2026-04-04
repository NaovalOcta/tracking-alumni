<?php

namespace App\Services;

class PriorityResolverEngine
{
    public const HIERARCHY = [
        'linkedin'              => 1,
        'company_official_site' => 2,
        'verified_news'         => 3,
        'aggregator'            => 4,
        'social_media'          => 5,
    ];

    public const MAX_CONFLICT_PENALTY = 0.30;
    public const PENALTY_PER_CONFLICT = 0.15;

    /**
     * Resolve conflicts based on deterministic hierarchy
     *
     * @param array $geminiOutput
     * @param array $evidences
     * @return array
     */
    public function resolve(array $geminiOutput, array $evidences = []): array
    {
        $resolvedData = $geminiOutput['extracted_data'] ?? [];
        $extractedConflicts = $geminiOutput['extracted_conflicts'] ?? [];
        
        $conflictTrace = [];
        $totalPenalty = 0.0;

        foreach ($extractedConflicts as $conflict) {
            $field = $conflict['field'] ?? 'unknown';
            $primaryValue = $conflict['primary_value'] ?? null;
            $primaryUrl = $conflict['primary_source_url'] ?? null;
            $conflictValue = $conflict['conflicting_value'] ?? null;
            $conflictUrl = $conflict['conflicting_source_url'] ?? null;
            
            if (!$primaryUrl || !$conflictUrl) {
                continue; // Cannot resolve without URLs
            }

            $primaryType = $this->classifySource($primaryUrl);
            $conflictType = $this->classifySource($conflictUrl);

            $primaryRank = self::HIERARCHY[$primaryType] ?? 4;
            $conflictRank = self::HIERARCHY[$conflictType] ?? 4;

            $resolution = 'kept_primary';
            $rejectedValues = [$conflictValue];
            $resolutionReason = "Primary source ({$primaryType}) rank $primaryRank vs Conflict ({$conflictType}) rank $conflictRank. Primary kept.";

            // Lower rank number means higher priority
            if ($conflictRank < $primaryRank) {
                $resolvedData[$field] = $conflictValue;
                $resolution = 'overridden_by_conflict';
                $rejectedValues = [$primaryValue];
                $resolutionReason = "Conflict source ({$conflictType}) rank $conflictRank outranks Primary ({$primaryType}) rank $primaryRank. Overridden.";
            }

            // Apply penalty per conflict
            $totalPenalty += self::PENALTY_PER_CONFLICT;

            $conflictTrace[] = [
                'field' => $field,
                'values' => [
                    'primary' => $primaryValue,
                    'conflict' => $conflictValue
                ],
                'urls' => [
                    'primary' => $primaryUrl,
                    'conflict' => $conflictUrl
                ],
                'resolution' => $resolution,
                'rejected_values' => $rejectedValues,
                'resolution_reason' => $resolutionReason,
            ];
        }

        // Cap penalty (Max 0.30)
        $totalPenalty = min($totalPenalty, self::MAX_CONFLICT_PENALTY);

        return [
            'resolved_data' => $resolvedData,
            'conflict_trace' => $conflictTrace,
            'penalties_applied' => $totalPenalty
        ];
    }

    /**
     * Classify source URL into predefined hierarchy types
     *
     * @param string $url
     * @return string
     */
    public function classifySource(string $url): string
    {
        $urlLower = strtolower($url);

        if (str_contains($urlLower, 'linkedin.com')) {
            return 'linkedin';
        }

        if (
            str_contains($urlLower, 'kompas') ||
            str_contains($urlLower, 'detik') ||
            str_contains($urlLower, 'cnbc') ||
            str_contains($urlLower, 'tribun') ||
            str_contains($urlLower, 'liputan6') ||
            str_contains($urlLower, 'tempo')
        ) {
            return 'verified_news';
        }

        if (
            str_contains($urlLower, 'instagram') ||
            str_contains($urlLower, 'facebook') ||
            str_contains($urlLower, 'tiktok') ||
            str_contains($urlLower, 'twitter') ||
            str_contains($urlLower, 'x.com')
        ) {
            return 'social_media';
        }

        if (
            str_contains($urlLower, 'medium') ||
            str_contains($urlLower, 'blogspot') ||
            str_contains($urlLower, 'wordpress') ||
            str_contains($urlLower, 'quora')
        ) {
            return 'aggregator';
        }

        if (preg_match('/(?:go\.id|co\.id|com)(?:\/|$|\?)/i', $urlLower)) {
            return 'company_official_site';
        }

        return 'aggregator';
    }

    /**
     * Find primary source type from evidences
     *
     * @param array $evidences
     * @return string
     */
    public function findPrimarySourceType(array $evidences): string
    {
        $bestRank = 99;
        $bestType = 'aggregator';

        foreach ($evidences as $evidence) {
            $url = $evidence['url'] ?? '';
            if ($url) {
                $type = $this->classifySource($url);
                $rank = self::HIERARCHY[$type] ?? 4;
                if ($rank < $bestRank) {
                    $bestRank = $rank;
                    $bestType = $type;
                }
            }
        }

        return $bestType;
    }
}
