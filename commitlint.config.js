// https://github.com/conventional-changelog/commitlint/blob/master/docs/reference-rules.md
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'body-max-line-length': [1, 'always', 500],
        'footer-max-line-length': [1, 'always', 200],
        'header-max-length': [1, 'always', 100],
        'scope-case': [2, 'always', ['lower-case', 'kebab-case']],
        'subject-case': [2, 'never', ['start-case', 'pascal-case', 'upper-case']]
    }
};
