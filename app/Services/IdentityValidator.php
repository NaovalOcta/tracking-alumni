<?php

namespace App\Services;

class IdentityValidator
{
    protected PriorityResolverEngine $priorityResolver;

    public function __construct(PriorityResolverEngine $priorityResolver)
    {
        $this->priorityResolver = $priorityResolver;
    }

    /**
     * Validate alumni identity against evidences
     *
     * @param array $alumniData
     * @param array $evidences
     * @return array
     */
    public function validate(array $alumniData, array $evidences): array
    {
        if (empty($evidences)) {
            return [
                'identity_confidence' => 0.0,
                'gate_status' => 'REJECT',
                'signals' => [
                    'name_similarity' => 0.0,
                    'company_alignment' => 0.0,
                    'cross_source_bonus' => 0.0
                ]
            ];
        }

        $nameSimilarity = $this->calculateNameSimilarity($alumniData, $evidences);
        $companyAlignment = $this->calculateCompanyAlignment($evidences);
        $crossSourceBonus = $this->calculateCrossSourceBonus($alumniData, $evidences);

        // Formula MUST match EXACTLY: (0.5 * Name) + (0.35 * Company) + (0.15 * Cross Source)
        $identityScore = (0.5 * $nameSimilarity) + (0.35 * $companyAlignment) + (0.15 * $crossSourceBonus);
        
        $gateStatus = 'REJECT';
        if ($identityScore >= 0.85) {
            $gateStatus = 'STRONG';
        } elseif ($identityScore >= 0.70) {
            $gateStatus = 'WEAK';
        }

        return [
            'identity_confidence' => round($identityScore, 4),
            'gate_status' => $gateStatus,
            'signals' => [
                'name_similarity' => $nameSimilarity,
                'company_alignment' => $companyAlignment,
                'cross_source_bonus' => $crossSourceBonus
            ]
        ];
    }

    /**
     * Calculate name similarity score based on exact, partial, or fuzzy match
     *
     * @param array $alumniData
     * @param array $evidences
     * @return float
     */
    public function calculateNameSimilarity(array $alumniData, array $evidences): float
    {
        $namaLengkap = strtolower(trim($alumniData['nama_lengkap'] ?? ''));
        $variasi = array_map('strtolower', array_map('trim', $alumniData['nama_variasi'] ?? []));
        $names = array_unique(array_filter(array_merge([$namaLengkap], $variasi)));

        if (empty($names)) {
            return 0.0;
        }

        $highestSimilarity = 0.0;

        foreach ($names as $name) {
            if (!$name) continue;
            
            $nameParts = array_values(array_filter(explode(' ', $name)));
            $firstName = $nameParts[0] ?? '';
            $lastName = count($nameParts) > 1 ? end($nameParts) : $firstName;

            foreach ($evidences as $evidence) {
                $text = strtolower(($evidence['title'] ?? '') . ' ' . ($evidence['snippet'] ?? ''));
                if (!$text) continue;

                // Exact match
                if (str_contains($text, $name)) {
                    if (1.0 > $highestSimilarity) {
                        $highestSimilarity = 1.0;
                    }
                    continue;
                }

                // Partial match (first + last name)
                if ($firstName && $lastName && $firstName !== $lastName) {
                    if (str_contains($text, $firstName) && str_contains($text, $lastName)) {
                        if (0.7 > $highestSimilarity) {
                            $highestSimilarity = 0.7;
                        }
                        continue;
                    }
                }

                // Fuzzy / weak match (just first name)
                if ($firstName && str_contains($text, $firstName)) {
                    if (0.4 > $highestSimilarity) {
                        $highestSimilarity = 0.4;
                    }
                }
            }
        }

        return $highestSimilarity;
    }

    /**
     * Calculate company alignment weight based on source tier
     *
     * @param array $evidences
     * @return float
     */
    public function calculateCompanyAlignment(array $evidences): float
    {
        $highestWeight = 0.0;
        $companyKeywords = [
            ' pt ', ' pt.', ' cv ', ' cv.', ' tbk', ' univ ', ' university', ' universitas', 
            ' institute', ' institut', ' inc', ' corp', ' ltd', ' bumn', ' dinas', 
            ' kementerian', ' bank ', ' group', ' sekolah', ' instansi', ' company', ' perusahaan'
        ];

        foreach ($evidences as $evidence) {
            $snippet = strtolower(($evidence['title'] ?? '') . ' ' . ($evidence['snippet'] ?? ''));
            $url = $evidence['url'] ?? '';

            if (!$url) continue;

            $hasMention = false;
            foreach ($companyKeywords as $keyword) {
                if (str_contains($snippet, $keyword)) {
                    $hasMention = true;
                    break;
                }
            }

            $sourceType = $this->priorityResolver->classifySource($url);
            
            // Assume inherently strong alignment context if from linkedin or official site
            if ($sourceType === 'linkedin' || $sourceType === 'company_official_site') {
                $hasMention = true;
            }

            if ($hasMention) {
                $weight = 0.0;
                switch ($sourceType) {
                    case 'linkedin':
                    case 'company_official_site':
                        $weight = 1.0;
                        break;
                    case 'verified_news':
                        $weight = 0.7;
                        break;
                    case 'aggregator':
                        $weight = 0.4;
                        break;
                    case 'social_media':
                    default:
                        $weight = 0.0;
                        break;
                }

                if ($weight > $highestWeight) {
                    $highestWeight = $weight;
                }
            }
        }

        return $highestWeight;
    }

    /**
     * Calculate cross source consistency bonus
     *
     * @param array $alumniData
     * @param array $evidences
     * @return float
     */
    public function calculateCrossSourceBonus(array $alumniData, array $evidences): float
    {
        $namaLengkap = strtolower(trim($alumniData['nama_lengkap'] ?? ''));
        $variasi = array_map('strtolower', array_map('trim', $alumniData['nama_variasi'] ?? []));
        $names = array_unique(array_filter(array_merge([$namaLengkap], $variasi)));

        if (empty($names)) {
            return 0.0;
        }

        $trustedSourcesMatches = [];

        foreach ($evidences as $evidence) {
            $url = $evidence['url'] ?? '';
            if (!$url) continue;

            $sourceType = $this->priorityResolver->classifySource($url);
            
            // Only count TRUSTED sources
            if (!in_array($sourceType, ['linkedin', 'company_official_site', 'verified_news'])) {
                continue;
            }

            $text = strtolower(($evidence['title'] ?? '') . ' ' . ($evidence['snippet'] ?? ''));
            
            $hasExactMatch = false;
            foreach ($names as $name) {
                if (str_contains($text, $name)) {
                    $hasExactMatch = true;
                    break;
                }
            }

            if ($hasExactMatch) {
                $domain = parse_url($url, PHP_URL_HOST);
                if ($domain) {
                    // Normalize domain (e.g., www.linkedin.com -> linkedin.com)
                    $domain = preg_replace('/^www\./', '', $domain);
                    $trustedSourcesMatches[$domain] = true;
                } else {
                    $trustedSourcesMatches[$url] = true;
                }
            }
        }

        $matchCount = count($trustedSourcesMatches);

        if ($matchCount >= 3) {
            return 1.0;
        } elseif ($matchCount == 2) {
            return 0.5;
        }

        return 0.0;
    }
}
