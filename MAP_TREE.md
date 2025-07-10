tree 
.
├── BandFront_Media_Players_Modernization_Guide.md
├── Bandfront_WordPress_Modernization_Report.md
├── CLOUD_STORAGE.md
├── ERRORS.md
├── JOBz.md
├── MAP.md
├── README.md
├── addons
│   ├── google-drive
│   │   ├── BFP_CLOUD_DRIVE.clss.php
│   │   └── google-api-php-client
│   │       ├── src
│   │       │   └── Google
│   │       │       ├── AccessToken
│   │       │       │   ├── Revoke.php
│   │       │       │   └── Verify.php
│   │       │       ├── AuthHandler
│   │       │       │   ├── AuthHandlerFactory.php
│   │       │       │   ├── Guzzle5AuthHandler.php
│   │       │       │   └── Guzzle6AuthHandler.php
│   │       │       ├── Client.php
│   │       │       ├── Collection.php
│   │       │       ├── Exception.php
│   │       │       ├── Http
│   │       │       │   ├── Batch.php
│   │       │       │   ├── MediaFileUpload.php
│   │       │       │   └── REST.php
│   │       │       ├── Model.php
│   │       │       ├── Service
│   │       │       │   ├── Exception.php
│   │       │       │   └── Resource.php
│   │       │       ├── Service.php
│   │       │       ├── Task
│   │       │       │   ├── Exception.php
│   │       │       │   ├── Retryable.php
│   │       │       │   └── Runner.php
│   │       │       ├── Utils
│   │       │       │   └── UriTemplate.php
│   │       │       └── autoload.php
│   │       └── vendor
│   │           ├── autoload.php
│   │           ├── composer
│   │           │   ├── ClassLoader.php
│   │           │   ├── InstalledVersions.php
│   │           │   ├── LICENSE
│   │           │   ├── autoload_classmap.php
│   │           │   ├── autoload_files.php
│   │           │   ├── autoload_namespaces.php
│   │           │   ├── autoload_psr4.php
│   │           │   ├── autoload_real.php
│   │           │   ├── autoload_static.php
│   │           │   ├── installed.json
│   │           │   ├── installed.php
│   │           │   └── platform_check.php
│   │           ├── google
│   │           │   ├── apiclient
│   │           │   │   └── src
│   │           │   │       ├── AccessToken
│   │           │   │       │   ├── Revoke.php
│   │           │   │       │   └── Verify.php
│   │           │   │       ├── AuthHandler
│   │           │   │       │   ├── AuthHandlerFactory.php
│   │           │   │       │   ├── Guzzle5AuthHandler.php
│   │           │   │       │   ├── Guzzle6AuthHandler.php
│   │           │   │       │   └── Guzzle7AuthHandler.php
│   │           │   │       ├── Client.php
│   │           │   │       ├── Collection.php
│   │           │   │       ├── Exception.php
│   │           │   │       ├── Http
│   │           │   │       │   ├── Batch.php
│   │           │   │       │   ├── MediaFileUpload.php
│   │           │   │       │   └── REST.php
│   │           │   │       ├── Model.php
│   │           │   │       ├── Service
│   │           │   │       │   ├── Exception.php
│   │           │   │       │   ├── README.md
│   │           │   │       │   └── Resource.php
│   │           │   │       ├── Service.php
│   │           │   │       ├── Task
│   │           │   │       │   ├── Composer.php
│   │           │   │       │   ├── Exception.php
│   │           │   │       │   ├── Retryable.php
│   │           │   │       │   └── Runner.php
│   │           │   │       ├── Utils
│   │           │   │       │   └── UriTemplate.php
│   │           │   │       └── aliases.php
│   │           │   ├── apiclient-services
│   │           │   │   └── src
│   │           │   │       └── Google
│   │           │   │           └── Service
│   │           │   │               ├── Drive
│   │           │   │               │   ├── About.php
│   │           │   │               │   ├── AboutDriveThemes.php
│   │           │   │               │   ├── AboutStorageQuota.php
│   │           │   │               │   ├── AboutTeamDriveThemes.php
│   │           │   │               │   ├── Change.php
│   │           │   │               │   ├── ChangeList.php
│   │           │   │               │   ├── Channel.php
│   │           │   │               │   ├── Comment.php
│   │           │   │               │   ├── CommentList.php
│   │           │   │               │   ├── CommentQuotedFileContent.php
│   │           │   │               │   ├── ContentRestriction.php
│   │           │   │               │   ├── Drive.php
│   │           │   │               │   ├── DriveBackgroundImageFile.php
│   │           │   │               │   ├── DriveCapabilities.php
│   │           │   │               │   ├── DriveFile.php
│   │           │   │               │   ├── DriveFileCapabilities.php
│   │           │   │               │   ├── DriveFileContentHints.php
│   │           │   │               │   ├── DriveFileContentHintsThumbnail.php
│   │           │   │               │   ├── DriveFileImageMediaMetadata.php
│   │           │   │               │   ├── DriveFileImageMediaMetadataLocation.php
│   │           │   │               │   ├── DriveFileShortcutDetails.php
│   │           │   │               │   ├── DriveFileVideoMediaMetadata.php
│   │           │   │               │   ├── DriveList.php
│   │           │   │               │   ├── DriveRestrictions.php
│   │           │   │               │   ├── FileList.php
│   │           │   │               │   ├── GeneratedIds.php
│   │           │   │               │   ├── Permission.php
│   │           │   │               │   ├── PermissionList.php
│   │           │   │               │   ├── PermissionPermissionDetails.php
│   │           │   │               │   ├── PermissionTeamDrivePermissionDetails.php
│   │           │   │               │   ├── Reply.php
│   │           │   │               │   ├── ReplyList.php
│   │           │   │               │   ├── Resource
│   │           │   │               │   │   ├── About.php
│   │           │   │               │   │   ├── Changes.php
│   │           │   │               │   │   ├── Channels.php
│   │           │   │               │   │   ├── Comments.php
│   │           │   │               │   │   ├── Drives.php
│   │           │   │               │   │   ├── Files.php
│   │           │   │               │   │   ├── Permissions.php
│   │           │   │               │   │   ├── Replies.php
│   │           │   │               │   │   ├── Revisions.php
│   │           │   │               │   │   └── Teamdrives.php
│   │           │   │               │   ├── Revision.php
│   │           │   │               │   ├── RevisionList.php
│   │           │   │               │   ├── StartPageToken.php
│   │           │   │               │   ├── TeamDrive.php
│   │           │   │               │   ├── TeamDriveBackgroundImageFile.php
│   │           │   │               │   ├── TeamDriveCapabilities.php
│   │           │   │               │   ├── TeamDriveList.php
│   │           │   │               │   ├── TeamDriveRestrictions.php
│   │           │   │               │   └── User.php
│   │           │   │               ├── Drive.php
│   │           │   │               ├── Oauth2
│   │           │   │               │   ├── Resource
│   │           │   │               │   │   ├── Userinfo.php
│   │           │   │               │   │   ├── UserinfoV2.php
│   │           │   │               │   │   └── UserinfoV2Me.php
│   │           │   │               │   ├── Tokeninfo.php
│   │           │   │               │   └── Userinfo.php
│   │           │   │               └── Oauth2.php
│   │           │   └── auth
│   │           │       ├── autoload.php
│   │           │       └── src
│   │           │           ├── AccessToken.php
│   │           │           ├── ApplicationDefaultCredentials.php
│   │           │           ├── Cache
│   │           │           │   ├── InvalidArgumentException.php
│   │           │           │   ├── Item.php
│   │           │           │   ├── MemoryCacheItemPool.php
│   │           │           │   └── SysVCacheItemPool.php
│   │           │           ├── CacheTrait.php
│   │           │           ├── Credentials
│   │           │           │   ├── AppIdentityCredentials.php
│   │           │           │   ├── GCECredentials.php
│   │           │           │   ├── IAMCredentials.php
│   │           │           │   ├── InsecureCredentials.php
│   │           │           │   ├── ServiceAccountCredentials.php
│   │           │           │   ├── ServiceAccountJwtAccessCredentials.php
│   │           │           │   └── UserRefreshCredentials.php
│   │           │           ├── CredentialsLoader.php
│   │           │           ├── FetchAuthTokenCache.php
│   │           │           ├── FetchAuthTokenInterface.php
│   │           │           ├── GCECache.php
│   │           │           ├── GetQuotaProjectInterface.php
│   │           │           ├── HttpHandler
│   │           │           │   ├── Guzzle5HttpHandler.php
│   │           │           │   ├── Guzzle6HttpHandler.php
│   │           │           │   ├── Guzzle7HttpHandler.php
│   │           │           │   ├── HttpClientCache.php
│   │           │           │   └── HttpHandlerFactory.php
│   │           │           ├── Iam.php
│   │           │           ├── Middleware
│   │           │           │   ├── AuthTokenMiddleware.php
│   │           │           │   ├── ScopedAccessTokenMiddleware.php
│   │           │           │   └── SimpleMiddleware.php
│   │           │           ├── OAuth2.php
│   │           │           ├── ProjectIdProviderInterface.php
│   │           │           ├── ServiceAccountSignerTrait.php
│   │           │           ├── SignBlobInterface.php
│   │           │           ├── Subscriber
│   │           │           │   ├── AuthTokenSubscriber.php
│   │           │           │   ├── ScopedAccessTokenSubscriber.php
│   │           │           │   └── SimpleSubscriber.php
│   │           │           └── UpdateMetadataInterface.php
│   │           ├── guzzlehttp
│   │           │   ├── guzzle
│   │           │   │   └── src
│   │           │   │       ├── BodySummarizer.php
│   │           │   │       ├── BodySummarizerInterface.php
│   │           │   │       ├── Client.php
│   │           │   │       ├── ClientInterface.php
│   │           │   │       ├── ClientTrait.php
│   │           │   │       ├── Cookie
│   │           │   │       │   ├── CookieJar.php
│   │           │   │       │   ├── CookieJarInterface.php
│   │           │   │       │   ├── FileCookieJar.php
│   │           │   │       │   ├── SessionCookieJar.php
│   │           │   │       │   └── SetCookie.php
│   │           │   │       ├── Exception
│   │           │   │       │   ├── BadResponseException.php
│   │           │   │       │   ├── ClientException.php
│   │           │   │       │   ├── ConnectException.php
│   │           │   │       │   ├── GuzzleException.php
│   │           │   │       │   ├── InvalidArgumentException.php
│   │           │   │       │   ├── RequestException.php
│   │           │   │       │   ├── ServerException.php
│   │           │   │       │   ├── TooManyRedirectsException.php
│   │           │   │       │   └── TransferException.php
│   │           │   │       ├── Handler
│   │           │   │       │   ├── CurlFactory.php
│   │           │   │       │   ├── CurlFactoryInterface.php
│   │           │   │       │   ├── CurlHandler.php
│   │           │   │       │   ├── CurlMultiHandler.php
│   │           │   │       │   ├── EasyHandle.php
│   │           │   │       │   ├── MockHandler.php
│   │           │   │       │   ├── Proxy.php
│   │           │   │       │   └── StreamHandler.php
│   │           │   │       ├── HandlerStack.php
│   │           │   │       ├── MessageFormatter.php
│   │           │   │       ├── MessageFormatterInterface.php
│   │           │   │       ├── Middleware.php
│   │           │   │       ├── Pool.php
│   │           │   │       ├── PrepareBodyMiddleware.php
│   │           │   │       ├── RedirectMiddleware.php
│   │           │   │       ├── RequestOptions.php
│   │           │   │       ├── RetryMiddleware.php
│   │           │   │       ├── TransferStats.php
│   │           │   │       ├── Utils.php
│   │           │   │       ├── functions.php
│   │           │   │       └── functions_include.php
│   │           │   ├── promises
│   │           │   │   ├── CHANGELOG.md
│   │           │   │   ├── LICENSE
│   │           │   │   ├── Makefile
│   │           │   │   ├── README.md
│   │           │   │   ├── composer.json
│   │           │   │   ├── phpstan-baseline.neon
│   │           │   │   ├── phpstan.neon.dist
│   │           │   │   ├── psalm.xml
│   │           │   │   └── src
│   │           │   │       ├── AggregateException.php
│   │           │   │       ├── CancellationException.php
│   │           │   │       ├── Coroutine.php
│   │           │   │       ├── Create.php
│   │           │   │       ├── Each.php
│   │           │   │       ├── EachPromise.php
│   │           │   │       ├── FulfilledPromise.php
│   │           │   │       ├── Is.php
│   │           │   │       ├── Promise.php
│   │           │   │       ├── PromiseInterface.php
│   │           │   │       ├── PromisorInterface.php
│   │           │   │       ├── RejectedPromise.php
│   │           │   │       ├── RejectionException.php
│   │           │   │       ├── TaskQueue.php
│   │           │   │       ├── TaskQueueInterface.php
│   │           │   │       ├── Utils.php
│   │           │   │       ├── functions.php
│   │           │   │       └── functions_include.php
│   │           │   └── psr7
│   │           │       └── src
│   │           │           ├── AppendStream.php
│   │           │           ├── BufferStream.php
│   │           │           ├── CachingStream.php
│   │           │           ├── DroppingStream.php
│   │           │           ├── FnStream.php
│   │           │           ├── Header.php
│   │           │           ├── InflateStream.php
│   │           │           ├── LazyOpenStream.php
│   │           │           ├── LimitStream.php
│   │           │           ├── Message.php
│   │           │           ├── MessageTrait.php
│   │           │           ├── MimeType.php
│   │           │           ├── MultipartStream.php
│   │           │           ├── NoSeekStream.php
│   │           │           ├── PumpStream.php
│   │           │           ├── Query.php
│   │           │           ├── Request.php
│   │           │           ├── Response.php
│   │           │           ├── Rfc7230.php
│   │           │           ├── ServerRequest.php
│   │           │           ├── Stream.php
│   │           │           ├── StreamDecoratorTrait.php
│   │           │           ├── StreamWrapper.php
│   │           │           ├── UploadedFile.php
│   │           │           ├── Uri.php
│   │           │           ├── UriNormalizer.php
│   │           │           ├── UriResolver.php
│   │           │           ├── Utils.php
│   │           │           ├── functions.php
│   │           │           └── functions_include.php
│   │           ├── monolog
│   │           │   └── monolog
│   │           │       └── src
│   │           │           └── Monolog
│   │           │               ├── DateTimeImmutable.php
│   │           │               ├── ErrorHandler.php
│   │           │               ├── Formatter
│   │           │               │   ├── ChromePHPFormatter.php
│   │           │               │   ├── ElasticaFormatter.php
│   │           │               │   ├── ElasticsearchFormatter.php
│   │           │               │   ├── FlowdockFormatter.php
│   │           │               │   ├── FluentdFormatter.php
│   │           │               │   ├── FormatterInterface.php
│   │           │               │   ├── GelfMessageFormatter.php
│   │           │               │   ├── HtmlFormatter.php
│   │           │               │   ├── JsonFormatter.php
│   │           │               │   ├── LineFormatter.php
│   │           │               │   ├── LogglyFormatter.php
│   │           │               │   ├── LogmaticFormatter.php
│   │           │               │   ├── LogstashFormatter.php
│   │           │               │   ├── MongoDBFormatter.php
│   │           │               │   ├── NormalizerFormatter.php
│   │           │               │   ├── ScalarFormatter.php
│   │           │               │   └── WildfireFormatter.php
│   │           │               ├── Handler
│   │           │               │   ├── AbstractHandler.php
│   │           │               │   ├── AbstractProcessingHandler.php
│   │           │               │   ├── AbstractSyslogHandler.php
│   │           │               │   ├── AmqpHandler.php
│   │           │               │   ├── BrowserConsoleHandler.php
│   │           │               │   ├── BufferHandler.php
│   │           │               │   ├── ChromePHPHandler.php
│   │           │               │   ├── CouchDBHandler.php
│   │           │               │   ├── CubeHandler.php
│   │           │               │   ├── Curl
│   │           │               │   │   └── Util.php
│   │           │               │   ├── DeduplicationHandler.php
│   │           │               │   ├── DoctrineCouchDBHandler.php
│   │           │               │   ├── DynamoDbHandler.php
│   │           │               │   ├── ElasticaHandler.php
│   │           │               │   ├── ElasticsearchHandler.php
│   │           │               │   ├── ErrorLogHandler.php
│   │           │               │   ├── FallbackGroupHandler.php
│   │           │               │   ├── FilterHandler.php
│   │           │               │   ├── FingersCrossed
│   │           │               │   │   ├── ActivationStrategyInterface.php
│   │           │               │   │   ├── ChannelLevelActivationStrategy.php
│   │           │               │   │   └── ErrorLevelActivationStrategy.php
│   │           │               │   ├── FingersCrossedHandler.php
│   │           │               │   ├── FirePHPHandler.php
│   │           │               │   ├── FleepHookHandler.php
│   │           │               │   ├── FlowdockHandler.php
│   │           │               │   ├── FormattableHandlerInterface.php
│   │           │               │   ├── FormattableHandlerTrait.php
│   │           │               │   ├── GelfHandler.php
│   │           │               │   ├── GroupHandler.php
│   │           │               │   ├── Handler.php
│   │           │               │   ├── HandlerInterface.php
│   │           │               │   ├── HandlerWrapper.php
│   │           │               │   ├── IFTTTHandler.php
│   │           │               │   ├── InsightOpsHandler.php
│   │           │               │   ├── LogEntriesHandler.php
│   │           │               │   ├── LogglyHandler.php
│   │           │               │   ├── LogmaticHandler.php
│   │           │               │   ├── MailHandler.php
│   │           │               │   ├── MandrillHandler.php
│   │           │               │   ├── MissingExtensionException.php
│   │           │               │   ├── MongoDBHandler.php
│   │           │               │   ├── NativeMailerHandler.php
│   │           │               │   ├── NewRelicHandler.php
│   │           │               │   ├── NoopHandler.php
│   │           │               │   ├── NullHandler.php
│   │           │               │   ├── OverflowHandler.php
│   │           │               │   ├── PHPConsoleHandler.php
│   │           │               │   ├── ProcessHandler.php
│   │           │               │   ├── ProcessableHandlerInterface.php
│   │           │               │   ├── ProcessableHandlerTrait.php
│   │           │               │   ├── PsrHandler.php
│   │           │               │   ├── PushoverHandler.php
│   │           │               │   ├── RedisHandler.php
│   │           │               │   ├── RollbarHandler.php
│   │           │               │   ├── RotatingFileHandler.php
│   │           │               │   ├── SamplingHandler.php
│   │           │               │   ├── SendGridHandler.php
│   │           │               │   ├── Slack
│   │           │               │   │   └── SlackRecord.php
│   │           │               │   ├── SlackHandler.php
│   │           │               │   ├── SlackWebhookHandler.php
│   │           │               │   ├── SocketHandler.php
│   │           │               │   ├── SqsHandler.php
│   │           │               │   ├── StreamHandler.php
│   │           │               │   ├── SwiftMailerHandler.php
│   │           │               │   ├── SyslogHandler.php
│   │           │               │   ├── SyslogUdp
│   │           │               │   │   └── UdpSocket.php
│   │           │               │   ├── SyslogUdpHandler.php
│   │           │               │   ├── TelegramBotHandler.php
│   │           │               │   ├── TestHandler.php
│   │           │               │   ├── WebRequestRecognizerTrait.php
│   │           │               │   ├── WhatFailureGroupHandler.php
│   │           │               │   └── ZendMonitorHandler.php
│   │           │               ├── Logger.php
│   │           │               ├── Processor
│   │           │               │   ├── GitProcessor.php
│   │           │               │   ├── HostnameProcessor.php
│   │           │               │   ├── IntrospectionProcessor.php
│   │           │               │   ├── MemoryPeakUsageProcessor.php
│   │           │               │   ├── MemoryProcessor.php
│   │           │               │   ├── MemoryUsageProcessor.php
│   │           │               │   ├── MercurialProcessor.php
│   │           │               │   ├── ProcessIdProcessor.php
│   │           │               │   ├── ProcessorInterface.php
│   │           │               │   ├── PsrLogMessageProcessor.php
│   │           │               │   ├── TagProcessor.php
│   │           │               │   ├── UidProcessor.php
│   │           │               │   └── WebProcessor.php
│   │           │               ├── Registry.php
│   │           │               ├── ResettableInterface.php
│   │           │               ├── SignalHandler.php
│   │           │               ├── Test
│   │           │               │   └── TestCase.php
│   │           │               └── Utils.php
│   │           ├── phpseclib
│   │           │   └── phpseclib
│   │           │       ├── AUTHORS
│   │           │       ├── BACKERS.md
│   │           │       ├── LICENSE
│   │           │       ├── README.md
│   │           │       ├── appveyor.yml
│   │           │       ├── composer.json
│   │           │       └── phpseclib
│   │           │           ├── Crypt
│   │           │           │   ├── AES.php
│   │           │           │   ├── Base.php
│   │           │           │   ├── Blowfish.php
│   │           │           │   ├── DES.php
│   │           │           │   ├── Hash.php
│   │           │           │   ├── RC2.php
│   │           │           │   ├── RC4.php
│   │           │           │   ├── RSA.php
│   │           │           │   ├── Random.php
│   │           │           │   ├── Rijndael.php
│   │           │           │   ├── TripleDES.php
│   │           │           │   └── Twofish.php
│   │           │           ├── File
│   │           │           │   ├── ANSI.php
│   │           │           │   ├── ASN1
│   │           │           │   │   └── Element.php
│   │           │           │   ├── ASN1.php
│   │           │           │   └── X509.php
│   │           │           ├── Math
│   │           │           │   └── BigInteger.php
│   │           │           ├── Net
│   │           │           │   ├── SCP.php
│   │           │           │   ├── SFTP
│   │           │           │   │   └── Stream.php
│   │           │           │   ├── SFTP.php
│   │           │           │   ├── SSH1.php
│   │           │           │   └── SSH2.php
│   │           │           ├── System
│   │           │           │   └── SSH
│   │           │           │       ├── Agent
│   │           │           │       │   └── Identity.php
│   │           │           │       └── Agent.php
│   │           │           ├── bootstrap.php
│   │           │           └── openssl.cnf
│   │           ├── psr
│   │           │   ├── cache
│   │           │   │   ├── CHANGELOG.md
│   │           │   │   ├── LICENSE.txt
│   │           │   │   ├── README.md
│   │           │   │   ├── composer.json
│   │           │   │   └── src
│   │           │   │       ├── CacheException.php
│   │           │   │       ├── CacheItemInterface.php
│   │           │   │       ├── CacheItemPoolInterface.php
│   │           │   │       └── InvalidArgumentException.php
│   │           │   ├── http-client
│   │           │   │   ├── CHANGELOG.md
│   │           │   │   ├── LICENSE
│   │           │   │   ├── README.md
│   │           │   │   ├── composer.json
│   │           │   │   └── src
│   │           │   │       ├── ClientExceptionInterface.php
│   │           │   │       ├── ClientInterface.php
│   │           │   │       ├── NetworkExceptionInterface.php
│   │           │   │       └── RequestExceptionInterface.php
│   │           │   ├── http-message
│   │           │   │   ├── CHANGELOG.md
│   │           │   │   ├── LICENSE
│   │           │   │   ├── README.md
│   │           │   │   ├── composer.json
│   │           │   │   └── src
│   │           │   │       ├── MessageInterface.php
│   │           │   │       ├── RequestInterface.php
│   │           │   │       ├── ResponseInterface.php
│   │           │   │       ├── ServerRequestInterface.php
│   │           │   │       ├── StreamInterface.php
│   │           │   │       ├── UploadedFileInterface.php
│   │           │   │       └── UriInterface.php
│   │           │   └── log
│   │           │       ├── LICENSE
│   │           │       ├── Psr
│   │           │       │   └── Log
│   │           │       │       ├── AbstractLogger.php
│   │           │       │       ├── InvalidArgumentException.php
│   │           │       │       ├── LogLevel.php
│   │           │       │       ├── LoggerAwareInterface.php
│   │           │       │       ├── LoggerAwareTrait.php
│   │           │       │       ├── LoggerInterface.php
│   │           │       │       ├── LoggerTrait.php
│   │           │       │       ├── NullLogger.php
│   │           │       │       └── Test
│   │           │       │           ├── DummyTest.php
│   │           │       │           ├── LoggerInterfaceTest.php
│   │           │       │           └── TestLogger.php
│   │           │       ├── README.md
│   │           │       └── composer.json
│   │           └── ralouphie
│   │               └── getallheaders
│   │                   ├── LICENSE
│   │                   ├── README.md
│   │                   ├── composer.json
│   │                   └── src
│   │                       └── getallheaders.php
│   ├── google-drive.addon.php
│   └── visualizations.addon.php
├── backup_plugin.sh
├── backup_plugin_make_downloadable.sh
├── bfp.php
├── bfp.php.backup-20250708-052412
├── clear_opcache.sh
├── css
│   ├── mejs-skins
│   │   ├── controls-wmp-bg.png
│   │   ├── mejs-skins.css
│   │   ├── mejs-skins.min.css
│   │   └── modern-bfp-skin.css
│   ├── style.admin.css
│   └── style.css
├── inc
│   ├── auto_update.inc.php
│   ├── cache.inc.php
│   └── tools.inc.php
├── includes
│   ├── class-bfp-admin.php
│   ├── class-bfp-audio-processor.php
│   ├── class-bfp-cloud-tools.php
│   ├── class-bfp-config.php
│   ├── class-bfp-file-handler.php
│   ├── class-bfp-hooks-manager.php
│   ├── class-bfp-player-manager.php
│   ├── class-bfp-player-renderer.php
│   ├── class-bfp-woocommerce.php
│   └── class-bfp-woocommerce.php.backup-20250708-051619
├── js
│   ├── admin.js
│   └── public.js
├── languages
│   ├── bandfront-player-en_US.mo
│   ├── bandfront-player-en_US.po
│   ├── bandfront-player-en_US.pot
│   └── messages.mo
├── pagebuilders
│   ├── builders.php
│   ├── builders.php.backup-20250708-051555
│   ├── elementor
│   │   ├── elementor.pb.php
│   │   └── elementor_category.pb.php
│   └── gutenberg
│       ├── block.json
│       ├── gutenberg.css
│       ├── gutenberg.js
│       ├── gutenberg.js.backup-20250708-052521
│       ├── wcblocks.css
│       └── wcblocks.js
├── test
│   ├── test_mp3_class.php
│   ├── test_outputs
│   └── test_plugin.php
├── update-translations.sh
├── update-wavesurfer.sh
├── vendors
│   ├── demo
│   │   └── demo.mp3
│   ├── php-mp3
│   │   └── class.mp3.php
│   ├── wavesurfer-source
│   │   ├── AGENTS.md
│   │   ├── AI_OVERVIEW.md
│   │   ├── CONTRIBUTING.md
│   │   ├── LICENSE
│   │   ├── README.md
│   │   ├── cypress
│   │   │   ├── e2e
│   │   │   │   ├── abort.cy.js
│   │   │   │   ├── basic.cy.js
│   │   │   │   ├── envelope.cy.js
│   │   │   │   ├── error.cy.js
│   │   │   │   ├── hover.cy.js
│   │   │   │   ├── index.html
│   │   │   │   ├── options.cy.js
│   │   │   │   ├── regions-no-audio.cy.js
│   │   │   │   ├── regions.cy.js
│   │   │   │   ├── spectrogram.cy.js
│   │   │   │   ├── umd.cy.js
│   │   │   │   ├── umd.html
│   │   │   │   └── webaudio.cy.js
│   │   │   ├── snapshots
│   │   │   │   ├── envelope.cy.js
│   │   │   │   │   ├── envelope-add-point.snap.png
│   │   │   │   │   └── envelope-basic.snap.png
│   │   │   │   ├── hover.cy.js
│   │   │   │   │   ├── hover-prefer-left-false-near-right-edge.snap.png
│   │   │   │   │   ├── hover-prefer-left-false.snap.png
│   │   │   │   │   ├── hover-prefer-left-true-near-left-edge.snap.png
│   │   │   │   │   └── hover-prefer-left-true.snap.png
│   │   │   │   ├── options.cy.js
│   │   │   │   │   ├── autoCenter-false.snap.png
│   │   │   │   │   ├── autoScroll-false.snap.png
│   │   │   │   │   ├── barAlign-barWidth.snap.png
│   │   │   │   │   ├── barAlign-bottom.snap.png
│   │   │   │   │   ├── barAlign-top.snap.png
│   │   │   │   │   ├── barHeight.snap.png
│   │   │   │   │   ├── barWidth.snap.png
│   │   │   │   │   ├── bars.snap.png
│   │   │   │   │   ├── colors-gradient.snap.png
│   │   │   │   │   ├── colors.snap.png
│   │   │   │   │   ├── cursor.snap.png
│   │   │   │   │   ├── custom-render.snap.png
│   │   │   │   │   ├── fetch-options.snap.png
│   │   │   │   │   ├── height-10.snap.png
│   │   │   │   │   ├── height-auto-0.snap.png
│   │   │   │   │   ├── height-auto.snap.png
│   │   │   │   │   ├── loadBlob.snap.png
│   │   │   │   │   ├── media.snap.png
│   │   │   │   │   ├── minPxPerSec-hideScrollbar.snap.png
│   │   │   │   │   ├── normalize.snap.png
│   │   │   │   │   ├── peaks.snap.png
│   │   │   │   │   ├── plugins-regions-timeline.snap.png
│   │   │   │   │   ├── plugins-regions.snap.png
│   │   │   │   │   ├── pre-decoded-no-audio.snap.png
│   │   │   │   │   ├── split-channels-options.snap.png
│   │   │   │   │   ├── split-channels.snap.png
│   │   │   │   │   ├── very-wide-container.snap.png
│   │   │   │   │   ├── width-100.snap.png
│   │   │   │   │   ├── width-10rem.snap.png
│   │   │   │   │   ├── width-200px.snap.png
│   │   │   │   │   └── width-300.snap.png
│   │   │   │   ├── regions.cy.js
│   │   │   │   │   └── regions-channelIdx.snap.png
│   │   │   │   └── spectrogram.cy.js
│   │   │   │       ├── spectrogram-basic.snap.png
│   │   │   │       ├── spectrogram-no-labels.snap.png
│   │   │   │       └── spectrogram-unhidden.snap.png
│   │   │   └── support
│   │   │       ├── commands.ts
│   │   │       └── e2e.ts
│   │   ├── cypress.config.js
│   │   ├── eslint.config.js
│   │   ├── examples
│   │   │   ├── _preview.js
│   │   │   ├── all-options.js
│   │   │   ├── audio
│   │   │   │   ├── 1khz.mp3
│   │   │   │   ├── ah.mp4
│   │   │   │   ├── ahoy.mp4
│   │   │   │   ├── audio.wav
│   │   │   │   ├── demo.wav
│   │   │   │   ├── ee.mp4
│   │   │   │   ├── er.mp4
│   │   │   │   ├── hat.mp4
│   │   │   │   ├── hen.mp4
│   │   │   │   ├── hook.mp4
│   │   │   │   ├── hot.mp4
│   │   │   │   ├── ih.mp4
│   │   │   │   ├── librivox.mp3
│   │   │   │   ├── modular.mp4
│   │   │   │   ├── mono.mp3
│   │   │   │   ├── nasa.mp4
│   │   │   │   ├── oh.mp4
│   │   │   │   ├── oo.mp4
│   │   │   │   ├── reed.sh
│   │   │   │   ├── stereo.mp3
│   │   │   │   └── uh.mp4
│   │   │   ├── bars.js
│   │   │   ├── basic.js
│   │   │   ├── custom-render.js
│   │   │   ├── envelope.js
│   │   │   ├── events.js
│   │   │   ├── fm-synth.js
│   │   │   ├── gradient.js
│   │   │   ├── hover.js
│   │   │   ├── minimap.js
│   │   │   ├── multitrack.js
│   │   │   ├── phase-vocoder
│   │   │   │   ├── index.js
│   │   │   │   └── phase-vocoder.min.js
│   │   │   ├── pitch-worker.js
│   │   │   ├── pitch.js
│   │   │   ├── predecoded.js
│   │   │   ├── react-global-player.js
│   │   │   ├── react.js
│   │   │   ├── record-sync.js
│   │   │   ├── record.js
│   │   │   ├── regions.js
│   │   │   ├── silence.js
│   │   │   ├── soundcloud.js
│   │   │   ├── spectrogram.js
│   │   │   ├── speed.js
│   │   │   ├── split-channels.js
│   │   │   ├── styling.js
│   │   │   ├── timeline-custom.js
│   │   │   ├── timeline.js
│   │   │   ├── video.js
│   │   │   ├── vowels.js
│   │   │   ├── webaudio-shim.js
│   │   │   ├── webaudio.js
│   │   │   ├── zoom-plugin.js
│   │   │   └── zoom.js
│   │   ├── index.html
│   │   ├── jest.config.js
│   │   ├── package.json
│   │   ├── rollup.config.js
│   │   ├── scripts
│   │   │   ├── clean.cjs
│   │   │   ├── plugin.sh
│   │   │   └── plugin.ts.template
│   │   ├── src
│   │   │   ├── __tests__
│   │   │   │   ├── base-plugin.test.ts
│   │   │   │   ├── dom.test.ts
│   │   │   │   ├── draggable.test.ts
│   │   │   │   ├── event-emitter.test.ts
│   │   │   │   ├── fetcher.test.ts
│   │   │   │   ├── player.test.ts
│   │   │   │   ├── renderer.test.ts
│   │   │   │   ├── timer.test.ts
│   │   │   │   └── wavesurfer.test.ts
│   │   │   ├── base-plugin.ts
│   │   │   ├── decoder.ts
│   │   │   ├── dom.ts
│   │   │   ├── draggable.ts
│   │   │   ├── event-emitter.ts
│   │   │   ├── fetcher.ts
│   │   │   ├── player.ts
│   │   │   ├── plugins
│   │   │   │   ├── envelope.ts
│   │   │   │   ├── hover.ts
│   │   │   │   ├── minimap.ts
│   │   │   │   ├── record.ts
│   │   │   │   ├── regions.ts
│   │   │   │   ├── spectrogram.ts
│   │   │   │   ├── timeline.ts
│   │   │   │   └── zoom.ts
│   │   │   ├── renderer.ts
│   │   │   ├── timer.ts
│   │   │   ├── wavesurfer.ts
│   │   │   └── webaudio.ts
│   │   ├── tsconfig.json
│   │   ├── tsconfig.test.json
│   │   └── yarn.lock
│   └── wavesurfer.js -> vendors/wavesurfer-source/dist/wavesurfer.umd.min.js
├── views
│   ├── assets
│   │   ├── skin1.png
│   │   ├── skin1_btn.png
│   │   ├── skin2.png
│   │   ├── skin2_btn.png
│   │   ├── skin3.png
│   │   └── skin3_btn.png
│   ├── global_options.php
│   └── player_options.php
└── widgets
    ├── playlist_widget
    │   ├── css
    │   │   └── style.css
    │   └── js
    │       └── public.js
    └── playlist_widget.php

123 directories, 660 files