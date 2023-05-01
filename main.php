<?php

declare(strict_types=1);

use GuzzleHttp\Client;

require 'vendor/autoload.php';

function fetchAiGeneratedTitleAndDescription(string $commitChanges): array
{
    $client = new Client();
    $response = $client->post('https://saleh-hashemi.ir/open-ai/commit-message', [
        'form_params' => ['commit_changes' => $commitChanges]
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

    exec("git reset --soft HEAD~1");
    exec("git commit -m {$newTitle} -m {$newDescription}");
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
    $command = "git diff {$commitSha}~ {$commitSha}";

    exec($command, $output, $return_var);

    // Check if the command executed successfully
    if ($return_var == 0) {
        $output = array_slice($output, 0, 100);

        $commitChanges = implode("\n", $output);

        echo "Git diff output:\n" . $commitChanges;
    } else {
        echo "Error: Could not run git diff. Return code: " . $return_var;
        exit;
    }

    echo "Commit Changes: " . $commitChanges;

    $committerName = exec("git log -1 --pretty=%cn $commitSha");
    $committerEmail = exec("git log -1 --pretty=%ce $commitSha");

    if ($commitTitle === '[ai]') {
        list($newTitle, $newDescription) = fetchAiGeneratedTitleAndDescription($commitChanges);
        updateLastCommitMessage($newTitle, $newDescription, $committerEmail, $committerName);
    }
}

main();
