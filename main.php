<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

require 'vendor/autoload.php';

function main(): void
{
    $commitSha = getenv('GITHUB_SHA') ?: '';
    exec('git config --global --add safe.directory /github/workspace');

    $commitTitle = exec('git log -1 --pretty=%s');

    $committerName = exec("git log -1 --pretty=%cn $commitSha");
    $committerEmail = exec("git log -1 --pretty=%ce $commitSha");

    if ($commitTitle === '[ai]') {
        $model = getenv('OPENAI_MODEL') ?: 'gpt-3.5-turbo'; // Default to gpt-3.5-turbo if no environment variable is set

        if (!in_array($model, ['gpt-4', 'gpt-4-32k', 'gpt-3.5-turbo'])) {
            echo "::error::Invalid model specified. Please use either gpt-3.5-turbo', 'gpt-4' or 'gpt-4-32k'." .
                PHP_EOL;
            exit(1);
        }

        list($newTitle, $newDescription) = fetchAiGeneratedTitleAndDescription(
            getCommitChanges($commitSha),
            getenv('OPENAI_API_KEY'),
            $model,
        );

        updateLastCommitMessage($newTitle, $newDescription, $committerEmail, $committerName);
    }
}

main();

function fetchAiGeneratedTitleAndDescription(string $commitChanges, string $openAiApiKey, string $model): array
{
    $prompt = generatePrompt($commitChanges);

    $input_data = [
        "temperature" => 0.7,
        "max_tokens" => 300,
        "frequency_penalty" => 0,
        'model' => $model,
        "messages" => [
            [
                'role' => 'user',
                'content' => $prompt
            ],
        ]
    ];

    try {
        $client = new Client([
            'base_uri' => 'https://api.openai.com',
            'headers' => [
                'Authorization' => 'Bearer ' . $openAiApiKey,
                'Content-Type' => 'application/json'
            ]
        ]);

        $response = $client->post('/v1/chat/completions', [
            'json' => $input_data
        ]);

        $complete = json_decode($response->getBody()->getContents(), true);
        $output = $complete['choices'][0]['message']['content'];

        return extractTitleAndDescription($output);

    } catch (GuzzleException $e) {
        echo "::error::Error fetching AI-generated title and description: " . $e->getMessage() . PHP_EOL;
        exit(1);
    }
}

function generatePrompt(string $commitChanges): string
{
    return "Based on the following line-by-line changes in a commit, please generate an informative commit title and description
     \n(max two or three lines of description to not exceed the model max token limitation):
     \nCommit changes:
     \n{$commitChanges}
     \nFormat your response as follows:
     \nCommit title: [Generated commit title]
     \nCommit description: [Generated commit description]";
}

function extractTitleAndDescription(string $output): array
{
    $title = '';
    $description = '';
    $responseLines = explode("\n", $output);
    foreach ($responseLines as $line) {
        if (str_starts_with($line, 'Commit title: ')) {
            $title = str_replace('Commit title: ', '', $line);
        } elseif (str_starts_with($line, 'Commit description: ')) {
            $description = str_replace('Commit description: ', '', $line);
        }
    }

    return [$title, $description];
}

function updateLastCommitMessage(
    string $newTitle,
    string $newDescription,
    string $committerEmail,
    string $committerName
): void {
    configureGitCommitter($committerEmail, $committerName);

    $newTitle = escapeshellarg($newTitle);
    $newDescription = escapeshellarg($newDescription);

    exec("git reset --soft HEAD~1");
    exec("git commit -m {$newTitle} -m {$newDescription}");
    exec("git push origin --force");

    unsetGitCommitterConfiguration();
}

function configureGitCommitter(string $committerEmail, string $committerName): void
{
    exec("git config user.email '{$committerEmail}'");
    exec("git config user.name '{$committerName}'");
}

function unsetGitCommitterConfiguration(): void
{
    exec("git config --unset user.email");
    exec("git config --unset user.name");
}

function getCommitChanges(string $commitSha): string
{
    $command = "git diff {$commitSha}~ {$commitSha}";

    exec($command, $output, $return_var);

    if ($return_var == 0) {
        $length = getenv('OPENAI_MODEL') ? match (getenv('OPENAI_MODEL')) {
            'gpt-3.5-turbo' => 400,
            'gpt-4' => 800,
            'gpt-4-32k' => 3200,
        } : 400;

        $output = array_slice($output, 0, $length);
        return implode("\n", $output);
    } else {
        echo "Error: Could not run git diff. Return code: " . $return_var;
        exit(1);
    }
}

