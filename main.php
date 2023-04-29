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

function amendCommitMessage(string $newMessage, string $committerEmail, string $committerName): void
{
    exec("git config user.email '{$committerEmail}'");
    exec("git config user.name '{$committerName}'");
    exec("git commit --amend -m '{$newMessage}'");
    exec("git config --unset user.email");
    exec("git config --unset user.name");
}

function main(): void
{
    $committerEmail = getenv('INPUT_COMMITTER_EMAIL') ?: '';
    $committerName = getenv('INPUT_COMMITTER_NAME') ?: '';
    $commitSha = getenv('GITHUB_SHA') ?: '';

    exec('git config --global --add safe.directory /github/workspace');

    $url = 'https://saleh-hashemi.ir/open-ai/commit-message';
    $commitTitle = exec('git log -1 --pretty=%s');
    $commitChanges = exec('git show ' . $commitSha . ' | head -n 50');

    echo "Commit Title: " . $commitTitle . '\n';
    echo "Commit Changes: " . $commitChanges . '\n';

    amendCommitMessage('test', $committerEmail, $committerName);
    /*    $commitDescription = getCommitDescription($url, $commitTitle, $commitChanges);

        if ($commitDescription) {
            echo "Generated commit description:\n";
            echo $commitDescription;
        } else {
            echo "Failed to generate commit description.";
        }*/
}

main();
