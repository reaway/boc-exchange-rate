<?php

namespace BocExchangeRate\Http;

use RuntimeException;
use InvalidArgumentException;
use CURLFile;

class CurlClient
{
    private array $defaults = [
        'timeout' => 30,
        'connect_timeout' => 10,
        'verify_ssl' => false,
        'follow_location' => true,
        'max_redirects' => 5,
        'user_agent' => '',
        'headers' => [],
        'curl' => [],
    ];

    /**
     * 构造 HTTP 客户端。
     *
     * @param array<string,mixed> $defaults 默认配置，会覆盖内置配置。
     */
    public function __construct(array $defaults = [])
    {
        $this->defaults = array_replace($this->defaults, $defaults);
    }

    private function headerToLines(array $headers): array
    {
        $lines = [];
        foreach ($headers as $k => $v) {
            if (is_int($k)) {
                $lines[] = (string)$v;
                continue;
            }
            if (is_array($v)) {
                foreach ($v as $item) {
                    $lines[] = "{$k}: {$item}";
                }
                continue;
            }
            $lines[] = "{$k}: {$v}";
        }
        return $lines;
    }

    private function buildBaseOptions(string $url, array $headers, array $options, array &$responseHeaders): array
    {
        $cfg = array_replace($this->defaults, $options);
        $headers = array_replace($this->defaults['headers'] ?? [], $headers);

        $curlOptions = [
            CURLOPT_URL => $url,
            CURLOPT_CONNECTTIMEOUT => (int)$cfg['connect_timeout'],
            CURLOPT_TIMEOUT => (int)$cfg['timeout'],
            CURLOPT_FOLLOWLOCATION => (bool)$cfg['follow_location'],
            CURLOPT_MAXREDIRS => (int)$cfg['max_redirects'],
            CURLOPT_USERAGENT => (string)$cfg['user_agent'],
            CURLOPT_SSL_VERIFYPEER => (bool)$cfg['verify_ssl'],
            CURLOPT_SSL_VERIFYHOST => (bool)$cfg['verify_ssl'] ? 2 : 0,
            CURLOPT_HTTPHEADER => $this->headerToLines($headers),
            CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
                $len = strlen($line);
                $line = trim($line);
                if ($line === '' || str_starts_with($line, 'HTTP/')) {
                    return $len;
                }
                $parts = explode(':', $line, 2);
                if (count($parts) === 2) {
                    $name = trim($parts[0]);
                    $value = trim($parts[1]);
                    if (isset($responseHeaders[$name])) {
                        $responseHeaders[$name] = is_array($responseHeaders[$name])
                            ? [...$responseHeaders[$name], $value]
                            : [$responseHeaders[$name], $value];
                    } else {
                        $responseHeaders[$name] = $value;
                    }
                }
                return $len;
            },
            CURLINFO_HEADER_OUT => true, // curl_getinfo带请求头部
        ];

        if (isset($cfg['proxy'])) {
            $curlOptions[CURLOPT_PROXY] = (string)$cfg['proxy'];
        }

        if (isset($cfg['basic_auth']) && is_array($cfg['basic_auth'])) {
            $curlOptions[CURLOPT_HTTPAUTH] = CURLAUTH_BASIC;
            $curlOptions[CURLOPT_USERPWD] = ($cfg['basic_auth']['user'] ?? '') . ':' . ($cfg['basic_auth']['pass'] ?? '');
        }

        if (!empty($cfg['curl']) && is_array($cfg['curl'])) {
            $curlOptions = array_replace($curlOptions, $cfg['curl']);
        }

