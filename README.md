# AI Commit Message Generator GitHub Action

This GitHub Action automatically generates commit messages and descriptions for your commits using AI. 

When you make a commit with the title [ai], this action will analyze the commit changes, use AI to generate a commit title and description, and update the commit message accordingly.

### Sample Output
![](./img/sample1.jpg)
![](./img/sample2.jpg)

## Features
* Automatically generates meaningful commit titles and descriptions for your commits
* Utilizes AI to analyze commit changes and generate relevant commit messages
* Maintains the original author of the commit (committer name and email)

## Usage
To use this action in your GitHub repository, follow these steps:

Add the following workflow file to your repository in the .github/workflows directory, and name it ai_commit_message_generator.yml:

```bash
name: AI Commit Message Generator

on: [push]

jobs:
  ai_commit_message:
    runs-on: ubuntu-latest
    permissions:
      contents: write
    steps:
      - name: Checkout repository
        uses: actions/checkout@v3
        with:
          fetch-depth: 0

      - name: Replace commit message with AI-generated title
        uses: salehhashemi1992/ai-commit-description@v0.1.0
```

Now, whenever you push a commit with the title [ai], this action will automatically generate a commit title and description using AI and update the commit message accordingly.

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

-   [Saleh Hashemi](https://github.com/salehhashemi1992)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.