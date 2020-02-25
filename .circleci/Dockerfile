ARG NODE_TAG="12-alpine"
FROM node:${NODE_TAG} as Runner
WORKDIR /usr/src/app
RUN apk add --no-cache bash git curl
RUN yarn global add conventional-changelog-cli conventional-recommended-bump
COPY release-version.sh /usr/local/bin/release-version
RUN chmod a+x /usr/local/bin/release-version
ENV CURRENT_VERSION="" \
    DRY_RUN="0" \
    GH_REPOSITORY="k911/swoole-bundle" \
    GH_COMMITER_NAME="k911" \
    GH_COMMITER_EMAIL="konradobal@gmail.com" \
    GH_TOKEN="" \
    GH_RELEASE_DRAFT="false" \
    GH_RELEASE_PRERELEASE="false"
ENTRYPOINT ["release-version"]