        return $curlOptions;
    }

    /**
     * 通用 HTTP 请求方法（支持 GET/POST/PUT/DELETE/PATCH...）。
     *
     * @param string $method HTTP 方法
     * @param string $url 请求地址
     * @param string|array<string,mixed>|null $body 请求体
     * @param array<string,string|array<int,string>> $headers 请求头
     * @param array<string,mixed> $options 本次请求覆盖配置
     * @return array
     */
    public function sendRequest(string $method, string $url, string|array|null $body = null, array $headers = [], array $options = [])
    {
        $ch = curl_init();
        if ($ch === false) {
            throw new RuntimeException('curl_init failed');
        }

        $responseHeaders = [];
        $opts = $this->buildBaseOptions($url, $headers, $options, $responseHeaders);
        $opts = array_replace($opts, [
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_RETURNTRANSFER => true,
        ]);
        if ($body !== null) {
            $opts[CURLOPT_POSTFIELDS] = $body;
        }
        curl_setopt_array($ch, $opts);

        $responseBody = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $info = curl_getinfo($ch);

        curl_close($ch);

        return [
            'status' => $status,
            'headers' => $responseHeaders,
            'body' => $responseBody === false ? '' : $responseBody,
            'errno' => $errno,
            'error' => $error !== '' ? $error : null,
            'info' => $info,
        ];
    }

    /**
     * 发送 GET 请求。
     *
     * @param string $url 请求地址
     * @param array<string,scalar|null> $query Query 参数
     * @param array<string,string|array<int,string>> $headers 请求头
     * @param array<string,mixed> $options 本次请求覆盖配置
     * @return array
     */
    public function get(string $url, array $query = [], array $headers = [], array $options = []): array
    {
        if ($query !== []) {
            $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
        }
        return $this->sendRequest('GET', $url, null, $headers, $options);
    }

    /**
     * 发送 POST 请求（原始 body）。
     *
     * @param string $url 请求地址
     * @param string|array<string,mixed>|null $body 请求体
     * @param array<string,string|array<int,string>> $headers 请求头
     * @param array<string,mixed> $options 本次请求覆盖配置
     * @return array
     */
    public function post(string $url, string|array|null $body = null, array $headers = [], array $options = []): array
    {
        return $this->sendRequest('POST', $url, $body, $headers, $options);
    }

    /**
     * 发送 application/x-www-form-urlencoded POST 请求。
     *
     * @param string $url 请求地址
     * @param array<string,scalar|null> $form 表单字段
     * @param array<string,string|array<int,string>> $headers 请求头
     * @param array<string,mixed> $options 本次请求覆盖配置
     * @return array
     */
    public function postForm(string $url, array $form = [], array $headers = [], array $options = []): array
    {
        $body = http_build_query($form);
        $headers['Content-Type'] = 'application/x-www-form-urlencoded';
        return $this->post($url, $body, $headers, $options);
    }

    /**
     * 发送 application/json POST 请求。
     *
     * @param string $url 请求地址
     * @param array<string,mixed>|object $data JSON 数据
     * @param array<string,string|array<int,string>> $headers 请求头
     * @param array<string,mixed> $options 本次请求覆盖配置
     * @param int $jsonEncodeOptions
     * @return array
     */
    public function postJson(string $url, array|object $data, array $headers = [], array $options = [], int $jsonEncodeOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE): array
    {
        $headers['Content-Type'] = 'application/json;charset=UTF-8';
        $json = json_encode($data, $jsonEncodeOptions);
        return $this->post($url, $json, $headers, $options);
    }

    /**
     * 发送 POST 请求（Multipart 表单，用于文件上传）
     *
     * @param string $url 请求 URL
     * @param array $fields 普通表单字段 [name => value]
     * @param array $files 文件字段 [name => ['path1', 'path2', 'path3']
     * @param array $headers 自定义请求头
     */
    public function postMultipart(string $url, array $files = [], array $fields = [], array $headers = [], array $options = []): array
    {
        $body = $fields;
        if (!empty($files)) {
            foreach ($files as $name => $paths) {
                if (!is_string($name) || $name === '') {
                    throw new InvalidArgumentException('Invalid file field name');
                }

                $list = is_array($paths) ? array_values($paths) : [$paths];
                foreach ($list as $index => $path) {
                    if (!is_string($path) || $path === '' || !is_file($path)) {
                        throw new InvalidArgumentException('File not found: ' . (string)$path);
                    }

                    $key = count($list) > 1 ? $name . '[' . $index . ']' : $name;
                    $body[$key] = new CURLFile($path, null, basename($path));
                }
            }
        }
        return $this->post($url, $body, $headers, $options);
    }
}