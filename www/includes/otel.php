<?php

/**
 * @file
 * OpenTelemetry bootstrap.
 *
 * Reads OTel config from constants defined in conf/general:
 *   - OTEL_SERVICE_NAME           e.g. "twfy" (default)
 *   - OTEL_EXPORTER_OTLP_ENDPOINT e.g. "http://jaeger:4318"
 *                                 Leave blank/undefined to disable tracing.
 *   - OTEL_EXPORTER_OTLP_HEADERS  e.g. "x-api-key=abcd,another=value"
 *                                 (only needed for hosted backends; blank for Jaeger).
 *   - OTEL_DEPLOYMENT_ENVIRONMENT e.g. "dev", "staging", "production"
 *
 * Exposes:
 *   otel_init()                            - idempotent; sets up tracer provider.
 *   otel_tracer()                          - get the global tracer (or null if disabled).
 *   otel_start_root_span($name, $attrs)    - start a root span; returns a scope handle.
 *   otel_end_root_span($scope, $status)    - end the root span and detach.
 */

use OpenTelemetry\API\Trace\SpanKind;
use OpenTelemetry\API\Trace\StatusCode;
use OpenTelemetry\Contrib\Otlp\OtlpHttpTransportFactory;
use OpenTelemetry\Contrib\Otlp\SpanExporter;
use OpenTelemetry\SDK\Common\Attribute\Attributes;
use OpenTelemetry\SDK\Common\Util\ShutdownHandler;
use OpenTelemetry\SDK\Resource\ResourceInfo;
use OpenTelemetry\SDK\Resource\ResourceInfoFactory;
use OpenTelemetry\SDK\Trace\SpanProcessor\BatchSpanProcessor;
use OpenTelemetry\SDK\Trace\TracerProvider;
use OpenTelemetry\SemConv\ResourceAttributes;

/**
 * Initialise OpenTelemetry. Safe to call multiple times.
 *
 * Returns true if tracing is enabled, false otherwise (e.g. no endpoint configured,
 * or SDK classes not available).
 */
function otel_init(): bool {
    static $initialised = null;
    if ($initialised !== null) {
        return $initialised;
    }

    $endpoint = defined('OTEL_EXPORTER_OTLP_ENDPOINT') ? trim((string) OTEL_EXPORTER_OTLP_ENDPOINT) : '';
    if ($endpoint === '') {
        return $initialised = false;
    }

    // Autoloader may not have been included yet by callers other than init.php.
    $autoload = __DIR__ . '/../../vendor/autoload.php';
    if (!class_exists(TracerProvider::class, false) && is_readable($autoload)) {
        require_once $autoload;
    }
    if (!class_exists(TracerProvider::class)) {
        return $initialised = false;
    }

    $serviceName = defined('OTEL_SERVICE_NAME') && OTEL_SERVICE_NAME !== ''
        ? (string) OTEL_SERVICE_NAME
        : 'twfy';
    $environment = defined('OTEL_DEPLOYMENT_ENVIRONMENT') ? (string) OTEL_DEPLOYMENT_ENVIRONMENT : '';
    $headers = otel_parse_headers(defined('OTEL_EXPORTER_OTLP_HEADERS') ? (string) OTEL_EXPORTER_OTLP_HEADERS : '');

    try {
        $transport = (new OtlpHttpTransportFactory())->create(
            rtrim($endpoint, '/') . '/v1/traces',
            'application/x-protobuf',
            $headers,
        );
        $exporter = new SpanExporter($transport);

        $attrs = [ResourceAttributes::SERVICE_NAME => $serviceName];
        if ($environment !== '') {
            $attrs[ResourceAttributes::DEPLOYMENT_ENVIRONMENT_NAME] = $environment;
        }
        $resource = ResourceInfoFactory::defaultResource()->merge(
            ResourceInfo::create(Attributes::create($attrs)),
        );

        $tracerProvider = TracerProvider::builder()
            ->addSpanProcessor(new BatchSpanProcessor($exporter, \OpenTelemetry\API\Common\Time\Clock::getDefault()))
            ->setResource($resource)
            ->build();
    } catch (\Throwable $e) {
        // Never let tracing setup break the app.
        error_log('otel_init failed: ' . $e->getMessage());
        return $initialised = false;
    }

    $GLOBALS['__otel_tracer'] = $tracerProvider->getTracer('openaustralia/twfy');

    // Flush spans at the end of the PHP process.
    ShutdownHandler::register($tracerProvider->shutdown(...));

    return $initialised = true;
}

/**
 * Parse "k1=v1,k2=v2" into ["k1" => "v1", "k2" => "v2"].
 */
function otel_parse_headers(string $raw): array {
    $headers = [];
    if ($raw === '') {
        return $headers;
    }
    foreach (explode(',', $raw) as $pair) {
        $pair = trim($pair);
        if ($pair === '' || !str_contains($pair, '=')) {
            continue;
        }
        [$k, $v] = explode('=', $pair, 2);
        $headers[trim($k)] = trim($v);
    }
    return $headers;
}

/**
 * Returns the global tracer, or null if tracing is disabled.
 */
function otel_tracer(): ?\OpenTelemetry\API\Trace\TracerInterface {
    return $GLOBALS['__otel_tracer'] ?? null;
}

/**
 * Start a root span. Returns an opaque handle (array) you pass back to otel_end_root_span,
 * or null if tracing is disabled.
 *
 * @param string $name   span name, e.g. "GET /mp" or "job alertmailer"
 * @param array  $attrs  optional span attributes
 * @param int    $kind   SpanKind::KIND_SERVER for HTTP, KIND_INTERNAL for jobs
 */
function otel_start_root_span(string $name, array $attrs = [], int $kind = SpanKind::KIND_INTERNAL): ?array {
    $tracer = otel_tracer();
    if ($tracer === null) {
        return null;
    }

    $builder = $tracer->spanBuilder($name)->setSpanKind($kind);
    foreach ($attrs as $k => $v) {
        $builder->setAttribute($k, $v);
    }
    $span = $builder->startSpan();
    $scope = $span->activate();
    return ['span' => $span, 'scope' => $scope];
}

/**
 * End a root span started with otel_start_root_span().
 *
 * @param array|null   $handle    return value from otel_start_root_span(), or null
 * @param \Throwable|null $error  optional exception to record + mark span as errored
 */
function otel_end_root_span(?array $handle, ?\Throwable $error = null): void {
    if ($handle === null) {
        return;
    }
    /** @var \OpenTelemetry\API\Trace\SpanInterface $span */
    $span = $handle['span'];
    /** @var \OpenTelemetry\Context\ScopeInterface $scope */
    $scope = $handle['scope'];
    try {
        if ($error !== null) {
            $span->recordException($error);
            $span->setStatus(StatusCode::STATUS_ERROR, $error->getMessage());
        }
    } finally {
        $scope->detach();
        $span->end();
    }
}
