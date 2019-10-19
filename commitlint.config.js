// https://github.com/conventional-changelog/commitlint/blob/master/docs/reference-rules.md
module.exports = {
    extends: ['@commitlint/config-conventional'],
    rules: {
        'header-max-length': [1, 'always', 72],
        'scope-case': [2, 'always', ['lower-case', 'kebab-case']],
        'subject-case': [2, 'never', ['start-case', 'pascal-case', 'upper-case']]
    }
};
