<?php

// Function to get HTML content from a URL with error handling
function getHTMLContent($url) {
    $options = array(
        'http' => array(
            'method' => "GET",
            'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36"
        )
    );
    $context = stream_context_create($options);
    $html = @file_get_contents($url, false, $context);
    if (!$html) {
        throw new Exception("Failed to retrieve content from URL: $url");
    }
    return $html;
}

// Function to extract article titles and URLs with date filtering
function scrapeHeadlines($url, $startDate = "2022-01-01") {
    $html = getHTMLContent($url);

    // Load HTML into DOM
    $dom = new DOMDocument();
    @$dom->loadHTML($html);

    // Define a flexible selector for various article structures
    $xpath = new DOMXPath($dom);
    $articles = $xpath->query('//article/h2/a | //h2/a | //.//a[contains(@class, "title")]'); // Adjust selector based on website structure

    $headlines = [];
    foreach ($articles as $article) {
        $title = trim($article->nodeValue);
        $link = $article->getAttribute('href');

        // Ensure link is absolute
        if (strpos($link, 'http') !== 0) {
            $link = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST) . $link;
        }

        // Check if article has a publish date (optional)
        $publishDate = null;
        $dateElement = $article->parentNode->parentNode; // Adjust based on website structure
        if ($dateElement) {
            $dateText = $dateElement->textContent;
            // Implement logic to parse date from text (depends on website format)
            // You can use libraries like DateTime to validate and format dates
        }

        // Filter based on publish date (if available)
        if (!$publishDate || strtotime($publishDate) >= strtotime($startDate)) {
            $headlines[] = ['title' => $title, 'link' => $link, 'date' => $publishDate];
        }
    }

    // Sort headlines by date (descending)
    usort($headlines, function ($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    return $headlines;
}

try {
    // Choose the website URL (comment out others)
    $url = 'https://www.theverge.com/'; // The Verge
    //$url = 'https://wired.com/'; // Wired
    //$url = 'https://mashable.com/'; // Mashable

    $headlines = scrapeHeadlines($url);

    // Display headlines
    echo "<!DOCTYPE html><html><head><title>Title Aggregator</title></head><body style='background-color: black; color: white; font-family: Arial, sans-serif;'>";
    echo "<h1>Article Headlines</h1>";
    echo "<ul>";
    foreach ($headlines as $headline) {
        $dateString = $headline['date'] ? "({$headline['date']})" : "";
        echo "<li><a href='" . $headline['link'] . "' style='color: white; text-decoration: none;'>" . $headline['title'] . $dateString . "</a></li>";
    }
    echo "</ul>";
    echo "</body></html>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
