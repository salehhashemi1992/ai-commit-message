<?php

declare(strict_types=1);

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

function getCommitDescription(string $url, string $title, string $changes): string
{
    $client = new Client();

    try {
        $response = $client->request('POST', $url, [
            'json' => [
                'title' => $title,
                'changes' => $changes,
            ],
        ]);

        if ($response->getStatusCode() === 200) {
            $body = json_decode((string)$response->getBody(), true);
            return $body['description'] ?? '';
        }
    } catch (GuzzleException $e) {
        // Handle exceptions
    }

    return '';
}

function main(): void
{
    $url = 'https://saleh-hashemi.ir/open-ai/commit-message';
    $commitSha = getenv('GITHUB_SHA') ?: '';
    $commitTitle = exec('git log -1 --pretty=%s');
    $commitChanges = exec('git diff-tree --no-commit-id --name-status -r ' . $commitSha . ' | head -n 50');

    echo $commitTitle . '\n';
    echo $commitChanges . '\n';
/*    $commitDescription = getCommitDescription($url, $commitTitle, $commitChanges);

    if ($commitDescription) {
        echo "Generated commit description:\n";
        echo $commitDescription;
    } else {
        echo "Failed to generate commit description.";
    }*/
}

main();
