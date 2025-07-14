/var/www/html/wp-content/plugins/bandfront-player
├── .gitignore
├── .gitmodules
├── BandfrontPlayer.php
├── build
│   ├── build.sh
│   ├── composer.sh
│   ├── update-translations.sh
│   └── update-wavesurfer.sh
├── builders
│   ├── builders.php
│   ├── elementor
│   │   ├── elementor.pb.php
│   │   └── elementor_category.pb.php
│   └── gutenberg
│       ├── block.json
│       ├── gutenberg.css
│       ├── gutenberg.js
│       ├── render.php
│       ├── wcblocks.css
│       └── wcblocks.js
├── composer.json
├── composer.lock
├── css
│   ├── admin-notices.css
│   ├── skins
│   │   ├── custom.css
│   │   ├── dark.css
│   │   └── light.css
│   ├── style-admin.css
│   ├── style.css
│   └── widget-style.css
├── js
│   ├── admin.js
│   ├── engine-full.js
│   ├── engine.js
│   ├── engine.js.older
│   ├── wavesurfer.js
│   └── widget.js
├── languages
│   ├── bandfront-player-en_US.mo
│   ├── bandfront-player-en_US.po
│   ├── bandfront-player-en_US.pot
│   ├── bandfront-player.pot
│   └── messages.mo
├── md-files
│   ├── MAP.md.older
│   ├── MAP_STATE_CONFIG_&_VARIABLES.md.older
│   └── REFACTORS
├── src
│   ├── Admin.php
│   ├── Audio.php
│   ├── Config.php
│   ├── CoverRenderer.php
│   ├── Hooks.php
│   ├── Modules
│   │   ├── Dropbox
│   │   └── GoogleDrive
│   │       ├── GoogleDriveClient.php
│   │       └── google-api-php-client
│   │           ├── src
│   │           │   └── Google
│   │           │       ├── AccessToken
│   │           │       │   ├── Revoke.php
│   │           │       │   └── Verify.php
│   │           │       ├── AuthHandler
│   │           │       │   ├── AuthHandlerFactory.php
│   │           │       │   ├── Guzzle5AuthHandler.php
│   │           │       │   └── Guzzle6AuthHandler.php
│   │           │       ├── Client.php
│   │           │       ├── Collection.php
│   │           │       ├── Exception.php
│   │           │       ├── Http
│   │           │       │   ├── Batch.php
│   │           │       │   ├── MediaFileUpload.php
│   │           │       │   └── REST.php
│   │           │       ├── Model.php
│   │           │       ├── Service
│   │           │       │   ├── Exception.php
│   │           │       │   └── Resource.php
│   │           │       ├── Service.php
│   │           │       ├── Task
│   │           │       │   ├── Exception.php
│   │           │       │   ├── Retryable.php
│   │           │       │   └── Runner.php
│   │           │       ├── Utils
│   │           │       │   └── UriTemplate.php
│   │           │       └── autoload.php
│   │           └── vendor
│   │               ├── autoload.php
│   │               ├── composer
│   │               │   ├── ClassLoader.php
│   │               │   ├── InstalledVersions.php
│   │               │   ├── LICENSE
│   │               │   ├── autoload_classmap.php
│   │               │   ├── autoload_files.php
│   │               │   ├── autoload_namespaces.php
│   │               │   ├── autoload_psr4.php
│   │               │   ├── autoload_real.php
│   │               │   ├── autoload_static.php
│   │               │   ├── installed.json
│   │               │   ├── installed.php
│   │               │   └── platform_check.php
│   │               ├── google
│   │               │   ├── apiclient
│   │               │   │   └── src
│   │               │   │       ├── AccessToken
│   │               │   │       │   ├── Revoke.php
│   │               │   │       │   └── Verify.php
│   │               │   │       ├── AuthHandler
│   │               │   │       │   ├── AuthHandlerFactory.php
│   │               │   │       │   ├── Guzzle5AuthHandler.php
│   │               │   │       │   ├── Guzzle6AuthHandler.php
│   │               │   │       │   └── Guzzle7AuthHandler.php
│   │               │   │       ├── Client.php
│   │               │   │       ├── Collection.php
│   │               │   │       ├── Exception.php
│   │               │   │       ├── Http
│   │               │   │       │   ├── Batch.php
│   │               │   │       │   ├── MediaFileUpload.php
│   │               │   │       │   └── REST.php
│   │               │   │       ├── Model.php
│   │               │   │       ├── Service
│   │               │   │       │   ├── Exception.php
│   │               │   │       │   └── Resource.php
│   │               │   │       ├── Service.php
│   │               │   │       ├── Task
│   │               │   │       │   ├── Composer.php
│   │               │   │       │   ├── Exception.php
│   │               │   │       │   ├── Retryable.php
│   │               │   │       │   └── Runner.php
│   │               │   │       ├── Utils
│   │               │   │       │   └── UriTemplate.php
│   │               │   │       └── aliases.php
│   │               │   ├── apiclient-services
│   │               │   │   └── src
│   │               │   │       └── Google
│   │               │   │           └── Service
│   │               │   │               ├── Drive
│   │               │   │               │   ├── About.php
│   │               │   │               │   ├── AboutDriveThemes.php
│   │               │   │               │   ├── AboutStorageQuota.php
│   │               │   │               │   ├── AboutTeamDriveThemes.php
│   │               │   │               │   ├── Change.php
│   │               │   │               │   ├── ChangeList.php
│   │               │   │               │   ├── Channel.php
│   │               │   │               │   ├── Comment.php
│   │               │   │               │   ├── CommentList.php
│   │               │   │               │   ├── CommentQuotedFileContent.php
│   │               │   │               │   ├── ContentRestriction.php
│   │               │   │               │   ├── Drive.php
│   │               │   │               │   ├── DriveBackgroundImageFile.php
│   │               │   │               │   ├── DriveCapabilities.php
│   │               │   │               │   ├── DriveFile.php
│   │               │   │               │   ├── DriveFileCapabilities.php
│   │               │   │               │   ├── DriveFileContentHints.php
│   │               │   │               │   ├── DriveFileContentHintsThumbnail.php
│   │               │   │               │   ├── DriveFileImageMediaMetadata.php
│   │               │   │               │   ├── DriveFileImageMediaMetadataLocation.php
│   │               │   │               │   ├── DriveFileShortcutDetails.php
│   │               │   │               │   ├── DriveFileVideoMediaMetadata.php
│   │               │   │               │   ├── DriveList.php
│   │               │   │               │   ├── DriveRestrictions.php
│   │               │   │               │   ├── FileList.php
│   │               │   │               │   ├── GeneratedIds.php
│   │               │   │               │   ├── Permission.php
│   │               │   │               │   ├── PermissionList.php
│   │               │   │               │   ├── PermissionPermissionDetails.php
│   │               │   │               │   ├── PermissionTeamDrivePermissionDetails.php
│   │               │   │               │   ├── Reply.php
│   │               │   │               │   ├── ReplyList.php
│   │               │   │               │   ├── Resource
│   │               │   │               │   │   ├── About.php
│   │               │   │               │   │   ├── Changes.php
│   │               │   │               │   │   ├── Channels.php
│   │               │   │               │   │   ├── Comments.php
│   │               │   │               │   │   ├── Drives.php
│   │               │   │               │   │   ├── Files.php
│   │               │   │               │   │   ├── Permissions.php
│   │               │   │               │   │   ├── Replies.php
│   │               │   │               │   │   ├── Revisions.php
│   │               │   │               │   │   └── Teamdrives.php
│   │               │   │               │   ├── Revision.php
│   │               │   │               │   ├── RevisionList.php
│   │               │   │               │   ├── StartPageToken.php
│   │               │   │               │   ├── TeamDrive.php
│   │               │   │               │   ├── TeamDriveBackgroundImageFile.php
│   │               │   │               │   ├── TeamDriveCapabilities.php
│   │               │   │               │   ├── TeamDriveList.php
│   │               │   │               │   ├── TeamDriveRestrictions.php
│   │               │   │               │   └── User.php
│   │               │   │               ├── Drive.php
│   │               │   │               ├── Oauth2
│   │               │   │               │   ├── Resource
│   │               │   │               │   │   ├── Userinfo.php
│   │               │   │               │   │   ├── UserinfoV2.php
│   │               │   │               │   │   └── UserinfoV2Me.php
│   │               │   │               │   ├── Tokeninfo.php
│   │               │   │               │   └── Userinfo.php
│   │               │   │               └── Oauth2.php
│   │               │   └── auth
│   │               │       ├── autoload.php
│   │               │       └── src
│   │               │           ├── AccessToken.php
│   │               │           ├── ApplicationDefaultCredentials.php
│   │               │           ├── Cache
│   │               │           │   ├── InvalidArgumentException.php
│   │               │           │   ├── Item.php
│   │               │           │   ├── MemoryCacheItemPool.php
│   │               │           │   └── SysVCacheItemPool.php
│   │               │           ├── CacheTrait.php
│   │               │           ├── Credentials
│   │               │           │   ├── AppIdentityCredentials.php
│   │               │           │   ├── GCECredentials.php
│   │               │           │   ├── IAMCredentials.php
│   │               │           │   ├── InsecureCredentials.php
│   │               │           │   ├── ServiceAccountCredentials.php
│   │               │           │   ├── ServiceAccountJwtAccessCredentials.php
│   │               │           │   └── UserRefreshCredentials.php
│   │               │           ├── CredentialsLoader.php
│   │               │           ├── FetchAuthTokenCache.php
│   │               │           ├── FetchAuthTokenInterface.php
│   │               │           ├── GCECache.php
│   │               │           ├── GetQuotaProjectInterface.php
│   │               │           ├── HttpHandler
│   │               │           │   ├── Guzzle5HttpHandler.php
│   │               │           │   ├── Guzzle6HttpHandler.php
│   │               │           │   ├── Guzzle7HttpHandler.php
│   │               │           │   ├── HttpClientCache.php
│   │               │           │   └── HttpHandlerFactory.php
│   │               │           ├── Iam.php
│   │               │           ├── Middleware
│   │               │           │   ├── AuthTokenMiddleware.php
│   │               │           │   ├── ScopedAccessTokenMiddleware.php
│   │               │           │   └── SimpleMiddleware.php
│   │               │           ├── OAuth2.php
│   │               │           ├── ProjectIdProviderInterface.php
│   │               │           ├── ServiceAccountSignerTrait.php
│   │               │           ├── SignBlobInterface.php
│   │               │           ├── Subscriber
│   │               │           │   ├── AuthTokenSubscriber.php
│   │               │           │   ├── ScopedAccessTokenSubscriber.php
│   │               │           │   └── SimpleSubscriber.php
│   │               │           └── UpdateMetadataInterface.php
│   │               ├── guzzlehttp
│   │               │   ├── guzzle
│   │               │   │   └── src
│   │               │   │       ├── BodySummarizer.php
│   │               │   │       ├── BodySummarizerInterface.php
│   │               │   │       ├── Client.php
│   │               │   │       ├── ClientInterface.php
│   │               │   │       ├── ClientTrait.php
│   │               │   │       ├── Cookie
│   │               │   │       │   ├── CookieJar.php
│   │               │   │       │   ├── CookieJarInterface.php
│   │               │   │       │   ├── FileCookieJar.php
│   │               │   │       │   ├── SessionCookieJar.php
│   │               │   │       │   └── SetCookie.php
│   │               │   │       ├── Exception
│   │               │   │       │   ├── BadResponseException.php
│   │               │   │       │   ├── ClientException.php
│   │               │   │       │   ├── ConnectException.php
│   │               │   │       │   ├── GuzzleException.php
│   │               │   │       │   ├── InvalidArgumentException.php
│   │               │   │       │   ├── RequestException.php
│   │               │   │       │   ├── ServerException.php
│   │               │   │       │   ├── TooManyRedirectsException.php
│   │               │   │       │   └── TransferException.php
│   │               │   │       ├── Handler
│   │               │   │       │   ├── CurlFactory.php
│   │               │   │       │   ├── CurlFactoryInterface.php
│   │               │   │       │   ├── CurlHandler.php
│   │               │   │       │   ├── CurlMultiHandler.php
│   │               │   │       │   ├── EasyHandle.php
│   │               │   │       │   ├── MockHandler.php
│   │               │   │       │   ├── Proxy.php
│   │               │   │       │   └── StreamHandler.php
│   │               │   │       ├── HandlerStack.php
│   │               │   │       ├── MessageFormatter.php
│   │               │   │       ├── MessageFormatterInterface.php
│   │               │   │       ├── Middleware.php
│   │               │   │       ├── Pool.php
│   │               │   │       ├── PrepareBodyMiddleware.php
│   │               │   │       ├── RedirectMiddleware.php
│   │               │   │       ├── RequestOptions.php
│   │               │   │       ├── RetryMiddleware.php
│   │               │   │       ├── TransferStats.php
│   │               │   │       ├── Utils.php
│   │               │   │       ├── functions.php
│   │               │   │       └── functions_include.php
│   │               │   ├── promises
│   │               │   │   ├── .php_cs.dist
│   │               │   │   ├── LICENSE
│   │               │   │   ├── Makefile
│   │               │   │   ├── composer.json
│   │               │   │   ├── phpstan-baseline.neon
│   │               │   │   ├── phpstan.neon.dist
│   │               │   │   ├── psalm.xml
│   │               │   │   └── src
│   │               │   │       ├── AggregateException.php
│   │               │   │       ├── CancellationException.php
│   │               │   │       ├── Coroutine.php
│   │               │   │       ├── Create.php
│   │               │   │       ├── Each.php
│   │               │   │       ├── EachPromise.php
│   │               │   │       ├── FulfilledPromise.php
│   │               │   │       ├── Is.php
│   │               │   │       ├── Promise.php
│   │               │   │       ├── PromiseInterface.php
│   │               │   │       ├── PromisorInterface.php
│   │               │   │       ├── RejectedPromise.php
│   │               │   │       ├── RejectionException.php
│   │               │   │       ├── TaskQueue.php
│   │               │   │       ├── TaskQueueInterface.php
│   │               │   │       ├── Utils.php
│   │               │   │       ├── functions.php
│   │               │   │       └── functions_include.php
│   │               │   └── psr7
│   │               │       └── src
│   │               │           ├── AppendStream.php
│   │               │           ├── BufferStream.php
│   │               │           ├── CachingStream.php
│   │               │           ├── DroppingStream.php
│   │               │           ├── FnStream.php
│   │               │           ├── Header.php
│   │               │           ├── InflateStream.php
│   │               │           ├── LazyOpenStream.php
│   │               │           ├── LimitStream.php
│   │               │           ├── Message.php
│   │               │           ├── MessageTrait.php
│   │               │           ├── MimeType.php
│   │               │           ├── MultipartStream.php
│   │               │           ├── NoSeekStream.php
│   │               │           ├── PumpStream.php
│   │               │           ├── Query.php
│   │               │           ├── Request.php
│   │               │           ├── Response.php
│   │               │           ├── Rfc7230.php
│   │               │           ├── ServerRequest.php
│   │               │           ├── Stream.php
│   │               │           ├── StreamDecoratorTrait.php
│   │               │           ├── StreamWrapper.php
│   │               │           ├── UploadedFile.php
│   │               │           ├── Uri.php
│   │               │           ├── UriNormalizer.php
│   │               │           ├── UriResolver.php
│   │               │           ├── Utils.php
│   │               │           ├── functions.php
│   │               │           └── functions_include.php
│   │               ├── monolog
│   │               │   └── monolog
│   │               │       └── src
│   │               │           └── Monolog
│   │               │               ├── DateTimeImmutable.php
│   │               │               ├── ErrorHandler.php
│   │               │               ├── Formatter
│   │               │               │   ├── ChromePHPFormatter.php
│   │               │               │   ├── ElasticaFormatter.php
│   │               │               │   ├── ElasticsearchFormatter.php
│   │               │               │   ├── FlowdockFormatter.php
│   │               │               │   ├── FluentdFormatter.php
│   │               │               │   ├── FormatterInterface.php
│   │               │               │   ├── GelfMessageFormatter.php
│   │               │               │   ├── HtmlFormatter.php
│   │               │               │   ├── JsonFormatter.php
│   │               │               │   ├── LineFormatter.php
│   │               │               │   ├── LogglyFormatter.php
│   │               │               │   ├── LogmaticFormatter.php
│   │               │               │   ├── LogstashFormatter.php
│   │               │               │   ├── MongoDBFormatter.php
│   │               │               │   ├── NormalizerFormatter.php
│   │               │               │   ├── ScalarFormatter.php
│   │               │               │   └── WildfireFormatter.php
│   │               │               ├── Handler
│   │               │               │   ├── AbstractHandler.php
│   │               │               │   ├── AbstractProcessingHandler.php
│   │               │               │   ├── AbstractSyslogHandler.php
│   │               │               │   ├── AmqpHandler.php
│   │               │               │   ├── BrowserConsoleHandler.php
│   │               │               │   ├── BufferHandler.php
│   │               │               │   ├── ChromePHPHandler.php
│   │               │               │   ├── CouchDBHandler.php
│   │               │               │   ├── CubeHandler.php
│   │               │               │   ├── Curl
│   │               │               │   │   └── Util.php
│   │               │               │   ├── DeduplicationHandler.php
│   │               │               │   ├── DoctrineCouchDBHandler.php
│   │               │               │   ├── DynamoDbHandler.php
│   │               │               │   ├── ElasticaHandler.php
│   │               │               │   ├── ElasticsearchHandler.php
│   │               │               │   ├── ErrorLogHandler.php
│   │               │               │   ├── FallbackGroupHandler.php
│   │               │               │   ├── FilterHandler.php
│   │               │               │   ├── FingersCrossed
│   │               │               │   │   ├── ActivationStrategyInterface.php
│   │               │               │   │   ├── ChannelLevelActivationStrategy.php
│   │               │               │   │   └── ErrorLevelActivationStrategy.php
│   │               │               │   ├── FingersCrossedHandler.php
│   │               │               │   ├── FirePHPHandler.php
│   │               │               │   ├── FleepHookHandler.php
│   │               │               │   ├── FlowdockHandler.php
│   │               │               │   ├── FormattableHandlerInterface.php
│   │               │               │   ├── FormattableHandlerTrait.php
│   │               │               │   ├── GelfHandler.php
│   │               │               │   ├── GroupHandler.php
│   │               │               │   ├── Handler.php
│   │               │               │   ├── HandlerInterface.php
│   │               │               │   ├── HandlerWrapper.php
│   │               │               │   ├── IFTTTHandler.php
│   │               │               │   ├── InsightOpsHandler.php
│   │               │               │   ├── LogEntriesHandler.php
│   │               │               │   ├── LogglyHandler.php
│   │               │               │   ├── LogmaticHandler.php
│   │               │               │   ├── MailHandler.php
│   │               │               │   ├── MandrillHandler.php
│   │               │               │   ├── MissingExtensionException.php
│   │               │               │   ├── MongoDBHandler.php
│   │               │               │   ├── NativeMailerHandler.php
│   │               │               │   ├── NewRelicHandler.php
│   │               │               │   ├── NoopHandler.php
│   │               │               │   ├── NullHandler.php
│   │               │               │   ├── OverflowHandler.php
│   │               │               │   ├── PHPConsoleHandler.php
│   │               │               │   ├── ProcessHandler.php
│   │               │               │   ├── ProcessableHandlerInterface.php
│   │               │               │   ├── ProcessableHandlerTrait.php
│   │               │               │   ├── PsrHandler.php
│   │               │               │   ├── PushoverHandler.php
│   │               │               │   ├── RedisHandler.php
│   │               │               │   ├── RollbarHandler.php
│   │               │               │   ├── RotatingFileHandler.php
│   │               │               │   ├── SamplingHandler.php
│   │               │               │   ├── SendGridHandler.php
│   │               │               │   ├── Slack
│   │               │               │   │   └── SlackRecord.php
│   │               │               │   ├── SlackHandler.php
│   │               │               │   ├── SlackWebhookHandler.php
│   │               │               │   ├── SocketHandler.php
│   │               │               │   ├── SqsHandler.php
│   │               │               │   ├── StreamHandler.php
│   │               │               │   ├── SwiftMailerHandler.php
│   │               │               │   ├── SyslogHandler.php
│   │               │               │   ├── SyslogUdp
│   │               │               │   │   └── UdpSocket.php
│   │               │               │   ├── SyslogUdpHandler.php
│   │               │               │   ├── TelegramBotHandler.php
│   │               │               │   ├── TestHandler.php
│   │               │               │   ├── WebRequestRecognizerTrait.php
│   │               │               │   ├── WhatFailureGroupHandler.php
│   │               │               │   └── ZendMonitorHandler.php
│   │               │               ├── Logger.php
│   │               │               ├── Processor
│   │               │               │   ├── GitProcessor.php
│   │               │               │   ├── HostnameProcessor.php
│   │               │               │   ├── IntrospectionProcessor.php
│   │               │               │   ├── MemoryPeakUsageProcessor.php
│   │               │               │   ├── MemoryProcessor.php
│   │               │               │   ├── MemoryUsageProcessor.php
│   │               │               │   ├── MercurialProcessor.php
│   │               │               │   ├── ProcessIdProcessor.php
│   │               │               │   ├── ProcessorInterface.php
│   │               │               │   ├── PsrLogMessageProcessor.php
│   │               │               │   ├── TagProcessor.php
│   │               │               │   ├── UidProcessor.php
│   │               │               │   └── WebProcessor.php
│   │               │               ├── Registry.php
│   │               │               ├── ResettableInterface.php
│   │               │               ├── SignalHandler.php
│   │               │               ├── Test
│   │               │               │   └── TestCase.php
│   │               │               └── Utils.php
│   │               ├── phpseclib
│   │               │   └── phpseclib
│   │               │       ├── AUTHORS
│   │               │       ├── LICENSE
│   │               │       ├── appveyor.yml
│   │               │       ├── composer.json
│   │               │       └── phpseclib
│   │               │           ├── Crypt
│   │               │           │   ├── AES.php
│   │               │           │   ├── Base.php
│   │               │           │   ├── Blowfish.php
│   │               │           │   ├── DES.php
│   │               │           │   ├── Hash.php
│   │               │           │   ├── RC2.php
│   │               │           │   ├── RC4.php
│   │               │           │   ├── RSA.php
│   │               │           │   ├── Random.php
│   │               │           │   ├── Rijndael.php
│   │               │           │   ├── TripleDES.php
│   │               │           │   └── Twofish.php
│   │               │           ├── File
│   │               │           │   ├── ANSI.php
│   │               │           │   ├── ASN1
│   │               │           │   │   └── Element.php
│   │               │           │   ├── ASN1.php
│   │               │           │   └── X509.php
│   │               │           ├── Math
│   │               │           │   └── BigInteger.php
│   │               │           ├── Net
│   │               │           │   ├── SCP.php
│   │               │           │   ├── SFTP
│   │               │           │   │   └── Stream.php
│   │               │           │   ├── SFTP.php
│   │               │           │   ├── SSH1.php
│   │               │           │   └── SSH2.php
│   │               │           ├── System
│   │               │           │   └── SSH
│   │               │           │       ├── Agent
│   │               │           │       │   └── Identity.php
│   │               │           │       └── Agent.php
│   │               │           ├── bootstrap.php
│   │               │           └── openssl.cnf
│   │               ├── psr
│   │               │   ├── cache
│   │               │   │   ├── LICENSE.txt
│   │               │   │   ├── composer.json
│   │               │   │   └── src
│   │               │   │       ├── CacheException.php
│   │               │   │       ├── CacheItemInterface.php
│   │               │   │       ├── CacheItemPoolInterface.php
│   │               │   │       └── InvalidArgumentException.php
│   │               │   ├── http-client
│   │               │   │   ├── LICENSE
│   │               │   │   ├── composer.json
│   │               │   │   └── src
│   │               │   │       ├── ClientExceptionInterface.php
│   │               │   │       ├── ClientInterface.php
│   │               │   │       ├── NetworkExceptionInterface.php
│   │               │   │       └── RequestExceptionInterface.php
│   │               │   ├── http-message
│   │               │   │   ├── LICENSE
│   │               │   │   ├── composer.json
│   │               │   │   └── src
│   │               │   │       ├── MessageInterface.php
│   │               │   │       ├── RequestInterface.php
│   │               │   │       ├── ResponseInterface.php
│   │               │   │       ├── ServerRequestInterface.php
│   │               │   │       ├── StreamInterface.php
│   │               │   │       ├── UploadedFileInterface.php
│   │               │   │       └── UriInterface.php
│   │               │   └── log
│   │               │       ├── LICENSE
│   │               │       ├── Psr
│   │               │       │   └── Log
│   │               │       │       ├── AbstractLogger.php
│   │               │       │       ├── InvalidArgumentException.php
│   │               │       │       ├── LogLevel.php
│   │               │       │       ├── LoggerAwareInterface.php
│   │               │       │       ├── LoggerAwareTrait.php
│   │               │       │       ├── LoggerInterface.php
│   │               │       │       ├── LoggerTrait.php
│   │               │       │       ├── NullLogger.php
│   │               │       │       └── Test
│   │               │       │           ├── DummyTest.php
│   │               │       │           ├── LoggerInterfaceTest.php
│   │               │       │           └── TestLogger.php
│   │               │       └── composer.json
│   │               └── ralouphie
│   │                   └── getallheaders
│   │                       ├── LICENSE
│   │                       ├── composer.json
│   │                       └── src
│   │                           └── getallheaders.php
│   ├── Player.php
│   ├── Plugin.php
│   ├── Utils
│   │   ├── Analytics.php
│   │   ├── Cache.php
│   │   ├── Cloud.php
│   │   ├── Files.php
│   │   ├── Preview.php
│   │   ├── Update.php
│   │   └── Utils.php
│   ├── Views
│   │   ├── audio-engine-settings.php
│   │   ├── global-admin-options.php
│   │   └── product-options.php
│   ├── Widgets
│   │   └── PlaylistWidget.php
│   └── WooCommerce.php
├── test
│   ├── Woocommerce.php
│   ├── backup_plugin.sh
│   ├── backup_plugin_make_downloadable.sh
│   ├── clear_opcache.sh
│   ├── demo
│   │   └── demo.mp3
│   ├── test_mp3_class.php
│   ├── test_outputs
│   └── test_plugin.php
└── vendor
    ├── autoload.php
    ├── composer
    │   ├── ClassLoader.php
    │   ├── LICENSE
    │   ├── autoload_classmap.php
    │   ├── autoload_files.php
    │   ├── autoload_namespaces.php
    │   ├── autoload_psr4.php
    │   ├── autoload_real.php
    │   ├── autoload_static.php
    │   └── platform_check.php
    ├── php-mp3
    │   └── class.mp3.php
    └── wavesurfer
        ├── plugins
        │   ├── minimap.min.js
        │   ├── regions.min.js
        │   └── timeline.min.js
        ├── version.txt
        ├── wavesurfer.esm.js
        └── wavesurfer.min.js

109 directories, 504 files
