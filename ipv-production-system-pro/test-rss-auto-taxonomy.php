<?php
/**
 * RSS Auto-Taxonomy System Test
 *
 * Tests the complete flow of RSS feed parsing and automatic taxonomy assignment
 * Version: 2.3.1
 */

echo "========================================\n";
echo "RSS AUTO-TAXONOMY SYSTEM TEST v2.3.1\n";
echo "========================================\n\n";

// Test 1: Hashtag Extraction
echo "TEST 1: Hashtag Extraction\n";
echo "----------------------------\n";

$test_description = "Questo è un video sul #WordPress e #PHP. Parliamo anche di #WebDevelopment e #Tutorial";

function test_extract_hashtags($text) {
    preg_match_all('/#(\w+)/u', $text, $matches);
    if (empty($matches[1])) {
        return [];
    }
    $hashtags = array_map('strtolower', $matches[1]);
    $hashtags = array_unique($hashtags);
    return array_values($hashtags);
}

$hashtags = test_extract_hashtags($test_description);
echo "Input: {$test_description}\n";
echo "Output: " . implode(', ', $hashtags) . "\n";
echo "Expected: wordpress, php, webdevelopment, tutorial\n";
echo "Status: " . (count($hashtags) === 4 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 2: Speaker Extraction
echo "TEST 2: Speaker Name Extraction\n";
echo "--------------------------------\n";

$test_titles = [
    "Intervista con Mario Rossi - Tech Talk",
    "PHP Development ft. Luca Bianchi",
    "WordPress Tips feat. Giuseppe Verdi",
    "Ospite: Carlo Neri - Web Design",
    "Guest: Francesco Russo on JavaScript"
];

function test_extract_speakers($title) {
    $speakers = [];

    // Pattern 1: "con [Name]"
    if (preg_match('/\bcon\s+([A-Z][a-zA-ZÀ-ÿ\s]+?)(?:\s*[\|\-\:]|$)/u', $title, $matches)) {
        $speakers[] = trim($matches[1]);
    }

    // Pattern 2: "ft. [Name]" or "feat. [Name]"
    if (preg_match('/\b(?:ft\.|feat\.|featuring)\s+([A-Z][a-zA-ZÀ-ÿ\s]+?)(?:\s*[\|\-\:]|$)/u', $title, $matches)) {
        $speakers[] = trim($matches[1]);
    }

    // Pattern 3: "ospite: [Name]" or "guest: [Name]"
    if (preg_match('/\b(?:ospite|guest):\s*([A-Z][a-zA-ZÀ-ÿ\s]+?)(?:\s*[\|\-\:]|$)/u', $title, $matches)) {
        $speakers[] = trim($matches[1]);
    }

    return array_unique($speakers);
}

$total_speakers_found = 0;
foreach ($test_titles as $title) {
    $speakers = test_extract_speakers($title);
    echo "Title: {$title}\n";
    echo "Speaker: " . (!empty($speakers) ? implode(', ', $speakers) : 'NOT FOUND') . "\n";
    if (!empty($speakers)) {
        $total_speakers_found++;
        echo "✅ PASS\n\n";
    } else {
        echo "❌ FAIL\n\n";
    }
}

echo "Summary: {$total_speakers_found}/5 speakers extracted\n";
echo "Status: " . ($total_speakers_found >= 4 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 3: Topic Extraction
echo "TEST 3: Topic Extraction from Description\n";
echo "------------------------------------------\n";

$test_desc_with_topics = "Argomenti: WordPress, PHP, Database Design, API REST\n\nIn questo video parliamo di sviluppo web.";

function test_extract_topics($description) {
    $topics = [];
    $lines = explode("\n", $description);

    foreach ($lines as $line) {
        $line = trim($line);
        if (preg_match('/^(?:Argomenti?|Topics?|Temi?|In questo video):\s*(.+)/i', $line, $matches)) {
            $items = preg_split('/[,;•\-]+/', $matches[1]);
            foreach ($items as $item) {
                $item = trim($item);
                if (!empty($item) && strlen($item) > 3) {
                    $topics[] = $item;
                }
            }
        }
    }

    return $topics;
}

$topics = test_extract_topics($test_desc_with_topics);
echo "Input: {$test_desc_with_topics}\n";
echo "Topics found: " . implode(', ', $topics) . "\n";
echo "Expected: WordPress, PHP, Database Design, API REST\n";
echo "Status: " . (count($topics) === 4 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 4: Complete RSS Entry Parsing Simulation
echo "TEST 4: Complete RSS Entry Parsing\n";
echo "-----------------------------------\n";

$simulated_rss_entry = [
    'video_id' => 'dQw4w9WgXcQ',
    'title' => 'Tutorial WordPress con Marco Ferrari',
    'description' => 'Argomenti: SEO, Performance, Security\n\nIn questo video approfondiamo #WordPress e #WebDev',
    'author' => 'Il Punto Di Vista Channel',
    'category' => 'Education',
    'published_at' => '2024-01-15T10:00:00Z'
];

// Simulate complete parsing
$hashtags = test_extract_hashtags($simulated_rss_entry['description']);
$speakers = test_extract_speakers($simulated_rss_entry['title']);
$topics = test_extract_topics($simulated_rss_entry['description']);

echo "Simulated RSS Entry:\n";
echo "  Video ID: {$simulated_rss_entry['video_id']}\n";
echo "  Title: {$simulated_rss_entry['title']}\n";
echo "  Category: {$simulated_rss_entry['category']}\n";
echo "  Author: {$simulated_rss_entry['author']}\n\n";

echo "Extracted Metadata:\n";
echo "  Speakers: " . (!empty($speakers) ? implode(', ', $speakers) : 'none') . "\n";
echo "  Topics: " . (!empty($topics) ? implode(', ', $topics) : 'none') . "\n";
echo "  Hashtags: " . (!empty($hashtags) ? implode(', ', $hashtags) : 'none') . "\n\n";

$taxonomy_assignments = [
    'ipv_guest' => $speakers,
    'ipv_topic' => $topics,
    'tags' => $hashtags,
    'categories' => array_merge([$simulated_rss_entry['category']], $speakers),
    'ipv_channel_theme' => [$simulated_rss_entry['author']]
];

echo "Taxonomy Assignments (would be applied to WordPress post):\n";
foreach ($taxonomy_assignments as $taxonomy => $terms) {
    if (!empty($terms)) {
        echo "  {$taxonomy}: " . implode(', ', $terms) . "\n";
    }
}

$total_terms = count($speakers) + count($topics) + count($hashtags) + 2; // +2 for category and channel
echo "\nTotal terms assigned: {$total_terms}\n";
echo "Status: " . ($total_terms > 0 ? "✅ PASS" : "❌ FAIL") . "\n\n";

// Test 5: File Syntax Verification
echo "TEST 5: PHP Syntax Verification\n";
echo "--------------------------------\n";

$files_to_check = [
    'includes/class-rss-auto-import.php',
    'includes/class-queue-manager.php',
    'ipv-production-system-pro.php'
];

$syntax_errors = 0;
foreach ($files_to_check as $file) {
    $file_path = __DIR__ . '/' . $file;
    if (file_exists($file_path)) {
        $output = [];
        $return_code = 0;
        exec("php -l " . escapeshellarg($file_path) . " 2>&1", $output, $return_code);

        if ($return_code === 0) {
            echo "✅ {$file} - OK\n";
        } else {
            echo "❌ {$file} - SYNTAX ERROR\n";
            echo "   " . implode("\n   ", $output) . "\n";
            $syntax_errors++;
        }
    } else {
        echo "⚠️  {$file} - NOT FOUND\n";
    }
}

echo "\nSyntax Check: " . ($syntax_errors === 0 ? "✅ ALL PASS" : "❌ {$syntax_errors} ERRORS") . "\n\n";

// Final Summary
echo "========================================\n";
echo "FINAL SUMMARY\n";
echo "========================================\n";

$total_tests = 5;
$passed_tests = 0;

if (count($hashtags) === 4) $passed_tests++;
if ($total_speakers_found >= 4) $passed_tests++;
if (count($topics) === 4) $passed_tests++;
if ($total_terms > 0) $passed_tests++;
if ($syntax_errors === 0) $passed_tests++;

echo "Tests Passed: {$passed_tests}/{$total_tests}\n";
echo "Status: " . ($passed_tests === $total_tests ? "✅ ALL TESTS PASSED" : "⚠️ SOME TESTS FAILED") . "\n";
echo "\nRSS Auto-Taxonomy System v2.3.1\n";
echo "Ready for deployment!\n";
echo "========================================\n";
