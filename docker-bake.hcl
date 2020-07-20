variable "PHP_VERSION" {
	default = "7.4"
}

variable "BUILD_TYPE" {
	default = "std"
}

target "cli" {
  cache-from = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-cli"]
  cache-to   = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-cli,mode=max"]
  output     = ["type=registry"]
}

target "composer" {
  cache-from = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-composer"]
  cache-to   = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-composer,mode=max"]
  output     = ["type=registry"]
}

target "coverage-xdebug" {
  cache-from = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-coverage-xdebug"]
  cache-to   = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-coverage-xdebug,mode=max"]
  output     = ["type=registry"]
}

target "coverage-pcov" {
  cache-from = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-coverage-pcov"]
  cache-to   = ["type=registry,ref=k911/swoole-bundle-cache:${PHP_VERSION}-${BUILD_TYPE}-coverage-pcov,mode=max"]
  output     = ["type=registry"]
}
