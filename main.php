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

function amendCommitMessage(string $commitDescription): void
{
    $amendMessage = "git commit --amend -m \"$(git log -1 --pretty=%B)\" -m \"$commitDescription\"";
    exec($amendMessage);
}

function main(): void
{
    exec('git config --global --add safe.directory /github/workspace');

    $url = 'https://saleh-hashemi.ir/open-ai/commit-message';
    $commitTitle = exec('git log -1 --pretty=%s');
    $commitChanges = exec('git show --oneline HEAD | tail -n +2 | head -n 50');

    echo "Commit Title: " . $commitTitle . '\n';
    echo "Commit Changes: " . $commitChanges . '\n';

    amendCommitMessage('test');
/*    $commitDescription = getCommitDescription($url, $commitTitle, $commitChanges);

    if ($commitDescription) {
        echo "Generated commit description:\n";
        echo $commitDescription;
    } else {
        echo "Failed to generate commit description.";
    }*/
}

main();
