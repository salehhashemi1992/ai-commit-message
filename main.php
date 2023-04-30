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

function amendCommitMessage(
    string $commitTitle,
    string $commitDescription,
    string $committerEmail,
    string $committerName
): void {
    exec("git config user.email '{$committerEmail}'");
    exec("git config user.name '{$committerName}'");

    $commitTitle = escapeshellarg($commitTitle);
    $commitDescription = escapeshellarg($commitDescription);
    exec("git commit --amend -m {$commitTitle} -m {$commitDescription}");

    exec("git push --force-with-lease");

    exec("git config --unset user.email");
    exec("git config --unset user.name");
}

function newAmendCommitMessage(
    string $commitSha,
    string $commitTitle,
    string $commitDescription,
    string $committerEmail,
    string $committerName
): void {
    exec("git config user.email '{$committerEmail}'");
    exec("git config user.name '{$committerName}'");

    // 1. Create a new branch at the commit you want to amend
    $newBranch = 'temp_amend_branch';
    exec("git checkout -b {$newBranch} {$commitSha}");

    // 2. Amend the commit on the new branch
    $commitTitle = escapeshellarg($commitTitle);
    $commitDescription = escapeshellarg($commitDescription);
    exec("git commit --amend -m {$commitTitle} -m {$commitDescription}");

    // 3. Perform an interactive rebase to incorporate the amended commit into the main branch
    exec("git checkout main");
    exec("git rebase --onto {$newBranch} {$commitSha}^");

    // 4. Push the changes to the remote repository
    exec("git push");

    // Clean up: delete the temporary branch and unset user.email and user.name
    exec("git branch -D {$newBranch}");
    exec("git config --unset user.email");
    exec("git config --unset user.name");
}

function main(): void
{
    $commitSha = getenv('GITHUB_SHA') ?: '';

    exec('git config --global --add safe.directory /github/workspace');

    $url = 'https://saleh-hashemi.ir/open-ai/commit-message';
    $commitTitle = exec('git log -1 --pretty=%s');
    $commitChanges = exec('git show ' . $commitSha . ' | head -n 50');

    // Get the committer's name and email from the commit
    $committerName = exec("git log -1 --pretty=%cn {$commitSha}");
    $committerEmail = exec("git log -1 --pretty=%ce {$commitSha}");

    echo "Commit Email: " . $committerEmail . '\n';
    echo "Commit Name: " . $committerName . '\n';
    echo "Commit Title: " . $commitTitle . '\n';
    echo "Commit Changes: " . $commitChanges . '\n';

    newAmendCommitMessage($commitSha, 'test', 'test2', $committerEmail, $committerName);
    /*    $commitDescription = getCommitDescription($url, $commitTitle, $commitChanges);

        if ($commitDescription) {
            echo "Generated commit description:\n";
            echo $commitDescription;
        } else {
            echo "Failed to generate commit description.";
        }*/
}

main();
