<?php

declare(strict_types=1);

use GuzzleHttp\Client;

require 'vendor/autoload.php';

function fetchAiGeneratedTitleAndDescription(string $commitChanges): array
{
    $client = new Client();
    $response = $client->post('https://saleh-hashemi.ir/open-ai/commit-message', [
        'json' => ['commit_changes' => $commitChanges]
    ]);

    $responseData = json_decode((string)$response->getBody(), true);

    return [$responseData['title'], $responseData['description']];
}

function updateLastCommitMessage(
    string $newTitle,
    string $newDescription,
    string $committerEmail,
    string $committerName
): void {
    exec("git config user.email '{$committerEmail}'");
    exec("git config user.name '{$committerName}'");

    $newTitle = escapeshellarg($newTitle);
    $newDescription = escapeshellarg($newDescription);

    // Soft reset to the previous commit
    exec("git reset --soft HEAD~1");

    // Create a new commit with the updated title and description
    exec("git commit -m {$newTitle} -m {$newDescription}");

    // Push the changes to the remote repository
    exec("git push origin --force");

    // Clean up: unset user.email and user.name
    exec("git config --unset user.email");
    exec("git config --unset user.name");
}

function main(): void
{
    $commitSha = getenv('GITHUB_SHA') ?: '';
    exec('git config --global --add safe.directory /github/workspace');

    $commitTitle = exec('git log -1 --pretty=%s');
    $commitChanges = exec("git diff {$commitSha}~ {$commitSha} --unified=0");

    $committerName = exec("git log -1 --pretty=%cn $commitSha");
    $committerEmail = exec("git log -1 --pretty=%ce $commitSha");

    // Call the updateLastCommitMessage function with the correct arguments
    if ($commitTitle === '[ai]') {
        list($newTitle, $newDescription) = fetchAiGeneratedTitleAndDescription($commitChanges);
        updateLastCommitMessage($newTitle, $newDescription, $committerEmail, $committerName);
    }
}

main();
